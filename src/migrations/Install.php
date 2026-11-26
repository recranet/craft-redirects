<?php

namespace recranet\redirects\migrations;

use craft\db\Migration;

class Install extends Migration
{
    public function safeUp(): bool
    {
        $this->createTable('{{%redirects}}', [
            'id' => $this->primaryKey(),
            'siteId' => $this->integer()->null(),
            'fromUrl' => $this->string(500)->notNull(),
            'toUrl' => $this->string(500)->notNull(),
            'type' => $this->smallInteger()->notNull()->defaultValue(302),
            'matchType' => $this->string(10)->notNull()->defaultValue('exact'),
            'priority' => $this->integer()->notNull()->defaultValue(0),
            'label' => $this->string(255)->null(),
            'notes' => $this->text(),
            'enabled' => $this->boolean()->notNull()->defaultValue(true),
            'hitCount' => $this->integer()->notNull()->defaultValue(0),
            'lastHitAt' => $this->dateTime()->null(),
            'expiryDate' => $this->dateTime()->null(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, '{{%redirects}}', ['fromUrl']);
        $this->createIndex(null, '{{%redirects}}', ['siteId']);

        $this->addForeignKey(
            null,
            '{{%redirects}}',
            'siteId',
            '{{%sites}}',
            'id',
            'CASCADE',
        );

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropTableIfExists('{{%redirects}}');

        return true;
    }
}
