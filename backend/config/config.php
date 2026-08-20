<?php
/**
 * backend/config/config.php
 *
 * Centralized Configuration System
 * Manages database parameters, cPanel integration variables, Flutterwave credentials,
 * and platform domain defaults securely without hardcoded secrets.
 */

declare(strict_types=1);

// Platform base domain configuration
define('APP_NAME', 'nodexGosolutions');
define('MAIN_DOMAIN', getenv('MAIN_DOMAIN') ?: 'nodexplatform.com.ng');
define('WEBSITE_SUBDOMAIN', getenv('WEBSITE_SUBDOMAIN') ?: 'nodexplatform.com.ng');
define('APP_BASE_URL', getenv('APP_BASE_URL') ?: 'https://' . MAIN_DOMAIN);

// System database base directory
define('DB_BASE_DIR', __DIR__ . '/../../databases');

// Public tenant websites base directory
define('TENANT_PUBLIC_DIR', __DIR__ . '/../../public');

// cPanel API Configuration
define('CPANEL_HOST', getenv('CPANEL_HOST') ?: 'localhost');
define('CPANEL_USERNAME', getenv('CPANEL_USERNAME') ?: '');
define('CPANEL_API_TOKEN', getenv('CPANEL_API_TOKEN') ?: '');
define('CPANEL_PORT', (int)(getenv('CPANEL_PORT') ?: 2083));

// Flutterwave Payment Credentials
define('FLW_PUBLIC_KEY', getenv('FLW_PUBLIC_KEY') ?: '');
define('FLW_SECRET_KEY', getenv('FLW_SECRET_KEY') ?: '');
define('FLW_ENCRYPTION_KEY', getenv('FLW_ENCRYPTION_KEY') ?: '');

/**
 * Returns configuration settings array.
 */
function getConfig(): array {
    return [
        'app' => [
            'name' => APP_NAME,
            'main_domain' => MAIN_DOMAIN,
            'subdomain_suffix' => WEBSITE_SUBDOMAIN,
            'base_url' => APP_BASE_URL,
        ],
        'cpanel' => [
            'host' => CPANEL_HOST,
            'username' => CPANEL_USERNAME,
            'api_token' => CPANEL_API_TOKEN,
            'port' => CPANEL_PORT,
        ],
        'flutterwave' => [
            'public_key' => FLW_PUBLIC_KEY,
            'secret_key' => FLW_SECRET_KEY,
            'encryption_key' => FLW_ENCRYPTION_KEY,
        ]
    ];
}
