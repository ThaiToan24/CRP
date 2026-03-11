<?php
/**
 * Login Page
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/utils/Auth.php';

$auth = new Auth($db);
$error = '';
$success = '';

// use Url helper for consistent base URL including /public
require_once __DIR__ . '/../../src/utils/Url.php';
$baseUrl = getBaseUrl();

// If already logged in, redirect
if ($auth->isLoggedIn()) {
    $user = $auth->getCurrentUser();
    if ($user['role'] === 'admin') {
        redirect('admin/dashboard.php');
    } elseif ($user['role'] === 'seller') {
        redirect('seller/dashboard.php');
    } else {
        redirect('pages/home.php');
    }
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Collect fingerprint data from hidden inputs
    $fingerprintData = [
        'user_agent' => $_POST['f_user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? '',
        'device_type' => $_POST['f_device_type'] ?? 'Unknown',
        'typing_speed_ms' => intval($_POST['f_typing_speed'] ?? 0),
        'response_time_ms' => intval($_POST['f_response_time'] ?? 0),
        'client_ip' => $_POST['f_client_ip'] ?? '',
        'precise_location' => $_POST['f_precise_location'] ?? ''
    ];
    
    $result = $auth->login($email, $password, $fingerprintData);
    
        if ($result['success']) {
        $user = $result['user'];
        if ($user['role'] === 'admin') {
            redirect('admin/dashboard.php?section=users');
        } elseif ($user['role'] === 'seller') {
            redirect('seller/dashboard.php');
        } else {
            redirect('pages/home.php');
        }
    } else {
        $error = $result['message'];
    }
}

$pageTitle = 'Login - DB eCommerce';
// $baseUrl already set by top-of-file logic
?>

<?php include __DIR__ . '/../../src/views/header.php'; ?>

<div class="container mt-8">
    <div class="max-w-md mx-auto bg-white p-8 rounded-lg shadow-lg">
        <h2 class="text-2xl font-bold mb-6 text-center">Login to DB eCommerce</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-4" id="loginForm">
            <!-- Hidden Fingerprint Inputs -->
            <input type="hidden" name="f_user_agent" id="f_user_agent" value="">
            <input type="hidden" name="f_device_type" id="f_device_type" value="">
            <input type="hidden" name="f_typing_speed" id="f_typing_speed" value="0">
            <input type="hidden" name="f_response_time" id="f_response_time" value="0">
            <input type="hidden" name="f_client_ip" id="f_client_ip" value="">
            <input type="hidden" name="f_precise_location" id="f_precise_location" value="">

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn btn-primary w-full">Login</button>
        </form>
        
        <div class="text-center mt-6">
            <p>Don't have an account? <a href="register.php" class="text-primary font-bold">Register here</a></p>
        </div>
        
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const pageLoadTime = Date.now();
    let keydownTimes = [];
    let lastKeydownTime = null;

    // Detect Device Type
    const ua = navigator.userAgent;
    let deviceType = "Desktop";
    if (/Mobi|Android/i.test(ua)) {
        deviceType = "Mobile";
    } else if (/Tablet|iPad/i.test(ua)) {
        deviceType = "Tablet";
    }

    // Set basic info
    document.getElementById("f_user_agent").value = ua;
    document.getElementById("f_device_type").value = deviceType;
    
    // Fetch true external IP (useful for localhost testing)
    fetch('https://api.ipify.org?format=json')
        .then(response => response.json())
        .then(data => {
            document.getElementById("f_client_ip").value = data.ip;
        })
        .catch(err => console.log('IP fetch failed', err));

    // Request precise GPS location from the browser
    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                document.getElementById("f_precise_location").value = lat + "," + lng;
            },
            function(error) {
                console.log("Geolocation denied or unavailable:", error.message);
                // Fallback to IP logic remains active as f_precise_location stays empty
            },
            { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
        );
    }

    // Track typing speed on email and password fields
    const inputs = [document.getElementById("email"), document.getElementById("password")];
    inputs.forEach(input => {
        if (input) {
            input.addEventListener("keydown", function(e) {
                const now = Date.now();
                if (lastKeydownTime) {
                    const diff = now - lastKeydownTime;
                    if (diff < 1000) { // Keep reasonable intervals
                        keydownTimes.push(diff);
                    }
                }
                lastKeydownTime = now;
            });
        }
    });

    // Calculate metrics on form submission
    document.getElementById("loginForm").addEventListener("submit", function() {
        const submitTime = Date.now();
        const responseTime = submitTime - pageLoadTime;
        document.getElementById("f_response_time").value = responseTime;

        if (keydownTimes.length > 0) {
            const sum = keydownTimes.reduce((a, b) => a + b, 0);
            const avg = Math.round(sum / keydownTimes.length);
            document.getElementById("f_typing_speed").value = avg;
        }
    });
});
</script>
</div>

<?php include __DIR__ . '/../../src/views/footer.php'; ?>
