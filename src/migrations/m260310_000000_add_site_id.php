<?php

namespace recranet\redirects\migrations;

use craft\db\Migration;

class m260310_000000_add_site_id extends Migration
{
    public function safeUp(): bool
    {
        $schema = $this->db->getSchema();

        // Add siteId to redirects table
        $redirectsTable = $schema->getTableSchema('{{%redirects}}');
        if ($redirectsTable && $redirectsTable->getColumn('siteId') === null) {
            $this->addColumn('{{%redirects}}', 'siteId', $this->integer()->null()->after('id'));
            $this->createIndex(null, '{{%redirects}}', ['siteId']);
            $this->addForeignKey(null, '{{%redirects}}', 'siteId', '{{%sites}}', 'id', 'CASCADE');
        }

        // Add siteId to 404s table
        $notFoundTable = $schema->getTableSchema('{{%redirects_404s}}');
        if ($notFoundTable && $notFoundTable->getColumn('siteId') === null) {
            $this->addColumn('{{%redirects_404s}}', 'siteId', $this->integer()->null()->after('id'));
            $this->addForeignKey(null, '{{%redirects_404s}}', 'siteId', '{{%sites}}', 'id', 'CASCADE');
        }

        // Drop old unique index on url, create new composite unique index
        $this->dropUniqueIndexByColumns('{{%redirects_404s}}', ['url']);
        $this->createIndex(null, '{{%redirects_404s}}', ['url', 'siteId'], true);

        return true;
    }

    public function safeDown(): bool
    {
        // Restore original unique index
        $this->dropUniqueIndexByColumns('{{%redirects_404s}}', ['url', 'siteId']);
        $this->createIndex(null, '{{%redirects_404s}}', ['url'], true);

        // Remove FKs and columns
        $this->dropForeignKeyByColumn('{{%redirects_404s}}', 'siteId');
        $this->dropColumn('{{%redirects_404s}}', 'siteId');

        $this->dropForeignKeyByColumn('{{%redirects}}', 'siteId');
        $this->dropColumn('{{%redirects}}', 'siteId');

        return true;
    }

    private function dropUniqueIndexByColumns(string $table, array $columns): void
    {
        $indexes = $this->db->getSchema()->getTableIndexes($this->db->getSchema()->getRawTableName($table));

        foreach ($indexes as $index) {
            if ($index->columnNames === $columns && $index->isUnique) {
                $this->dropIndex($index->name, $table);
                return;
            }
        }
    }

    private function dropForeignKeyByColumn(string $table, string $column): void
    {
        $schema = $this->db->getSchema();
        $tableSchema = $schema->getTableSchema($table);

        if ($tableSchema === null) {
            return;
        }

        foreach ($tableSchema->foreignKeys as $name => $fk) {
            if (isset($fk[$column])) {
                $this->dropForeignKey($name, $table);
                return;
            }
        }
    }
}
