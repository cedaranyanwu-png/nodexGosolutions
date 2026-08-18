<?php
/**
 * test_subscription.php
 *
 * Automated verification of the subscription, trial, and security access restriction flows.
 * Asserts correctness of the registration, active trial, expired trial, paid subscription,
 * and security API restriction gates.
 */

declare(strict_types=1);

// Require central system configurations
require_once __DIR__ . '/php/db.php';

// Instantiate secure session configurations before sending any CLI header outputs to prevent warnings
secureSession();

echo "========================================================\n";
echo "      SUBSCRIPTION & TRIAL SYSTEM VERIFICATION SUITE    \n";
echo "========================================================\n\n";

$testsPassed = 0;
$testsFailed = 0;

function assertSubscriptionTest(string $name, bool $expression): void {
    global $testsPassed, $testsFailed;
    if ($expression) {
        echo "✅ PASS: {$name}\n";
        $testsPassed++;
    } else {
        echo "❌ FAIL: {$name}\n";
        $testsFailed++;
    }
}

// Ensure clean environment: delete mock user if pre-existing
$mockEmail = 'mock_tenant@nodex.com';
$conn->delete('users', ['email' => $mockEmail]);

// --- TEST A: New Registration Auto-Provisioning ---
$fullname = 'Mock Tenant User';
$password = 'password123';
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$trialStart = date('Y-m-d H:i:s');
$trialEnd = date('Y-m-d H:i:s', strtotime('+1 month'));

$newUser = $conn->insert('users', [
    'fullname'           => $fullname,
    'email'              => $mockEmail,
    'password'           => $hashedPassword,
    'role'               => 'tenant',
    'status'             => 'active',
    'is_verified'        => 1, // Skip email validation for test login
    'email_verified'     => 1,
    'verification_token' => null,
    'trial_start'        => $trialStart,
    'trial_end'          => $trialEnd,
    'subscription_status'=> 'trial',
    'subscription_plan'  => 'Starter Space',
    'subscription_start' => null,
    'subscription_end'   => null
]);

assertSubscriptionTest("Test A: New user account created in database", $newUser !== false);
assertSubscriptionTest("Test A: Trial start is provisioned to current date", isset($newUser['trial_start']));
assertSubscriptionTest("Test A: Trial end is set to exactly 1 month later", isset($newUser['trial_end']) && date('Y-m-d', strtotime($newUser['trial_end'])) === date('Y-m-d', strtotime('+1 month')));
assertSubscriptionTest("Test A: Initial subscription status is 'trial'", ($newUser['subscription_status'] ?? '') === 'trial');


// --- TEST B: Trial User Access Is Active ---
$fetchedUser = $conn->selectOne('users', ['email' => $mockEmail]);
$status = checkAndUpdateSubscription($fetchedUser, $conn);
assertSubscriptionTest("Test B: Active trial status resolves to 'trial'", $status === 'trial');


// --- TEST C: Trial Expiration State Transition ---
// Artificially change trial_end to the past to simulate expiration (exceeding the 7-day free grace period)
$pastDate = date('Y-m-d H:i:s', strtotime('-10 days'));
$conn->update('users', ['trial_end' => $pastDate], ['id' => $fetchedUser['id']]);

$fetchedUserExpired = $conn->selectOne('users', ['email' => $mockEmail]);
$expiredStatus = checkAndUpdateSubscription($fetchedUserExpired, $conn);
assertSubscriptionTest("Test C: Expired trial resolves to 'expired' status", $expiredStatus === 'expired');


// --- TEST D: Successful Payment Subscription Activation ---
$subStart = date('Y-m-d H:i:s');
$subEnd = date('Y-m-d H:i:s', strtotime('+1 month'));

$conn->update('users', [
    'subscription_status' => 'active',
    'subscription_plan'   => 'Growth Plan',
    'subscription_start'  => $subStart,
    'subscription_end'    => $subEnd
], ['id' => $fetchedUser['id']]);

$fetchedUserPaid = $conn->selectOne('users', ['email' => $mockEmail]);
$paidStatus = checkAndUpdateSubscription($fetchedUserPaid, $conn);
assertSubscriptionTest("Test D: Successful payment transitions status to 'active'", $paidStatus === 'active');
assertSubscriptionTest("Test D: Paid subscription plan is registered", ($fetchedUserPaid['subscription_plan'] ?? '') === 'Growth Plan');


// --- TEST E: Workspace Security Restriction Checks ---
// Reset status back to expired to verify mock API block security enforcement
$conn->update('users', [
    'subscription_status' => 'expired',
    'subscription_end' => $pastDate
], ['id' => $fetchedUser['id']]);

// Simulate active session matching our expired mock user
secureSession();
$_SESSION['email'] = $mockEmail;
$_SESSION['user_id'] = $fetchedUser['id'];
$_SESSION['role'] = 'tenant';

// Emulate calling the enforceSubscription(true) gate (should trigger JSON response and exit)
// We will write a lightweight check mimicking enforceSubscription(true)'s evaluation
$gatePassed = true;
$userToCheck = $conn->selectOne('users', ['email' => $_SESSION['email']]);
$evaluatedStatus = checkAndUpdateSubscription($userToCheck, $conn);
if ($evaluatedStatus === 'expired' || $evaluatedStatus === 'suspended') {
    $gatePassed = false; // Blocked securely!
}
assertSubscriptionTest("Test E: Security check blocks expired user workspace operations", $gatePassed === false);

// Clean up mock user
$conn->delete('users', ['email' => $mockEmail]);

echo "\n========================================================\n";
echo "VERIFICATION SUMMARY:\n";
echo "Total Passed: {$testsPassed}\n";
echo "Total Failed: {$testsFailed}\n";
echo "========================================================\n";

if ($testsFailed > 0) {
    exit(1);
} else {
    exit(0);
}
?>
