<?php

namespace Orlinkzz\Waha\Database;

use PDO;
use PDOException;
use Orlinkzz\Waha\Database\Migrations\CreateWahaSessionsTable;
use Orlinkzz\Waha\Database\Migrations\CreateWahaMessagesTable;
use Orlinkzz\Waha\Database\Migrations\CreateWahaContactsTable;
use Orlinkzz\Waha\Database\Migrations\CreateWahaMessageLogsTable;

class MigrationManager
{
    private PDO $pdo;
    private string $driver;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;

        // Try to get driver using getAttribute, fallback to manual detection
        try {
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        } catch (\Exception $e) {
            // Fallback to driver detection via DSN if available
            $dsn = $pdo->getAttribute(PDO::ATTR_DSN);
            if (strpos($dsn, 'mysql:') === 0) {
                $driver = 'mysql';
            } elseif (strpos($dsn, 'pgsql:') === 0) {
                $driver = 'pgsql';
            } elseif (strpos($dsn, 'sqlite:') === 0) {
                $driver = 'sqlite';
            } else {
                $driver = 'unknown';
            }
        }

        $this->driver = $driver;

        // Normalize driver name
        if (in_array($this->driver, ['pgsql', 'postgres', 'postgresql'])) {
            $this->driver = 'postgres';
        } elseif (in_array($this->driver, ['mysql', 'mysqli'])) {
            $this->driver = 'mysql';
        } elseif ($this->driver === 'sqlite') {
            $this->driver = 'sqlite';
        } else {
            throw new \InvalidArgumentException("Unsupported database driver: {$this->driver}");
        }
    }

    /**
     * Run all migrations
     */
    public function migrate(): array
    {
        $results = [];
        $migrations = $this->getMigrations();

        foreach ($migrations as $migrationClass) {
            try {
                $migration = new $migrationClass($this->driver);
                $sql = $migration->up();

                // Execute multiple statements if separated by semicolons
                $statements = array_filter(array_map('trim', explode(';', $sql)));

                foreach ($statements as $statement) {
                    if (!empty($statement)) {
                        $this->pdo->exec($statement);
                    }
                }

                $results[] = [
                    'migration' => $migrationClass,
                    'status' => 'success',
                    'message' => 'Migrated successfully'
                ];
            } catch (PDOException $e) {
                $results[] = [
                    'migration' => $migrationClass,
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Rollback all migrations
     */
    public function rollback(): array
    {
        $results = [];
        $migrations = array_reverse($this->getMigrations());

        foreach ($migrations as $migrationClass) {
            try {
                $migration = new $migrationClass($this->driver);
                $sql = $migration->down();

                $this->pdo->exec($sql);

                $results[] = [
                    'migration' => $migrationClass,
                    'status' => 'success',
                    'message' => 'Rolled back successfully'
                ];
            } catch (PDOException $e) {
                $results[] = [
                    'migration' => $migrationClass,
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Get SQL for all migrations (for manual import)
     */
    public function getMigrationSql(): string
    {
        $sql = '';
        $migrations = $this->getMigrations();

        foreach ($migrations as $migrationClass) {
            $migration = new $migrationClass($this->driver);
            $sql .= "-- Migration: {$migrationClass}\n";
            $sql .= $migration->up() . "\n\n";
        }

        return $sql;
    }

    /**
     * Get rollback SQL for all migrations
     */
    public function getRollbackSql(): string
    {
        $sql = '';
        $migrations = array_reverse($this->getMigrations());

        foreach ($migrations as $migrationClass) {
            $migration = new $migrationClass($this->driver);
            $sql .= "-- Rollback: {$migrationClass}\n";
            $sql .= $migration->down() . "\n\n";
        }

        return $sql;
    }

    /**
     * Get list of migration classes
     */
    private function getMigrations(): array
    {
        return [
            CreateWahaSessionsTable::class,
            CreateWahaContactsTable::class,
            CreateWahaMessagesTable::class,
            CreateWahaMessageLogsTable::class,
        ];
    }

    /**
     * Get database driver
     */
    public function getDriver(): string
    {
        return $this->driver;
    }
}