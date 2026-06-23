<?php

namespace Orlinkzz\Waha\Database\Migrations;

class CreateWahaContactsTable extends BaseMigration
{
    public function __construct(string $driver = 'mysql')
    {
        parent::__construct('waha_contacts', $driver);
        $this->setupColumns();
        $this->setupIndexes();
    }

    private function setupColumns(): void
    {
        $this->addColumn('id', 'BIGINT UNSIGNED', [
            'nullable' => false,
            'auto_increment' => true
        ]);

        $this->addColumn('session_id', 'BIGINT UNSIGNED', [
            'nullable' => false
        ]);

        $this->addColumn('contact_id', 'VARCHAR(255)', [
            'nullable' => false
        ]);

        $this->addColumn('phone_number', 'VARCHAR(50)', [
            'nullable' => false
        ]);

        $this->addColumn('name', 'VARCHAR(255)', [
            'nullable' => true
        ]);

        $this->addColumn('is_blocked', 'BOOLEAN', [
            'nullable' => false,
            'default' => '0'
        ]);

        $this->addColumn('is_business', 'BOOLEAN', [
            'nullable' => false,
            'default' => '0'
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
        $this->addIndex('idx_session_id', ['session_id'], 'index');
        $this->addIndex('idx_contact_id', ['contact_id'], 'unique');
        $this->addIndex('idx_phone_number', ['phone_number'], 'index');
        $this->addIndex('idx_name', ['name'], 'index');
        $this->addIndex('idx_created_at', ['created_at'], 'index');
    }
}