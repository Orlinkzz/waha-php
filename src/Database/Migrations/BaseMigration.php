<?php

namespace Orlinkzz\Waha\Database\Migrations;

use Orlinkzz\Waha\Database\Migration;

abstract class BaseMigration extends Migration
{
    protected string $driver;

    public function __construct(string $tableName, string $driver = 'mysql')
    {
        parent::__construct($tableName);
        $this->driver = $driver;
    }

    protected function buildColumnDefinition(array $column): string
    {
        $name = $this->quoteIdentifier($column['name']);
        $type = $column['type'];

        if ($this->driver === 'postgres') {
            // PostgreSQL specific type mappings
            $type = $this->convertPostgresType($type);
        }

        $definition = "{$name} {$type}";

        if (isset($column['options']['nullable']) && !$column['options']['nullable']) {
            $definition .= ' NOT NULL';
        }

        if (isset($column['options']['default'])) {
            $default = $column['options']['default'];
            if (is_string($default) && !str_starts_with($default, "'")) {
                $definition .= " DEFAULT {$default}";
            } else {
                $definition .= " DEFAULT '{$default}'";
            }
        }

        if (isset($column['options']['auto_increment']) && $column['options']['auto_increment']) {
            if ($this->driver === 'mysql') {
                $definition .= ' AUTO_INCREMENT';
            } elseif ($this->driver === 'postgres') {
                // PostgreSQL handles auto-increment differently
                $definition = str_replace('INTEGER', 'SERIAL', $definition);
                $definition = str_replace('BIGINT', 'BIGSERIAL', $definition);
            }
        }

        return $definition;
    }

    protected function quoteIdentifier(string $identifier): string
    {
        if ($this->driver === 'mysql') {
            return "`{$identifier}`";
        } elseif ($this->driver === 'postgres') {
            return "\"{$identifier}\"";
        }
        return "`{$identifier}`"; // Default to MySQL style
    }

    protected function convertPostgresType(string $type): string
    {
        // Convert MySQL types to PostgreSQL equivalents
        $typeMap = [
            'TINYINT' => 'SMALLINT',
            'MEDIUMINT' => 'INTEGER',
            'LONGTEXT' => 'TEXT',
            'MEDIUMTEXT' => 'TEXT',
            'TIMESTAMP' => 'TIMESTAMP WITHOUT TIME ZONE',
            'DATETIME' => 'TIMESTAMP WITHOUT TIME ZONE',
            'TINYTEXT' => 'VARCHAR(255)',
            'BIT' => 'BOOLEAN',
        ];

        foreach ($typeMap as $mysqlType => $pgType) {
            if (stripos($type, $mysqlType) !== false) {
                return str_ireplace($mysqlType, $pgType, $type);
            }
        }

        return $type;
    }

    protected function buildIndexDefinitions(): string
    {
        $definitions = [];
        foreach ($this->indexes as $index) {
            $indexName = $this->quoteIdentifier($index['name']);
            $columnList = implode(', ', array_map([$this, 'quoteIdentifier'], $index['columns']));

            if ($index['type'] === 'primary') {
                $definitions[] = "PRIMARY KEY ({$columnList})";
            } elseif ($index['type'] === 'unique') {
                $definitions[] = "UNIQUE ({$columnList})";
            } elseif ($index['type'] === 'foreign') {
                // Foreign key definition would be handled separately
                continue;
            } else {
                // Skip index creation in table definition - create separately
                continue;
            }
        }

        return implode(', ', $definitions);
    }
}