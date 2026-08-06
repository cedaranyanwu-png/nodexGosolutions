<?php
/**
 * database.php
 *
 * This file contains a secure, custom, file-locked JSON-based Database Engine.
 * It serves as a unified replacement for legacy MySQLi queries.
 */

// Enable strict typing for architectural safety
declare(strict_types=1);

/**
 * Class Database
 *
 * Simulates relational database tables using local JSON files with write locking (LOCK_EX).
 */
class Database
{
    // Directory path on disk where database tables are stored
    private string $storagePath;
    // Current active database schema folder name
    private string $activeDb;

    /**
     * Database Constructor
     *
     * @param string $storagePath Directory path to store dynamic json tables.
     * @param string|null $defaultDb Default database folder name.
     */
    public function __construct(string $storagePath = __DIR__ . '/../databases', ?string $defaultDb = null)
    {
        // Normalize trailing slashes
        $this->storagePath = rtrim($storagePath, '/\\');
        if ($defaultDb !== null) {
            // Bind default schema
            $this->useDatabase($defaultDb);
        }
    }

    /**
     * Set active database schema folder.
     *
     * @param string $dbName Schema folder name.
     * @return self
     */
    public function useDatabase(string $dbName): self
    {
        $this->activeDb = $dbName;
        // Construct the full database schema folder path on disk
        $fullPath = $this->storagePath . '/' . $dbName;
        // Automatically create database directory if it does not exist
        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }
        return $this;
    }

    /**
     * Create a new database table schema file.
     *
     * @param string $tableName Table name.
     * @return bool True if created or exists, false on failure.
     */
    public function createTable(string $tableName): bool
    {
        $filePath = $this->getTableFilePath($tableName);
        if (file_exists($filePath)) {
            // Already exists, return true
            return true;
        }
        // Write an empty JSON array to initialize table records
        return file_put_contents($filePath, json_encode([]), LOCK_EX) !== false;
    }

    /**
     * Drop a database table file from disk.
     *
     * @param string $tableName Table name to delete.
     * @return bool True on success, false on failure.
     */
    public function dropTable(string $tableName): bool
    {
        $filePath = $this->getTableFilePath($tableName);
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return false;
    }

    /**
     * Insert a new row (record) into a table.
     *
     * @param string $tableName Target table name.
     * @param array $data Row column values array.
     * @return array|false Succeeded row data with unique ID, or false on failure.
     */
    public function insert(string $tableName, array $data): array|false
    {
        $filePath = $this->getTableFilePath($tableName);
        if (!file_exists($filePath)) {
            $this->createTable($tableName);
        }

        // Lock file and read existing records
        $records = $this->readRecordsWithLock($filePath, $lockHandle);
        if ($records === null) {
            return false;
        }

        // Calculate next auto-incrementing ID integer
        $nextId = 1;
        foreach ($records as $rec) {
            if (isset($rec['id']) && (int)$rec['id'] >= $nextId) {
                $nextId = (int)$rec['id'] + 1;
            }
        }

        // Append unique ID and timestamps to record
        $data['id'] = $nextId;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        // Append to the list and write back
        $records[] = $data;
        $written = $this->writeRecordsWithLock($lockHandle, $records);

        return $written ? $data : false;
    }

    /**
     * Select rows from table matching criteria.
     *
     * @param string $tableName Table name.
     * @param array $where Filter criteria columns.
     * @return array Matching row arrays list.
     */
    public function select(string $tableName, array $where = []): array
    {
        $filePath = $this->getTableFilePath($tableName);
        if (!file_exists($filePath)) {
            return [];
        }

        // Lock file and read existing records
        $records = $this->readRecordsWithLock($filePath, $lockHandle);
        if ($records === null) {
            return [];
        }
        $this->releaseLock($lockHandle);

        // If no filter is provided, return all records
        if (empty($where)) {
            return $records;
        }

        $results = [];
        foreach ($records as $rec) {
            $match = true;
            foreach ($where as $key => $val) {
                if (!isset($rec[$key]) || $rec[$key] != $val) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                $results[] = $rec;
            }
        }

        return $results;
    }

    /**
     * Select a single row matching criteria.
     *
     * @param string $tableName Table name.
     * @param array $where Filter columns.
     * @return array|null Returns first matched row or null.
     */
    public function selectOne(string $tableName, array $where): ?array
    {
        $rows = $this->select($tableName, $where);
        return count($rows) > 0 ? $rows[0] : null;
    }

    /**
     * Update existing rows inside table matching criteria.
     *
     * @param string $tableName Table name.
     * @param array $data Columns to update.
     * @param array $where Filter criteria.
     * @return int Total updated rows count.
     */
    public function update(string $tableName, array $data, array $where): int
    {
        $filePath = $this->getTableFilePath($tableName);
        if (!file_exists($filePath)) {
            return 0;
        }

        // Lock file and read existing records
        $records = $this->readRecordsWithLock($filePath, $lockHandle);
        if ($records === null) {
            return 0;
        }

        $updatedCount = 0;
        foreach ($records as &$rec) {
            $match = true;
            foreach ($where as $key => $val) {
                if (!isset($rec[$key]) || $rec[$key] != $val) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                // Apply update fields
                foreach ($data as $k => $v) {
                    $rec[$k] = $v;
                }
                $rec['updated_at'] = date('Y-m-d H:i:s');
                $updatedCount++;
            }
        }

        if ($updatedCount > 0) {
            $this->writeRecordsWithLock($lockHandle, $records);
        } else {
            $this->releaseLock($lockHandle);
        }

        return $updatedCount;
    }

    /**
     * Delete rows from table matching criteria.
     *
     * @param string $tableName Table name.
     * @param array $where Filter criteria.
     * @return int Total deleted rows count.
     */
    public function delete(string $tableName, array $where): int
    {
        $filePath = $this->getTableFilePath($tableName);
        if (!file_exists($filePath)) {
            return 0;
        }

        // Lock file and read existing records
        $records = $this->readRecordsWithLock($filePath, $lockHandle);
        if ($records === null) {
            return 0;
        }

        $remainingRecords = [];
        $deletedCount = 0;

        foreach ($records as $rec) {
            $match = true;
            foreach ($where as $key => $val) {
                if (!isset($rec[$key]) || $rec[$key] != $val) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                $deletedCount++;
            } else {
                $remainingRecords[] = $rec;
            }
        }

        if ($deletedCount > 0) {
            $this->writeRecordsWithLock($lockHandle, $remainingRecords);
        } else {
            $this->releaseLock($lockHandle);
        }

        return $deletedCount;
    }

    /**
     * Search table records matching query pattern across specific columns.
     *
     * @param string $tableName Table name.
     * @param string $query Text query to search for.
     * @param array $columns Target columns to inspect.
     * @return array Matched row arrays.
     */
    public function search(string $tableName, string $query, array $columns = []): array
    {
        $rows = $this->select($tableName);
        if (empty($query)) {
            return $rows;
        }

        $query = strtolower(trim($query));
        $results = [];

        foreach ($rows as $row) {
            $match = false;
            // Define inspection columns list
            $targetCols = empty($columns) ? array_keys($row) : $columns;
            foreach ($targetCols as $col) {
                if (isset($row[$col]) && str_contains(strtolower((string)$row[$col]), $query)) {
                    $match = true;
                    break;
                }
            }
            if ($match) {
                $results[] = $row;
            }
        }

        return $results;
    }

    /**
     * Helper to resolve clean absolute file paths for table JSON files.
     */
    private function getTableFilePath(string $tableName): string
    {
        return $this->storagePath . '/' . $this->activeDb . '/' . $tableName . '.json';
    }

    /**
     * Lock the table file and read records.
     */
    private function readRecordsWithLock(string $filePath, &$lockHandle): ?array
    {
        // Open file handle in read/write mode
        $lockHandle = fopen($filePath, 'c+');
        if (!$lockHandle) {
            return null;
        }

        // Apply exclusive blocking write lock
        if (!flock($lockHandle, LOCK_EX)) {
            fclose($lockHandle);
            return null;
        }

        // Read all contents
        $size = filesize($filePath);
        $content = '';
        if ($size > 0) {
            rewind($lockHandle);
            $content = fread($lockHandle, $size);
        }

        return json_decode($content ?: '[]', true) ?: [];
    }

    /**
     * Write records and release file lock.
     */
    private function writeRecordsWithLock($lockHandle, array $records): bool
    {
        // Truncate file length to zero before rewriting
        ftruncate($lockHandle, 0);
        rewind($lockHandle);
        // Write formatted json back to file
        $written = fwrite($lockHandle, json_encode($records, JSON_PRETTY_PRINT)) !== false;
        // Release write lock and close handle
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
        return $written;
    }

    /**
     * Release active lock and close file handle.
     */
    private function releaseLock($lockHandle): void
    {
        if ($lockHandle) {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
        }
    }
}
?>
