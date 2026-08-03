<?php
declare(strict_types=1);

$dataFile = __DIR__ . '/qrcodes.json';

function getData(string $file): array {
    if (!file_exists($file)) {
        file_put_contents($file, json_encode([], JSON_PRETTY_PRINT));
        return [];
    }
    return json_decode(file_get_contents($file), true) ?? [];
}

function saveData(string $file, array $data): void {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

function generateShortCode(int $length = 6): string {
    return substr(bin2hex(random_bytes($length)), 0, $length);
}

// ==========================================
// 1. REDIRECT HANDLER (Dynamic QR Scans)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['r'])) {
    $code = trim($_GET['r']);
    $db = getData($dataFile);

    if (isset($db[$code])) {
        // Increment Analytics Counter
        $db[$code]['scans'] += 1;
        $db[$code]['last_scan'] = date('Y-m-d H:i:s');
        saveData($dataFile, $db);

        // Redirect to Target URL
        header("Location: " . $db[$code]['target_url'], true, 302);
        exit;
    } else {
        http_response_code(404);
        die("<h3>404 - Invalid Dynamic QR Code</h3>");
    }
}

// ==========================================
// 2. AJAX ENDPOINTS
// ==========================================
$action = $_GET['action'] ?? '';

if ($action === 'create_dynamic' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $rawUrl = $_POST['target_url'] ?? '';
    $targetUrl = filter_var(trim($rawUrl), FILTER_VALIDATE_URL);

    if (!$targetUrl) {
        echo json_encode(['success' => false, 'error' => 'Please enter a valid destination URL.']);
        exit;
    }

    $db = getData($dataFile);
    $code = generateShortCode(6);

    $db[$code] = [
        'target_url' => $targetUrl,
        'scans'      => 0,
        'created_at' => date('Y-m-d H:i:s'),
        'last_scan'  => null
    ];

    saveData($dataFile, $db);

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $qrUrl = "{$protocol}{$host}{$scriptDir}/api.php?r={$code}";

    echo json_encode([
        'success' => true,
        'code'    => $code,
        'qr_url'  => $qrUrl
    ]);
    exit;
}

if ($action === 'get_analytics' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    $db = getData($dataFile);
    echo json_encode(['success' => true, 'data' => $db]);
    exit;
}