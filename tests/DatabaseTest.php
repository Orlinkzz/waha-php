<?php

namespace Orlinkzz\Waha\Tests;

use PHPUnit\Framework\TestCase;
use Orlinkzz\Waha\WahaConfig;
use Orlinkzz\Waha\WahaClient;
use Orlinkzz\Waha\Database\MigrationManager;
use Orlinkzz\Waha\Database\DatabaseConnection;
use Orlinkzz\Waha\Database\Repositories\SessionRepository;
use Orlinkzz\Waha\Database\Repositories\MessageLogRepository;
use Orlinkzz\Waha\Database\Repositories\MessageRepository;
use Orlinkzz\Waha\Database\Repositories\ContactRepository;
use PDO;

class DatabaseTest extends TestCase
{
    private ?PDO $pdo = null;
    private array $dbConfig = [];

    protected function setUp(): void
    {
        // Use SQLite in-memory for testing
        $this->dbConfig = [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ];

        // Create PDO connection for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create test tables manually since SQLite doesn't need migration
        $this->createTestTables();
    }

    private function createTestTables(): void
    {
        $sql = "
        CREATE TABLE waha_sessions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            session_name VARCHAR(255) NOT NULL UNIQUE,
            status VARCHAR(50) NOT NULL DEFAULT 'inactive',
            qr_code TEXT,
            connected_at DATETIME,
            disconnected_at DATETIME,
            metadata TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE waha_contacts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            session_id INTEGER NOT NULL,
            contact_id VARCHAR(255) NOT NULL UNIQUE,
            phone_number VARCHAR(50) NOT NULL,
            name VARCHAR(255),
            is_blocked BOOLEAN NOT NULL DEFAULT 0,
            is_business BOOLEAN NOT NULL DEFAULT 0,
            metadata TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE waha_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            session_id INTEGER NOT NULL,
            message_id VARCHAR(255) NOT NULL UNIQUE,
            chat_id VARCHAR(255) NOT NULL,
            from_me BOOLEAN NOT NULL DEFAULT 0,
            message_type VARCHAR(50) NOT NULL DEFAULT 'text',
            content TEXT,
            timestamp INTEGER NOT NULL,
            metadata TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE waha_message_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            session_id INTEGER NOT NULL,
            chat_id VARCHAR(255) NOT NULL,
            message_type VARCHAR(50) NOT NULL DEFAULT 'text',
            content TEXT,
            status VARCHAR(50) NOT NULL DEFAULT 'pending',
            error_message TEXT,
            sent_at DATETIME,
            metadata TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        );
        ";

        $this->pdo->exec($sql);
    }

    public function testDatabaseConnection(): void
    {
        $connection = new DatabaseConnection($this->dbConfig);
        $pdo = $connection->getConnection();

        $this->assertInstanceOf(PDO::class, $pdo);
    }

    public function testSessionRepository(): void
    {
        $repo = new SessionRepository($this->pdo);

        // Test create
        $sessionId = $repo->create('test_session', 'active', ['test' => 'data']);
        $this->assertIsInt($sessionId);
        $this->assertGreaterThan(0, $sessionId);

        // Test findById
        $session = $repo->findById($sessionId);
        $this->assertNotNull($session);
        $this->assertEquals('test_session', $session['session_name']);
        $this->assertEquals('active', $session['status']);

        // Test findByName
        $session = $repo->findByName('test_session');
        $this->assertNotNull($session);
        $this->assertEquals($sessionId, $session['id']);

        // Test updateStatus
        $result = $repo->updateStatus($sessionId, 'inactive');
        $this->assertTrue($result);

        // Test getOrCreate
        $session = $repo->getOrCreate('new_session', ['new' => 'data']);
        $this->assertNotNull($session);
        $this->assertEquals('new_session', $session['session_name']);

        // Test findAll
        $sessions = $repo->findAll();
        $this->assertIsArray($sessions);
        $this->assertGreaterThanOrEqual(2, count($sessions));
    }

    public function testMessageLogRepository(): void
    {
        $sessionRepo = new SessionRepository($this->pdo);
        $session = $sessionRepo->getOrCreate('test_message_session');

        $repo = new MessageLogRepository($this->pdo);

        // Test create
        $logId = $repo->create(
            $session['id'],
            '628123456789@c.us',
            'text',
            'Test message content',
            'pending',
            ['test' => 'data']
        );
        $this->assertIsInt($logId);
        $this->assertGreaterThan(0, $logId);

        // Test updateStatus
        $result = $repo->updateStatus($logId, 'sent');
        $this->assertTrue($result);

        // Test markAsSent
        $result = $repo->markAsSent($logId, ['response' => 'ok']);
        $this->assertTrue($result);

        // Test markAsFailed
        $logId2 = $repo->create(
            $session['id'],
            '628123456789@c.us',
            'text',
            'Test message 2',
            'pending'
        );
        $result = $repo->markAsFailed($logId2, 'Test error message');
        $this->assertTrue($result);

        // Test findBySession
        $logs = $repo->findBySession($session['id']);
        $this->assertIsArray($logs);
        $this->assertGreaterThanOrEqual(2, count($logs));

        // Test countByStatus
        $counts = $repo->countByStatus($session['id']);
        $this->assertIsArray($counts);
        $this->assertArrayHasKey('sent', $counts);
        $this->assertArrayHasKey('failed', $counts);

        // Test findByStatus
        $pendingLogs = $repo->findByStatus('pending');
        $this->assertIsArray($pendingLogs);
    }

    public function testMessageRepository(): void
    {
        $sessionRepo = new SessionRepository($this->pdo);
        $session = $sessionRepo->getOrCreate('test_message_repo_session');

        $repo = new MessageRepository($this->pdo);

        // Test create
        $messageId = $repo->create(
            $session['id'],
            'msg_' . uniqid(),
            '628123456789@c.us',
            true,
            'text',
            'Test message content',
            time(),
            ['test' => 'data']
        );
        $this->assertIsInt($messageId);
        $this->assertGreaterThan(0, $messageId);

        // Test findByMessageId
        $message = $repo->findByMessageId('msg_' . uniqid()); // This should return null since ID doesn't exist
        $this->assertNull($message);

        // Let's create a message with a known ID
        $knownMsgId = 'test_known_msg_id';
        $messageId2 = $repo->create(
            $session['id'],
            $knownMsgId,
            '628123456789@c.us',
            false,
            'image',
            'Test image caption',
            time() - 100,
            null
        );

        $message = $repo->findByMessageId($knownMsgId);
        $this->assertNotNull($message);
        $this->assertEquals($knownMsgId, $message['message_id']);

        // Test findByChatId
        $messages = $repo->findByChatId('628123456789@c.us');
        $this->assertIsArray($messages);
        $this->assertGreaterThanOrEqual(2, count($messages));

        // Test findBySession
        $messages = $repo->findBySession($session['id']);
        $this->assertIsArray($messages);
        $this->assertGreaterThanOrEqual(2, count($messages));
    }

    public function testContactRepository(): void
    {
        $sessionRepo = new SessionRepository($this->pdo);
        $session = $sessionRepo->getOrCreate('test_contact_session');

        $repo = new ContactRepository($this->pdo);

        // Test create
        $contactId = $repo->create(
            $session['id'],
            'contact_test_123',
            '628123456789',
            'Test Contact',
            false,
            false,
            ['custom' => 'data']
        );
        $this->assertIsInt($contactId);
        $this->assertGreaterThan(0, $contactId);

        // Test findByContactId
        $contact = $repo->findByContactId('contact_test_123');
        $this->assertNotNull($contact);
        $this->assertEquals('Test Contact', $contact['name']);

        // Test findByPhoneNumber
        $contact = $repo->findByPhoneNumber('628123456789');
        $this->assertNotNull($contact);
        $this->assertEquals('contact_test_123', $contact['contact_id']);

        // Test getOrCreate
        $contact = $repo->getOrCreate($session['id'], 'contact_new_456', '628987654321', 'New Contact');
        $this->assertNotNull($contact);
        $this->assertEquals('New Contact', $contact['name']);

        // Try getOrCreate again - should return existing record
        $existingContact = $repo->getOrCreate($session['id'], 'contact_new_456', '628987654321', 'Different Name');
        $this->assertEquals($contact['id'], $existingContact['id']); // Same ID means same record

        // Test findBySession
        $contacts = $repo->findBySession($session['id']);
        $this->assertIsArray($contacts);
        $this->assertGreaterThanOrEqual(2, count($contacts));

        // Test findAll
        $allContacts = $repo->findAll();
        $this->assertIsArray($allContacts);
        $this->assertGreaterThanOrEqual(2, count($allContacts));
    }

    public function testWahaClientWithDatabase(): void
    {
        $config = new WahaConfig(
            baseUrl: 'http://localhost:3000',
            apiKey: 'test-key',
            session: 'test_client_session',
            database: $this->dbConfig
        );

        $client = new WahaClient($config);

        // Test that repositories were initialized
        $reflection = new \ReflectionClass($client);
        $sessionRepoProp = $reflection->getProperty('sessionRepo');
        $sessionRepoProp->setAccessible(true);
        $sessionRepo = $sessionRepoProp->getValue($client);

        $messageLogRepoProp = $reflection->getProperty('messageLogRepo');
        $messageLogRepoProp->setAccessible(true);
        $messageLogRepo = $messageLogRepoProp->getValue($client);

        $this->assertInstanceOf(SessionRepository::class, $sessionRepo);
        $this->assertInstanceOf(MessageLogRepository::class, $messageLogRepo);
    }

    public function testMigrationManager(): void
    {
        $manager = new MigrationManager($this->pdo);

        // Since we already created tables manually for SQLite,
        // we'll test the driver detection
        $this->assertEquals('sqlite', $manager->getDriver());
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
    }
}