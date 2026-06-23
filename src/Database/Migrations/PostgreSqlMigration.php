<?php

namespace Orlinkzz\Waha\Database\Migrations;

class PostgreSqlMigration extends BaseMigration
{
    public function __construct(string $tableName)
    {
        parent::__construct($tableName, 'postgres');
    }

    public function up(): string
    {
        $tableName = $this->quoteIdentifier($this->tableName);
        $columnDefinitions = [];

        foreach ($this->columns as $column) {
            $columnDefinitions[] = $this->buildColumnDefinition($column);
        }

        // Add indexes to column definitions
        $indexDefinitions = $this->buildIndexDefinitions();
        if (!empty($indexDefinitions)) {
            $columnDefinitions[] = $indexDefinitions;
        }

        $columnsSql = implode(', ', $columnDefinitions);

        $sql = "CREATE TABLE IF NOT EXISTS {$tableName} ({$columnsSql});";

        // Add separate index creation statements
        foreach ($this->indexes as $index) {
            if ($index['type'] === 'index') {
                $indexName = $this->quoteIdentifier($index['name']);
                $columnList = implode(', ', array_map([$this, 'quoteIdentifier'], $index['columns']));
                $sql .= "\nCREATE INDEX {$indexName} ON {$tableName} ({$columnList});";
            }
        }

        return $sql;
    }

    public function down(): string
    {
        $tableName = $this->quoteIdentifier($this->tableName);
        return "DROP TABLE IF EXISTS {$tableName} CASCADE;";
    }
}