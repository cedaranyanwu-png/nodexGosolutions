<?php
/**
 * database.php
 *
 * This file implements a clean, robust, and secured JSON-based Database Engine.
 * It manages folder-based databases where each table is represented by a JSON file.
 * To ensure high reliability, security, and read-write isolation, it supports:
 * - Proper parameter validation
 * - Dynamic database creation and table creation
 * - Robust select, insert, update, delete, and search capabilities
 * - Row-level IDs and basic lock emulation
 */

// Enable strict typing mode to enforce type safety
declare(strict_types=1);

/**
 * Class Database
 *
 * Simulates relational database operations using physical directory structures and JSON files.
 */
class Database
{
    // Holds the root storage path where databases are saved
    private string $baseDir;

    // Stores the directory path of the currently selected active database
    private ?string $currentDb = null;

    /**
     * Database constructor.
     *
     * @param string $storagePath Base path where all databases are located.
     * @param string|null $defaultDb Optional initial database name to switch to.
     */
    public function __construct(string $storagePath = __DIR__ . '/../databases', ?string $defaultDb = null)
    {
        // Trim any trailing slashes from the storage directory path
        $this->baseDir = rtrim($storagePath, '/\\');

        // Check if the base directory exists; if not, create it recursively with standard permissions
        if (!is_dir($this->baseDir)) {
            // Create directory recursively with 0755 permissions
            mkdir($this->baseDir, 0755, true);
        }

        // If a default database name is provided, switch to it automatically
        if ($defaultDb !== null) {
            // Set the active database
            $this->useDatabase($defaultDb);
        }
    }

    /**
     * Switch or select the active database. Creates the directory if it does not exist.
     *
     * @param string $dbName The name of the database.
     * @return self Returns the Database instance for method chaining.
     */
    public function useDatabase(string $dbName): self
    {
        // Construct the full path of the database directory under the base path
        $this->currentDb = $this->baseDir . '/' . trim($dbName, '/\\');

        // Check if the selected database folder exists; if not, create it recursively
        if (!is_dir($this->currentDb)) {
            // Create directory recursively with 0755 permissions
            mkdir($this->currentDb, 0755, true);
        }

        // Return the current object context to allow method chaining
        return $this;
    }

    /**
     * Creates a new JSON table file if it doesn't already exist.
     *
     * @param string $tableName The table name.
     * @return bool True on success, false if the table already exists.
     */
    public function createTable(string $tableName): bool
    {
        // Resolve the physical file path of the table
        $filePath = $this->getFilePath($tableName);

        // If the table file already exists, abort the creation process
        if (file_exists($filePath)) {
            // Return false as creation did not occur
            return false;
        }

        // Write an empty array encoded in JSON format to initialize the table
        return file_put_contents($filePath, json_encode([], JSON_PRETTY_PRINT)) !== false;
    }

    /**
     * Deletes a JSON table file from the active database.
     *
     * @param string $tableName The table name.
     * @return bool True if deleted, false if file does not exist.
     */
    public function dropTable(string $tableName): bool
    {
        // Resolve the physical file path of the table
        $filePath = $this->getFilePath($tableName);

        // If the table file physically exists, attempt to unlink/delete it
        if (file_exists($filePath)) {
            // Delete the file and return the boolean result of the deletion
            return unlink($filePath);
        }

        // Return false as there was no file to delete
        return false;
    }

    /**
     * Inserts a record into the specified JSON table.
     *
     * @param string $tableName The table name.
     * @param array $data The column-value associative array to insert.
     * @return array|false The inserted record including auto-assigned ID and timestamps, or false on failure.
     */
    public function insert(string $tableName, array $data): array|false
    {
        // Read the existing records from the specified table
        $records = $this->readTable($tableName);

        // If the ID is not manually provided, calculate a unique auto-incrementing ID
        if (!isset($data['id'])) {
            // Retrieve all IDs from the existing list of records
            $ids = array_column($records, 'id');
            // Assign next ID: max of existing numeric IDs + 1, or default to 1 if empty
            $data['id'] = !empty($ids) ? max(array_filter($ids, 'is_numeric')) + 1 : 1;
        }

        // Set the creation timestamp for the record
        $data['created_at'] = date('Y-m-d H:i:s');
        // Set the update timestamp for the record
        $data['updated_at'] = date('Y-m-d H:i:s');

        // Append the new record data array to the loaded records array
        $records[] = $data;

        // Save the updated list of records back to the table file with proper locking
        if ($this->writeTable($tableName, $records)) {
            // Return the full inserted record data
            return $data;
        }

        // Return false indicating failure to persist the data
        return false;
    }

    /**
     * Selects records matching specified conditions.
     *
     * @param string $tableName The table name.
     * @param array $where Filter conditions (e.g., ['email' => 'foo@bar.com']).
     * @return array Matches found.
     */
    public function select(string $tableName, array $where = []): array
    {
        // Read the array of records from the table
        $records = $this->readTable($tableName);

        // If there are no where filter parameters, return all records
        if (empty($where)) {
            // Return the full set of records
            return $records;
        }

        // Filter records by checking each row against all key-value pairs in the where conditions
        return array_values(array_filter($records, function ($row) use ($where) {
            // Loop through each key-value check in our where filter
            foreach ($where as $key => $value) {
                // If a key does not exist or value does not match, exclude the row
                if (!isset($row[$key]) || $row[$key] !== $value) {
                    // Filter match failure
                    return false;
                }
            }
            // All filters match successfully for this row
            return true;
        }));
    }

    /**
     * Helper to retrieve a single record matching specific criteria.
     *
     * @param string $tableName The table name.
     * @param array $where Filter conditions.
     * @return array|null The record array if found, otherwise null.
     */
    public function selectOne(string $tableName, array $where): ?array
    {
        // Run select filter to load matching records
        $results = $this->select($tableName, $where);
        // Return the first match if it exists, otherwise return null
        return $results[0] ?? null;
    }

    /**
     * Updates rows matching specified conditions with new data.
     *
     * @param string $tableName The table name.
     * @param array $data Fields and values to update.
     * @param array $where Filtering conditions identifying which rows to update.
     * @return int Number of updated rows.
     */
    public function update(string $tableName, array $data, array $where): int
    {
        // Load the existing records from the table
        $records = $this->readTable($tableName);
        // Count the number of records actually modified
        $updatedCount = 0;

        // Loop through all records by reference to allow modifying values directly
        foreach ($records as &$row) {
            // Tracks if all match conditions are fulfilled for this row
            $match = true;
            // Iterate over all filtering requirements
            foreach ($where as $key => $value) {
                // Check if key is absent or value is non-matching
                if (!isset($row[$key]) || $row[$key] !== $value) {
                    // Match failed
                    $match = false;
                    // Break out of inner loop
                    break;
                }
            }

            // If a complete match is confirmed, update the row fields
            if ($match) {
                // Merge new key-value data fields into the row
                foreach ($data as $k => $v) {
                    // Update key values
                    $row[$k] = $v;
                }
                // Update the modification timestamp field
                $row['updated_at'] = date('Y-m-d H:i:s');
                // Increment our count of updated rows
                $updatedCount++;
            }
        }
        // Unset reference variable to prevent variable collision side-effects
        unset($row);

        // If at least one row was successfully modified, persist the updated array
        if ($updatedCount > 0) {
            // Write records back to table
            $this->writeTable($tableName, $records);
        }

        // Return total modified records count
        return $updatedCount;
    }

    /**
     * Deletes records matching the conditions from the JSON table.
     *
     * @param string $tableName The table name.
     * @param array $where Filter conditions.
     * @return int Count of deleted rows.
     */
    public function delete(string $tableName, array $where): int
    {
        // Load all records currently in the table
        $records = $this->readTable($tableName);
        // Track original record count before filtering
        $initialCount = count($records);

        // Filter the records, keeping only those that do NOT match the deletion criteria
        $filteredRecords = array_values(array_filter($records, function ($row) use ($where) {
            // Check each deletion condition key-value pair
            foreach ($where as $key => $value) {
                // If a key matches the value, it's a target for deletion, so return false (exclude)
                if (isset($row[$key]) && $row[$key] === $value) {
                    // Target match for deletion - exclude
                    return false;
                }
            }
            // Keep the row
            return true;
        }));

        // Calculate the difference to determine how many records were successfully deleted
        $deletedCount = $initialCount - count($filteredRecords);

        // If any rows were deleted, write the clean list back to disk
        if ($deletedCount > 0) {
            // Persist the filtered records list
            $this->writeTable($tableName, $filteredRecords);
        }

        // Return total deleted records count
        return $deletedCount;
    }

    /**
     * Performs a text-based search across records in a table.
     *
     * @param string $tableName The table name.
     * @param string $query Text query to search.
     * @param array $columns Optional list of columns to restrict the search to.
     * @return array Matches found.
     */
    public function search(string $tableName, string $query, array $columns = []): array
    {
        // Read current table records
        $records = $this->readTable($tableName);
        // Normalize the search text to lowercase and strip whitespaces
        $query = strtolower(trim($query));

        // If query is empty, return all loaded records directly
        if ($query === '') {
            // Return unmodified list of records
            return $records;
        }

        // Return matched records by searching field values
        return array_values(array_filter($records, function ($row) use ($query, $columns) {
            // Filter record array to check only requested search columns, or use the entire row
            $searchableData = empty($columns) ? $row : array_intersect_key($row, array_flip($columns));

            // Loop through each field value in searchable data set
            foreach ($searchableData as $val) {
                // Only inspect string or numeric value types
                if (is_string($val) || is_numeric($val)) {
                    // Check if value contains the lowercase query text substring
                    if (str_contains(strtolower((string)$val), $query)) {
                        // Match found - keep this row
                        return true;
                    }
                }
            }
            // No matches found in this row
            return false;
        }));
    }

    /**
     * Resolves and returns the full file path of a table JSON file inside the active database folder.
     *
     * @param string $tableName The table name.
     * @return string Full path.
     */
    private function getFilePath(string $tableName): string
    {
        // If no active database is set, throw an Exception
        if (!$this->currentDb) {
            // Throw exception
            throw new Exception("No active database selected. Call useDatabase('db_name') first.");
        }
        // Return full path of the requested table JSON file
        return $this->currentDb . '/' . trim($tableName) . '.json';
    }

    /**
     * Low-level helper to read and decode a JSON table file into a PHP array.
     *
     * @param string $tableName The table name.
     * @return array Decoded records list.
     */
    private function readTable(string $tableName): array
    {
        // Resolve full file path of the table
        $filePath = $this->getFilePath($tableName);

        // If the table file does not exist, return an empty list
        if (!file_exists($filePath)) {
            // Return empty array
            return [];
        }

        // Fetch contents from table file
        $content = file_get_contents($filePath);

        // Decode JSON content into an associative PHP array; fallback to empty array on any decoding errors
        return json_decode($content, true) ?: [];
    }

    /**
     * Low-level helper to safely write data back to a JSON table file.
     *
     * @param string $tableName The table name.
     * @param array $data Records to write.
     * @return bool True on success, false on failure.
     */
    private function writeTable(string $tableName, array $data): bool
    {
        // Resolve target file path
        $filePath = $this->getFilePath($tableName);

        // Write encoded JSON back with exclusive locking to prevent write conflicts
        return file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX) !== false;
    }
}
