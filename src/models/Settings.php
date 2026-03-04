<?php

namespace custom\redirects\models;

use craft\base\Model;

class Settings extends Model
{
    public bool $logging404Enabled = true;

    protected function defineRules(): array
    {
        return [
            ['logging404Enabled', 'boolean'],
        ];
    }
}
