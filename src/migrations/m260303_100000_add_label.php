<?php

namespace custom\redirects\migrations;

use craft\db\Migration;

class m260303_100000_add_label extends Migration
{
    public function safeUp(): bool
    {
        $this->addColumn('{{%redirects}}', 'label', $this->string(255)->null()->after('type'));

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropColumn('{{%redirects}}', 'label');

        return true;
    }
}
