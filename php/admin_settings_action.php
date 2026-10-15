<?php
/**
 * admin_settings_action.php
 *
 * Secure backend processor that handles administrative configurations.
 * Saves/updates Flutterwave payment settings credentials safely in the system database.
 * Detects masked keys (with asterisks) to preserve previously saved keys instead of overwriting.
 * Restricts privileges to Super Admins or users possessing settings.edit capabilities.
 * Fully documented line-by-line to ensure perfect readability.
 */

// Enable strict typing for safety
declare(strict_types=1);

// Require core configuration files
require_once __DIR__ . '/db.php';

// Instantiate secure session configurations
secureSession();

// RBAC Enforcement Guard: only super admins or users with settings.edit capabilities can enter
if (!checkAdminPermission('settings.edit')) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized: Settings modification privileges required.'], 401);
}

// Restrict requests to POST actions only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

// Extract payment configuration parameters from payload
$flwPublicKey     = cleanInput($_POST['flw_public_key'] ?? '');
$flwSecretKey     = cleanInput($_POST['flw_secret_key'] ?? '');
$flwEncryptionKey = cleanInput($_POST['flw_encryption_key'] ?? '');

// Initialize settings database table
$conn->createTable('settings');

// Retrieve existing settings to check for masking overrides
$existing = $conn->selectOne('settings', ['id' => 'flutterwave']);

// Helper to check if a submitted key is masked (meaning no change was made)
$isMasked = function(string $key): bool {
    return str_contains($key, '***') || str_contains($key, '••••');
};

// Process Public Key
if ($isMasked($flwPublicKey) && $existing) {
    $finalPublicKey = $existing['flw_public_key'] ?? '';
} else {
    $finalPublicKey = $flwPublicKey;
}

// Process Secret Key
if ($isMasked($flwSecretKey) && $existing) {
    $finalSecretKey = $existing['flw_secret_key'] ?? '';
} else {
    $finalSecretKey = $flwSecretKey;
}

// Process Encryption Key
if ($isMasked($flwEncryptionKey) && $existing) {
    $finalEncryptionKey = $existing['flw_encryption_key'] ?? '';
} else {
    $finalEncryptionKey = $flwEncryptionKey;
}

// Ensure keys are not completely empty
if (empty($finalPublicKey) || empty($finalSecretKey)) {
    jsonResponse(['success' => false, 'message' => 'Public and Secret keys cannot be left blank.'], 400);
}

// Persist the updated configuration back inside the system settings table
$conn->update('settings', [
    'flw_public_key'     => $finalPublicKey,
    'flw_secret_key'     => $finalSecretKey,
    'flw_encryption_key' => $finalEncryptionKey,
    'updated_at'         => date('Y-m-d H:i:s')
], ['id' => 'flutterwave']);

// Log administrative activity audit trace
$conn->createTable('activity_logs');
$conn->insert('activity_logs', [
    'user_id'    => $_SESSION['user_id'] ?? 0,
    'email'      => $_SESSION['email'] ?? 'admin',
    'action'     => 'settings_modified',
    'details'    => 'Updated global Flutterwave Payment configuration credentials',
    'created_at' => date('Y-m-d H:i:s')
]);

jsonResponse(['success' => true, 'message' => 'Flutterwave settings updated successfully. Keys have been secured server-side.']);
?>
