<?php

namespace recranet\redirects\services;

use Craft;
use craft\base\Component;
use recranet\redirects\models\NotFoundModel;
use recranet\redirects\records\NotFoundRecord;
use yii\db\Expression;

class NotFoundService extends Component
{
    public function logNotFound(string $url, ?int $siteId = null): void
    {
        $normalized = '/' . ltrim($url, '/');

        $query = NotFoundRecord::find()
            ->where(['url' => $normalized]);

        if ($siteId !== null) {
            $query->andWhere(['siteId' => $siteId]);
        } else {
            $query->andWhere(['siteId' => null]);
        }

        $record = $query->one();

        if ($record) {
            Craft::$app->getDb()->createCommand()
                ->update('{{%redirects_404s}}', [
                    'hitCount' => new Expression('[[hitCount]] + 1'),
                    'lastHitAt' => (new \DateTime())->format('Y-m-d H:i:s'),
                ], ['id' => $record->id], [], false)
                ->execute();
        } else {
            $record = new NotFoundRecord();
            $record->url = $normalized;
            $record->siteId = $siteId;
            $record->hitCount = 1;
            $record->lastHitAt = (new \DateTime())->format('Y-m-d H:i:s');
            $record->save();
        }
    }

    public function getAllNotFounds(?int $siteId = null): array
    {
        $query = NotFoundRecord::find()->orderBy(['hitCount' => SORT_DESC]);

        if ($siteId !== null) {
            $query->andWhere(['siteId' => $siteId]);
        }

        $records = $query->all();

        return array_map(fn(NotFoundRecord $record) => $this->recordToModel($record), $records);
    }

    public function deleteNotFoundById(int $id): bool
    {
        $record = NotFoundRecord::findOne($id);

        return $record ? (bool)$record->delete() : false;
    }

    public function deleteAllNotFounds(?int $siteId = null): void
    {
        if ($siteId !== null) {
            NotFoundRecord::deleteAll(['siteId' => $siteId]);
        } else {
            NotFoundRecord::deleteAll();
        }
    }

    private function recordToModel(NotFoundRecord $record): NotFoundModel
    {
        $model = new NotFoundModel();
        $model->id = $record->id;
        $model->siteId = $record->siteId ? (int)$record->siteId : null;
        $model->url = $record->url;
        $model->hitCount = (int)$record->hitCount;
        $model->lastHitAt = $record->lastHitAt;
        $model->dateCreated = $record->dateCreated;

        return $model;
    }
}
