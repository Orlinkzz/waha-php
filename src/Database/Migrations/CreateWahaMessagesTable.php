<?php

namespace Orlinkzz\Waha\Database\Migrations;

class CreateWahaMessagesTable extends BaseMigration
{
    public function __construct(string $driver = 'mysql')
    {
        parent::__construct('waha_messages', $driver);
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

        $this->addColumn('message_id', 'VARCHAR(255)', [
            'nullable' => false
        ]);

        $this->addColumn('chat_id', 'VARCHAR(255)', [
            'nullable' => false
        ]);

        $this->addColumn('from_me', 'BOOLEAN', [
            'nullable' => false,
            'default' => '0'
        ]);

        $this->addColumn('message_type', 'VARCHAR(50)', [
            'nullable' => false,
            'default' => "'text'"
        ]);

        $this->addColumn('content', 'TEXT', [
            'nullable' => true
        ]);

        $this->addColumn('timestamp', 'BIGINT', [
            'nullable' => false
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
        $this->addIndex('idx_message_id', ['message_id'], 'unique');
        $this->addIndex('idx_chat_id', ['chat_id'], 'index');
        $this->addIndex('idx_timestamp', ['timestamp'], 'index');
        $this->addIndex('idx_from_me', ['from_me'], 'index');
        $this->addIndex('idx_created_at', ['created_at'], 'index');
    }
}