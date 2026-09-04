<?php
declare(strict_types=1);

namespace Unwinded\Core;

use PDO;
use PDOStatement;

/**
 * Thin PDO wrapper. All SQL lives in Repository classes.
 * Provides begin/commit/rollback transaction helpers and a
 * consistent statement execution API.
 */
class Database
{
    private PDO $pdo;
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->connect();
    }

    private function connect(): void
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $this->config['host']    ?? 'localhost',
            $this->config['port']    ?? 3306,
            $this->config['database'] ?? '',
            $this->config['charset'] ?? 'utf8mb4'
        );
        $this->pdo = new PDO(
            $dsn,
            $this->config['username'] ?? '',
            $this->config['password'] ?? '',
            $this->config['options']  ?? []
        );
        $this->pdo->exec("SET time_zone = '+00:00'");
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Execute a prepared statement and return it.
     */
    public function execute(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Return all rows as an array of associative arrays.
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->execute($sql, $params)->fetchAll();
    }

    /**
     * Return the first row, or null.
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $row = $this->execute($sql, $params)->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * Return a single scalar value from the first column of the first row.
     */
    public function fetchScalar(string $sql, array $params = []): mixed
    {
        $row = $this->execute($sql, $params)->fetch(PDO::FETCH_NUM);
        return $row !== false ? $row[0] : null;
    }

    /**
     * Insert a row and return the last inserted ID.
     */
    public function insert(string $table, array $data): int|string
    {
        $cols   = array_keys($data);
        $places = array_map(fn($c) => ':' . $c, $cols);
        $sql    = sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            $table,
            implode(', ', array_map(fn($c) => '`' . $c . '`', $cols)),
            implode(', ', $places)
        );
        $this->execute($sql, $data);
        return $this->pdo->lastInsertId();
    }

    /**
     * Update rows matching $where and return affected count.
     */
    public function update(string $table, array $data, array $where): int
    {
        $setClauses   = array_map(fn($c) => '`' . $c . '` = :set_' . $c, array_keys($data));
        $whereClauses = array_map(fn($c) => '`' . $c . '` = :whr_' . $c, array_keys($where));
        $sql = sprintf(
            'UPDATE `%s` SET %s WHERE %s',
            $table,
            implode(', ', $setClauses),
            implode(' AND ', $whereClauses)
        );
        $params = [];
        foreach ($data  as $k => $v) { $params['set_' . $k] = $v; }
        foreach ($where as $k => $v) { $params['whr_' . $k] = $v; }
        return $this->execute($sql, $params)->rowCount();
    }

    public function beginTransaction(): void   { $this->pdo->beginTransaction(); }
    public function commit(): void             { $this->pdo->commit(); }
    public function rollback(): void           { $this->pdo->rollBack(); }
    public function inTransaction(): bool      { return $this->pdo->inTransaction(); }

    /**
     * Execute a callable inside a transaction; rolls back on exception.
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function lastInsertId(): int|string
    {
        return $this->pdo->lastInsertId();
    }

    public function quote(mixed $value): string
    {
        return $this->pdo->quote((string) $value);
    }
}
