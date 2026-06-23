<?php

namespace Orlinkzz\Waha;

class WahaConfig
{
    public function __construct(
        public readonly string $baseUrl,
        public readonly string $apiKey,
        public readonly string $session = 'default',

        // Anti-banned delays (in milliseconds)
        public readonly int $typingDelayMin = 1000,   // min delay simulate typing
        public readonly int $typingDelayMax = 3000,   // max delay simulate typing

        // Bulk send throttle
        public readonly int $bulkDelayMin = 30000,    // 30s min between messages
        public readonly int $bulkDelayMax = 60000,    // 60s max between messages
        public readonly int $bulkBatchSize = 4,       // max messages per batch
        public readonly int $bulkBatchPauseMin = 60,  // pause between batches (seconds)
        public readonly int $bulkBatchPauseMax = 120,

        public readonly int $timeout = 30,

        // Database configuration
        public readonly ?array $database = null,
    ) {}

    public static function fromArray(array $config): self
    {
        return new self(
            baseUrl: $config['base_url'],
            apiKey: $config['api_key'],
            session: $config['session'] ?? 'default',
            typingDelayMin: $config['typing_delay_min'] ?? 1000,
            typingDelayMax: $config['typing_delay_max'] ?? 3000,
            bulkDelayMin: $config['bulk_delay_min'] ?? 30000,
            bulkDelayMax: $config['bulk_delay_max'] ?? 60000,
            bulkBatchSize: $config['bulk_batch_size'] ?? 4,
            bulkBatchPauseMin: $config['bulk_batch_pause_min'] ?? 60,
            bulkBatchPauseMax: $config['bulk_batch_pause_max'] ?? 120,
            timeout: $config['timeout'] ?? 30,
            database: $config['database'] ?? null,
        );
    }
}
