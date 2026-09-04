<?php
declare(strict_types=1);

namespace Unwinded\Core;

/**
 * Simple migration runner.
 * Migrations are PHP files in database/migrations/ that return an array
 * with 'up' (SQL string or callable) and 'down' (SQL string or callable).
 * Runs are tracked in the `migrations` table.
 */
class Migrator
{
    private string $migrationPath;

    public function __construct(private Database $db, string $basePath)
    {
        $this->migrationPath = $basePath . '/database/migrations';
    }

    public function install(): void
    {
        $this->db->pdo()->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration  VARCHAR(255) NOT NULL UNIQUE,
                batch      INT UNSIGNED NOT NULL DEFAULT 1,
                ran_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        ");
    }

    /** Run all pending migrations. Returns list of run migration names. */
    public function run(): array
    {
        $this->install();
        $ran = $this->getRan();
        $pending = $this->getPending($ran);
        if (empty($pending)) return [];

        $batch = $this->getNextBatch();
        $executed = [];
        foreach ($pending as $file) {
            $migration = require $file;
            $this->runUp($migration['up']);
            $name = basename($file, '.php');
            $this->db->insert('migrations', ['migration' => $name, 'batch' => $batch, 'ran_at' => now()]);
            $executed[] = $name;
            echo "  Ran: {$name}\n";
        }
        return $executed;
    }

    /** Rollback the last batch. */
    public function rollback(): array
    {
        $this->install();
        $batch = $this->getLastBatch();
        if ($batch === 0) return [];

        $rows = $this->db->fetchAll(
            "SELECT migration FROM migrations WHERE batch = ? ORDER BY id DESC",
            [$batch]
        );
        $rolled = [];
        foreach ($rows as $row) {
            $file = $this->migrationPath . '/' . $row['migration'] . '.php';
            if (file_exists($file)) {
                $migration = require $file;
                $this->runDown($migration['down']);
            }
            $this->db->execute("DELETE FROM migrations WHERE migration = ?", [$row['migration']]);
            $rolled[] = $row['migration'];
            echo "  Rolled back: {$row['migration']}\n";
        }
        return $rolled;
    }

    /** Drop ALL tables and re-run all migrations (development only). */
    public function fresh(): void
    {
        $this->db->pdo()->exec("SET FOREIGN_KEY_CHECKS = 0");
        $tables = $this->db->fetchAll("SHOW TABLES");
        foreach ($tables as $row) {
            $table = array_values($row)[0];
            $this->db->pdo()->exec("DROP TABLE IF EXISTS `{$table}`");
        }
        $this->db->pdo()->exec("SET FOREIGN_KEY_CHECKS = 1");
        echo "Dropped all tables.\n";
        $this->run();
    }

    private function runUp(string|callable $sql): void
    {
        if (is_callable($sql)) {
            $sql($this->db);
            return;
        }
        // Split on statement delimiter for multi-statement migrations.
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
            $this->db->pdo()->exec($stmt);
        }
    }

    private function runDown(string|callable $sql): void
    {
        $this->runUp($sql);
    }

    private function getRan(): array
    {
        $rows = $this->db->fetchAll("SELECT migration FROM migrations");
        return array_column($rows, 'migration');
    }

    private function getPending(array $ran): array
    {
        $files = glob($this->migrationPath . '/*.php') ?: [];
        sort($files);
        return array_filter($files, fn($f) => !in_array(basename($f, '.php'), $ran, true));
    }

    private function getNextBatch(): int
    {
        return (int) ($this->db->fetchScalar("SELECT COALESCE(MAX(batch), 0) FROM migrations") ?? 0) + 1;
    }

    private function getLastBatch(): int
    {
        return (int) ($this->db->fetchScalar("SELECT COALESCE(MAX(batch), 0) FROM migrations") ?? 0);
    }
}
