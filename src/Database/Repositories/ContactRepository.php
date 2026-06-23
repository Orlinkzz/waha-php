<?php

namespace Orlinkzz\Waha\Database\Repositories;

use PDO;

class ContactRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(
        int $sessionId,
        string $contactId,
        string $phoneNumber,
        ?string $name = null,
        bool $isBlocked = false,
        bool $isBusiness = false,
        ?array $metadata = null
    ): int {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            "INSERT INTO waha_contacts
             (session_id, contact_id, phone_number, name, is_blocked, is_business, metadata, created_at, updated_at)
             VALUES (:session_id, :contact_id, :phone_number, :name, :is_blocked, :is_business, :metadata, :created_at, :updated_at)"
        );

        $stmt->execute([
            'session_id' => $sessionId,
            'contact_id' => $contactId,
            'phone_number' => $phoneNumber,
            'name' => $name,
            'is_blocked' => $isBlocked ? 1 : 0,
            'is_business' => $isBusiness ? 1 : 0,
            'metadata' => $metadata ? json_encode($metadata) : null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findByContactId(string $contactId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM waha_contacts WHERE contact_id = :contact_id");
        $stmt->execute(['contact_id' => $contactId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findByPhoneNumber(string $phoneNumber): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM waha_contacts WHERE phone_number = :phone_number");
        $stmt->execute(['phone_number' => $phoneNumber]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function getOrCreate(
        int $sessionId,
        string $contactId,
        string $phoneNumber,
        ?string $name = null,
        ?array $metadata = null
    ): array {
        $contact = $this->findByContactId($contactId);

        if (!$contact) {
            $id = $this->create($sessionId, $contactId, $phoneNumber, $name, false, false, $metadata);
            $contact = $this->findByContactId($contactId);
        }

        return $contact;
    }

    public function findBySession(int $sessionId, int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM waha_contacts
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

    public function findAll(int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM waha_contacts
             ORDER BY created_at DESC
             LIMIT :limit OFFSET :offset"
        );

        $stmt->execute([
            'limit' => $limit,
            'offset' => $offset,
        ]);

        return $stmt->fetchAll();
    }
}
