<?php
/**
 * backend/services/SubscriptionService.php
 *
 * Central Service for Subscription & Trial Management
 * Manages subscription states, plan limits, custom domain eligibility, and feature gates.
 */

declare(strict_types=1);

require_once __DIR__ . '/../database/db.php';

class SubscriptionService {
    private Database $db;

    public function __construct(?Database $db = null) {
        global $conn;
        $this->db = $db ?? $conn;
    }

    public function getActivePlans(): array {
        $this->db->createTable('plans');
        return $this->db->select('plans', ['is_active' => 1]) ?: [];
    }

    public function getPlanById(string $planId): ?array {
        $this->db->createTable('plans');
        $plan = $this->db->selectOne('plans', ['id' => $planId]);
        if (!$plan && ctype_digit($planId)) {
            $plan = $this->db->selectOne('plans', ['id' => (int)$planId]);
        }

        if (!$plan) {
            $allPlans = $this->db->select('plans') ?: [];
            foreach ($allPlans as $candidate) {
                if (strtolower((string)($candidate['id'] ?? '')) === strtolower($planId) ||
                    strtolower((string)($candidate['name'] ?? '')) === strtolower($planId)) {
                    $plan = $candidate;
                    break;
                }
            }
        }
        return $plan;
    }

    public function getUserSubscription(array $user): array {
        $status = checkAndUpdateSubscription($user, $this->db);

        $trialStart = $user['trial_start'] ?? null;
        $trialEnd = $user['trial_end'] ?? null;
        $daysRemaining = 0;

        if ($status === 'trial' && $trialEnd) {
            $secondsLeft = strtotime($trialEnd) - time();
            $daysRemaining = max(0, (int)ceil($secondsLeft / 86400));
        }

        $planName = $user['subscription_plan'] ?? ($status === 'trial' ? 'Starter Space (Free Trial)' : 'Micro Plan');
        $planRecord = $this->getPlanById((string)($user['subscription_plan_id'] ?? 'micro')) ?? [
            'name' => $planName,
            'price' => $status === 'trial' ? 0 : 3000,
            'currency' => 'NGN'
        ];

        return [
            'status' => $status,
            'plan_name' => $planName,
            'plan_details' => $planRecord,
            'trial_start' => $trialStart,
            'trial_end' => $trialEnd,
            'days_remaining' => $daysRemaining,
            'subscription_start' => $user['subscription_start'] ?? null,
            'subscription_end' => $user['subscription_end'] ?? null,
            'allows_custom_domain' => $this->canConnectCustomDomain($user)
        ];
    }

    public function canConnectCustomDomain(array $user): bool {
        $status = checkAndUpdateSubscription($user, $this->db);
        if ($status !== 'active') {
            return false;
        }

        $planName = strtolower((string)($user['subscription_plan'] ?? ''));
        // Free trial and Micro (₦3,000) plans are strictly restricted to subdomains only
        if (str_contains($planName, 'starter') || str_contains($planName, 'micro') || str_contains($planName, 'trial')) {
            return false;
        }

        // Growth and Business Pro plans allow custom domain integration
        return true;
    }

    public function canCreateWebsite(array $user): bool {
        $status = checkAndUpdateSubscription($user, $this->db);
        if ($status === 'expired' || $status === 'suspended') {
            return false;
        }

        $this->db->createTable('websites');
        $userWebsites = $this->db->select('websites', ['user_id' => $user['id']]) ?: [];

        if ($status === 'trial') {
            return count($userWebsites) < 1;
        }

        $planName = strtolower((string)($user['subscription_plan'] ?? ''));
        if (str_contains($planName, 'micro')) {
            return count($userWebsites) < 1;
        }

        return true;
    }
}
