<?php

namespace Orlinkzz\Waha\Database;

use PDO;
use PDOException;

class DatabaseConnection
{
    private PDO $pdo;

    public function __construct(array $config)
    {
        $driver = $config['driver'] ?? 'mysql';
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? ($driver === 'pgsql' ? '5432' : '3306');
        $database = $config['database'] ?? 'waha';
        $username = $config['username'] ?? 'root';
        $password = $config['password'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';

        $dsn = $this->buildDsn($driver, $host, $port, $database, $charset);

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            throw new \RuntimeException("Database connection failed: " . $e->getMessage());
        }
    }

    private function buildDsn(string $driver, string $host, string $port, string $database, string $charset): string
    {
        if ($driver === 'pgsql') {
            return "pgsql:host={$host};port={$port};dbname={$database}";
        } elseif ($driver === 'sqlite') {
            return "sqlite:{$database}";
        }

        return "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
    }

    public function getConnection(): PDO
    {
        return $this->pdo;
    }
}
