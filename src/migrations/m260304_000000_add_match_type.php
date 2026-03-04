<?php

namespace recranet\redirects\migrations;

use craft\db\Migration;

class m260304_000000_add_match_type extends Migration
{
    public function safeUp(): bool
    {
        $this->addColumn('{{%redirects}}', 'matchType', $this->string(10)->notNull()->defaultValue('exact')->after('type'));

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropColumn('{{%redirects}}', 'matchType');

        return true;
    }
}
