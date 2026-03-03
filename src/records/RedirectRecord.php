<?php

namespace custom\redirects\records;

use craft\db\ActiveRecord;

class RedirectRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%redirects}}';
    }
}
