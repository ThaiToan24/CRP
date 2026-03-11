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
     * @param array $fingerprintData
     * @return array - ['success' => bool, 'message' => string, 'user' => array|null]
     */
    public function login($email, $password, $fingerprintData = []) {
        // Check if user exists
        $stmt = $this->db->prepare("SELECT id, email, name, password, role, status FROM users WHERE email = ? AND deleted_at IS NULL");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Get IP early for logging tracking
        $serverIp = $_SERVER['REMOTE_ADDR'] ?? '';
        $jsIp = $fingerprintData['client_ip'] ?? '';
        $ip = (!empty($jsIp) && ($serverIp == '127.0.0.1' || $serverIp == '::1')) ? $jsIp : $serverIp;
        
        if ($result->num_rows === 0) {
            // Log failed attempt since email might be guessed
            $stmtFail = $this->db->prepare("INSERT INTO failed_logins (email, ip_address) VALUES (?, ?)");
            if ($stmtFail) {
                $stmtFail->bind_param("ss", $email, $ip);
                $stmtFail->execute();
            }
            return ['success' => false, 'message' => 'Email not found'];
        }
        
        $user = $result->fetch_assoc();
        
        // Check if account is active
        if ($user['status'] !== 'active') {
            return ['success' => false, 'message' => 'Account is ' . $user['status']];
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            // Log failed attempt
            $stmtFail = $this->db->prepare("INSERT INTO failed_logins (email, ip_address) VALUES (?, ?)");
            if ($stmtFail) {
                $stmtFail->bind_param("ss", $email, $ip);
                $stmtFail->execute();
            }
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
        
        $location = 'Unknown';
        $locationFoundByGps = false;
        
        // Check for precise GPS location first
        $preciseLocation = $fingerprintData['precise_location'] ?? '';
        $gpsString = '';
        if (!empty($preciseLocation)) {
            $parts = explode(',', $preciseLocation);
            if (count($parts) == 2) {
                $lat = trim($parts[0]);
                $lng = trim($parts[1]);
                $gpsString = " (Lat: " . $lat . ", Lng: " . $lng . ")";
                
                // Attempt Reverse Geocoding for highly accurate localized city names
                $loc_api_url = "https://api.bigdatacloud.net/data/reverse-geocode-client?latitude={$lat}&longitude={$lng}&localityLanguage=vi";
                // Increase timeout slightly for the external API but don't block login too long
                $ctx = stream_context_create(['http' => ['timeout' => 2]]);
                $loc_response = @file_get_contents($loc_api_url, false, $ctx);
                if ($loc_response) {
                    $loc_data = json_decode($loc_response, true);
                    $city = $loc_data['city'] ?? $loc_data['locality'] ?? $loc_data['principalSubdivision'] ?? '';
                    $country = $loc_data['countryName'] ?? '';
                    if (!empty($city) && !empty($country)) {
                        $location = $city . ', ' . $country;
                        $locationFoundByGps = true;
                    }
                }
            }
        }
        
        // Determine location based on IP if GPS reverse geocoding failed or wasn't provided
        if (!$locationFoundByGps) {
            if ($ip == '127.0.0.1' || $ip == '::1') {
                $location = 'Localhost';
            } else {
                $api_url = "http://ip-api.com/json/{$ip}";
                $ctx = stream_context_create(['http' => ['timeout' => 2]]);
                $api_response = @file_get_contents($api_url, false, $ctx);
                if ($api_response) {
                    $geo_data = json_decode($api_response, true);
                    if (isset($geo_data['status']) && $geo_data['status'] === 'success') {
                        $location = $geo_data['city'] . ', ' . $geo_data['country'];
                    }
                }
            }
        }
        
        // Combine City Location with precise GPS coordinates
        if (!empty($gpsString)) {
            $location .= $gpsString;
        }
        
        // Gather fingerprint features
        $userAgent = $fingerprintData['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? '';
        $deviceType = $fingerprintData['device_type'] ?? 'Desktop';
        $typingSpeed = isset($fingerprintData['typing_speed_ms']) ? intval($fingerprintData['typing_speed_ms']) : 0;
        $responseTime = isset($fingerprintData['response_time_ms']) ? intval($fingerprintData['response_time_ms']) : 0;
        
        // Time features
        $hour = (int)date('H');
        $timeCategory = ($hour >= 8 && $hour <= 18) ? 'Business Hours' : 'Night';
        $dayOfWeek = date('l'); 
        $loginTime = date('Y-m-d H:i:s');
        
        // ======== RULE-BASED ANOMALY DETECTION ========
        $anomalyReasons = [];
        $rule_anomaly_score = 0;
        
        // Rule 1: Multiple failed logins (>= 3 in last 15 mins)
        $resFailed = $this->db->query("SELECT COUNT(*) FROM failed_logins WHERE email = '" . $this->db->real_escape_string($email) . "' AND attempt_time > NOW() - INTERVAL 15 MINUTE");
        $failCount = $resFailed ? (int)$resFailed->fetch_row()[0] : 0;
        if ($failCount >= 3) {
            $anomalyReasons[] = "- Multiple failed login attempts.";
            $rule_anomaly_score += 0.5;
        }

        // Rule 2: Unusual login frequency (>= 5 successful in last 15 mins)
        $resFreq = $this->db->query("SELECT COUNT(*) FROM login_fingerprints WHERE user_id = {$user['id']} AND login_time > NOW() - INTERVAL 15 MINUTE");
        $freqCount = $resFreq ? (int)$resFreq->fetch_row()[0] : 0;
        if ($freqCount >= 5) {
            $anomalyReasons[] = "- Unusually high login frequency in a short time window.";
            $rule_anomaly_score += 0.4;
        }

        // Apply rules that require login history
        $resHist = $this->db->query("SELECT 1 FROM login_fingerprints WHERE user_id = {$user['id']} LIMIT 1");
        if ($resHist && $resHist->num_rows > 0) {
            // Rule 3: Unused device pattern
            $resDev = $this->db->query("SELECT 1 FROM login_fingerprints WHERE user_id = {$user['id']} AND device_type = '" . $this->db->real_escape_string($deviceType) . "' LIMIT 1");
            if (!$resDev || $resDev->num_rows == 0) {
                $anomalyReasons[] = "- Login from a new device that has not been used before.";
                $rule_anomaly_score += 0.3;
            }

            // Rule 4: Unusual geographic location
            $locationParts = explode(',', $location);
            $cityStr = $this->db->real_escape_string(trim($locationParts[0]));
            if ($cityStr !== 'Localhost') {
                $resLoc = $this->db->query("SELECT 1 FROM login_fingerprints WHERE user_id = {$user['id']} AND geo_location LIKE '%$cityStr%' LIMIT 1");
                if (!$resLoc || $resLoc->num_rows == 0) {
                    $anomalyReasons[] = "- Login from an unusual location (no previous login history in $cityStr).";
                    $rule_anomaly_score += 0.3;
                }
            }
        }
        
        // Clear old failed attempts after successful login
        $this->db->query("DELETE FROM failed_logins WHERE email = '" . $this->db->real_escape_string($email) . "'");
        
        // Call AI Anomaly Detection API
        $is_anomaly = count($anomalyReasons) > 0 ? 1 : 0;
        $anomaly_score = $rule_anomaly_score;
        $ai_data = [
            'user_id' => $user['id'],
            'ip_address' => $ip,
            'geo_location' => $location,
            'user_agent' => $userAgent,
            'device_type' => $deviceType,
            'time_category' => $timeCategory,
            'day_of_week' => $dayOfWeek
        ];
        
        $ch = curl_init('http://localhost:8000/api/predict_anomaly');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($ai_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2); 
        
        $response = curl_exec($ch);
        if ($response !== false) {
            $apiResult = json_decode($response, true);
            if (isset($apiResult['is_anomaly']) && $apiResult['is_anomaly']) {
                $anomalyReasons[] = "- AI detected unusual interaction behavior.";
                $is_anomaly = 1;
                $anomaly_score = max($anomaly_score, $apiResult['anomaly_score'] ?? 0);
            }
        }
        curl_close($ch);

        // Store anomaly alert in session if triggered
        if ($is_anomaly) {
            $base_msg = "UNUSUAL LOGIN DETECTED for the following reasons:\n" . implode("\n", $anomalyReasons);
            $_SESSION['anomaly_alert'] = $base_msg;
        }
        
        // Insert into login_fingerprints
        $stmtFingerprint = $this->db->prepare("INSERT INTO login_fingerprints (user_id, ip_address, geo_location, user_agent, device_type, login_time, time_category, day_of_week, typing_speed_ms, response_time_ms, is_anomaly, anomaly_score) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmtFingerprint) {
            $stmtFingerprint->bind_param("isssssssiidi", $user['id'], $ip, $location, $userAgent, $deviceType, $loginTime, $timeCategory, $dayOfWeek, $typingSpeed, $responseTime, $is_anomaly, $anomaly_score);
            $stmtFingerprint->execute();
        }
        
        // Log latest successful login history
        $this->db->query("DELETE FROM login_history WHERE user_id = " . intval($user['id']));
        $stmtLog = $this->db->prepare("INSERT INTO login_history (user_id, ip_address, location) VALUES (?, ?, ?)");
        if ($stmtLog) {
            $stmtLog->bind_param("iss", $user['id'], $ip, $location);
            $stmtLog->execute();
        }
        
        // Prepare formatted string for the green popup
        $displayLocation = "Location: " . explode(' (', $location)[0];
        if (!empty($gpsString)) {
            // Keep latitude/longitude in English in the popup
            $displayLocation .= "<br>Coordinates: " . trim(str_replace(['(', ')'], ['', ''], $gpsString));
        }

        // Set session variable for UI alert
        $_SESSION['login_location_alert'] = $displayLocation;
        
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
