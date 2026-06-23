<?php

namespace Orlinkzz\Waha\Database\Migrations;

class CreateWahaMessageLogsTable extends BaseMigration
{
    public function __construct(string $driver = 'mysql')
    {
        parent::__construct('waha_message_logs', $driver);
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

        $this->addColumn('chat_id', 'VARCHAR(255)', [
            'nullable' => false
        ]);

        $this->addColumn('message_type', 'VARCHAR(50)', [
            'nullable' => false,
            'default' => "'text'"
        ]);

        $this->addColumn('content', 'TEXT', [
            'nullable' => true
        ]);

        $this->addColumn('status', 'VARCHAR(50)', [
            'nullable' => false,
            'default' => "'pending'"
        ]);

        $this->addColumn('error_message', 'TEXT', [
            'nullable' => true
        ]);

        $this->addColumn('sent_at', 'TIMESTAMP', [
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
        $this->addIndex('idx_session_id', ['session_id'], 'index');
        $this->addIndex('idx_chat_id', ['chat_id'], 'index');
        $this->addIndex('idx_status', ['status'], 'index');
        $this->addIndex('idx_sent_at', ['sent_at'], 'index');
        $this->addIndex('idx_created_at', ['created_at'], 'index');
    }
}