<?php

namespace Orlinkzz\Waha\Database\Repositories;

use PDO;

class MessageRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(
        int $sessionId,
        string $messageId,
        string $chatId,
        bool $fromMe,
        string $messageType,
        ?string $content,
        int $timestamp,
        ?array $metadata = null
    ): int {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            "INSERT INTO waha_messages
             (session_id, message_id, chat_id, from_me, message_type, content, timestamp, metadata, created_at, updated_at)
             VALUES (:session_id, :message_id, :chat_id, :from_me, :message_type, :content, :timestamp, :metadata, :created_at, :updated_at)"
        );

        $stmt->execute([
            'session_id' => $sessionId,
            'message_id' => $messageId,
            'chat_id' => $chatId,
            'from_me' => $fromMe ? 1 : 0,
            'message_type' => $messageType,
            'content' => $content,
            'timestamp' => $timestamp,
            'metadata' => $metadata ? json_encode($metadata) : null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findByMessageId(string $messageId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM waha_messages WHERE message_id = :message_id");
        $stmt->execute(['message_id' => $messageId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findByChatId(string $chatId, int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM waha_messages
             WHERE chat_id = :chat_id
             ORDER BY timestamp DESC
             LIMIT :limit OFFSET :offset"
        );

        $stmt->execute([
            'chat_id' => $chatId,
            'limit' => $limit,
            'offset' => $offset,
        ]);

        return $stmt->fetchAll();
    }

    public function findBySession(int $sessionId, int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM waha_messages
             WHERE session_id = :session_id
             ORDER BY timestamp DESC
             LIMIT :limit OFFSET :offset"
        );

        $stmt->execute([
            'session_id' => $sessionId,
            'limit' => $limit,
            'offset' => $offset,
        ]);

        return $stmt->fetchAll();
    }
}
