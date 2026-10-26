<?php

namespace recranet\redirects\services;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use recranet\redirects\helpers\RedirectMatcher;
use recranet\redirects\models\RedirectModel;
use recranet\redirects\records\RedirectRecord;

class RedirectsService extends Component
{
    private const CACHE_KEY = 'redirects:enabled-rows';
    private const CACHE_DURATION = 3600;

    public function getAllRedirects(?int $siteId = null): array
    {
        $query = RedirectRecord::find()->orderBy(['id' => SORT_DESC]);

        if ($siteId !== null) {
            $query->andWhere(['or', ['siteId' => null], ['siteId' => $siteId]]);
        }

        $records = $query->all();

        return array_map(fn(RedirectRecord $record) => $this->recordToModel($record), $records);
    }

    public function getRedirectById(int $id): ?RedirectModel
    {
        $record = RedirectRecord::findOne($id);

        return $record ? $this->recordToModel($record) : null;
    }

    /**
     * @param string|null $fullPath The request path including the site's base path (e.g. "de/page"
     *   when the site lives at /de). Tried when the site-relative $path doesn't match.
     */
    public function findRedirectByPath(string $path, ?int $siteId = null, ?string $hostInfo = null, ?string $fullPath = null): ?RedirectModel
    {
        $row = RedirectMatcher::matchRequest($this->getEnabledRedirectRows(), $path, $fullPath, $siteId, $hostInfo);

        return $row ? $this->rowToModel($row) : null;
    }

    /**
     * All enabled redirect rows, cached so front-end requests skip the database.
     */
    private function getEnabledRedirectRows(): array
    {
        return Craft::$app->getCache()->getOrSet(self::CACHE_KEY, function() {
            return (new Query())
                ->from(RedirectRecord::tableName())
                ->where(['enabled' => true])
                ->all();
        }, self::CACHE_DURATION);
    }

    public function invalidateCache(): void
    {
        Craft::$app->getCache()->delete(self::CACHE_KEY);
    }

    public function saveRedirect(RedirectModel $model): bool
    {
        // Normalize: ensure leading slash (only for exact matches, unless it's a full URL)
        if (
            $model->matchType === 'exact' &&
            $model->fromUrl &&
            !str_starts_with($model->fromUrl, '/') &&
            !preg_match('#^https?://#i', $model->fromUrl)
        ) {
            $model->fromUrl = '/' . $model->fromUrl;
        }

        if (!$model->validate()) {
            return false;
        }

        // Validate regex pattern
        if ($model->matchType === 'regex' && $model->fromUrl) {
            if (@preg_match('#' . $model->fromUrl . '#', '') === false) {
                $model->addError('fromUrl', 'Invalid regex pattern.');
                return false;
            }
        }

        // Check for duplicate fromUrl (only for exact matches), scoped per site
        if ($model->matchType === 'exact') {
            $normalizedFrom = strtolower(rtrim($model->fromUrl, '/'));
            $duplicateQuery = RedirectRecord::find()
                ->where(['lower(TRIM(TRAILING \'/\' FROM [[fromUrl]]))' => $normalizedFrom])
                ->andWhere(['matchType' => 'exact']);

            // Scope duplicate check to same site
            if ($model->siteId !== null) {
                $duplicateQuery->andWhere(['siteId' => $model->siteId]);
            } else {
                $duplicateQuery->andWhere(['siteId' => null]);
            }

            if ($model->id) {
                $duplicateQuery->andWhere(['not', ['id' => $model->id]]);
            }

            if ($duplicateQuery->exists()) {
                $model->addError('fromUrl', 'A redirect for this URL already exists.');
                return false;
            }
        }

        $record = $model->id ? RedirectRecord::findOne($model->id) : new RedirectRecord();

        if (!$record) {
            return false;
        }

        $record->siteId = $model->siteId;
        $record->fromUrl = $model->fromUrl;
        $record->toUrl = $model->toUrl;
        $record->type = $model->type;
        $record->matchType = $model->matchType;
        $record->priority = $model->priority;
        $record->label = $model->label;
        $record->notes = $model->notes;
        $record->enabled = $model->enabled;
        $record->expiryDate = Db::prepareDateForDb($model->expiryDate);

        if (!$record->save()) {
            $model->addErrors($record->getErrors());
            return false;
        }

        $model->id = $record->id;
        $this->invalidateCache();

        return true;
    }

    /**
     * Create (or update) a 301 redirect after an element's URI changed.
     * Also removes redirects that would loop and re-points existing
     * redirects that targeted the old URI.
     */
    public function createAutoRedirect(string $oldUri, string $newUri, int $siteId, int $type = 301): void
    {
        $fromUrl = '/' . ltrim($oldUri, '/');
        $toUrl = '/' . ltrim($newUri, '/');

        $fromNormalized = strtolower(rtrim($fromUrl, '/'));
        $toNormalized = strtolower(rtrim($toUrl, '/'));

        if ($fromNormalized === $toNormalized) {
            return;
        }

        $siteCondition = ['or', ['siteId' => null], ['siteId' => $siteId]];

        // Remove redirects that would shadow the new URI (and cause loops),
        // e.g. when an element moves back to a previously used slug
        $loopRecords = RedirectRecord::find()
            ->where(['lower(TRIM(TRAILING \'/\' FROM [[fromUrl]]))' => $toNormalized])
            ->andWhere(['matchType' => 'exact'])
            ->andWhere($siteCondition)
            ->all();

        foreach ($loopRecords as $record) {
            $record->delete();
        }

        // Re-point existing redirects that targeted the old URI, so no chains form
        $chainRecords = RedirectRecord::find()
            ->where(['lower(TRIM(TRAILING \'/\' FROM [[toUrl]]))' => $fromNormalized])
            ->andWhere($siteCondition)
            ->all();

        foreach ($chainRecords as $record) {
            $record->toUrl = $toUrl;
            $record->save();
        }

        // Upsert the redirect itself
        $record = RedirectRecord::find()
            ->where(['lower(TRIM(TRAILING \'/\' FROM [[fromUrl]]))' => $fromNormalized])
            ->andWhere(['matchType' => 'exact', 'siteId' => $siteId])
            ->one();

        if (!$record) {
            $record = new RedirectRecord();
            $record->siteId = $siteId;
            $record->fromUrl = $fromUrl;
            $record->type = $type;
            $record->matchType = 'exact';
            $record->notes = 'Automatically created after a URI change.';
        }

        $record->toUrl = $toUrl;
        $record->enabled = true;
        $record->save();

        $this->invalidateCache();
    }

    public function deleteRedirectById(int $id): bool
    {
        $record = RedirectRecord::findOne($id);
        $deleted = $record ? (bool)$record->delete() : false;

        if ($deleted) {
            $this->invalidateCache();
        }

        return $deleted;
    }

    /**
     * Detect redirect chains: does toUrl match any existing fromUrl?
     */
    public function detectChain(RedirectModel $model): ?string
    {
        if (!$model->toUrl) {
            return null;
        }

        $toNormalized = strtolower(rtrim($model->toUrl, '/'));

        $query = RedirectRecord::find()
            ->where(['lower(TRIM(TRAILING \'/\' FROM [[fromUrl]]))' => $toNormalized])
            ->andWhere(['matchType' => 'exact']);

        // Scope chain detection to same site + global
        if ($model->siteId !== null) {
            $query->andWhere(['or', ['siteId' => null], ['siteId' => $model->siteId]]);
        }

        $chainTarget = $query->one();

        if ($chainTarget) {
            return "Chain detected: {$model->toUrl} redirects further to {$chainTarget->toUrl}. Consider pointing directly to {$chainTarget->toUrl}.";
        }

        return null;
    }

    /**
     * Bulk enable/disable redirects.
     */
    public function bulkSetEnabled(array $ids, bool $enabled): int
    {
        $count = Craft::$app->getDb()->createCommand()
            ->update('{{%redirects}}', ['enabled' => $enabled], ['id' => $ids])
            ->execute();

        $this->invalidateCache();

        return $count;
    }

    /**
     * Bulk change redirect type.
     */
    public function bulkSetType(array $ids, int $type): int
    {
        $count = Craft::$app->getDb()->createCommand()
            ->update('{{%redirects}}', ['type' => $type], ['id' => $ids])
            ->execute();

        $this->invalidateCache();

        return $count;
    }

    /**
     * Bulk delete redirects.
     */
    public function bulkDelete(array $ids): int
    {
        $count = RedirectRecord::deleteAll(['id' => $ids]);

        $this->invalidateCache();

        return $count;
    }

    /**
     * Export redirects as CSV string, optionally filtered by site.
     */
    public function exportCsv(?int $siteId = null): string
    {
        $redirects = $this->getAllRedirects($siteId);

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['from', 'to', 'type', 'matchType', 'priority', 'site', 'label', 'notes', 'enabled', 'expiryDate']);

        foreach ($redirects as $redirect) {
            $siteHandle = '';
            if ($redirect->siteId !== null) {
                $site = Craft::$app->getSites()->getSiteById($redirect->siteId);
                $siteHandle = $site ? $site->handle : '';
            }

            fputcsv($handle, [
                $redirect->fromUrl,
                $redirect->toUrl,
                $redirect->type,
                $redirect->matchType,
                $redirect->priority,
                $siteHandle,
                $redirect->label,
                $redirect->notes,
                $redirect->enabled ? 'yes' : 'no',
                $redirect->expiryDate ? $redirect->expiryDate->format('Y-m-d H:i:s') : '',
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    public function importRedirects(array $rows, ?int $defaultSiteId = null): array
    {
        $imported = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $model = new RedirectModel();
            $model->fromUrl = $row['fromUrl'] ?? null;
            $model->toUrl = $row['toUrl'] ?? null;
            $model->type = !empty($row['type']) ? (int)$row['type'] : 301;
            $model->matchType = $row['matchType'] ?? 'exact';
            $model->priority = isset($row['priority']) && $row['priority'] !== '' ? (int)$row['priority'] : 0;
            $model->label = $row['label'] ?? null;
            $model->notes = $row['notes'] ?? null;

            if (!empty($row['expiryDate'])) {
                $model->expiryDate = DateTimeHelper::toDateTime($row['expiryDate']) ?: null;
            }

            // Resolve siteId from row data or use default
            if (!empty($row['siteId'])) {
                $siteValue = $row['siteId'];
                // Try as numeric ID first
                if (is_numeric($siteValue)) {
                    $site = Craft::$app->getSites()->getSiteById((int)$siteValue);
                    $model->siteId = $site ? $site->id : $defaultSiteId;
                } else {
                    // Try as site handle
                    $site = Craft::$app->getSites()->getSiteByHandle($siteValue);
                    $model->siteId = $site ? $site->id : $defaultSiteId;
                }
            } else {
                $model->siteId = $defaultSiteId;
            }

            if ($this->saveRedirect($model)) {
                $imported++;
            } else {
                $errors[] = [
                    'row' => $index + 1,
                    'data' => $row,
                    'errors' => $model->getErrors(),
                ];
            }
        }

        return [
            'imported' => $imported,
            'total' => count($rows),
            'errors' => $errors,
        ];
    }

    private function rowToModel(array $row): RedirectModel
    {
        $model = new RedirectModel();
        $model->id = (int)$row['id'];
        $model->siteId = isset($row['siteId']) && $row['siteId'] !== null ? (int)$row['siteId'] : null;
        $model->fromUrl = $row['fromUrl'];
        $model->toUrl = $row['toUrl'];
        $model->type = (int)$row['type'];
        $model->matchType = $row['matchType'] ?? 'exact';
        $model->priority = (int)($row['priority'] ?? 0);
        $model->label = $row['label'] ?? null;
        $model->notes = $row['notes'] ?? null;
        $model->enabled = (bool)$row['enabled'];
        $model->expiryDate = !empty($row['expiryDate']) ? DateTimeHelper::toDateTime($row['expiryDate']) : null;

        return $model;
    }

    private function recordToModel(RedirectRecord $record): RedirectModel
    {
        $model = new RedirectModel();
        $model->id = $record->id;
        $model->siteId = $record->siteId ? (int)$record->siteId : null;
        $model->fromUrl = $record->fromUrl;
        $model->toUrl = $record->toUrl;
        $model->type = $record->type;
        $model->matchType = $record->matchType ?? 'exact';
        $model->priority = (int)($record->priority ?? 0);
        $model->label = $record->label;
        $model->notes = $record->notes;
        $model->enabled = (bool)$record->enabled;
        $model->expiryDate = $record->expiryDate ? DateTimeHelper::toDateTime($record->expiryDate) : null;

        return $model;
    }
}
