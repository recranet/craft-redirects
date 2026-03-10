<?php

namespace recranet\redirects\models;

use Craft;
use craft\base\Model;

class RedirectModel extends Model
{
    public ?int $id = null;
    public ?string $fromUrl = null;
    public ?string $toUrl = null;
    public int $type = 302;
    public string $matchType = 'exact';
    public ?string $label = null;
    public ?string $notes = null;
    public bool $enabled = true;
    public int $hitCount = 0;
    public ?string $lastHitAt = null;
    public ?int $createdById = null;
    public ?string $createdByName = null;
    public ?string $dateCreated = null;
    public ?string $dateUpdated = null;

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
            ['fromUrl', 'match', 'pattern' => '/^\//', 'message' => 'Must start with /'],
            ['type', 'in', 'range' => [301, 302, 307, 308]],
            ['matchType', 'in', 'range' => ['exact', 'regex']],
            ['label', 'string', 'max' => 255],
            ['notes', 'safe'],
            ['enabled', 'boolean'],
            [['hitCount', 'lastHitAt', 'createdById'], 'safe'],
        ];
    }
}
