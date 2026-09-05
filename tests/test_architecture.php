<?php

declare(strict_types=1);

require_once __DIR__ . '/../main/config/DomainConfig.php';
require_once __DIR__ . '/../main/database/JsonDatabase.php';
require_once __DIR__ . '/../main/discovery/MainDiscovery.php';
require_once __DIR__ . '/../main/services/DomainResolver.php';
require_once __DIR__ . '/../main/services/DomainScanner.php';
require_once __DIR__ . '/../main/services/ProjectResolver.php';
require_once __DIR__ . '/../main/services/TenantManager.php';

use Main\Config\DomainConfig;
use Main\Database\JsonDatabase;
use Main\Discovery\MainDiscovery;
use Main\Services\DomainResolver;
use Main\Services\DomainScanner;
use Main\Services\ProjectResolver;
use Main\Services\TenantManager;

$testsPassed = 0;
$testsFailed = 0;

function assertTest(bool $condition, string $testName): void
{
    global $testsPassed, $testsFailed;
    if ($condition) {
        echo " [PASS] {$testName}\n";
        $testsPassed++;
    } else {
        echo " [FAIL] {$testName}\n";
        $testsFailed++;
    }
}

echo "==================================================\n";
echo " Running NodeX Architecture Test Suite\n";
echo "==================================================\n\n";

$db = new JsonDatabase(__DIR__ . '/../main/storage/db');

// Test 1: Main Domain Resolution
$resolver = new DomainResolver($db);
$res1 = $resolver->resolve('nodexplatform.com.ng');
assertTest($res1['type'] === 'MAIN' && $res1['target'] === 'main_domain', '1. Main domain resolves to MAIN');

// Test 2: Main Subdomain Resolution
$res2 = $resolver->resolve('admin.nodexplatform.com.ng');
assertTest($res2['type'] === 'MAIN' && $res2['subdomain_key'] === 'admin', '2. Admin subdomain resolves to MAIN admin module');

// Test 3: Registered Tenant Subdomain Resolution
$res3 = $resolver->resolve('customer1.nodexplatform.com.ng');
assertTest($res3['type'] === 'TENANT' && $res3['project_id'] === 'customer1', '3. Registered tenant subdomain resolves to TENANT project');

// Test 4: Registered Tenant Custom Domain Resolution
$res4 = $resolver->resolve('cedar.com');
assertTest($res4['type'] === 'TENANT' && $res4['project_id'] === 'cedar', '4. Registered tenant custom domain resolves to TENANT project');

// Test 5: Unknown Domain Resolution
$res5 = $resolver->resolve('unknown-random-domain.com');
assertTest($res5['type'] === 'UNKNOWN_DOMAIN', '5. Unknown domain is rejected (UNKNOWN_DOMAIN)');

// Test 6: Unregistered Public Folder Protection
$projectResolver = new ProjectResolver($db, __DIR__ . '/../public');
$isRegistered = $projectResolver->isFolderRegistered('random');
assertTest($isRegistered === false, '6. Unregistered public folder (/public/random/) is NOT registered');

// Test 7: Suspended Tenant Project Block
$tenantManager = new TenantManager($db, __DIR__ . '/../public');
$tenantManager->setProjectStatus('cedar', 'suspended');
$resSuspended = $projectResolver->resolveProject('cedar');
assertTest($resSuspended['success'] === false && $resSuspended['reason'] === 'PROJECT_SUSPENDED', '7. Suspended tenant project is blocked');
// Restore status
$tenantManager->setProjectStatus('cedar', 'active');

// Test 8: MAIN Module Self-Discovery
$discovery = new MainDiscovery(__DIR__ . '/../main');
$modules = $discovery->discoverModules();
assertTest(isset($modules['admin']) && isset($modules['analytics']), '8. MAIN self-discovery discovers admin and analytics modules');

// Test 9: Public Folder Non-Discovery Rule
$isDiscoverable = MainDiscovery::isPublicTenantFolderDiscoverable('cedar');
assertTest($isDiscoverable === false, '9. Public tenant folders are NEVER self-discovered');

// Test 10: JSON Database Concurrent File Locking & CRUD
$testRecord = $db->table('settings')->insert(['key' => 'unit_test', 'value' => 'passed']);
$found = $db->table('settings')->where('key', 'unit_test')->first();
assertTest($found !== null && $found['value'] === 'passed', '10. JSON Database insert & query with file locks works');
$db->table('settings')->delete($testRecord['id']);

// Test 11: Domain Scanner Diagnostic
$scanner = new DomainScanner(__DIR__ . '/..');
$findings = $scanner->scanForHardcodedMainDomain();
assertTest(count($findings) === 0, '11. Domain Scanner confirms 0 hardcoded main domain occurrences');

// Test 12: Domain Configuration Reload Test
$originalDomain = DomainConfig::mainDomain();
DomainConfig::setConfigPath(__DIR__ . '/../main/config/domain_config.json');
DomainConfig::reload();
assertTest(DomainConfig::mainDomain() === $originalDomain, '12. Domain configuration reloads cleanly without errors');

echo "\n==================================================\n";
echo " Test Suite Summary: {$testsPassed} Passed, {$testsFailed} Failed\n";
echo "==================================================\n";

if ($testsFailed > 0) {
    exit(1);
}
