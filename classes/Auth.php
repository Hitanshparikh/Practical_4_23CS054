<?php
/**
 * Authentication and Session Management Class
 * Made by Hitansh Parikh - 23CS054
 */

require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;
    private $sessionTimeout = 3600; // 1 hour
    
    public function __construct() {
        $this->db = getDB();
        $this->startSession();
    }
    
    private function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Regenerate session ID periodically for security
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity'] > $this->sessionTimeout)) {
            $this->logout();
        }
        $_SESSION['last_activity'] = time();
    }
    
    public function login($email, $password, $rememberMe = false) {
        try {
            // Input validation
            if (empty($email) || empty($password)) {
                return ['success' => false, 'message' => 'Email and password are required.'];
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid email format.'];
            }
            
            // Fetch user from database
            $user = $this->db->fetchOne(
                "SELECT * FROM users WHERE email = ? AND status = 'active'",
                [$email]
            );
            
            if (!$user) {
                // Log failed login attempt
                logActivity("Failed login attempt for email: {$email}");
                return ['success' => false, 'message' => 'Invalid email or password.'];
            }
            
            // Verify password
            if (!password_verify($password, $user['password'])) {
                logActivity("Failed login attempt for user ID: {$user['id']}");
                return ['success' => false, 'message' => 'Invalid email or password.'];
            }
            
            // Set session data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            
            // Handle remember me
            if ($rememberMe) {
                $this->setRememberMeCookie($user['id']);
            }
            
            // Update last login time
            $this->db->execute(
                "UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                [$user['id']]
            );
            
            // Log successful login
            logActivity("User logged in", 'users', $user['id']);
            
            return [
                'success' => true,
                'message' => 'Login successful.',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'full_name' => $_SESSION['full_name']
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed. Please try again.'];
        }
    }
    
    public function register($userData) {
        try {
            // Validate required fields
            $required = ['username', 'email', 'password', 'first_name', 'last_name'];
            foreach ($required as $field) {
                if (empty($userData[$field])) {
                    return ['success' => false, 'message' => ucfirst($field) . ' is required.'];
                }
            }
            
            // Validate email format
            if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid email format.'];
            }
            
            // Validate password strength
            if (strlen($userData['password']) < 8) {
                return ['success' => false, 'message' => 'Password must be at least 8 characters long.'];
            }
            
            // Check if username or email already exists
            $existing = $this->db->fetchOne(
                "SELECT id FROM users WHERE username = ? OR email = ?",
                [$userData['username'], $userData['email']]
            );
            
            if ($existing) {
                return ['success' => false, 'message' => 'Username or email already exists.'];
            }
            
            // Hash password
            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            // Insert new user
            $userId = $this->db->execute(
                "INSERT INTO users (username, email, password, first_name, last_name, phone, address, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $userData['username'],
                    $userData['email'],
                    $hashedPassword,
                    $userData['first_name'],
                    $userData['last_name'],
                    $userData['phone'] ?? null,
                    $userData['address'] ?? null,
                    $userData['role'] ?? 'user'
                ]
            );
            
            $newUserId = $this->db->lastInsertId();
            
            // Log registration
            logActivity("New user registered", 'users', $newUserId);
            
            return [
                'success' => true,
                'message' => 'Registration successful. You can now log in.',
                'user_id' => $newUserId
            ];
            
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }
    }
    
    public function logout() {
        $userId = $_SESSION['user_id'] ?? null;
        
        // Log logout
        if ($userId) {
            logActivity("User logged out", 'users', $userId);
        }
        
        // Clear remember me cookie
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        // Destroy session
        session_unset();
        session_destroy();
        
        // Start new session for any flash messages
        session_start();
        
        return ['success' => true, 'message' => 'Logged out successfully.'];
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'],
            'role' => $_SESSION['role'],
            'full_name' => $_SESSION['full_name']
        ];
    }
    
    public function hasRole($role) {
        return $this->isLoggedIn() && $_SESSION['role'] === $role;
    }
    
    public function hasPermission($permission) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $role = $_SESSION['role'];
        
        $permissions = [
            'admin' => ['*'], // All permissions
            'librarian' => [
                'manage_books', 'manage_loans', 'manage_reservations',
                'view_reports', 'manage_users_basic'
            ],
            'user' => [
                'view_books', 'borrow_books', 'reserve_books',
                'manage_profile', 'write_reviews'
            ]
        ];
        
        return in_array('*', $permissions[$role] ?? []) || 
               in_array($permission, $permissions[$role] ?? []);
    }
    
    public function requireLogin($redirectUrl = null) {
        if (!$this->isLoggedIn()) {
            $_SESSION['redirect_after_login'] = $redirectUrl ?? $_SERVER['REQUEST_URI'];
            header('Location: /wdf/practical_04_php_database/login.php');
            exit;
        }
    }
    
    public function requireRole($role) {
        $this->requireLogin();
        
        if (!$this->hasRole($role)) {
            header('HTTP/1.1 403 Forbidden');
            include __DIR__ . '/../error_pages/403.php';
            exit;
        }
    }
    
    public function requirePermission($permission) {
        $this->requireLogin();
        
        if (!$this->hasPermission($permission)) {
            header('HTTP/1.1 403 Forbidden');
            include __DIR__ . '/../error_pages/403.php';
            exit;
        }
    }
    
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Fetch current password hash
            $user = $this->db->fetchOne("SELECT password FROM users WHERE id = ?", [$userId]);
            
            if (!$user) {
                return ['success' => false, 'message' => 'User not found.'];
            }
            
            // Verify current password
            if (!password_verify($currentPassword, $user['password'])) {
                return ['success' => false, 'message' => 'Current password is incorrect.'];
            }
            
            // Validate new password
            if (strlen($newPassword) < 8) {
                return ['success' => false, 'message' => 'New password must be at least 8 characters long.'];
            }
            
            // Hash new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $this->db->execute(
                "UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                [$hashedPassword, $userId]
            );
            
            // Log password change
            logActivity("Password changed", 'users', $userId);
            
            return ['success' => true, 'message' => 'Password changed successfully.'];
            
        } catch (Exception $e) {
            error_log("Change password error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to change password.'];
        }
    }
    
    public function resetPassword($email) {
        try {
            $user = $this->db->fetchOne("SELECT id, username FROM users WHERE email = ?", [$email]);
            
            if (!$user) {
                // Don't reveal if email exists for security
                return ['success' => true, 'message' => 'If the email exists, a reset link has been sent.'];
            }
            
            // Generate reset token
            $resetToken = bin2hex(random_bytes(32));
            $resetExpiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store reset token (you might want to create a password_resets table)
            $this->db->execute(
                "UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?",
                [$resetToken, $resetExpiry, $user['id']]
            );
            
            // Here you would send an email with the reset link
            // For this demo, we'll just log it
            logActivity("Password reset requested", 'users', $user['id']);
            
            return [
                'success' => true,
                'message' => 'If the email exists, a reset link has been sent.',
                'reset_token' => $resetToken // In production, this would be sent via email
            ];
            
        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to process password reset.'];
        }
    }
    
    private function setRememberMeCookie($userId) {
        $token = bin2hex(random_bytes(32));
        $expiry = time() + (30 * 24 * 60 * 60); // 30 days
        
        // Store token in database (you might want to create a remember_tokens table)
        $this->db->execute(
            "UPDATE users SET remember_token = ?, remember_token_expires = ? WHERE id = ?",
            [$token, date('Y-m-d H:i:s', $expiry), $userId]
        );
        
        // Set cookie
        setcookie('remember_token', $token, $expiry, '/', '', true, true);
    }
    
    public function checkRememberMe() {
        if (!isset($_COOKIE['remember_token']) || $this->isLoggedIn()) {
            return false;
        }
        
        $token = $_COOKIE['remember_token'];
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE remember_token = ? AND remember_token_expires > NOW() AND status = 'active'",
            [$token]
        );
        
        if ($user) {
            // Auto login
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            
            logActivity("Auto login via remember me", 'users', $user['id']);
            
            return true;
        }
        
        // Invalid token, remove cookie
        setcookie('remember_token', '', time() - 3600, '/');
        return false;
    }
    
    public function getUserStats($userId) {
        try {
            $stats = [];
            
            // Total loans
            $stats['total_loans'] = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM loans WHERE user_id = ?",
                [$userId]
            )['count'];
            
            // Active loans
            $stats['active_loans'] = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM loans WHERE user_id = ? AND status = 'active'",
                [$userId]
            )['count'];
            
            // Total reservations
            $stats['total_reservations'] = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM reservations WHERE user_id = ?",
                [$userId]
            )['count'];
            
            // Reviews written
            $stats['reviews_written'] = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM reviews WHERE user_id = ?",
                [$userId]
            )['count'];
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Get user stats error: " . $e->getMessage());
            return [];
        }
    }
    
    public function updateProfile($userId, $data) {
        try {
            $allowedFields = ['first_name', 'last_name', 'phone', 'address'];
            $updates = [];
            $params = [];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "{$field} = ?";
                    $params[] = $data[$field];
                }
            }
            
            if (empty($updates)) {
                return ['success' => false, 'message' => 'No valid fields to update.'];
            }
            
            $params[] = $userId;
            $sql = "UPDATE users SET " . implode(', ', $updates) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            
            $this->db->execute($sql, $params);
            
            // Update session data if needed
            if (isset($data['first_name']) || isset($data['last_name'])) {
                $user = $this->db->fetchOne("SELECT first_name, last_name FROM users WHERE id = ?", [$userId]);
                $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
            }
            
            logActivity("Profile updated", 'users', $userId);
            
            return ['success' => true, 'message' => 'Profile updated successfully.'];
            
        } catch (Exception $e) {
            error_log("Update profile error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update profile.'];
        }
    }
}
?>