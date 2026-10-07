<?php
/**
 * db.php
 *
 * This file replaces the MySQLi connection layer with our custom JSON-based
 * Database Engine, providing robust authentication and security helpers.
 * It also automatically seeds the database with the main administrator account.
 */

// Enable strict typing for better reliability and fewer runtime bugs
declare(strict_types=1);

// Require our unified JSON Database engine class from the same directory
require_once __DIR__ . '/database.php';
// Require reusable SEO & JSON-LD schema helper component
require_once __DIR__ . '/seo_helper.php';
// Require dynamic Tool Discovery & Integration Manager
require_once __DIR__ . '/ToolManager.php';

// Instantiate global ToolManager service instance
$toolManager = new ToolManager();

// Initialize the Database instance targeting the 'system' JSON database directory
// We store this instance in $conn to maintain compatibility across existing files
$conn = new Database(__DIR__ . '/../databases', 'system');

// Ensure tables exist under 'system' database folder
$conn->createTable('users');
$conn->createTable('rate_limits');
$conn->createTable('login_attempts');
$conn->createTable('tickets'); // Support tickets database table
$conn->createTable('plans'); // Pricing plans configuration table
$conn->createTable('settings'); // Global application settings table (like Flutterwave keys)
$conn->createTable('payments'); // Central payment history log table
$conn->createTable('subscriptions'); // Active/expired hosting subscriptions
$conn->createTable('roles'); // Custom roles listing
$conn->createTable('permissions'); // Granular permissions mapped to roles
$conn->createTable('activity_logs'); // Secure administrative activity audits
$conn->createTable('workspaces'); // Workspace environments
$conn->createTable('workspace_members'); // Workspace team members & roles

// Dynamic Auto-Seeder: Seed the main administrator account if users table is empty
$usersCount = count($conn->select('users'));
if ($usersCount === 0) {
    // Hash password admin123 securely
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    // Seed admin profile
    $conn->insert('users', [
        'fullname' => 'Cedar Anyanwu',
        'email' => 'admin@nodexplatform.com.ng',
        'password' => $hashedPassword,
        'role' => 'super admin', // Upgraded to default Super Admin for complete RBAC support
        'status' => 'active',
        'is_verified' => 1,
        'email_verified' => 1,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);
}

// Auto-seed default pricing plans if the plans table is fresh
$plansCount = count($conn->select('plans'));
if ($plansCount === 0) {
    // Insert Micro plan (₦3,000 / month)
    $conn->insert('plans', [
        'id' => 'micro',
        'name' => 'Micro',
        'price' => 3000,
        'currency' => 'NGN',
        'billing_period' => 'month',
        'description' => 'Perfect for launching small custom hosting websites and projects.',
        'features' => json_encode(['1 Website', 'Shared SSL', '1 GB Bandwidth']),
        'is_active' => 1,
        'display_order' => 1,
        'is_recommended' => 0
    ]);
    // Insert Growth plan (₦25,000 / month)
    $conn->insert('plans', [
        'id' => 'growth',
        'name' => 'Growth',
        'price' => 25000,
        'currency' => 'NGN',
        'billing_period' => 'month',
        'description' => 'Ideal for growing developer portals and custom subdomain hosting sites.',
        'features' => json_encode(['Unlimited Websites', 'Full database integration', '10 GB Bandwidth', 'Priority SLA support']),
        'is_active' => 1,
        'display_order' => 2,
        'is_recommended' => 1
    ]);
    // Insert Business Pro plan (₦75,000 / month)
    $conn->insert('plans', [
        'id' => 'business_pro',
        'name' => 'Business Pro',
        'price' => 75000,
        'currency' => 'NGN',
        'billing_period' => 'month',
        'description' => 'High-capacity computational limits with multi-server cloud capabilities.',
        'features' => json_encode(['Dedicated servers', 'SLA 99.99% uptime', 'Unmetered bandwidth', 'All features included']),
        'is_active' => 1,
        'display_order' => 3,
        'is_recommended' => 0
    ]);
}

// Helper to discover and parse Flutterwave API keys from environment or text files
function discoverFlutterwaveKeys(): array {
    $flwSecret = getenv('FLW_SECRET_KEY') ?: '';
    $flwPublic = getenv('FLW_PUBLIC_KEY') ?: '';
    $flwEnc    = getenv('FLW_ENCRYPTION_KEY') ?: '';

    $possiblePaths = [
        __DIR__ . '/../flutterwave-keys.txt',
        __DIR__ . '/../flutterwave_keys.txt',
        __DIR__ . '/../flutterwave.txt',
        __DIR__ . '/../flw_keys.txt',
        '/tmp/flutterwave-keys.txt',
        '/tmp/flutterwave.txt'
    ];

    foreach ($possiblePaths as $filePath) {
        if (file_exists($filePath) && is_readable($filePath)) {
            $content = (string)file_get_contents($filePath);
            if (preg_match('/Secret\s*Key:\s*(\S+)/i', $content, $m)) {
                $flwSecret = trim($m[1]);
            } elseif (preg_match('/(FLWSECK[-_]\S+)/i', $content, $m)) {
                $flwSecret = trim($m[1]);
            }

            if (preg_match('/Public\s*Key:\s*(\S+)/i', $content, $m)) {
                $flwPublic = trim($m[1]);
            } elseif (preg_match('/(FLWPUBK[-_]\S+)/i', $content, $m)) {
                $flwPublic = trim($m[1]);
            }

            if (preg_match('/Encryption\s*Key:\s*(\S+)/i', $content, $m)) {
                $flwEnc = trim($m[1]);
            } elseif (preg_match('/(FLWENCK[-_]\S+)/i', $content, $m)) {
                $flwEnc = trim($m[1]);
            }
        }
    }

    if (empty($flwPublic) && !empty($flwSecret)) {
        $flwPublic = str_replace('FLWSECK-', 'FLWPUBK-', $flwSecret);
    }

    return [
        'secret' => $flwSecret,
        'public' => $flwPublic,
        'enc'    => $flwEnc
    ];
}

// Auto-seed or update initial Flutterwave payment settings configuration parameters
$allSettings = $conn->select('settings') ?: [];
$existingSettings = $allSettings[0] ?? null;
$discovered = discoverFlutterwaveKeys();

if (!$existingSettings) {
    $conn->insert('settings', [
        'id' => 'flutterwave',
        'flw_public_key' => $discovered['public'] ?: 'FLWPUBK_TEST-sandbox-pubkey-123456789',
        'flw_secret_key' => $discovered['secret'] ?: 'FLWSECK_TEST-sandbox-secretkey-123456789',
        'flw_encryption_key' => $discovered['enc'] ?: 'FLWENCK_TEST-sandbox-encryptionkey-123456789',
        'updated_at' => date('Y-m-d H:i:s')
    ]);
} else {
    // If settings currently store sandbox defaults but live/real keys are found in key file/env, sync them
    $currentSecret = $existingSettings['flw_secret_key'] ?? '';
    if (str_contains($currentSecret, 'TEST-sandbox') && !empty($discovered['secret']) && !str_contains($discovered['secret'], 'TEST-sandbox')) {
        $conn->update('settings', [
            'flw_public_key'     => $discovered['public'],
            'flw_secret_key'     => $discovered['secret'],
            'flw_encryption_key' => $discovered['enc'],
            'updated_at'         => date('Y-m-d H:i:s')
        ], ['id' => $existingSettings['id']]);
    }
}

// Auto-seed standard RBAC roles and permissions mapping
$rolesCount = count($conn->select('roles'));
if ($rolesCount === 0) {
    // Standard role registry records
    $conn->insert('roles', ['id' => 'super admin', 'name' => 'Super Admin', 'description' => 'Complete absolute administrative privileges']);
    $conn->insert('roles', ['id' => 'admin', 'name' => 'Admin', 'description' => 'General administration capabilities']);
    $conn->insert('roles', ['id' => 'manager', 'name' => 'Manager', 'description' => 'Manages users and website workspaces']);
    $conn->insert('roles', ['id' => 'support', 'name' => 'Support', 'description' => 'Assists clients and processes tickets']);
    $conn->insert('roles', ['id' => 'moderator', 'name' => 'Moderator', 'description' => 'Audits content and custom templates']);
}

$permissionsCount = count($conn->select('permissions'));
if ($permissionsCount === 0) {
    // Define initial helper permissions array mapping
    $defaultPermissions = [
        // Admin permissions assignments
        ['role' => 'admin', 'permission' => 'users.view', 'is_allowed' => 1],
        ['role' => 'admin', 'permission' => 'users.edit', 'is_allowed' => 1],
        ['role' => 'admin', 'permission' => 'users.suspend', 'is_allowed' => 1],
        ['role' => 'admin', 'permission' => 'websites.view', 'is_allowed' => 1],
        ['role' => 'admin', 'permission' => 'websites.suspend', 'is_allowed' => 1],
        ['role' => 'admin', 'permission' => 'payments.view', 'is_allowed' => 1],
        ['role' => 'admin', 'permission' => 'subscriptions.view', 'is_allowed' => 1],
        ['role' => 'admin', 'permission' => 'pricing.view', 'is_allowed' => 1],

        // Manager permissions assignments
        ['role' => 'manager', 'permission' => 'users.view', 'is_allowed' => 1],
        ['role' => 'manager', 'permission' => 'websites.view', 'is_allowed' => 1],
        ['role' => 'manager', 'permission' => 'websites.suspend', 'is_allowed' => 1],

        // Support permissions assignments
        ['role' => 'support', 'permission' => 'users.view', 'is_allowed' => 1],
        ['role' => 'support', 'permission' => 'websites.view', 'is_allowed' => 1],
    ];
    foreach ($defaultPermissions as $p) {
        $conn->insert('permissions', $p);
    }
}

// Ensure the new granular backup permissions exist in the dynamic database
// This ensures backward compatibility for pre-existing installations
$backupPerms = ['backups.view', 'backups.create', 'backups.verify', 'backups.restore', 'backups.delete'];
// Loop through each required backup permission
foreach ($backupPerms as $perm) {
    // Check if the permission is already mapped for the admin role
    $hasPerm = $conn->selectOne('permissions', ['role' => 'admin', 'permission' => $perm]);
    // If not, insert it securely
    if (!$hasPerm) {
        $conn->insert('permissions', ['role' => 'admin', 'permission' => $perm, 'is_allowed' => 1]);
    }
}

// Seed additional granular permissions dynamically
$additionalPerms = [
    ['role' => 'manager', 'permission' => 'teams.view', 'is_allowed' => 1],
    ['role' => 'manager', 'permission' => 'teams.manage', 'is_allowed' => 1],
    ['role' => 'moderator', 'permission' => 'moderation.view', 'is_allowed' => 1],
    ['role' => 'moderator', 'permission' => 'moderation.manage', 'is_allowed' => 1],
    ['role' => 'support', 'permission' => 'tickets.view', 'is_allowed' => 1],
    ['role' => 'support', 'permission' => 'tickets.manage', 'is_allowed' => 1]
];
foreach ($additionalPerms as $ap) {
    $hasAp = $conn->selectOne('permissions', ['role' => $ap['role'], 'permission' => $ap['permission']]);
    if (!$hasAp) {
        $conn->insert('permissions', $ap);
    }
}

// Require the central BackupManager class module
require_once __DIR__ . '/BackupManager.php';
// Instantiate the BackupManager service
$backupManager = new BackupManager();
// Automatically initialize the NGS Backups private folder structure on bootstrap
$backupManager->ensureBackupDirectory();

/**
 * Configure secure and isolated session properties.
 */
function secureSession(): void {
    // Configure session parameters if session is not yet active
    if (session_status() === PHP_SESSION_NONE) {
        // Prevent PHP from passing session ID in URLs
        @ini_set('session.use_strict_mode', '1');
        // Enforce session cookies to be accessed only via HTTP protocols
        @ini_set('session.use_only_cookies', '1');
        // Mitigate XSS attacks by locking access to session cookies via JS
        @ini_set('session.cookie_httponly', '1');
        // Allow sharing session cookie during standard redirection paths
        @ini_set('session.cookie_samesite', 'Lax');

        // Check if the current connection runs over a secured SSL transport layer
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            // Mark session cookie as secure
            @ini_set('session.cookie_secure', '1');
        }

        // Define an explicit, standard session name
        session_name('NODX_SESSION');

        // Invoke session start
        session_start();
    }

    // Manage session expiration and rotation for heightened security
    if (!isset($_SESSION['_created'])) {
        // Record creation time
        $_SESSION['_created'] = time();
    } elseif (time() - $_SESSION['_created'] > 1800) {
        // Regenerate unique session ID and flush old session data
        session_regenerate_id(true);
        // Reset creation timestamp
        $_SESSION['_created'] = time();
    }
}

/**
 * Generates a cryptographically strong, secure token.
 *
 * @param int $length The byte length of raw data before hex conversion.
 * @return string The generated secure token.
 */
function generateSecureToken(int $length = 32): string {
    // Generate secure bytes and return hex encoded string
    return bin2hex(random_bytes($length));
}

/**
 * Helper function to retrieve all workspaces accessible to a user (as owner or team member).
 * Auto-creates a default personal workspace for the user if none exists and migrates unassigned resources.
 */
function getUserWorkspaces(int $userId): array {
    global $conn;
    $conn->createTable('workspaces');
    $conn->createTable('workspace_members');

    // Get user details
    $user = $conn->selectOne('users', ['id' => $userId]);
    $userEmail = strtolower((string)($user['email'] ?? ''));

    // Owned workspaces
    $owned = $conn->select('workspaces', ['user_id' => $userId]) ?: [];

    // Workspaces where user is a team member
    $membershipRecords = $conn->select('workspace_members', ['email' => $userEmail]) ?: [];
    $joinedIds = [];
    foreach ($membershipRecords as $mem) {
        $wId = (int)($mem['workspace_id'] ?? 0);
        if ($wId > 0 && !in_array($wId, $joinedIds, true)) {
            $joinedIds[] = $wId;
        }
    }

    $joined = [];
    foreach ($joinedIds as $wId) {
        $ws = $conn->selectOne('workspaces', ['id' => $wId]);
        if ($ws && (int)($ws['user_id'] ?? 0) !== $userId) {
            $joined[] = $ws;
        }
    }

    $allWorkspaces = array_merge($owned, $joined);

    // If no workspace exists yet for this user, create a default personal workspace
    if (empty($allWorkspaces) && $user) {
        $ownerName = $user['fullname'] ?? 'Personal';
        $defaultWs = $conn->insert('workspaces', [
            'user_id' => $userId,
            'name' => "{$ownerName}'s Workspace",
            'description' => 'Default primary workspace environment.',
            'type' => 'Personal',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        if ($defaultWs) {
            // Register owner in workspace_members
            $conn->insert('workspace_members', [
                'workspace_id' => $defaultWs['id'],
                'user_id' => $userId,
                'fullname' => $user['fullname'] ?? 'Owner',
                'email' => $userEmail,
                'role' => 'owner',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $allWorkspaces = [$defaultWs];
        }
    }

    // Auto-migrate existing unassigned user websites to default workspace
    if (!empty($allWorkspaces)) {
        $defaultWsId = $allWorkspaces[0]['id'];
        $conn->createTable('websites');
        $userWebsites = $conn->select('websites', ['user_id' => $userId]) ?: [];
        foreach ($userWebsites as $web) {
            if (!isset($web['workspace_id']) || empty($web['workspace_id'])) {
                $conn->update('websites', ['workspace_id' => $defaultWsId], ['id' => $web['id']]);
            }
        }
    }

    return $allWorkspaces;
}

/**
 * Helper to retrieve the active selected workspace for a given user array.
 */
function getActiveWorkspace(array $user): array {
    global $conn;
    $userId = (int)($user['id'] ?? 0);
    $workspaces = getUserWorkspaces($userId);

    secureSession();

    if (isset($_SESSION['active_workspace_id'])) {
        $activeId = (int)$_SESSION['active_workspace_id'];
        foreach ($workspaces as $ws) {
            if ((int)($ws['id'] ?? 0) === $activeId) {
                return $ws;
            }
        }
    }

    // Default to first available workspace
    $activeWs = $workspaces[0] ?? [
        'id' => 1,
        'user_id' => $userId,
        'name' => 'Default Workspace',
        'description' => 'Primary workspace',
        'type' => 'Personal'
    ];

    $_SESSION['active_workspace_id'] = $activeWs['id'];
    return $activeWs;
}

/**
 * Clean and escape dangerous character sequences in user input to mitigate XSS injections.
 *
 * @param string $data Raw input data.
 * @return string Sanitized input string.
 */
function cleanInput(string $data): string {
    // Strip leading/trailing whitespaces
    $data = trim($data);
    // Remove backslashes
    $data = stripslashes($data);
    // HTML escape content using UTF-8 representation
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Checks and updates rate limit values inside the JSON database.
 *
 * @param string $identifier Unique rate limiting key (such as client IP + action name).
 * @param int $maxAttempts Total allowed action occurrences before lockout.
 * @param int $windowSeconds Lockout duration window in seconds.
 * @return array Evaluation details showing if allowed, remaining attempts, or retry delay.
 */
function checkRateLimit(string $identifier, int $maxAttempts = 5, int $windowSeconds = 900): array {
    // Grab global database connection instance
    global $conn;

    // Find the record matching the rate limit identifier
    $row = $conn->selectOne('rate_limits', ['identifier' => $identifier]);
    // Get the current Unix timestamp
    $now = time();

    // If no existing rate limit tracking record is found, initialize one
    if (!$row) {
        // Prepare new rate limit data structure
        $newLimit = [
            'identifier' => $identifier,
            'attempts' => 1,
            'last_attempt' => $now
        ];
        // Insert record into rate_limits JSON table
        $conn->insert('rate_limits', $newLimit);
        // Return success response with remaining attempts
        return ['allowed' => true, 'remaining' => $maxAttempts - 1];
    }

    // Reset rate limit attempts count if the tracking window has elapsed
    if ($now - (int)$row['last_attempt'] > $windowSeconds) {
        // Update tracking values
        $conn->update('rate_limits', ['attempts' => 1, 'last_attempt' => $now], ['identifier' => $identifier]);
        // Return success response with fresh attempts
        return ['allowed' => true, 'remaining' => $maxAttempts - 1];
    }

    // Handle threshold hit where max allowed rate attempts have been exhausted
    if ((int)$row['attempts'] >= $maxAttempts) {
        // Calculate remaining cooldown duration
        $retryAfter = $windowSeconds - ($now - (int)$row['last_attempt']);
        // Return rate limit blocked response
        return [
            'allowed' => false,
            'retry_after' => $retryAfter,
            'message' => "Too many attempts. Please try again in " . (int)ceil($retryAfter / 60) . " minutes."
        ];
    }

    // Update attempt metrics by incrementing the attempts count
    $conn->update('rate_limits', [
        'attempts' => (int)$row['attempts'] + 1,
        'last_attempt' => $now
    ], ['identifier' => $identifier]);

    // Return success response with updated remaining attempts
    return ['allowed' => true, 'remaining' => $maxAttempts - (int)$row['attempts'] - 1];
}

/**
 * Deletes rate limit logs to reset counts on successful actions (e.g., correct login).
 *
 * @param string $identifier Unique rate limiting key.
 */
function resetRateLimit(string $identifier): void {
    // Grab global database connection instance
    global $conn;
    // Remove the corresponding rate limit entry
    $conn->delete('rate_limits', ['identifier' => $identifier]);
}

/**
 * Return JSON response and terminate program execution.
 *
 * @param mixed $data Content to serialize and echo.
 * @param int $statusCode HTTP response status header.
 */
function jsonResponse(mixed $data, int $statusCode = 200): void {
    // Assign HTTP status code
    http_response_code($statusCode);
    // Send correct content encoding header
    header('Content-Type: application/json; charset=utf-8');
    // Output encoded json string
    echo json_encode($data);
    // Exit current execution context
    exit;
}

/**
 * Appends a dynamic auto-increment parameter to asset URLs based on file modification time.
 * Prevents browser caching issues when CSS or JS files are updated.
 *
 * @param string $path Clean relative asset path (e.g. '/css/style.css').
 * @return string Cache-busted asset URL string.
 */
function assetUrl(string $path): string {
    // Resolve absolute path to check file on disk
    $absPath = __DIR__ . '/..' . $path;
    // Fallback to static timestamp
    $version = '1.0.0';
    if (file_exists($absPath)) {
        // Retrieve file modification time to automatically increment versioning
        $version = (string)filemtime($absPath);
    }
    // Append query parameter with auto-increment version number
    return $path . '?v=' . $version;
}

/**
 * Checks, defaults, and transitions the subscription and trial status of a tenant user securely.
 * Always resolves correct trial status boundaries and updates the system database if a transition is detected.
 *
 * @param array $user Passed by reference to allow modifying current user array variables directly.
 * @param Database $dbConnection The active system database connection instance.
 * @return string Calculated current status ('trial', 'active', 'expired', 'suspended').
 */
function checkAndUpdateSubscription(array &$user, Database $dbConnection): string {
    // Admin role accounts are fully exempt from subscription constraints and are always active
    $uRole = strtolower((string)($user['role'] ?? ''));
    if ($uRole === 'admin' || $uRole === 'super admin' || $uRole === 'manager' || $uRole === 'support' || $uRole === 'moderator') {
        // Return active state for administrators
        return 'active';
    }

    // Default migration handler: if an existing user lacks trial/subscription properties, set safe starting defaults
    if (!isset($user['trial_start'])) {
        // Set start of free trial to account creation time or the current timestamp
        $createdAt = $user['created_at'] ?? date('Y-m-d H:i:s');
        // Ensure precise date string representation
        $user['trial_start'] = date('Y-m-d H:i:s', strtotime($createdAt));
        // Free trial runs for exactly one month after initial start date
        $user['trial_end'] = date('Y-m-d H:i:s', strtotime($createdAt . ' +1 month'));
        // Default starting status is 'trial'
        $user['subscription_status'] = 'trial';
        // Assign default subscription starter plan
        $user['subscription_plan'] = 'Starter Space';
        // Set starting paid dates to null as none exists yet
        $user['subscription_start'] = null;
        // Set ending paid dates to null
        $user['subscription_end'] = null;

        // Persist default trial/subscription metadata back to the users JSON database
        $dbConnection->update('users', [
            'trial_start' => $user['trial_start'],
            'trial_end' => $user['trial_end'],
            'subscription_status' => $user['subscription_status'],
            'subscription_plan' => $user['subscription_plan'],
            'subscription_start' => null,
            'subscription_end' => null,
        ], ['id' => $user['id']]);
    }

    // Enforcement guard: check if the account status is suspended globally
    if (strtolower((string)($user['status'] ?? '')) === 'suspended') {
        // Ensure user's internal subscription_status is updated if not already matched
        if (($user['subscription_status'] ?? '') !== 'suspended') {
            // Assign suspended state
            $user['subscription_status'] = 'suspended';
            // Update users table records to state suspended
            $dbConnection->update('users', ['subscription_status' => 'suspended'], ['id' => $user['id']]);
        }
        // Return suspended status
        return 'suspended';
    }

    // Capture current server-side timestamp
    $now = time();
    // Resolve trial boundaries as Unix timestamps
    $trialEnd = strtotime($user['trial_end'] ?: date('Y-m-d H:i:s'));
    // The 7-day free grace period ends exactly 7 days after trial ends
    $graceEnd = strtotime(date('Y-m-d H:i:s', $trialEnd) . ' +7 days');
    // Resolve paid subscription end boundary as timestamp if specified
    $subEnd = !empty($user['subscription_end']) ? strtotime($user['subscription_end']) : 0;

    // Grab current status from the user object
    $currentStatus = $user['subscription_status'] ?? 'trial';
    // Initialize temporary comparison variable
    $newStatus = $currentStatus;

    // Check if the current registered state is active
    if ($currentStatus === 'active') {
        // If a paid subscription end date exists and has elapsed, transition to expired
        if ($subEnd > 0 && $now > $subEnd) {
            // Mark as expired
            $newStatus = 'expired';
        }
    } else {
        // Evaluate trial duration with the 7-day free grace period
        if ($now < $trialEnd) {
            // Under normal active trial
            $newStatus = 'trial';
        } elseif ($now < $graceEnd) {
            // Inside the 7-day free grace period post-trial expiration
            $newStatus = 'grace';
        } else {
            // Completely expired past both the trial and the 7-day grace periods
            $newStatus = 'expired';
        }
    }

    // If a transition change has occurred, update the database record and local reference
    if ($newStatus !== $currentStatus) {
        // Update user array reference
        $user['subscription_status'] = $newStatus;
        // Persist status change into the custom users database table
        $dbConnection->update('users', ['subscription_status' => $newStatus], ['id' => $user['id']]);
    }

    // Return the final resolved subscription status
    return $newStatus;
}

/**
 * Enforces active free trial or active subscription checks on pages and API endpoints.
 * Redirects unauthorized standard users or serves JSON error blocks according to environment requests.
 *
 * @param bool $isApi Set true to render a direct JSON error response block instead of a redirect.
 */
function enforceSubscription(bool $isApi = false): void {
    // Grab central global database connector instance
    global $conn;

    // Ensure session is started and active
    secureSession();

    // Administrative accounts hold ultimate bypass privileges on all workspace restrictions
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        // Immediately return without restrictions
        return;
    }

    // Enforce active session requirements
    if (!isset($_SESSION['email'])) {
        // Check if current action is an API query
        if ($isApi) {
            // Output unauthenticated JSON error
            jsonResponse(['success' => false, 'message' => 'Unauthorized access. Please log in.'], 401);
        } else {
            // Redirect standard page loader to the Login Hub page
            header('Location: /login');
            // Halt script execution
            exit;
        }
    }

    // Query active user's credentials from the main users database
    $user = $conn->selectOne('users', ['email' => $_SESSION['email']]);
    // Check user record presence
    if (!$user) {
        // Check if query was made via API
        if ($isApi) {
            // Respond with user not found JSON error
            jsonResponse(['success' => false, 'message' => 'User account not found.'], 404);
        } else {
            // Redirect standard page loaders to login
            header('Location: /login');
            // Halt execution
            exit;
        }
    }

    // Invoke trial/subscription check helper to resolve real-time account status
    $status = checkAndUpdateSubscription($user, $conn);

    // Block workspace access if subscription or free trial is expired or suspended
    if ($status === 'expired' || $status === 'suspended') {
        // Check if query is an API call
        if ($isApi) {
            // Return forbidden HTTP status and detail payload block
            jsonResponse([
                'success' => false,
                'subscription_expired' => true,
                'message' => 'Your subscription or free trial has expired. Please subscribe to continue.'
            ], 403);
        } else {
            // Capture the current target URI path
            $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
            // Allow dashboard.php itself to load so it can render the restricted workspace block
            if (str_contains($requestUri, 'user/dashboard')) {
                // Return gracefully without redirecting to avoid infinite loop
                return;
            } else {
                // Redirect standard workspace loaders to user dashboard page to see subscription options
                header('Location: /user/dashboard');
                // Halt execution
                exit;
            }
        }
    }
}

/**
 * Checks if the currently authenticated user has a specific granular permission.
 * Supports absolute bypass for super administrators.
 *
 * @param string $permission Unique permission string (e.g. 'users.suspend')
 * @return bool True if authorized, false otherwise
 */
function checkAdminPermission(string $permission): bool {
    // Access central database connector
    global $conn;
    // Instantiate secure sessions
    secureSession();

    // Block non-logged in or non-admin roles
    if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {
        return false;
    }

    $role = strtolower((string)$_SESSION['role']);

    // Super Admin role possesses absolute override capabilities across all permission elements
    if ($role === 'super admin') {
        return true;
    }

    // Standard Admin is also granted super privilege bypass in our simple model
    if ($role === 'admin') {
        return true;
    }

    // Query exact granular capability in local permissions registry
    $record = $conn->selectOne('permissions', ['role' => $role, 'permission' => $permission]);
    if ($record && (int)($record['is_allowed'] ?? 0) === 1) {
        return true;
    }

    return false;
}

/**
 * Reusable permission checker that works platform-wide for all authenticated users/roles.
 * Resolves permissions recursively using "role -> permissions -> resources/actions" database mapping.
 * Supports absolute bypass for administrators and super administrators.
 */
function hasPermission(string $permission): bool {
    global $conn;
    if (session_status() === PHP_SESSION_NONE) {
        secureSession();
    }
    if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {
        return false;
    }
    $role = strtolower((string)$_SESSION['role']);
    // Administrators possess absolute platform capabilities
    if ($role === 'super admin' || $role === 'admin') {
        return true;
    }
    // Query local permissions mapping dynamically
    $conn->createTable('permissions');
    $record = $conn->selectOne('permissions', ['role' => $role, 'permission' => $permission]);
    return $record && (int)($record['is_allowed'] ?? 0) === 1;
}

/**
 * Restricts access to API or page requests strictly based on permissions and role boundaries.
 */
function enforcePermission(string $permission, bool $isApi = false): void {
    if (!hasPermission($permission)) {
        if ($isApi) {
            jsonResponse(['success' => false, 'message' => 'Forbidden. Missing permission: ' . $permission], 403);
        } else {
            header('Location: /login');
            exit;
        }
    }
}

/**
 * Restricts access to authenticated users only.
 */
function enforceAuth(bool $isApi = false): void {
    if (session_status() === PHP_SESSION_NONE) {
        secureSession();
    }
    if (!isset($_SESSION['email'])) {
        if ($isApi) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized. Please authenticate first.'], 401);
        } else {
            header('Location: /login');
            exit;
        }
    }
}

/**
 * Validates if the authenticated role is classified as internal staff.
 */
function isStaff(): bool {
    if (session_status() === PHP_SESSION_NONE) {
        secureSession();
    }
    $role = strtolower(trim((string)($_SESSION['role'] ?? 'tenant')));
    $roleNormalized = str_replace(' ', '', str_replace('_', '', $role));
    $staffRoles = ['admin', 'superadmin', 'manager', 'moderator', 'support', 'financial', 'marketinghead'];
    return in_array($roleNormalized, $staffRoles, true);
}

/**
 * Centralized Role-Based Page Permissions mapping for all admin/staff roles.
 * Controls sidebar visibility, direct page loads, backend validation and action gates.
 */
function getRolePagePermissions(): array {
    return [
        'superadmin' => ['*'],
        'super admin' => ['*'],
        'admin' => [
            '/admin/dashboard',
            '/admin/users',
            '/admin/websites',
            '/admin/pages',
            '/admin/templates',
            '/admin/categories',
            '/admin/payments',
            '/admin/revenue',
            '/admin/analytics',
            '/admin/marketing',
            '/admin/support',
            '/admin/moderation',
            '/admin/activity-logs',
            '/admin/profile'
        ],
        'manager' => [
            '/admin/dashboard',
            '/admin/users',
            '/admin/websites',
            '/admin/pages',
            '/admin/templates',
            '/admin/categories',
            '/admin/analytics',
            '/admin/support',
            '/admin/moderation',
            '/admin/activity-logs',
            '/admin/profile'
        ],
        'support' => [
            '/admin/dashboard',
            '/admin/users',
            '/admin/support',
            '/admin/activity-logs',
            '/admin/profile'
        ],
        'moderator' => [
            '/admin/dashboard',
            '/admin/users',
            '/admin/moderation',
            '/admin/activity-logs',
            '/admin/profile'
        ],
        'financial' => [
            '/admin/dashboard',
            '/admin/payments',
            '/admin/revenue',
            '/admin/financial-reports',
            '/admin/analytics',
            '/admin/activity-logs',
            '/admin/profile'
        ],
        'marketing_head' => [
            '/admin/dashboard',
            '/admin/marketing',
            '/admin/analytics',
            '/admin/revenue',
            '/admin/activity-logs',
            '/admin/profile'
        ]
    ];
}

/**
 * Checks if a specific role is authorized to access a given requested admin URI/endpoint.
 */
function hasAdminPagePermission(string $role, string $requestUri): bool {
    // Clean and normalize input role
    $roleClean = strtolower(trim($role));
    $roleClean = str_replace(' ', '', $roleClean); // e.g. super admin -> superadmin, marketing head -> marketinghead
    $roleClean = str_replace('_', '', $roleClean); // e.g. marketing_head -> marketinghead

    $mapping = getRolePagePermissions();
    // Normalize mapping keys
    $normalizedMapping = [];
    foreach ($mapping as $r => $perms) {
        $key = str_replace('_', '', str_replace(' ', '', strtolower($r)));
        $normalizedMapping[$key] = $perms;
    }

    if (!isset($normalizedMapping[$roleClean])) {
        return false;
    }

    $allowed = $normalizedMapping[$roleClean];
    if (in_array('*', $allowed, true)) {
        return true;
    }

    // Isolate path part of requestUri (remove query and strip .php or trailing slashes)
    $path = parse_url($requestUri, PHP_URL_PATH) ?? $requestUri;
    $path = rtrim($path, '/');
    if (str_ends_with($path, '.php')) {
        $path = substr($path, 0, -4);
    }

    foreach ($allowed as $p) {
        $pClean = rtrim($p, '/');
        if (str_ends_with($pClean, '.php')) {
            $pClean = substr($pClean, 0, -4);
        }
        if (strcasecmp($path, $pClean) === 0) {
            return true;
        }
    }

    return false;
}

/**
 * Dynamic Template Discovery & System Table Synchronizer Helper
 * Scans /templates/{category}/{template}/ on disk, syncs with JSON tables,
 * and returns all active and available templates with metadata.
 */
function getDiscoveredTemplates(): array {
    global $conn;
    $templatesDir = realpath(__DIR__ . '/../templates');
    if ($templatesDir === false || !is_dir($templatesDir)) {
        return [];
    }

    $conn->createTable('templates');
    $conn->createTable('categories');

    $categoriesOnDisk = array_filter(scandir($templatesDir), function($item) use ($templatesDir) {
        return $item !== '.' && $item !== '..' && is_dir($templatesDir . '/' . $item);
    });

    foreach ($categoriesOnDisk as $catSlug) {
        $existingCat = $conn->selectOne('categories', ['slug' => $catSlug]);
        if (!$existingCat) {
            $catName = ucwords(str_replace(['-', '_'], ' ', $catSlug));
            $conn->insert('categories', [
                'name' => $catName,
                'slug' => $catSlug,
                'count' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
    }

    $discovered = [];
    foreach ($categoriesOnDisk as $catSlug) {
        $catPath = $templatesDir . '/' . $catSlug;
        $tplFolders = array_filter(scandir($catPath), function($item) use ($catPath) {
            return $item !== '.' && $item !== '..' && is_dir($catPath . '/' . $item);
        });

        foreach ($tplFolders as $tplFolder) {
            $tplPath = $catPath . '/' . $tplFolder;
            $metaFile = $tplPath . '/template.json';
            $meta = [];
            if (file_exists($metaFile)) {
                $meta = json_decode((string)file_get_contents($metaFile), true) ?? [];
            }

            $tplName = $meta['name'] ?? ucwords(str_replace(['-', '_'], ' ', $tplFolder));
            $previewImg = $meta['preview_img'] ?? ('/templates/' . $catSlug . '/' . $tplFolder . '/preview.jpg');
            $description = $meta['description'] ?? 'Custom template layout.';
            $author = $meta['author'] ?? 'NodeX Platform';
            $version = $meta['version'] ?? '1.0.0';
            $status = $meta['status'] ?? 'active';

            // Sync with system DB
            $dbRecord = $conn->selectOne('templates', [
                'category' => $catSlug,
                'folder' => $tplFolder
            ]);

            if (!$dbRecord) {
                $dbRecord = $conn->selectOne('templates', ['name' => $tplName]);
            }

            if (!$dbRecord) {
                $inserted = $conn->insert('templates', [
                    'name' => $tplName,
                    'category' => $catSlug,
                    'folder' => $tplFolder,
                    'description' => $description,
                    'preview_img' => $previewImg,
                    'author' => $author,
                    'version' => $version,
                    'downloads' => 0,
                    'status' => $status,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                $tplId = $inserted['id'] ?? 0;
            } else {
                $tplId = $dbRecord['id'];
                $status = $dbRecord['status'] ?? $status;
                $conn->update('templates', [
                    'folder' => $tplFolder,
                    'category' => $catSlug,
                    'description' => $description,
                    'preview_img' => $previewImg
                ], ['id' => $tplId]);
            }

            $discovered[] = [
                'id' => $tplId,
                'name' => $tplName,
                'category' => $catSlug,
                'folder' => $tplFolder,
                'description' => $description,
                'preview_img' => $previewImg,
                'author' => $author,
                'version' => $version,
                'status' => $status,
                'path' => '/templates/' . $catSlug . '/' . $tplFolder
            ];
        }
    }

    return $discovered;
}

/**
 * Dynamic Whitelist Scanner for Platform Public Pages in main/public/
 * Returns array of approved public pages that administrators are authorized to edit.
 */
function getApprovedPublicPages(): array {
    $publicDir = realpath(__DIR__ . '/../main/public');
    if ($publicDir === false || !is_dir($publicDir)) {
        return [];
    }

    $excludedFiles = ['login.php', 'register.php', 'profile.php'];
    $approved = [];

    $files = scandir($publicDir);
    foreach ($files as $f) {
        if ($f === '.' || $f === '..' || in_array(strtolower($f), $excludedFiles, true)) {
            continue;
        }
        $fullPath = $publicDir . '/' . $f;
        if (is_file($fullPath)) {
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if ($ext === 'php' || $ext === 'html' || $ext === 'htm') {
                $approved[] = [
                    'filename' => $f,
                    'path' => '/main/public/' . $f,
                    'full_path' => $fullPath,
                    'last_modified' => date('Y-m-d H:i:s', filemtime($fullPath)),
                    'size' => filesize($fullPath)
                ];
            }
        }
    }

    return $approved;
}
?>
