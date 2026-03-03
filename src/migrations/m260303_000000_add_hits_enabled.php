<?php

namespace custom\redirects\migrations;

use craft\db\Migration;

class m260303_000000_add_hits_enabled extends Migration
{
    public function safeUp(): bool
    {
        $this->addColumn('{{%redirects}}', 'enabled', $this->boolean()->notNull()->defaultValue(true)->after('notes'));
        $this->addColumn('{{%redirects}}', 'hitCount', $this->integer()->notNull()->defaultValue(0)->after('enabled'));
        $this->addColumn('{{%redirects}}', 'lastHitAt', $this->dateTime()->null()->after('hitCount'));

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropColumn('{{%redirects}}', 'enabled');
        $this->dropColumn('{{%redirects}}', 'hitCount');
        $this->dropColumn('{{%redirects}}', 'lastHitAt');

        return true;
    }
}
