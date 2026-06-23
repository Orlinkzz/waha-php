<?php

namespace Orlinkzz\Waha\Tests;

use Mockery;
use PHPUnit\Framework\TestCase;
use Orlinkzz\Waha\Client\WahaHttpClient;
use Orlinkzz\Waha\Message\OutgoingMessage;
use Orlinkzz\Waha\Queue\BulkResult;
use Orlinkzz\Waha\WahaClient;
use Orlinkzz\Waha\WahaConfig;

class WahaClientTest extends TestCase
{
    private WahaConfig $config;

    protected function setUp(): void
    {
        $this->config = new WahaConfig(
            baseUrl: 'http://localhost:3000',
            apiKey: 'test-key',
            session: 'default',
            typingDelayMin: 0,
            typingDelayMax: 0,
            bulkDelayMin: 0,
            bulkDelayMax: 0,
            bulkBatchPauseMin: 0,
            bulkBatchPauseMax: 0,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_format_phone(): void
    {
        $this->assertEquals('628123456789@c.us', WahaClient::formatPhone('+62 812-3456-789'));
        $this->assertEquals('628123456789@c.us', WahaClient::formatPhone('628123456789'));
    }

    public function test_outgoing_message_to(): void
    {
        $msg = OutgoingMessage::to('+628123456789', 'Hello!');
        $this->assertEquals('628123456789@c.us', $msg->chatId);
        $this->assertEquals('Hello!', $msg->text);
    }

    public function test_outgoing_message_personalize(): void
    {
        $msg = OutgoingMessage::to('+628123456789', 'Halo {name}!');
        $personalized = $msg->personalize('Budi', false);
        $this->assertStringContainsString('Budi', $personalized->text);
        $this->assertStringNotContainsString('{name}', $personalized->text);
    }

    public function test_bulk_result_tracking(): void
    {
        $result = new BulkResult(3);
        $msg1 = OutgoingMessage::to('628111@c.us', 'Hi');

        $result->addSuccess($msg1, ['id' => 'abc']);
        $result->addFailure($msg1, 'Timeout');

        $this->assertEquals(1, $result->successCount());
        $this->assertEquals(1, $result->failureCount());
        $this->assertTrue($result->hasFailures());
    }

    public function test_config_from_array(): void
    {
        $config = WahaConfig::fromArray([
            'base_url'   => 'https://waha.example.com',
            'api_key'    => 'my-secret-key',
            'session'    => 'main',
            'bulk_batch_size' => 5,
        ]);

        $this->assertEquals('https://waha.example.com', $config->baseUrl);
        $this->assertEquals('my-secret-key', $config->apiKey);
        $this->assertEquals('main', $config->session);
        $this->assertEquals(5, $config->bulkBatchSize);
    }
}
