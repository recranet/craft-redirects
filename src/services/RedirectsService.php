<?php

namespace custom\redirects\services;

use Craft;
use craft\base\Component;
use craft\db\Query;
use custom\redirects\models\RedirectModel;
use custom\redirects\records\RedirectRecord;

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

        $record = RedirectRecord::find()
            ->where(['lower([[fromUrl]])' => [strtolower($pathNoSlash), strtolower($pathWithSlash)]])
            ->andWhere(['enabled' => true])
            ->one();

        return $record ? $this->recordToModel($record) : null;
    }

    public function saveRedirect(RedirectModel $model): bool
    {
        // Normalize: ensure leading slash
        if ($model->fromUrl && !str_starts_with($model->fromUrl, '/')) {
            $model->fromUrl = '/' . $model->fromUrl;
        }

        if (!$model->validate()) {
            return false;
        }

        $record = $model->id ? RedirectRecord::findOne($model->id) : new RedirectRecord();

        if (!$record) {
            return false;
        }

        $record->fromUrl = $model->fromUrl;
        $record->toUrl = $model->toUrl;
        $record->type = $model->type;
        $record->label = $model->label;
        $record->notes = $model->notes;
        $record->enabled = $model->enabled;

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
                'hitCount' => new \yii\db\Expression('[[hitCount]] + 1'),
                'lastHitAt' => (new \DateTime())->format('Y-m-d H:i:s'),
            ], ['id' => $id], [], false)
            ->execute();
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
        $model->label = $record->label;
        $model->notes = $record->notes;
        $model->enabled = (bool)$record->enabled;
        $model->hitCount = (int)$record->hitCount;
        $model->lastHitAt = $record->lastHitAt;
        $model->dateCreated = $record->dateCreated;
        $model->dateUpdated = $record->dateUpdated;

        return $model;
    }
}
