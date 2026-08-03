<?php
/**
 * qrcode.php
 *
 * Manages Dynamic QR Code generations, redirection counters, and scan metrics using the Database class.
 */

// Enable strict typing for safety
declare(strict_types=1);

// Require the centralized JSON Database class from same directory
require_once __DIR__ . '/database.php';

// Instantiate the Database pointing to /app/databases/qrcode
$db = new Database(__DIR__ . '/../databases', 'qrcode');

// Ensure table 'qrcodes' is created
$db->createTable('qrcodes');

/**
 * Generates a random alphanumeric short code of a specific length.
 *
 * @param int $length The code length.
 * @return string The generated code.
 */
function generateShortCode(int $length = 6): string {
    // Generate secure bytes and strip to length
    return substr(bin2hex(random_bytes($length)), 0, $length);
}

// ==========================================
// 1. REDIRECT HANDLER (Dynamic QR Scans)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['r'])) {
    // Sanitize input code parameter
    $code = trim($_GET['r']);

    // Select matched QR code details
    $link = $db->selectOne('qrcodes', ['code' => $code]);

    // Check if matching QR code record exists
    if ($link !== null) {
        // Increment analytics scans counter and record last scan timestamp
        $db->update('qrcodes', [
            'scans' => (int)($link['scans'] ?? 0) + 1,
            'last_scan' => date('Y-m-d H:i:s')
        ], ['id' => $link['id']]);

        // Redirect user to the target URL
        header("Location: " . $link['target_url'], true, 302);
        // Terminate execution
        exit;
    } else {
        // Send not found header
        http_response_code(404);
        // Terminate with error
        die("<h3>404 - Invalid Dynamic QR Code</h3>");
    }
}

// ==========================================
// 2. AJAX ENDPOINTS
// ==========================================
$action = $_GET['action'] ?? '';

// Check if request is a dynamic QR creation action
if ($action === 'create_dynamic' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Send response header
    header('Content-Type: application/json');

    // Extract target url inputs
    $rawUrl = $_POST['target_url'] ?? '';
    $targetUrl = filter_var(trim($rawUrl), FILTER_VALIDATE_URL);

    // Validate URL syntax
    if (!$targetUrl) {
        // Return validation failure JSON response
        echo json_encode(['success' => false, 'error' => 'Please enter a valid destination URL.']);
        // Stop execution
        exit;
    }

    // Generate unique short code
    do {
        // Generate short code
        $code = generateShortCode(6);
        // Verify code uniqueness in database
        $dup = $db->selectOne('qrcodes', ['code' => $code]);
    } while ($dup !== null);

    // Save QR Code record fields in qrcodes table
    $db->insert('qrcodes', [
        'code'       => $code,
        'target_url' => $targetUrl,
        'scans'      => 0,
        'created_at' => date('Y-m-d H:i:s'),
        'last_scan'  => null
    ]);

    // Determine protocol
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    // Capture current host
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Format analytic API scan endpoint URL
    $qrUrl = "{$protocol}{$host}/php/qrcode.php?r={$code}";

    // Output success details response
    echo json_encode([
        'success' => true,
        'code'    => $code,
        'qr_url'  => $qrUrl
    ]);
    // Terminate
    exit;
}

// Check if action matches fetch analytics request
if ($action === 'get_analytics' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    // Send json header
    header('Content-Type: application/json');
    // Load all records from qrcodes table
    $recordsList = $db->select('qrcodes');
    // Format records into an associative array indexed by 'code' to match frontend UI expectations
    $dbIndexed = [];
    foreach ($recordsList as $rec) {
        if (isset($rec['code'])) {
            $dbIndexed[$rec['code']] = $rec;
        }
    }
    // Return analytics list
    echo json_encode(['success' => true, 'data' => $dbIndexed]);
    // Terminate
    exit;
}
?>
