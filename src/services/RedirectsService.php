<?php

namespace recranet\redirects\services;

use Craft;
use craft\base\Component;
use recranet\redirects\models\RedirectModel;
use recranet\redirects\records\RedirectRecord;
use yii\db\Expression;

class RedirectsService extends Component
{
    public function getAllRedirects(): array
    {
        $records = RedirectRecord::find()->orderBy(['id' => SORT_DESC])->all();

        return array_map(fn(RedirectRecord $record) => $this->recordToModel($record), $records);
    }

    public function getRedirectById(int $id): ?RedirectModel
    {
        $record = RedirectRecord::findOne($id);

        return $record ? $this->recordToModel($record) : null;
    }

    public function findRedirectByPath(string $path): ?RedirectModel
    {
        $path = '/' . ltrim($path, '/');
        $pathNoSlash = rtrim($path, '/');
        $pathWithSlash = $pathNoSlash . '/';

        $hasMatchType = $this->hasColumn('matchType');

        // Try exact match first
        $query = RedirectRecord::find()
            ->where(['lower([[fromUrl]])' => [strtolower($pathNoSlash), strtolower($pathWithSlash)]])
            ->andWhere(['enabled' => true]);

        if ($hasMatchType) {
            $query->andWhere(['matchType' => 'exact']);
        }

        $record = $query->one();

        if ($record) {
            return $this->recordToModel($record);
        }

        // Try regex matches (only if matchType column exists)
        if ($hasMatchType) {
            $regexRecords = RedirectRecord::find()
                ->where(['enabled' => true, 'matchType' => 'regex'])
                ->all();

            foreach ($regexRecords as $record) {
                $pattern = '#' . $record->fromUrl . '#i';
                if (@preg_match($pattern, $path, $matches)) {
                    $model = $this->recordToModel($record);
                    // Support $1, $2 etc. backreferences in toUrl
                    $model->toUrl = @preg_replace($pattern, $model->toUrl, $path);
                    return $model;
                }
            }
        }

        return null;
    }

    private function hasColumn(string $column): bool
    {
        $schema = Craft::$app->getDb()->getTableSchema('{{%redirects}}');
        return $schema && $schema->getColumn($column) !== null;
    }

    public function saveRedirect(RedirectModel $model): bool
    {
        // Normalize: ensure leading slash (only for exact matches)
        if ($model->matchType === 'exact' && $model->fromUrl && !str_starts_with($model->fromUrl, '/')) {
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

        // Check for duplicate fromUrl (only for exact matches)
        if ($model->matchType === 'exact') {
            $normalizedFrom = strtolower(rtrim($model->fromUrl, '/'));
            $duplicateQuery = RedirectRecord::find()
                ->where(['lower(TRIM(TRAILING \'/\' FROM [[fromUrl]]))' => $normalizedFrom])
                ->andWhere(['matchType' => 'exact']);

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

        $record->fromUrl = $model->fromUrl;
        $record->toUrl = $model->toUrl;
        $record->type = $model->type;
        $record->matchType = $model->matchType;
        $record->label = $model->label;
        $record->notes = $model->notes;
        $record->enabled = $model->enabled;

        // Set createdById on new records
        if (!$model->id && !$model->createdById) {
            $currentUser = Craft::$app->getUser()->getIdentity();
            $record->createdById = $currentUser?->id;
        }

        if (!$record->save()) {
            $model->addErrors($record->getErrors());
            return false;
        }

        $model->id = $record->id;

        return true;
    }

    public function deleteRedirectById(int $id): bool
    {
        $record = RedirectRecord::findOne($id);

        return $record ? (bool)$record->delete() : false;
    }

    public function recordHit(int $id): void
    {
        Craft::$app->getDb()->createCommand()
            ->update('{{%redirects}}', [
                'hitCount' => new Expression('[[hitCount]] + 1'),
                'lastHitAt' => (new \DateTime())->format('Y-m-d H:i:s'),
            ], ['id' => $id], [], false)
            ->execute();
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

        $chainTarget = RedirectRecord::find()
            ->where(['lower(TRIM(TRAILING \'/\' FROM [[fromUrl]]))' => $toNormalized])
            ->andWhere(['matchType' => 'exact'])
            ->one();

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
        return Craft::$app->getDb()->createCommand()
            ->update('{{%redirects}}', ['enabled' => $enabled], ['id' => $ids])
            ->execute();
    }

    /**
     * Bulk change redirect type.
     */
    public function bulkSetType(array $ids, int $type): int
    {
        return Craft::$app->getDb()->createCommand()
            ->update('{{%redirects}}', ['type' => $type], ['id' => $ids])
            ->execute();
    }

    /**
     * Bulk delete redirects.
     */
    public function bulkDelete(array $ids): int
    {
        return RedirectRecord::deleteAll(['id' => $ids]);
    }

    /**
     * Export all redirects as CSV string.
     */
    public function exportCsv(): string
    {
        $redirects = $this->getAllRedirects();

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['from', 'to', 'type', 'matchType', 'label', 'notes', 'enabled', 'hits', 'lastHit', 'created']);

        foreach ($redirects as $redirect) {
            fputcsv($handle, [
                $redirect->fromUrl,
                $redirect->toUrl,
                $redirect->type,
                $redirect->matchType,
                $redirect->label,
                $redirect->notes,
                $redirect->enabled ? 'yes' : 'no',
                $redirect->hitCount,
                $redirect->lastHitAt ?? '',
                $redirect->dateCreated ?? '',
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    public function importRedirects(array $rows): array
    {
        $imported = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $model = new RedirectModel();
            $model->fromUrl = $row['fromUrl'] ?? null;
            $model->toUrl = $row['toUrl'] ?? null;
            $model->type = !empty($row['type']) ? (int)$row['type'] : 301;
            $model->matchType = $row['matchType'] ?? 'exact';
            $model->label = $row['label'] ?? null;
            $model->notes = $row['notes'] ?? null;

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

    private function recordToModel(RedirectRecord $record): RedirectModel
    {
        $model = new RedirectModel();
        $model->id = $record->id;
        $model->fromUrl = $record->fromUrl;
        $model->toUrl = $record->toUrl;
        $model->type = $record->type;
        $model->matchType = $record->matchType ?? 'exact';
        $model->label = $record->label;
        $model->notes = $record->notes;
        $model->enabled = (bool)$record->enabled;
        $model->hitCount = (int)$record->hitCount;
        $model->lastHitAt = $record->lastHitAt;
        $model->createdById = $record->createdById ? (int)$record->createdById : null;
        if ($model->createdById) {
            $user = Craft::$app->getUsers()->getUserById($model->createdById);
            $model->createdByName = $user?->fullName ?: $user?->username;
        }
        $model->dateCreated = $record->dateCreated;
        $model->dateUpdated = $record->dateUpdated;

        return $model;
    }
}
