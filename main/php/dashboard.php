<?php
/**
 * API Backend Handler
 * Integrates directly with core db.php module
 */

// Include your database setup & security functions
require_once __DIR__ . '/db.php';

// Initialize Secure Session Configuration
secureSession();

// Hardcoded single-tenant context (Can be dynamically replaced with $_SESSION['tenant_id'])
$tenant_id = 1;

// Global Client Identifier for Rate Limiting
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$action = isset($_REQUEST['action']) ? cleanInput($_REQUEST['action']) : '';

switch ($action) {

    // 1. GET DASHBOARD OVERVIEW DATA
    case 'get_overview':
        // Fetch Tenant Master Data
        $stmt = $conn->prepare("SELECT space_name, domain, tier, earnings, storage_used, storage_limit, nodes_count, api_key, total_requests FROM tenants WHERE id = ?");
        $stmt->bind_param("i", $tenant_id);
        $stmt->execute();
        $tenant = $stmt->get_result()->fetch_assoc();

        if (!$tenant) {
            jsonResponse(['status' => 'error', 'message' => 'Tenant account not found.'], 404);
        }

        // Fetch Uploaded Files
        $fileStmt = $conn->prepare("SELECT file_name AS name, file_size AS size FROM tenant_files WHERE tenant_id = ? ORDER BY id DESC");
        $fileStmt->bind_param("i", $tenant_id);
        $fileStmt->execute();
        $filesResult = $fileStmt->get_result();
        $files = [];
        while ($f = $filesResult->fetch_assoc()) {
            $files[] = $f;
        }

        // Fetch System Logs
        $logStmt = $conn->prepare("SELECT created_at AS timestamp, message FROM system_logs WHERE tenant_id = ? ORDER BY id DESC LIMIT 15");
        $logStmt->bind_param("i", $tenant_id);
        $logStmt->execute();
        $logsResult = $logStmt->get_result();
        $logs = [];
        while ($l = $logsResult->fetch_assoc()) {
            $logs[] = $l;
        }

        jsonResponse([
            'status' => 'success',
            'data' => [
                'nodes'         => (int)$tenant['nodes_count'],
                'earnings'      => (float)$tenant['earnings'],
                'storage_used'  => (float)$tenant['storage_used'],
                'storage_limit' => (float)$tenant['storage_limit'],
                'tier'          => $tenant['tier'],
                'space_name'    => $tenant['space_name'],
                'api_key'       => $tenant['api_key'],
                'domain'        => $tenant['domain'],
                'requests'      => (int)$tenant['total_requests'],
                'files'         => $files,
                'logs'          => $logs
            ]
        ]);
        break;

    // 2. SUBSCRIPTION PLAN UPGRADE
    case 'subscribe':
        $rateCheck = checkRateLimit("subscribe_" . $clientIp, 5, 300);
        if (!$rateCheck['allowed']) {
            jsonResponse(['status' => 'error', 'message' => $rateCheck['message']], 429);
        }

        $tier = isset($_POST['tier']) ? cleanInput($_POST['tier']) : '';
        $price = isset($_POST['price']) ? (float)$_POST['price'] : 0.0;

        if (empty($tier)) {
            jsonResponse(['status' => 'error', 'message' => 'Invalid plan tier selected.'], 400);
        }

        // Update Tier and Scale Compute Node Count
        $stmt = $conn->prepare("UPDATE tenants SET tier = ?, nodes_count = nodes_count + 1 WHERE id = ?");
        $stmt->bind_param("si", $tier, $tenant_id);
        $stmt->execute();

        // Write Log Entry
        $logMessage = "Subscribed/Upgraded to {$tier} Plan.";
        $logStmt = $conn->prepare("INSERT INTO system_logs (tenant_id, message) VALUES (?, ?)");
        $logStmt->bind_param("is", $tenant_id, $logMessage);
        $logStmt->execute();

        jsonResponse(['status' => 'success', 'message' => "Successfully upgraded to {$tier}!"]);
        break;

    // 3. FILE DEPLOYMENT & UPLOAD
    case 'upload_deploy':
        $rateCheck = checkRateLimit("upload_" . $clientIp, 10, 600);
        if (!$rateCheck['allowed']) {
            jsonResponse(['status' => 'error', 'message' => $rateCheck['message']], 429);
        }

        if (!isset($_FILES['project_file']) || $_FILES['project_file']['error'] !== UPLOAD_ERR_OK) {
            jsonResponse(['status' => 'error', 'message' => 'No file uploaded or file transfer error.'], 400);
        }

        $file = $_FILES['project_file'];
        $fileName = cleanInput(basename($file['name']));
        $fileBytes = $file['size'];
        $fileMb = round($fileBytes / (1024 * 1024), 2);
        $sizeFormatted = $fileMb > 0 ? $fileMb . ' MB' : round($fileBytes / 1024, 2) . ' KB';

        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $targetPath = $uploadDir . time() . '_' . $fileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Log File to DB
            $stmt = $conn->prepare("INSERT INTO tenant_files (tenant_id, file_name, file_size, file_path) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $tenant_id, $fileName, $sizeFormatted, $targetPath);
            $stmt->execute();

            // Increment Tenant Storage Usage
            $storageStmt = $conn->prepare("UPDATE tenants SET storage_used = storage_used + ? WHERE id = ?");
            $storageStmt->bind_param("di", $fileMb, $tenant_id);
            $storageStmt->execute();

            // Log System Event
            $logMessage = "Deployed file package: {$fileName}";
            $logStmt = $conn->prepare("INSERT INTO system_logs (tenant_id, message) VALUES (?, ?)");
            $logStmt->bind_param("is", $tenant_id, $logMessage);
            $logStmt->execute();

            jsonResponse(['status' => 'success', 'message' => "File '{$fileName}' successfully deployed to node space."]);
        } else {
            jsonResponse(['status' => 'error', 'message' => 'Failed to save file on host server.'], 500);
        }
        break;

    // 4. WEB TERMINAL EXECUTION PIPELINE
    case 'terminal_exec':
        $rateCheck = checkRateLimit("terminal_" . $clientIp, 20, 60);
        if (!$rateCheck['allowed']) {
            jsonResponse(['status' => 'error', 'output' => '[ERROR] Rate limit exceeded. Try again in a minute.'], 429);
        }

        $command = isset($_POST['command']) ? cleanInput($_POST['command']) : '';
        $cmdLower = strtolower(trim($command));
        $output = '';

        if ($cmdLower === 'status') {
            $output = '[INFO] Orbital Telemetry: 100% Operational | Latency: 12ms | CPU: 1.8%';
        } elseif (strpos($cmdLower, 'deploy') === 0) {
            $output = '[DEPLOY] Parsing manifests... Automated container pipeline active.';
        } elseif ($cmdLower === 'help') {
            $output = '[HELP] Available commands: status, deploy, whoami, clear';
        } elseif ($cmdLower === 'whoami') {
            $output = "[USER] Active session tenant ID: {$tenant_id} | Security Role: Admin";
        } else {
            $output = "[SYS] Executed '{$command}'. Command terminated with status code 0.";
        }

        jsonResponse(['status' => 'success', 'output' => $output]);
        break;

    // 5. REQUEST EARNINGS PAYOUT
    case 'request_payout':
        $stmt = $conn->prepare("SELECT earnings FROM tenants WHERE id = ?");
        $stmt->bind_param("i", $tenant_id);
        $stmt->execute();
        $earnings = (float)$stmt->get_result()->fetch_assoc()['earnings'];

        if ($earnings <= 0) {
            jsonResponse(['status' => 'error', 'message' => 'No claimable earnings balance available.'], 400);
        }

        // Reset Balance
        $resetStmt = $conn->prepare("UPDATE tenants SET earnings = 0.00 WHERE id = ?");
        $resetStmt->bind_param("i", $tenant_id);
        $resetStmt->execute();

        // Write Log Entry
        $formattedEarnings = number_format($earnings, 2);
        $logMessage = "Payout requested for ₦{$formattedEarnings}";
        $logStmt = $conn->prepare("INSERT INTO system_logs (tenant_id, message) VALUES (?, ?)");
        $logStmt->bind_param("is", $tenant_id, $logMessage);
        $logStmt->execute();

        jsonResponse(['status' => 'success', 'message' => "Payout request of ₦{$formattedEarnings} successfully sent."]);
        break;

    // 6. REGENERATE API KEY
    case 'regen_api_key':
        $newKey = generateSecureToken(32);

        $stmt = $conn->prepare("UPDATE tenants SET api_key = ? WHERE id = ?");
        $stmt->bind_param("si", $newKey, $tenant_id);
        $stmt->execute();

        // Log System Event
        $logMessage = "API Credential regenerated.";
        $logStmt = $conn->prepare("INSERT INTO system_logs (tenant_id, message) VALUES (?, ?)");
        $logStmt->bind_param("is", $tenant_id, $logMessage);
        $logStmt->execute();

        jsonResponse(['status' => 'success', 'new_key' => $newKey]);
        break;

    // 7. BIND CUSTOM DOMAIN
    case 'bind_domain':
        $domain = isset($_POST['domain']) ? cleanInput($_POST['domain']) : '';

        if (empty($domain)) {
            jsonResponse(['status' => 'error', 'message' => 'Custom domain name cannot be blank.'], 400);
        }

        $stmt = $conn->prepare("UPDATE tenants SET domain = ? WHERE id = ?");
        $stmt->bind_param("si", $domain, $tenant_id);
        $stmt->execute();

        // Log System Event
        $logMessage = "Custom Domain bound: {$domain}";
        $logStmt = $conn->prepare("INSERT INTO system_logs (tenant_id, message) VALUES (?, ?)");
        $logStmt->bind_param("is", $tenant_id, $logMessage);
        $logStmt->execute();

        jsonResponse(['status' => 'success', 'message' => "Custom domain '{$domain}' connected successfully."]);
        break;

    // 8. UPDATE ACCOUNT PREFERENCES
    case 'update_settings':
        $spaceName = isset($_POST['space_name']) ? cleanInput($_POST['space_name']) : '';

        if (empty($spaceName)) {
            jsonResponse(['status' => 'error', 'message' => 'Space name cannot be empty.'], 400);
        }

        $stmt = $conn->prepare("UPDATE tenants SET space_name = ? WHERE id = ?");
        $stmt->bind_param("si", $spaceName, $tenant_id);
        $stmt->execute();

        // Log System Event
        $logMessage = "Space preferences updated to '{$spaceName}'";
        $logStmt = $conn->prepare("INSERT INTO system_logs (tenant_id, message) VALUES (?, ?)");
        $logStmt->bind_param("is", $tenant_id, $logMessage);
        $logStmt->execute();

        jsonResponse(['status' => 'success', 'message' => 'Tenant settings saved successfully.']);
        break;

    default:
        jsonResponse(['status' => 'error', 'message' => 'Invalid or missing API endpoint action.'], 400);
        break;
}