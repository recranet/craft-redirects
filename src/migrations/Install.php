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
            'notes' => $this->text(),
            'enabled' => $this->boolean()->notNull()->defaultValue(true),
            'hitCount' => $this->integer()->notNull()->defaultValue(0),
            'lastHitAt' => $this->dateTime()->null(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, '{{%redirects}}', ['fromUrl']);

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropTableIfExists('{{%redirects}}');

        return true;
    }
}
