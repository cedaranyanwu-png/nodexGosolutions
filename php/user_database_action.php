<?php
/**
 * user_database_action.php
 *
 * Implements a secure JSON-based Database Management Endpoint for tenant workspaces.
 * Allows users to list, create, and drop custom tables, as well as perform CRUD
 * operations (insert, list, delete rows) inside their dynamically isolated JSON database.
 */

// Enable strict typing for architectural safety
declare(strict_types=1);

// Require system database connection configs and security helpers
require_once __DIR__ . '/db.php';

// Instantiate secure session context
secureSession();

// Access Control: Ensure the user session is authenticated
if (!isset($_SESSION['user_id'])) {
    // Return unauthorized HTTP response code
    jsonResponse(['success' => false, 'message' => 'Unauthorized access.'], 401);
}

// Security Enforcement: Ensure standard user's trial or subscription is active.
// Serves JSON error blocks directly to caller in case of subscription expiration or suspension.
enforceSubscription(true);

// Identify isolated tenant-specific JSON database name
$userId = (int)$_SESSION['user_id'];
$userDbName = 'user_db_' . $userId;

// Initialize custom file-based JSON Database instance for this tenant
$userDb = new Database(__DIR__ . '/../databases', $userDbName);

// Capture input request actions parameters
$action = cleanInput($_POST['action'] ?? $_GET['action'] ?? '');

// Match and execute requested database administrative operations
switch ($action) {

    // ACTION: List all existing custom tables inside user database folder
    case 'list_tables':
        // Define directory pointer to the user's isolated database path
        $dbPath = __DIR__ . '/../databases/' . $userDbName;
        $tables = [];

        // Check if the directory exists on disk
        if (is_dir($dbPath)) {
            // Open the directory and read files
            if ($handle = opendir($dbPath)) {
                while (false !== ($entry = readdir($handle))) {
                    // Check if file is a valid JSON table file (excluding navigation dots)
                    if ($entry !== '.' && $entry !== '..' && str_ends_with(strtolower($entry), '.json')) {
                        // Strip the .json extension to isolate clean table name
                        $tables[] = substr($entry, 0, -5);
                    }
                }
                closedir($handle);
            }
        }

        // Return table list payload
        jsonResponse([
            'success' => true,
            'tables'  => $tables,
            'message' => 'Tables list retrieved successfully.'
        ]);
        break;

    // ACTION: Create a new custom database table schema file
    case 'create_table':
        // Capture and sanitize raw table name
        $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', cleanInput($_POST['table_name'] ?? ''));

        // Reject empty table names
        if (empty($tableName)) {
            jsonResponse(['success' => false, 'message' => 'Please provide a valid table name.'], 400);
        }

        // Invoke custom Database driver to create the schema table file
        $created = $userDb->createTable($tableName);

        if ($created) {
            jsonResponse(['success' => true, 'message' => "Table '{$tableName}' created successfully!"]);
        } else {
            jsonResponse(['success' => false, 'message' => 'Failed to create table. It may already exist.'], 500);
        }
        break;

    // ACTION: Drop (delete) an existing database table schema file from disk
    case 'drop_table':
        // Capture and sanitize target table name
        $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', cleanInput($_POST['table_name'] ?? ''));

        // Reject empty table names
        if (empty($tableName)) {
            jsonResponse(['success' => false, 'message' => 'Invalid table name supplied.'], 400);
        }

        // Invoke Database driver to drop the table file
        $dropped = $userDb->dropTable($tableName);

        if ($dropped) {
            jsonResponse(['success' => true, 'message' => "Table '{$tableName}' dropped successfully!"]);
        } else {
            jsonResponse(['success' => false, 'message' => 'Failed to delete table.'], 500);
        }
        break;

    // ACTION: Retrieve all rows (records) inside a specific custom table
    case 'get_rows':
        // Capture and validate table name
        $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', cleanInput($_GET['table_name'] ?? ''));

        if (empty($tableName)) {
            jsonResponse(['success' => false, 'message' => 'Missing table name.'], 400);
        }

        // Select all rows from table
        $rows = $userDb->select($tableName);

        jsonResponse([
            'success' => true,
            'rows'    => $rows,
            'message' => 'Rows retrieved successfully.'
        ]);
        break;

    // ACTION: Insert a new row with custom JSON columns
    case 'insert_row':
        // Capture and validate table name
        $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', cleanInput($_POST['table_name'] ?? ''));

        if (empty($tableName)) {
            jsonResponse(['success' => false, 'message' => 'Missing table name.'], 400);
        }

        // Capture raw custom columns data array from input post params
        $columnsRaw = $_POST['columns'] ?? [];
        $rowData = [];

        // Check if data is array
        if (is_array($columnsRaw)) {
            foreach ($columnsRaw as $col) {
                $colName = preg_replace('/[^a-zA-Z0-9_]/', '', cleanInput($col['name'] ?? ''));
                $colVal  = cleanInput($col['value'] ?? '');
                if (!empty($colName)) {
                    $rowData[$colName] = $colVal;
                }
            }
        }

        // Reject empty rows
        if (empty($rowData)) {
            jsonResponse(['success' => false, 'message' => 'Please provide at least one valid column value.'], 400);
        }

        // Insert row using custom Database driver
        $insertedRow = $userDb->insert($tableName, $rowData);

        if ($insertedRow !== false) {
            jsonResponse([
                'success' => true,
                'row'     => $insertedRow,
                'message' => 'Row inserted successfully!'
            ]);
        } else {
            jsonResponse(['success' => false, 'message' => 'Failed to insert row.'], 500);
        }
        break;

    // ACTION: Delete a row by its unique ID
    case 'delete_row':
        // Capture table name and row ID
        $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', cleanInput($_POST['table_name'] ?? ''));
        $rowId     = (int)($_POST['row_id'] ?? 0);

        if (empty($tableName) || $rowId <= 0) {
            jsonResponse(['success' => false, 'message' => 'Missing table name or invalid row ID.'], 400);
        }

        // Execute row deletion statement
        $deletedCount = $userDb->delete($tableName, ['id' => $rowId]);

        if ($deletedCount > 0) {
            jsonResponse(['success' => true, 'message' => 'Row deleted successfully!']);
        } else {
            jsonResponse(['success' => false, 'message' => 'Failed to locate or delete designated row.'], 500);
        }
        break;

    // DEFAULT Scenario: Return invalid action warning
    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action supplied.'], 400);
        break;
}
?>
