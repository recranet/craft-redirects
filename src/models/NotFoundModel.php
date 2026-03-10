<?php

namespace recranet\redirects\models;

use Craft;
use craft\base\Model;

class NotFoundModel extends Model
{
    public ?int $id = null;
    public ?int $siteId = null;
    public ?string $url = null;
    public int $hitCount = 1;
    public ?string $lastHitAt = null;
    public ?string $dateCreated = null;

    public function getSiteName(): string
    {
        if ($this->siteId === null) {
            return Craft::t('redirects', 'All Sites');
        }

        $site = Craft::$app->getSites()->getSiteById($this->siteId);

        return $site ? $site->name : Craft::t('redirects', 'Unknown site');
    }
}
