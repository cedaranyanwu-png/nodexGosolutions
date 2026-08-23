<?php
/**
 * backend/auth/AuthService.php
 * Authentication and session service.
 */
declare(strict_types=1);

require_once __DIR__ . '/../database/db.php';

class AuthService {
    private Database $db;

    public function __construct(?Database $db = null) {
        global $conn;
        $this->db = $db ?? $conn;
    }

    public function login(string $email, string $password): array {
        $email = strtolower(trim($email));
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Email and password are required.'];
        }

        $user = $this->db->selectOne('users', ['email' => $email]);
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }

        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }

        if (strtolower((string)($user['status'] ?? '')) === 'suspended') {
            return ['success' => false, 'message' => 'Account is suspended. Please contact support.'];
        }

        secureSession();
        $_SESSION['email'] = $user['email'];
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['role'] = $user['role'];

        return [
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'fullname' => $user['fullname'],
                'role' => $user['role']
            ]
        ];
    }

    public function register(string $fullname, string $email, string $password): array {
        $fullname = cleanInput($fullname);
        $email = strtolower(trim($email));

        if (empty($fullname) || empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'All fields are required.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email address.'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters.'];
        }

        $existing = $this->db->selectOne('users', ['email' => $email]);
        if ($existing) {
            return ['success' => false, 'message' => 'Email address is already registered.'];
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $createdAt = date('Y-m-d H:i:s');
        $trialStart = $createdAt;
        $trialEnd = date('Y-m-d H:i:s', strtotime($createdAt . ' +1 month'));

        $user = $this->db->insert('users', [
            'fullname' => $fullname,
            'email' => $email,
            'password' => $hashedPassword,
            'role' => 'tenant',
            'status' => 'active',
            'is_verified' => 1,
            'email_verified' => 1,
            'trial_start' => $trialStart,
            'trial_end' => $trialEnd,
            'subscription_status' => 'trial',
            'subscription_plan' => 'Starter Space',
            'created_at' => $createdAt,
            'updated_at' => $createdAt
        ]);

        if (!$user) {
            return ['success' => false, 'message' => 'Failed to register account.'];
        }

        secureSession();
        $_SESSION['email'] = $email;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['fullname'] = $fullname;
        $_SESSION['role'] = 'tenant';

        return [
            'success' => true,
            'message' => 'Account registered successfully',
            'user' => [
                'id' => $user['id'],
                'email' => $email,
                'fullname' => $fullname,
                'role' => 'tenant'
            ]
        ];
    }

    public function getCurrentUser(): ?array {
        secureSession();
        if (empty($_SESSION['email'])) {
            return null;
        }
        return $this->db->selectOne('users', ['email' => $_SESSION['email']]);
    }

    public function logout(): void {
        secureSession();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        @session_destroy();
    }
}
