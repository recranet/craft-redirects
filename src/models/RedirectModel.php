<?php

namespace recranet\redirects\models;

use Craft;
use craft\base\Model;

class RedirectModel extends Model
{
    public ?int $id = null;
    public ?int $siteId = null;
    public ?string $fromUrl = null;
    public ?string $toUrl = null;
    public int $type = 302;
    public string $matchType = 'exact';
    public int $priority = 0;
    public ?string $label = null;
    public ?string $notes = null;
    public bool $enabled = true;
    public ?\DateTime $expiryDate = null;
    public int $hitCount = 0;
    public ?\DateTime $lastHitAt = null;

    public function getSiteName(): string
    {
        if ($this->siteId === null) {
            return Craft::t('redirects', 'All Sites');
        }

        $site = Craft::$app->getSites()->getSiteById($this->siteId);

        return $site ? $site->name : Craft::t('redirects', 'Unknown site');
    }

    public static function siteOptions(): array
    {
        $options = [
            ['value' => '', 'label' => Craft::t('redirects', 'All Sites')],
        ];

        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $options[] = ['value' => $site->id, 'label' => $site->name];
        }

        return $options;
    }

    public static function matchTypeOptions(): array
    {
        return [
            'exact' => Craft::t('redirects', 'Exact match'),
            'regex' => Craft::t('redirects', 'Regex pattern'),
        ];
    }

    public static function typeOptions(): array
    {
        return [
            302 => Craft::t('redirects', '302 — Temporary (browser does not cache)'),
            301 => Craft::t('redirects', '301 — Permanent (browser caches redirect)'),
            307 => Craft::t('redirects', '307 — Temporary (preserve method, browser does not cache)'),
            308 => Craft::t('redirects', '308 — Permanent (preserve method, browser caches redirect)'),
        ];
    }

    protected function defineRules(): array
    {
        return [
            [['fromUrl', 'toUrl'], 'required'],
            [['fromUrl', 'toUrl'], 'string', 'max' => 500],
            [
                'fromUrl',
                'match',
                'pattern' => '#^(/|https?://)#i',
                'message' => 'Must start with / or be a full URL (https://...)',
                'when' => fn(self $model) => $model->matchType === 'exact',
            ],
            ['type', 'in', 'range' => [301, 302, 307, 308]],
            ['matchType', 'in', 'range' => ['exact', 'regex']],
            ['priority', 'integer'],
            ['label', 'string', 'max' => 255],
            ['notes', 'safe'],
            ['enabled', 'boolean'],
            ['siteId', 'safe'],
            ['expiryDate', 'safe'],
        ];
    }
}
