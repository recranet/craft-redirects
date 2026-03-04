<?php

namespace custom\redirects\migrations;

use craft\db\Migration;

class Install extends Migration
{
    public function safeUp(): bool
    {
        $this->createTable('{{%redirects}}', [
            'id' => $this->primaryKey(),
            'fromUrl' => $this->string(500)->notNull(),
            'toUrl' => $this->string(500)->notNull(),
            'type' => $this->smallInteger()->notNull()->defaultValue(301),
            'matchType' => $this->string(10)->notNull()->defaultValue('exact'),
            'label' => $this->string(255)->null(),
            'notes' => $this->text(),
            'enabled' => $this->boolean()->notNull()->defaultValue(true),
            'hitCount' => $this->integer()->notNull()->defaultValue(0),
            'lastHitAt' => $this->dateTime()->null(),
            'createdById' => $this->integer()->null(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, '{{%redirects}}', ['fromUrl']);

        $this->addForeignKey(
            null,
            '{{%redirects}}',
            'createdById',
            '{{%users}}',
            'id',
            'SET NULL',
        );

        // 404 log table
        $this->createTable('{{%redirects_404s}}', [
            'id' => $this->primaryKey(),
            'url' => $this->string(500)->notNull(),
            'hitCount' => $this->integer()->notNull()->defaultValue(1),
            'lastHitAt' => $this->dateTime()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, '{{%redirects_404s}}', ['url'], true);

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropTableIfExists('{{%redirects_404s}}');
        $this->dropTableIfExists('{{%redirects}}');

        return true;
    }
}
