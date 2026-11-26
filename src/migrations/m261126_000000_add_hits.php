<?php

namespace recranet\redirects\migrations;

use craft\db\Migration;

/**
 * Brings back the hit counter: how often a redirect fired and when it last did.
 */
class m261126_000000_add_hits extends Migration
{
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%redirects}}', 'hitCount')) {
            $this->addColumn('{{%redirects}}', 'hitCount', $this->integer()->notNull()->defaultValue(0)->after('enabled'));
        }

        if (!$this->db->columnExists('{{%redirects}}', 'lastHitAt')) {
            $this->addColumn('{{%redirects}}', 'lastHitAt', $this->dateTime()->null()->after('hitCount'));
        }

        return true;
    }

    public function safeDown(): bool
    {
        if ($this->db->columnExists('{{%redirects}}', 'lastHitAt')) {
            $this->dropColumn('{{%redirects}}', 'lastHitAt');
        }

        if ($this->db->columnExists('{{%redirects}}', 'hitCount')) {
            $this->dropColumn('{{%redirects}}', 'hitCount');
        }

        return true;
    }
}
