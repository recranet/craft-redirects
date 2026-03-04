<?php

namespace custom\redirects\models;

use craft\base\Model;

class NotFoundModel extends Model
{
    public ?int $id = null;
    public ?string $url = null;
    public int $hitCount = 1;
    public ?string $lastHitAt = null;
    public ?string $dateCreated = null;
}
