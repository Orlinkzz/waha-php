<?php

namespace Orlinkzz\Waha\Laravel\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;
use Orlinkzz\Waha\Database\MigrationManager;

class WahaMigrateCommand extends Command
{
    protected $signature = 'waha:migrate {--rollback : Rollback the migrations} {--driver= : Database driver (mysql or pgsql)} {--force : Force the operation to run}';

    protected $description = 'Run WAHA database migrations';

    public function handle()
    {
        if (!$this->option('force') && !$this->confirm('Are you sure you want to run the migrations?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        try {
            $driver = $this->getDatabaseDriver();

            if ($this->option('rollback')) {
                $this->rollbackMigrations($driver);
            } else {
                $this->runMigrations($driver);
            }
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function getDatabaseDriver(): string
    {
        $driver = $this->option('driver');

        if (!$driver) {
            $driver = config('database.default');
        }

        if (in_array($driver, ['pgsql', 'postgres', 'postgresql'])) {
            return 'postgres';
        } elseif (in_array($driver, ['mysql', 'mariadb'])) {
            return 'mysql';
        }

        throw new \InvalidArgumentException("Unsupported database driver: {$driver}. Please specify with --driver option.");
    }

    private function runMigrations(string $driver): void
    {
        $this->info("Running WAHA migrations for {$driver}...");

        // Get PDO connection
        $connection = DB::connection();
        $pdo = $connection->getPdo();

        $manager = new MigrationManager($pdo);

        if ($manager->getDriver() !== $driver) {
            throw new \InvalidArgumentException("PDO driver does not match specified driver");
        }

        $results = $manager->migrate();

        foreach ($results as $result) {
            if ($result['status'] === 'success') {
                $this->info("✓ Migrated: " . $result['migration']);
            } else {
                $this->error("✗ Failed: " . $result['migration'] . " - " . $result['message']);
            }
        }

        $this->info('Migrations completed!');
    }

    private function rollbackMigrations(string $driver): void
    {
        $this->info("Rolling back WAHA migrations for {$driver}...");

        // Get PDO connection
        $connection = DB::connection();
        $pdo = $connection->getPdo();

        $manager = new MigrationManager($pdo);

        if ($manager->getDriver() !== $driver) {
            throw new \InvalidArgumentException("PDO driver does not match specified driver");
        }

        $results = $manager->rollback();

        foreach ($results as $result) {
            if ($result['status'] === 'success') {
                $this->info("✓ Rolled back: " . $result['migration']);
            } else {
                $this->error("✗ Failed: " . $result['migration'] . " - " . $result['message']);
            }
        }

        $this->info('Rollback completed!');
    }
}