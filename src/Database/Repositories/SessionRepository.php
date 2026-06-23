<?php

namespace Orlinkzz\Waha\Database\Repositories;

use PDO;

class SessionRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(string $sessionName, string $status = 'inactive', ?array $metadata = null): int
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            "INSERT INTO waha_sessions (session_name, status, metadata, created_at, updated_at)
             VALUES (:session_name, :status, :metadata, :created_at, :updated_at)"
        );

        $stmt->execute([
            'session_name' => $sessionName,
            'status' => $status,
            'metadata' => $metadata ? json_encode($metadata) : null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateStatus(int $id, string $status): bool
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            "UPDATE waha_sessions
             SET status = :status,
                 connected_at = CASE WHEN :status = 'active' THEN :now ELSE connected_at END,
                 disconnected_at = CASE WHEN :status = 'inactive' THEN :now ELSE disconnected_at END,
                 updated_at = :now
             WHERE id = :id"
        );

        return $stmt->execute([
            'status' => $status,
            'now' => $now,
            'id' => $id,
        ]);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM waha_sessions WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findByName(string $sessionName): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM waha_sessions WHERE session_name = :session_name");
        $stmt->execute(['session_name' => $sessionName]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function getOrCreate(string $sessionName, ?array $metadata = null): array
    {
        $session = $this->findByName($sessionName);

        if (!$session) {
            $id = $this->create($sessionName, 'inactive', $metadata);
            $session = $this->findById($id);
        }

        return $session;
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM waha_sessions ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
