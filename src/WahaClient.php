<?php

namespace Orlinkzz\Waha;

use Orlinkzz\Waha\Client\WahaHttpClient;
use Orlinkzz\Waha\Message\OutgoingMessage;
use Orlinkzz\Waha\Queue\BulkResult;
use Orlinkzz\Waha\Database\DatabaseConnection;
use Orlinkzz\Waha\Database\Repositories\SessionRepository;
use Orlinkzz\Waha\Database\Repositories\MessageLogRepository;
use Orlinkzz\Waha\Database\Repositories\MessageRepository;
use Orlinkzz\Waha\Database\Repositories\ContactRepository;

class WahaClient
{
    private WahaHttpClient $http;
    private ?DatabaseConnection $dbConnection = null;
    private ?SessionRepository $sessionRepo = null;
    private ?MessageLogRepository $messageLogRepo = null;
    private ?MessageRepository $messageRepo = null;
    private ?ContactRepository $contactRepo = null;

    public function __construct(private readonly WahaConfig $config)
    {
        $this->http = new WahaHttpClient($config);
        $this->initializeDatabase();
    }

    private function initializeDatabase(): void
    {
        $dbConfig = $this->config->database ?? null;

        if (!$dbConfig || !is_array($dbConfig)) {
            return;
        }

        try {
            $this->dbConnection = new DatabaseConnection($dbConfig);
            $pdo = $this->dbConnection->getConnection();

            $this->sessionRepo = new SessionRepository($pdo);
            $this->messageLogRepo = new MessageLogRepository($pdo);
            $this->messageRepo = new MessageRepository($pdo);
            $this->contactRepo = new ContactRepository($pdo);
        } catch (\Exception $e) {
            error_log("WAHA Database initialization failed: " . $e->getMessage());
        }
    }

    // -----------------------------------------------------------------------
    // Database Logging Helpers
    // -----------------------------------------------------------------------

    private function logSessionStart(string $session): void
    {
        if (!$this->sessionRepo) return;
        try {
            $this->sessionRepo->getOrCreate($session);
        } catch (\Exception $e) {
            error_log("Failed to log session start: " . $e->getMessage());
        }
    }

    private function logSessionStatus(string $session, string $status): void
    {
        if (!$this->sessionRepo) return;
        try {
            $record = $this->sessionRepo->getOrCreate($session);
            $this->sessionRepo->updateStatus($record['id'], $status);
        } catch (\Exception $e) {
            error_log("Failed to log session status: " . $e->getMessage());
        }
    }

    private function logMessageAttempt(
        string $session,
        string $chatId,
        string $messageType,
        ?string $content,
        ?array $metadata = null
    ): ?int {
        if (!$this->messageLogRepo || !$this->sessionRepo) return null;
        try {
            $sessionRecord = $this->sessionRepo->getOrCreate($session);
            return $this->messageLogRepo->create(
                $sessionRecord['id'],
                $chatId,
                $messageType,
                $content,
                'pending',
                $metadata
            );
        } catch (\Exception $e) {
            error_log("Failed to log message attempt: " . $e->getMessage());
            return null;
        }
    }

    private function logMessageSuccess(int $logId, array $response): void
    {
        if (!$this->messageLogRepo) return;
        try {
            $this->messageLogRepo->markAsSent($logId, $response);
        } catch (\Exception $e) {
            error_log("Failed to log message success: " . $e->getMessage());
        }
    }

    private function logMessageFailure(int $logId, string $errorMessage): void
    {
        if (!$this->messageLogRepo) return;
        try {
            $this->messageLogRepo->markAsFailed($logId, $errorMessage);
        } catch (\Exception $e) {
            error_log("Failed to log message failure: " . $e->getMessage());
        }
    }

    private function logContact(string $session, string $contactId, ?string $name = null): void
    {
        if (!$this->contactRepo || !$this->sessionRepo) return;
        try {
            $sessionRecord = $this->sessionRepo->getOrCreate($session);
            // Extract phone number from contactId (format: 628123456789@c.us)
            $phoneNumber = str_replace('@c.us', '', $contactId);
            $this->contactRepo->getOrCreate(
                $sessionRecord['id'],
                $contactId,
                $phoneNumber,
                $name
            );
        } catch (\Exception $e) {
            error_log("Failed to log contact: " . $e->getMessage());
        }
    }

    // -----------------------------------------------------------------------
    // Session Management
    // -----------------------------------------------------------------------

    public function startSession(?string $session = null): array
    {
        $s = $session ?? $this->config->session;

        $this->logSessionStart($s);

        $result = $this->http->post("api/sessions/{$s}/start");

        $this->logSessionStatus($s, 'active');

        return $result;
    }

    public function stopSession(?string $session = null): array
    {
        $s = $session ?? $this->config->session;

        $result = $this->http->post("api/sessions/{$s}/stop");

        $this->logSessionStatus($s, 'inactive');

        return $result;
    }

    public function getSessionStatus(?string $session = null): array
    {
        $s = $session ?? $this->config->session;
        return $this->http->get("api/sessions/{$s}");
    }

    public function listSessions(): array
    {
        return $this->http->get('api/sessions');
    }

    // -----------------------------------------------------------------------
    // Anti-Banned Flow — sendSeen → startTyping → [delay] → stopTyping → send
    // -----------------------------------------------------------------------

    public function reply(string $chatId, string $text, ?string $session = null): array
    {
        $session ??= $this->config->session;

        $logId = $this->logMessageAttempt($session, $chatId, 'text', $text);

        $this->sendSeen($chatId, $session);
        $this->startTyping($chatId, $session);

        $delay = $this->calcTypingDelay($text);
        usleep($delay * 1000);

        $this->stopTyping($chatId, $session);

        try {
            $result = $this->sendText($chatId, $text, $session);

            if ($logId) {
                $this->logMessageSuccess($logId, $result);
            }

            $this->logContact($session, $chatId);

            return $result;
        } catch (\Exception $e) {
            if ($logId) {
                $this->logMessageFailure($logId, $e->getMessage());
            }
            throw $e;
        }
    }

    public function send(OutgoingMessage $message, bool $antiBanned = true): array
    {
        $session = $message->session ?? $this->config->session;

        if ($antiBanned) {
            return $this->reply($message->chatId, $message->text, $session);
        }

        return $this->sendText($message->chatId, $message->text, $session);
    }

    // -----------------------------------------------------------------------
    // Bulk / Broadcast Sending
    // -----------------------------------------------------------------------

    public function sendBulk(
        array $messages,
        ?callable $onEach = null,
        ?callable $onBatchPause = null
    ): BulkResult {
        $result = new BulkResult(total: count($messages));
        $batchCount = 0;

        foreach ($messages as $index => $message) {
            if ($index > 0 && $index % $this->config->bulkBatchSize === 0) {
                $batchCount++;
                $pause = rand($this->config->bulkBatchPauseMin, $this->config->bulkBatchPauseMax);

                if ($onBatchPause) {
                    $onBatchPause($batchCount, $pause);
                }

                sleep($pause);
            }

            $session = $message->session ?? $this->config->session;
            $logId = $this->logMessageAttempt($session, $message->chatId, 'text', $message->text);

            try {
                $this->startTyping($message->chatId, $session);
                $delay = $this->calcTypingDelay($message->text);
                usleep($delay * 1000);
                $this->stopTyping($message->chatId, $session);

                $response = $this->sendText($message->chatId, $message->text, $session);
                $result->addSuccess($message, $response);

                if ($logId) {
                    $this->logMessageSuccess($logId, $response);
                }

                $this->logContact($session, $message->chatId);

                if ($onEach) {
                    $onEach($message, $response, $index);
                }
            } catch (\Throwable $e) {
                $result->addFailure($message, $e->getMessage());

                if ($logId) {
                    $this->logMessageFailure($logId, $e->getMessage());
                }
            }

            if ($index < count($messages) - 1) {
                $delay = rand($this->config->bulkDelayMin, $this->config->bulkDelayMax);
                usleep($delay * 1000);
            }
        }

        return $result;
    }

    // -----------------------------------------------------------------------
    // Raw API Endpoints
    // -----------------------------------------------------------------------

    public function sendSeen(string $chatId, ?string $session = null): array
    {
        return $this->http->post('api/sendSeen', [
            'session' => $session ?? $this->config->session,
            'chatId'  => $chatId,
        ]);
    }

    public function startTyping(string $chatId, ?string $session = null): array
    {
        return $this->http->post('api/startTyping', [
            'session' => $session ?? $this->config->session,
            'chatId'  => $chatId,
        ]);
    }

    public function stopTyping(string $chatId, ?string $session = null): array
    {
        return $this->http->post('api/stopTyping', [
            'session' => $session ?? $this->config->session,
            'chatId'  => $chatId,
        ]);
    }

    public function sendText(string $chatId, string $text, ?string $session = null): array
    {
        $s = $session ?? $this->config->session;
        $logId = $this->logMessageAttempt($s, $chatId, 'text', $text);

        try {
            $result = $this->http->post('api/sendText', [
                'session' => $s,
                'chatId'  => $chatId,
                'text'    => $text,
            ]);

            if ($logId) {
                $this->logMessageSuccess($logId, $result);
            }

            $this->logContact($s, $chatId);

            return $result;
        } catch (\Exception $e) {
            if ($logId) {
                $this->logMessageFailure($logId, $e->getMessage());
            }
            throw $e;
        }
    }

    public function sendImage(string $chatId, string $urlOrBase64, string $caption = '', ?string $session = null): array
    {
        $s = $session ?? $this->config->session;
        $logId = $this->logMessageAttempt($s, $chatId, 'image', $caption, ['url' => $urlOrBase64]);

        try {
            $result = $this->http->post('api/sendImage', [
                'session' => $s,
                'chatId'  => $chatId,
                'file'    => ['url' => $urlOrBase64],
                'caption' => $caption,
            ]);

            if ($logId) {
                $this->logMessageSuccess($logId, $result);
            }

            $this->logContact($s, $chatId);

            return $result;
        } catch (\Exception $e) {
            if ($logId) {
                $this->logMessageFailure($logId, $e->getMessage());
            }
            throw $e;
        }
    }

    public function sendFile(string $chatId, string $url, string $filename, ?string $session = null): array
    {
        $s = $session ?? $this->config->session;
        $logId = $this->logMessageAttempt($s, $chatId, 'file', $filename, ['url' => $url]);

        try {
            $result = $this->http->post('api/sendFile', [
                'session'  => $s,
                'chatId'   => $chatId,
                'file'     => ['url' => $url],
                'filename' => $filename,
            ]);

            if ($logId) {
                $this->logMessageSuccess($logId, $result);
            }

            $this->logContact($s, $chatId);

            return $result;
        } catch (\Exception $e) {
            if ($logId) {
                $this->logMessageFailure($logId, $e->getMessage());
            }
            throw $e;
        }
    }

    public function getChats(?string $session = null): array
    {
        $s = $session ?? $this->config->session;
        return $this->http->get("api/{$s}/chats");
    }

    public function getMessages(string $chatId, int $limit = 20, ?string $session = null): array
    {
        $s = $session ?? $this->config->session;
        $result = $this->http->get("api/{$s}/chats/{$chatId}/messages", ['limit' => $limit]);

        // Log received messages to database
        if ($this->messageRepo && $this->sessionRepo) {
            try {
                $sessionRecord = $this->sessionRepo->getOrCreate($s);

                foreach ($result as $messageData) {
                    // Assuming messageData contains required fields
                    $messageId = $messageData['id'] ?? uniqid();
                    $messageType = $messageData['type'] ?? 'text';
                    $content = $messageData['body'] ?? $messageData['text'] ?? '';
                    $timestamp = $messageData['timestamp'] ?? time();

                    // Save to messages table
                    $this->messageRepo->create(
                        $sessionRecord['id'],
                        $messageId,
                        $chatId,
                        false, // fromMe = false (received messages)
                        $messageType,
                        $content,
                        $timestamp,
                        $messageData
                    );
                }
            } catch (\Exception $e) {
                error_log("Failed to log received messages: " . $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Handle incoming message from WAHA webhook
     */
    public function handleIncomingMessage(array $messageData): void
    {
        if (!$this->messageRepo || !$this->sessionRepo) {
            return;
        }

        try {
            // Extract session from message data
            $sessionName = $messageData['session'] ?? $this->config->session;
            $sessionRecord = $this->sessionRepo->getOrCreate($sessionName);

            // Extract message details
            $messageId = $messageData['id'] ?? $messageData['messageId'] ?? uniqid();
            $chatId = $messageData['chatId'] ?? $messageData['chat_id'] ?? '';
            $messageType = $messageData['type'] ?? $messageData['message_type'] ?? 'text';
            $content = $messageData['body'] ?? $messageData['text'] ?? $messageData['content'] ?? '';
            $timestamp = $messageData['timestamp'] ?? time();

            // Save to messages table
            $this->messageRepo->create(
                $sessionRecord['id'],
                $messageId,
                $chatId,
                false, // fromMe = false (received messages)
                $messageType,
                $content,
                $timestamp,
                $messageData
            );

            // Log contact if it's a new contact
            $this->logContact($sessionName, $chatId, $messageData['senderName'] ?? null);
        } catch (\Exception $e) {
            error_log("Failed to handle incoming message: " . $e->getMessage());
        }
    }

    // -----------------------------------------------------------------------
    // Database Query Helpers
    // -----------------------------------------------------------------------

    public function getMessageLogs(?string $status = null, int $limit = 100): array
    {
        if (!$this->messageLogRepo) {
            throw new \RuntimeException("Database not configured. Set WAHA_DB_* environment variables.");
        }

        if ($status) {
            return $this->messageLogRepo->findByStatus($status, $limit);
        }

        // If no status specified, return recent logs
        if ($this->sessionRepo) {
            $sessionRecord = $this->sessionRepo->getOrCreate($this->config->session);
            return $this->messageLogRepo->findBySession($sessionRecord['id'], $limit);
        }

        return [];
    }

    public function getSessions(): array
    {
        if (!$this->sessionRepo) {
            throw new \RuntimeException("Database not configured. Set WAHA_DB_* environment variables.");
        }
        return $this->sessionRepo->findAll();
    }

    public function getContacts(?string $session = null): array
    {
        if (!$this->contactRepo) {
            throw new \RuntimeException("Database not configured. Set WAHA_DB_* environment variables.");
        }

        if ($session) {
            $sessionRecord = $this->sessionRepo->getOrCreate($session);
            return $this->contactRepo->findBySession($sessionRecord['id']);
        }

        return $this->contactRepo->findAll();
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function calcTypingDelay(string $text): int
    {
        $base = rand($this->config->typingDelayMin, $this->config->typingDelayMax);
        $perChar = min(strlen($text) * 20, 5000);
        return $base + $perChar;
    }

    public static function formatPhone(string $phone): string
    {
        return OutgoingMessage::formatPhone($phone);
    }
}
