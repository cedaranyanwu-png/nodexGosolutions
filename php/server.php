<?php
/**
 * server.php
 *
 * This is the central modularized PHP server backend dispatcher for nodexGosolutions.
 * It acts as the single backend coordinator, requiring modular utility scripts
 * and delegating incoming requests to their respective functional components dynamically.
 */

// Enable strict typing for highest reliability
declare(strict_types=1);

// Include central database and session configuration helpers
require_once __DIR__ . '/db.php';

// Capture and sanitize incoming request endpoint parameter identifier
$endpoint = cleanInput($_REQUEST['endpoint'] ?? '');

// Match and delegate execution to the designated functional component
switch ($endpoint) {

    // ENDPOINT: Handle user and administrator logins
    case 'login':
        require_once __DIR__ . '/login.php';
        break;

    // ENDPOINT: Handle new tenant account registrations
    case 'register':
        require_once __DIR__ . '/register.php';
        break;

    // ENDPOINT: Dispatch account state modifications in Admin Dashboard
    case 'admin_action':
        require_once __DIR__ . '/admin_action.php';
        break;

    // ENDPOINT: Dispatch custom Database table actions in User Dashboard
    case 'user_database_action':
        require_once __DIR__ . '/user_database_action.php';
        break;

    // ENDPOINT: Handle background account verification transitions
    case 'verify':
        require_once __DIR__ . '/verify.php';
        break;

    // DEFAULT Scenario: Require and export all modular system functions for general file inclusions
    default:
        require_once __DIR__ . '/database.php';
        require_once __DIR__ . '/db.php';
        break;
}
?>
