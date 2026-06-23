<?php

namespace Orlinkzz\Waha\Database\Repositories;

use PDO;

class MessageLogRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(
        int $sessionId,
        string $chatId,
        string $messageType,
        ?string $content,
        string $status = 'pending',
        ?array $metadata = null
    ): int {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            "INSERT INTO waha_message_logs
             (session_id, chat_id, message_type, content, status, metadata, created_at, updated_at)
             VALUES (:session_id, :chat_id, :message_type, :content, :status, :metadata, :created_at, :updated_at)"
        );

        $stmt->execute([
            'session_id' => $sessionId,
            'chat_id' => $chatId,
            'message_type' => $messageType,
            'content' => $content,
            'status' => $status,
            'metadata' => $metadata ? json_encode($metadata) : null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateStatus(int $id, string $status, ?string $errorMessage = null): bool
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            "UPDATE waha_message_logs
             SET status = :status,
                 error_message = :error_message,
                 sent_at = CASE WHEN :status = 'sent' THEN :now ELSE sent_at END,
                 updated_at = :now
             WHERE id = :id"
        );

        return $stmt->execute([
            'status' => $status,
            'error_message' => $errorMessage,
            'now' => $now,
            'id' => $id,
        ]);
    }

    public function markAsSent(int $id, ?array $response = null): bool
    {
        $now = date('Y-m-d H:i:s');
        $metadata = $response ? json_encode($response) : null;

        $stmt = $this->pdo->prepare(
            "UPDATE waha_message_logs
             SET status = 'sent',
                 sent_at = :now,
                 metadata = COALESCE(:metadata, metadata),
                 updated_at = :now
             WHERE id = :id"
        );

        return $stmt->execute([
            'now' => $now,
            'metadata' => $metadata,
            'id' => $id,
        ]);
    }

    public function markAsFailed(int $id, string $errorMessage): bool
    {
        return $this->updateStatus($id, 'failed', $errorMessage);
    }

    public function findBySession(int $sessionId, int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM waha_message_logs
             WHERE session_id = :session_id
             ORDER BY created_at DESC
             LIMIT :limit OFFSET :offset"
        );

        $stmt->execute([
            'session_id' => $sessionId,
            'limit' => $limit,
            'offset' => $offset,
        ]);

        return $stmt->fetchAll();
    }

    public function countByStatus(int $sessionId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT status, COUNT(*) as count
             FROM waha_message_logs
             WHERE session_id = :session_id
             GROUP BY status"
        );

        $stmt->execute(['session_id' => $sessionId]);

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['status']] = (int) $row['count'];
        }

        return $result;
    }

    public function findByStatus(string $status, int $limit = 100): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM waha_message_logs
             WHERE status = :status
             ORDER BY created_at DESC
             LIMIT :limit"
        );

        $stmt->execute([
            'status' => $status,
            'limit' => $limit,
        ]);

        return $stmt->fetchAll();
    }
}
