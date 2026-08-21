<?php
/**
 * track_metrics.php
 *
 * Secure real-time endpoint that handles event tracking requests (page_views or clicks)
 * from deployed client-side websites. Updates the `traffic` table accordingly.
 * All lines are heavily commented to preserve scalability and maintainability.
 */

// Enable strict typing for safety
declare(strict_types=1);

// Require central system configurations and database engine
require_once __DIR__ . '/db.php';

// Set response headers
header('Content-Type: application/json; charset=utf-8');

// Ensure tables exist
$conn->createTable('websites');
$conn->createTable('traffic');

// Allow requests from all origins (CORS support for subdomains)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get subdomain parameter from GET or POST query
$subdomain = cleanInput($_REQUEST['subdomain'] ?? '');
$event     = cleanInput($_REQUEST['event'] ?? 'page_view'); // 'page_view' or 'click'

if (empty($subdomain)) {
    jsonResponse(['success' => false, 'message' => 'Missing subdomain parameter.'], 400);
}

// Locate matching website in database
$website = $conn->selectOne('websites', ['subdomain' => $subdomain]);
if (!$website) {
    jsonResponse(['success' => false, 'message' => 'Website workspace not found.'], 404);
}

$websiteId = (int)$website['id'];

// Check if a traffic record already exists for this website ID
$tRow = $conn->selectOne('traffic', ['website_id' => $websiteId]);

if (!$tRow) {
    // If not found, initialize default stats record
    $tRow = [
        'website_id' => $websiteId,
        'visits'     => 1,
        'visitors'   => 1,
        'page_views' => 1,
        'clicks'     => 0,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];

    if ($event === 'click') {
        $tRow['clicks'] = 1;
        $tRow['page_views'] = 0;
        $tRow['visits'] = 0;
        $tRow['visitors'] = 0;
    }

    $conn->insert('traffic', $tRow);
} else {
    // Increment specific metric fields based on dynamic events
    $visits    = (int)($tRow['visits'] ?? 0);
    $visitors  = (int)($tRow['visitors'] ?? 0);
    $pageViews = (int)($tRow['page_views'] ?? 0);
    $clicks    = (int)($tRow['clicks'] ?? 0);

    if ($event === 'page_view') {
        $pageViews++;
        $visits++;
        // Approximate visitors on basic heuristic (every 5th page view as a new visitor for testing)
        if ($pageViews % 5 === 0) {
            $visitors++;
        }
    } elseif ($event === 'click') {
        $clicks++;
    }

    $conn->update('traffic', [
        'visits'     => $visits,
        'visitors'   => $visitors,
        'page_views' => $pageViews,
        'clicks'     => $clicks,
        'updated_at' => date('Y-m-d H:i:s')
    ], ['website_id' => $websiteId]);
}

jsonResponse([
    'success' => true,
    'message' => 'Event tracked successfully.',
    'subdomain' => $subdomain,
    'event' => $event
]);
?>
