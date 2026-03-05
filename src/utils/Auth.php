<?php
/**
 * Authentication Class
 * Handles user login, register, logout, and session management
 */

class Auth {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Register a new user
     * @param string $email
     * @param string $password
     * @param string $name
     * @param string $phone
     * @param string $role - 'customer' or 'seller'
     * @return array - ['success' => bool, 'message' => string, 'user_id' => int|null]
     */
    public function register($email, $password, $name, $phone, $role = 'customer') {
        // Validate input
        if (!$this->validateEmail($email)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        
        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters'];
        }
        
        // Check if email already exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? AND deleted_at IS NULL");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return ['success' => false, 'message' => 'Email already registered'];
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        // Insert new user
        $stmt = $this->db->prepare("INSERT INTO users (email, password, name, phone, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $email, $hashedPassword, $name, $phone, $role);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Registration successful',
                'user_id' => $this->db->insert_id
            ];
        } else {
            return ['success' => false, 'message' => 'Registration failed'];
        }
    }
    
    /**
     * Login user
     * @param string $email
     * @param string $password
     * @return array - ['success' => bool, 'message' => string, 'user' => array|null]
     */
    public function login($email, $password) {
        // Check if user exists
        $stmt = $this->db->prepare("SELECT id, email, name, password, role, status FROM users WHERE email = ? AND deleted_at IS NULL");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Email not found'];
        }
        
        $user = $result->fetch_assoc();
        
        // Check if account is active
        if ($user['status'] !== 'active') {
            return ['success' => false, 'message' => 'Account is ' . $user['status']];
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid password'];
        }
        
        // Start session and store user info
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        
        // Get IP and Location for tracking
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $location = 'Unknown';
        
        // Determine location based on IP
        if ($ip == '127.0.0.1' || $ip == '::1') {
            $location = 'Localhost';
        } else {
            $api_url = "http://ip-api.com/json/{$ip}";
            $api_response = @file_get_contents($api_url);
            if ($api_response) {
                $geo_data = json_decode($api_response, true);
                if (isset($geo_data['status']) && $geo_data['status'] === 'success') {
                    $location = $geo_data['city'] . ', ' . $geo_data['country'];
                }
            }
        }
        
        // Log login history to database, but keep only the latest record per user
        // delete any existing entry first so that the table never accumulates multiple rows
        $this->db->query("DELETE FROM login_history WHERE user_id = " . intval($user['id']));
        $stmtLog = $this->db->prepare("INSERT INTO login_history (user_id, ip_address, location) VALUES (?, ?, ?)");
        if ($stmtLog) {
            $stmtLog->bind_param("iss", $user['id'], $ip, $location);
            $stmtLog->execute();
        }
        
        // Set session variable for UI alert
        $_SESSION['login_location_alert'] = $location;
        
        return [
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['name'],
                'role' => $user['role']
            ]
        ];
    }
    
    /**
     * Logout user
     */
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Get current user info
     */
    public function getCurrentUser() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'email' => $_SESSION['email'],
            'name' => $_SESSION['name'],
            'role' => $_SESSION['role']
        ];
    }
    
    /**
     * Check if user has a specific role
     */
    public function hasRole($role) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $this->isLoggedIn() && $_SESSION['role'] === $role;
    }
    
    /**
     * Validate email format
     */
    private function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
