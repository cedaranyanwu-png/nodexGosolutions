<?php
/**
 * NodeXGo shared JSON storage facade.
 *
 * The legacy Database engine remains the source of truth for compatibility.
 * This facade gives future modules one stable dependency instead of embedding
 * JSON reads and writes in page controllers or JavaScript-facing endpoints.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../php/database.php';

final class JsonStorage
{
    private Database $database;

    public function __construct(?Database $database = null)
    {
        $this->database = $database ?? new Database();
    }

    /** Select all records or records matching exact field values. */
    public function all(string $table, array $where = []): array
    {
        return $this->database->select($table, $where);
    }

    /** Return the first record matching exact field values, if present. */
    public function first(string $table, array $where): ?array
    {
        return $this->database->selectOne($table, $where);
    }

    /** Insert a record using the existing locked Database implementation. */
    public function insert(string $table, array $record): array|false
    {
        return $this->database->insert($table, $record);
    }

    /** Update matching records using the existing locked Database implementation. */
    public function update(string $table, array $data, array $where): int
    {
        return $this->database->update($table, $data, $where);
    }

    /** Delete matching records using the existing Database contract. */
    public function delete(string $table, array $where): int
    {
        return $this->database->delete($table, $where);
    }

    /** Expose the legacy engine only for gradual migration of existing services. */
    public function legacy(): Database
    {
        return $this->database;
    }
}
