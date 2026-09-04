<?php

declare(strict_types=1);

namespace Unwinded\Services;

use Unwinded\Core\Database;

/**
 * Reads and writes rows in the settings table.
 * All values are loaded once per request and cached in memory.
 */
class SettingsService
{
    private array $cache  = [];
    private bool  $loaded = false;

    public function __construct(private Database $db) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $this->load();
        return array_key_exists($key, $this->cache) ? $this->cache[$key] : $default;
    }

    /** Return all settings keyed by key. */
    public function all(): array
    {
        $this->load();
        return $this->cache;
    }

    /** Return settings rows grouped by group_name, with full row data. */
    public function grouped(): array
    {
        $rows    = $this->db->fetchAll("SELECT * FROM settings ORDER BY group_name, id");
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['group_name']][] = $row;
        }
        return $grouped;
    }

    /** Persist a single setting value. */
    public function set(string $key, ?string $value): void
    {
        $this->db->execute(
            "UPDATE settings SET value = ? WHERE `key` = ?",
            [$value, $key]
        );
        $this->cache[$key] = $value;
    }

    /** Bulk-update multiple settings from a flat key=>value map. */
    public function bulkSet(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->set((string) $key, $value === '' ? null : (string) $value);
        }
    }

    private function load(): void
    {
        if ($this->loaded) {
            return;
        }
        $rows = $this->db->fetchAll("SELECT `key`, value FROM settings");
        foreach ($rows as $row) {
            $this->cache[$row['key']] = $row['value'];
        }
        $this->loaded = true;
    }
}
