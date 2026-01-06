<?php
session_start();

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'srtravels');

// Create Connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check Connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set Timezone
date_default_timezone_set('Asia/Kolkata');

// Function to Sanitize Input (Updated with database escaping)
function sanitize_input($data) {
    global $conn;
    if (empty($data)) return '';
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = mysqli_real_escape_string($conn, $data);
    return $data;
}

// Generate random token for password reset
function generate_token($length = 32) {
    if (function_exists('random_bytes')) {
        return bin2hex(random_bytes($length));
    } else {
        // Fallback for older PHP versions
        return bin2hex(openssl_random_pseudo_bytes($length));
    }
}

// Check if email exists
function email_exists($email) {
    global $conn;
    $email = mysqli_real_escape_string($conn, $email);
    $query = "SELECT id FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);
    return mysqli_num_rows($result) > 0;
}

// Get user by email
function get_user_by_email($email) {
    global $conn;
    $email = mysqli_real_escape_string($conn, $email);
    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

// Get user by ID
function get_user_by_id($id) {
    global $conn;
    $id = (int)$id;
    $query = "SELECT * FROM users WHERE id = $id";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

// Check if User is Logged In
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Check if User is Admin
function is_admin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin';
}

// Check if User is Super Admin
function is_super_admin() {
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] == 'super_admin';
}

// Check if User has Permission
function has_permission($permission) {
    if (!is_admin()) return false;
    
    if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] == 'super_admin') {
        return true; // Super admin has all permissions
    }
    
    if (isset($_SESSION['permissions'])) {
        $permissions = explode(',', $_SESSION['permissions']);
        return in_array($permission, $permissions) || in_array('all', $permissions);
    }
    
    return false;
}

// Generate Booking ID
function generate_booking_id() {
    return 'BK-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

// Format Date
function format_date($date) {
    return date('d M Y', strtotime($date));
}

// Format Time
function format_time($time) {
    return date('h:i A', strtotime($time));
}

// Calculate Duration
function calculate_duration($departure, $arrival) {
    $departure = strtotime($departure);
    $arrival = strtotime($arrival);
    $diff = $arrival - $departure;
    
    $hours = floor($diff / 3600);
    $minutes = floor(($diff % 3600) / 60);
    
    if ($hours > 0 && $minutes > 0) {
        return $hours . 'h ' . $minutes . 'm';
    } elseif ($hours > 0) {
        return $hours . 'h';
    } else {
        return $minutes . 'm';
    }
}

// Send Email Function (for password reset)
function send_email($to, $subject, $message) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: SR Travels <noreply@srtravels.com>\r\n";
    $headers .= "Reply-To: noreply@srtravels.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    return mail($to, $subject, $message, $headers);
}

// Redirect function
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Set flash message
function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
}

// Display flash message
function display_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        echo '<div class="alert alert-' . $flash['type'] . ' alert-dismissible fade show" role="alert">';
        echo $flash['message'];
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        unset($_SESSION['flash_message']);
    }
}

// Log admin activity
function log_admin_activity($activity) {
    global $conn;
    
    if (isset($_SESSION['admin_id'])) {
        $admin_id = (int)$_SESSION['admin_id'];
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $user_agent = mysqli_real_escape_string($conn, $_SERVER['HTTP_USER_AGENT']);
        $activity = mysqli_real_escape_string($conn, $activity);
        
        $sql = "INSERT INTO admin_logs (admin_id, activity, ip_address, user_agent) 
                VALUES ($admin_id, '$activity', '$ip_address', '$user_agent')";
        mysqli_query($conn, $sql);
    }
}

// Add CSRF token functions
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Get current date and time
function get_current_datetime() {
    return date('Y-m-d H:i:s');
}

// Format currency
function format_currency($amount) {
    return '₹' . number_format($amount, 2);
}

// Get user's full name
function get_user_fullname($user_id) {
    global $conn;
    $user_id = (int)$user_id;
    $query = "SELECT full_name FROM users WHERE id = $user_id";
    $result = mysqli_query($conn, $query);
    $user = mysqli_fetch_assoc($result);
    return $user ? $user['full_name'] : 'Unknown User';
}

// Check if bus exists
function bus_exists($bus_id) {
    global $conn;
    $bus_id = (int)$bus_id;
    $query = "SELECT id FROM buses WHERE id = $bus_id";
    $result = mysqli_query($conn, $query);
    return mysqli_num_rows($result) > 0;
}

// Check if route exists
function route_exists($route_id) {
    global $conn;
    $route_id = (int)$route_id;
    $query = "SELECT id FROM bus_routes WHERE id = $route_id";
    $result = mysqli_query($conn, $query);
    return mysqli_num_rows($result) > 0;
}

// Validate date format
function validate_date($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// Clean old expired reset tokens (optional cleanup function)
function clean_expired_tokens() {
    global $conn;
    $sql = "UPDATE users SET reset_token = NULL, reset_token_expiry = NULL 
            WHERE reset_token_expiry < NOW()";
    mysqli_query($conn, $sql);
}

// Set a cookie
function set_cookie($name, $value, $days = 30) {
    setcookie($name, $value, time() + (86400 * $days), "/");
}

// Get cookie value
function get_cookie($name) {
    return isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;
}

// Delete cookie
function delete_cookie($name) {
    setcookie($name, "", time() - 3600, "/");
}

// Password strength checker
function check_password_strength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    if (!preg_match('/[\W]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    return $errors;
}
?>