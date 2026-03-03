<?php

namespace custom\redirects\models;

use craft\base\Model;

class RedirectModel extends Model
{
    public ?int $id = null;
    public ?string $fromUrl = null;
    public ?string $toUrl = null;
    public int $type = 301;
    public ?string $label = null;
    public ?string $notes = null;
    public bool $enabled = true;
    public int $hitCount = 0;
    public ?string $lastHitAt = null;
    public ?string $dateCreated = null;
    public ?string $dateUpdated = null;

    public static function typeOptions(): array
    {
        return [
            301 => '301 — Permanent',
            302 => '302 — Temporary',
            307 => '307 — Temporary (preserve method)',
            308 => '308 — Permanent (preserve method)',
        ];
    }

    protected function defineRules(): array
    {
        return [
            [['fromUrl', 'toUrl'], 'required'],
            [['fromUrl', 'toUrl'], 'string', 'max' => 500],
            ['fromUrl', 'match', 'pattern' => '/^\//', 'message' => 'Must start with /'],
            ['type', 'in', 'range' => [301, 302, 307, 308]],
            ['label', 'string', 'max' => 255],
            ['notes', 'safe'],
            ['enabled', 'boolean'],
            [['hitCount', 'lastHitAt'], 'safe'],
        ];
    }
}
