<?php

namespace Orlinkzz\Waha\Database;

abstract class Migration
{
    protected string $tableName;
    protected array $columns = [];
    protected array $indexes = [];

    public function __construct(string $tableName)
    {
        $this->tableName = $tableName;
    }

    abstract public function up(): string;
    abstract public function down(): string;

    protected function addColumn(string $name, string $type, array $options = []): void
    {
        $this->columns[] = [
            'name' => $name,
            'type' => $type,
            'options' => $options
        ];
    }

    protected function addIndex(string $name, array $columns, string $type = 'index'): void
    {
        $this->indexes[] = [
            'name' => $name,
            'columns' => $columns,
            'type' => $type
        ];
    }

    protected function getTablePrefix(): string
    {
        return '';
    }
}