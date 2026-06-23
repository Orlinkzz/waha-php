<?php

namespace Orlinkzz\Waha\Database\Migrations;

class CreateWahaSessionsTable extends BaseMigration
{
    public function __construct(string $driver = 'mysql')
    {
        parent::__construct('waha_sessions', $driver);
        $this->setupColumns();
        $this->setupIndexes();
    }

    private function setupColumns(): void
    {
        $this->addColumn('id', 'BIGINT UNSIGNED', [
            'nullable' => false,
            'auto_increment' => true
        ]);

        $this->addColumn('session_name', 'VARCHAR(255)', [
            'nullable' => false
        ]);

        $this->addColumn('status', 'VARCHAR(50)', [
            'nullable' => false,
            'default' => "'inactive'"
        ]);

        $this->addColumn('qr_code', 'TEXT', [
            'nullable' => true
        ]);

        $this->addColumn('connected_at', 'TIMESTAMP', [
            'nullable' => true
        ]);

        $this->addColumn('disconnected_at', 'TIMESTAMP', [
            'nullable' => true
        ]);

        $this->addColumn('metadata', 'JSON', [
            'nullable' => true
        ]);

        $this->addColumn('created_at', 'TIMESTAMP', [
            'nullable' => false,
            'default' => 'CURRENT_TIMESTAMP'
        ]);

        $this->addColumn('updated_at', 'TIMESTAMP', [
            'nullable' => false,
            'default' => 'CURRENT_TIMESTAMP'
        ]);
    }

    private function setupIndexes(): void
    {
        $this->addIndex('PRIMARY', ['id'], 'primary');
        $this->addIndex('idx_session_name', ['session_name'], 'unique');
        $this->addIndex('idx_status', ['status'], 'index');
        $this->addIndex('idx_created_at', ['created_at'], 'index');
    }
}