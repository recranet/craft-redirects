<?php

namespace recranet\redirects\migrations;

use craft\db\Migration;

class m260303_200000_add_created_by extends Migration
{
    public function safeUp(): bool
    {
        $this->addColumn('{{%redirects}}', 'createdById', $this->integer()->null()->after('lastHitAt'));

        $this->addForeignKey(
            null,
            '{{%redirects}}',
            'createdById',
            '{{%users}}',
            'id',
            'SET NULL',
        );

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropForeignKey('{{%redirects}}', 'createdById');
        $this->dropColumn('{{%redirects}}', 'createdById');

        return true;
    }
}
