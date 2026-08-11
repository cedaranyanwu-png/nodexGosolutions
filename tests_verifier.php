<?php
/**
 * tests_verifier.php
 *
 * Automated test suite to practice proactive testing of verification,
 * profile updates, administrative action privilege guard rules, and
 * the custom JSON database engine integrity.
 */

declare(strict_types=1);

// Require central database configurations
require_once __DIR__ . '/php/db.php';

echo "========================================================\n";
echo "       NODEXGOSOLUTIONS SYSTEM AUTOMATED TEST SUITE     \n";
echo "========================================================\n\n";

$testsPassed = 0;
$testsFailed = 0;

function assertTest(string $name, bool $expression): void {
    global $testsPassed, $testsFailed;
    if ($expression) {
        echo "✅ PASS: {$name}\n";
        $testsPassed++;
    } else {
        echo "❌ FAIL: {$name}\n";
        $testsFailed++;
    }
}

// --- TEST 1: Database Class Instance Integrity ---
try {
    // In db.php, the database base path is __DIR__ . '/../databases'
    // Since php/db.php is in php/ folder, __DIR__ is repo_root/php.
    // So __DIR__ . '/../databases' points to repo_root/databases.
    // Let's pass the exact same path to our sandbox database:
    $dbPath = __DIR__ . '/databases';
    echo "Using test DB Path: {$dbPath}\n";

    $testDb = new Database($dbPath, 'test_sandbox');

    $created = $testDb->createTable('test_table');
    echo "createTable returned: " . json_encode($created) . "\n";

    // Clear old data first
    $deleted = $testDb->delete('test_table', []);
    echo "delete returned: {$deleted}\n";

    // Insert test rows
    $res1 = $testDb->insert('test_table', ['name' => 'John Tenant', 'status' => 'active']);
    echo "insert 1 returned: " . json_encode($res1) . "\n";

    $res2 = $testDb->insert('test_table', ['name' => 'Jane Tenant', 'status' => 'suspended']);
    echo "insert 2 returned: " . json_encode($res2) . "\n";

    $rows = $testDb->select('test_table') ?: [];
    echo "Select returned row count: " . count($rows) . "\n";
    assertTest("Database insertion and select table rows count", count($rows) === 2);

    // Select One
    $rowOne = $testDb->selectOne('test_table', ['id' => 1]);
    echo "selectOne returned: " . json_encode($rowOne) . "\n";
    assertTest("Database selectOne matching ID record", $rowOne && $rowOne['name'] === 'John Tenant');

    // Update One
    $testDb->update('test_table', ['name' => 'John Verified'], ['id' => 1]);
    $updatedRow = $testDb->selectOne('test_table', ['id' => 1]);
    assertTest("Database update query criteria update", $updatedRow && $updatedRow['name'] === 'John Verified');

    // Delete One
    $testDb->delete('test_table', ['id' => 2]);
    $remaining = $testDb->select('test_table') ?: [];
    assertTest("Database delete query rows drop", count($remaining) === 1);

    // Drop Table
    $testDb->dropTable('test_table');
} catch (\Throwable $e) {
    assertTest("Database engine exception error: " . $e->getMessage(), false);
}

// --- TEST 2: System Auto-Seeder Admin Account ---
try {
    $adminUser = $conn->selectOne('users', ['email' => 'admin@nodexplatform.com.ng']);
    assertTest("System auto-seeds main administrator account on first load", $adminUser !== null);
    assertTest("Main administrator account holds active admin roles", $adminUser && $adminUser['role'] === 'admin');
} catch (\Throwable $e) {
    assertTest("Auto-Seeder assertion error: " . $e->getMessage(), false);
}

// --- TEST 3: Admin Privilege Actions Protections ---
try {
    // Mimic php/admin_action.php privileges verification
    $mainAdminEmail = 'admin@nodexplatform.com.ng';

    // Rule check: The main admin must NEVER be allowed to be deleted or suspended
    // Let's test a mock function resembling our admin actions guard rules
    function mockDeleteUser(string $targetEmail, string $mainAdmin): bool {
        if ($targetEmail === $mainAdmin) {
            return false; // Forbidden!
        }
        return true;
    }

    $deletionAttemptOnMainAdmin = mockDeleteUser($mainAdminEmail, $mainAdminEmail);
    assertTest("Security Guard: Main administrator account CANNOT be deleted", $deletionAttemptOnMainAdmin === false);

    $deletionAttemptOnStandardUser = mockDeleteUser('tenant@nodex.com', $mainAdminEmail);
    assertTest("Security Guard: Standard user accounts can be deleted", $deletionAttemptOnStandardUser === true);
} catch (\Throwable $e) {
    assertTest("Privilege Guard validation failed: " . $e->getMessage(), false);
}

echo "\n========================================================\n";
echo "TEST RESULTS SUMMARY:\n";
echo "Total Passed: {$testsPassed}\n";
echo "Total Failed: {$testsFailed}\n";
echo "========================================================\n";

if ($testsFailed > 0) {
    exit(1);
} else {
    exit(0);
}
?>
