<?php

namespace custom\redirects\records;

use craft\db\ActiveRecord;

class NotFoundRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%redirects_404s}}';
    }
}
