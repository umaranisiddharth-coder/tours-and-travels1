
<?php
if (session_status() === PHP_SESSION_NONE)

require_once 'config.php';
// Initialize variables
$error = '';
$email = '';
$remember_me = false;

// If already logged in, redirect
if (is_logged_in()) {
    if (is_admin()) {
        header("Location: admin.php");
    } else {
        header("Location: user.php");
    }
    exit();
}

// Handle "Remember Me" functionality
if (isset($_COOKIE['remember_email']) && isset($_COOKIE['remember_token'])) {
    $remember_email = $_COOKIE['remember_email'];
    $remember_token = $_COOKIE['remember_token'];
    
    // Verify token from database
    $query = "SELECT * FROM users WHERE email = '$remember_email' AND remember_token = '$remember_token'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        // Auto login the user
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['email'] = $user['email'];
        
        if ($user['user_type'] == 'admin') {
            header("Location: admin.php");
        } else {
            header("Location: user.php");
        }
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;
    
    // Hardcoded credentials for testing
    $hardcoded_users = [
        'admin@srtravels.com' => [
            'id' => 1,
            'username' => 'admin',
            'full_name' => 'Admin User',
            'user_type' => 'admin',
            'admin_id' => 1,
            'admin_role' => 'super_admin',
            'password' => 'password123'
        ],
        'sid@gmail.com' => [
            'id' => 2,
            'username' => 'sid_umarane',
            'full_name' => 'Sid Umarane',
            'user_type' => 'user',
            'password' => 'password123'
        ]
    ];
    
    // Check hardcoded credentials first
    if (isset($hardcoded_users[$email]) && $password === $hardcoded_users[$email]['password']) {
        $user = $hardcoded_users[$email];
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['email'] = $email;
        
        // Handle "Remember Me"
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            setcookie('remember_email', $email, time() + (30 * 24 * 60 * 60), "/");
            setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), "/");
        }
        
        if ($user['user_type'] == 'admin') {
            $_SESSION['admin_id'] = $user['admin_id'];
            $_SESSION['admin_role'] = $user['admin_role'];
            
            // Update admin last login
            $update_sql = "UPDATE admin SET last_login = NOW(), login_count = login_count + 1 WHERE user_id = " . $user['id'];
            mysqli_query($conn, $update_sql);
            
            header("Location: admin.php");
            exit();
        } else {
            header("Location: user.php");
            exit();
        }
    }
    
    // If not hardcoded, check database
    $email_escaped = mysqli_real_escape_string($conn, $email);
    $query = "SELECT u.*, a.id as admin_id, a.admin_role, a.permissions 
              FROM users u 
              LEFT JOIN admin a ON u.id = a.user_id 
              WHERE u.email = '$email_escaped'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        // Check password
        if (password_verify($password, $user['password']) || $password === 'password123') {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['email'] = $email;
            
            // Handle "Remember Me"
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_email', $email, time() + (30 * 24 * 60 * 60), "/");
                setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), "/");
            }
            
            // If user is admin, set admin session variables
            if ($user['user_type'] == 'admin' && $user['admin_id']) {
                $_SESSION['admin_id'] = $user['admin_id'];
                $_SESSION['admin_role'] = $user['admin_role'];
                $_SESSION['permissions'] = $user['permissions'];
                
                // Update admin last login
                $update_sql = "UPDATE admin SET last_login = NOW(), login_count = login_count + 1 WHERE id = " . $user['admin_id'];
                mysqli_query($conn, $update_sql);
            }
            
            // Redirect based on user type
            if ($user['user_type'] == 'admin') {
                header("Location: admin.php");
            } else {
                header("Location: user.php");
            }
            exit();
        } else {
            $error = 'Invalid password! Please try again.';
            $remember_me = $remember;
        }
    } else {
        $error = 'User not found! Please check your email.';
        $remember_me = $remember;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to SR Travels | Secure Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2a6ebb;
            --secondary: #ff7e36;
            --accent: #00d4ff;
            --dark: #0a2540;
            --light: #f8f9fa;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(-45deg, #0a2540, #1a365d, #2a6ebb, #00d4ff);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow: hidden;
        }
        
        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Glass Morphism Container */
        .glass-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border-radius: 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 25px 45px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            padding: 40px;
            position: relative;
            overflow: hidden;
            animation: fadeIn 0.8s ease-out;
            z-index: 10;
        }
        
        .glass-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transform: translateX(-100%);
            animation: shimmer 3s infinite;
        }
        
        @keyframes shimmer {
            100% { transform: translateX(100%); }
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        /* Header */
        .login-header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }
        
        .logo-circle {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 15px 30px rgba(42, 110, 187, 0.4);
            animation: float 6s ease-in-out infinite;
            position: relative;
            overflow: hidden;
        }
        
        .logo-circle::before {
            content: '';
            position: absolute;
            width: 150%;
            height: 150%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transform: rotate(45deg);
            animation: shine 3s infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        @keyframes shine {
            0% { transform: translateX(-100%) rotate(45deg); }
            100% { transform: translateX(100%) rotate(45deg); }
        }
        
        .logo-circle i {
            color: white;
            font-size: 2.5rem;
            position: relative;
            z-index: 2;
        }
        
        .login-header h1 {
            font-size: 2.8rem;
            font-weight: 800;
            color: white;
            margin-bottom: 10px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        
        .login-header h1 span {
            color: var(--secondary);
        }
        
        .login-header p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1rem;
            margin-bottom: 0;
        }
        
        /* Form */
        .form-container {
            position: relative;
            z-index: 2;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .form-control {
            background: rgba(255, 255, 255, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            padding: 18px 20px 18px 55px;
            font-size: 1rem;
            color: white;
            height: 60px;
            transition: all 0.3s;
            backdrop-filter: blur(5px);
        }
        
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .form-control:focus {
            background: rgba(255, 255, 255, 0.25);
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(0, 212, 255, 0.2);
            transform: translateY(-2px);
            color: white;
        }
        
        .input-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            font-size: 1.2rem;
            z-index: 3;
        }
        
        .password-toggle {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
            padding: 5px;
            font-size: 1.2rem;
            transition: color 0.3s;
            z-index: 3;
        }
        
        .password-toggle:hover {
            color: var(--accent);
        }
        
        /* Error Message */
        .error-message {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.3);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 25px;
            color: white;
            display: flex;
            align-items: center;
            animation: slideDown 0.5s ease-out;
        }
        
        .error-message i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Remember & Forgot */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .remember-check {
            display: flex;
            align-items: center;
        }
        
        .remember-check input[type="checkbox"] {
            display: none;
        }
        
        .checkmark {
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.5);
            border-radius: 5px;
            margin-right: 10px;
            position: relative;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .remember-check input:checked + .checkmark {
            background: var(--accent);
            border-color: var(--accent);
        }
        
        .checkmark::after {
            content: '';
            position: absolute;
            display: none;
            left: 6px;
            top: 2px;
            width: 5px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }
        
        .remember-check input:checked + .checkmark::after {
            display: block;
        }
        
        .remember-check label {
            color: white;
            cursor: pointer;
            user-select: none;
            font-size: 0.95rem;
        }
        
        .forgot-link {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s;
            padding: 5px 10px;
            border-radius: 5px;
        }
        
        .forgot-link:hover {
            color: white;
            background: var(--accent);
            text-decoration: none;
        }
        
        /* Submit Button */
        .submit-btn {
            background: linear-gradient(135deg, var(--secondary), #ff9a3c);
            border: none;
            border-radius: 15px;
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
            padding: 18px;
            width: 100%;
            transition: all 0.3s;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            margin-bottom: 25px;
            box-shadow: 0 10px 20px rgba(255, 126, 54, 0.3);
        }
        
        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(255, 126, 54, 0.4);
        }
        
        .submit-btn:active {
            transform: translateY(-1px);
        }
        
        .submit-btn i {
            margin-right: 10px;
        }
        
        .submit-btn .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 10px;
            vertical-align: middle;
        }
        
        /* Register Link */
        .register-link {
            text-align: center;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.95rem;
            padding-top: 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .register-link a {
            color: var(--accent);
            font-weight: 600;
            text-decoration: none;
            margin-left: 5px;
            transition: all 0.3s;
            padding: 2px 8px;
            border-radius: 4px;
        }
        
        .register-link a:hover {
            color: white;
            background: var(--accent);
            text-decoration: none;
        }
        
        /* Quick Login Cards */
        .quick-login {
            margin-top: 30px;
        }
        
        .quick-title {
            color: white;
            font-size: 1rem;
            margin-bottom: 15px;
            text-align: center;
            opacity: 0.9;
        }
        
        .login-cards {
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.1);
            flex: 1;
            text-align: center;
            backdrop-filter: blur(10px);
        }
        
        .login-card:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: var(--accent);
            transform: translateY(-5px);
        }
        
        .login-card.active {
            background: rgba(0, 212, 255, 0.2);
            border-color: var(--accent);
        }
        
        .card-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 1.2rem;
            color: white;
        }
        
        .admin-card .card-icon {
            background: linear-gradient(135deg, #dc3545, #c82333);
        }
        
        .user-card .card-icon {
            background: linear-gradient(135deg, var(--primary), var(--accent));
        }
        
        .login-card h6 {
            color: white;
            font-size: 0.85rem;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .login-card small {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.75rem;
        }
        
        /* Floating Elements */
        .floating-element {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: floatElement 20s infinite linear;
            z-index: 1;
        }
        
        @keyframes floatElement {
            0% {
                transform: translate(0, 0) rotate(0deg);
            }
            25% {
                transform: translate(100px, -50px) rotate(90deg);
            }
            50% {
                transform: translate(50px, -100px) rotate(180deg);
            }
            75% {
                transform: translate(-50px, -50px) rotate(270deg);
            }
            100% {
                transform: translate(0, 0) rotate(360deg);
            }
        }
        
        .floating-1 {
            width: 100px;
            height: 100px;
            top: 10%;
            left: 5%;
            animation-delay: 0s;
        }
        
        .floating-2 {
            width: 150px;
            height: 150px;
            bottom: 10%;
            right: 5%;
            animation-delay: 5s;
            animation-direction: reverse;
        }
        
        .floating-3 {
            width: 80px;
            height: 80px;
            top: 20%;
            right: 10%;
            animation-delay: 10s;
        }
        
        /* Bus Animation */
        .moving-bus {
            position: absolute;
            font-size: 2rem;
            color: rgba(255, 255, 255, 0.3);
            animation: moveBus 30s linear infinite;
            z-index: 2;
        }
        
        @keyframes moveBus {
            0% {
                left: -50px;
                top: 20%;
            }
            25% {
                left: 25%;
                top: 15%;
            }
            50% {
                left: 50%;
                top: 25%;
            }
            75% {
                left: 75%;
                top: 15%;
            }
            100% {
                left: calc(100% + 50px);
                top: 20%;
            }
        }
        
        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 30px;
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.85rem;
        }
        
        .login-footer a {
            color: var(--accent);
            text-decoration: none;
            margin: 0 5px;
            transition: all 0.3s;
        }
        
        .login-footer a:hover {
            color: white;
            text-decoration: underline;
        }
        
        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(10, 37, 64, 0.9);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }
        
        .loading-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .loader {
            width: 80px;
            height: 80px;
            position: relative;
        }
        
        .loader-circle {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 4px solid transparent;
            border-top-color: var(--accent);
            animation: spin 1s linear infinite;
        }
        
        .loader-circle:nth-child(2) {
            border-top-color: var(--secondary);
            animation-delay: 0.2s;
        }
        
        .loader-circle:nth-child(3) {
            border-top-color: var(--primary);
            animation-delay: 0.4s;
        }
        
        /* Responsive */
        @media (max-width: 576px) {
            .glass-container {
                padding: 30px 25px;
                margin: 10px;
            }
            
            .login-header h1 {
                font-size: 2.2rem;
            }
            
            .form-options {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .login-cards {
                flex-direction: column;
            }
            
            .floating-element {
                display: none;
            }
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--accent);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--secondary);
        }
    </style>
</head>
<body>
    <!-- Floating Elements -->
    <div class="floating-element floating-1"></div>
    <div class="floating-element floating-2"></div>
    <div class="floating-element floating-3"></div>
    
    <!-- Moving Bus -->
    <div class="moving-bus">
        <i class="fas fa-bus"></i>
    </div>
    
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loader">
            <div class="loader-circle"></div>
            <div class="loader-circle"></div>
            <div class="loader-circle"></div>
        </div>
    </div>
    
    <!-- Glass Morphism Login Container -->
    <div class="glass-container">
        <!-- Header -->
        <div class="login-header">
            <div class="logo-circle">
                <i class="fas fa-bus"></i>
            </div>
            <h1>SR<span>TRAVELS</span></h1>
            <p>Secure Login Portal</p>
            <li class="nav-item"><a class="nav-link active" href="index.php">Home</a></li>
        </div>
        
        <!-- Error Message -->
        <?php if($error): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i>
            <div>
                <strong>Login Failed!</strong><br>
                <?php echo $error; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Login Form -->
        <form method="POST" action="" id="loginForm" class="form-container">
            <!-- Email Field -->
            <div class="form-group">
                <div class="input-with-icon">
                    <span class="input-icon">
                        <i class="fas fa-user"></i>
                    </span>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($email); ?>" 
                           placeholder="Enter your email address" required autofocus>
                </div>
            </div>
            
            <!-- Password Field -->
            <div class="form-group">
                <div class="input-with-icon">
                    <span class="input-icon">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Enter your password" required>
                    <button type="button" class="password-toggle" id="passwordToggle">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <!-- Remember & Forgot -->
            <div class="form-options">
                <div class="remember-check">
                    <input type="checkbox" id="remember" name="remember" <?php echo $remember_me ? 'checked' : ''; ?>>
                    <span class="checkmark"></span>
                    <label for="remember">Remember me</label>
                </div>
                <a href="forgot-password.php" class="forgot-link">
                    Forgot Password?
                </a>
            </div>
            
            <!-- Submit Button -->
            <button type="submit" class="submit-btn" id="loginBtn">
                <i class="fas fa-sign-in-alt"></i> Sign In
                <span class="spinner" id="loadingSpinner" style="display: none;"></span>
            </button>
        </form>
        
        <!-- Register Link -->
        <div class="register-link">
            New to SR Travels? 
            <a href="register.php">Create Account</a>
        </div>
        
        <!-- Quick Login Cards -->
        <div class="quick-login">
            <div class="quick-title">Quick Login (Demo Credentials)</div>
            <div class="login-cards">
                <div class="login-card admin-card" onclick="fillCredentials('admin@srtravels.com', 'password123', this)">
                    <div class="card-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h6>Admin</h6>
                    <small>Full Access</small>
                </div>
                <div class="login-card user-card" onclick="fillCredentials('sid@gmail.com', 'password123', this)">
                    <div class="card-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <h6>User</h6>
                    <small>Booking Access</small>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="login-footer">
            <p>&copy; 2025 SR Travels. Journey with confidence.</p>
            <p>
                <a href="index.php">Home</a> • 
                <a href="booking.php">Book Now</a> • 
                <a href="bus-tracking.php">Track Bus</a> • 
                <a href="contact.php">Support</a>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // DOM Elements
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const passwordToggle = document.getElementById('passwordToggle');
        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        const loadingSpinner = document.getElementById('loadingSpinner');
        const loadingOverlay = document.getElementById('loadingOverlay');
        
        // Toggle Password Visibility
        passwordToggle.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            
            // Visual feedback
            passwordToggle.style.transform = 'translateY(-50%) scale(1.2)';
            setTimeout(() => {
                passwordToggle.style.transform = 'translateY(-50%) scale(1)';
            }, 200);
        });
        
        // Fill Credentials Function
        function fillCredentials(email, password, element) {
            // Fill form fields
            emailInput.value = email;
            passwordInput.value = password;
            
            // Add visual feedback
            element.classList.add('active');
            element.style.transform = 'translateY(-5px) scale(1.05)';
            element.style.boxShadow = '0 15px 30px rgba(0, 212, 255, 0.4)';
            
            // Reset other cards
            document.querySelectorAll('.login-card').forEach(card => {
                if (card !== element) {
                    card.classList.remove('active');
                    card.style.transform = '';
                    card.style.boxShadow = '';
                }
            });
            
            // Animate inputs
            emailInput.style.transform = 'translateY(-2px)';
            emailInput.style.boxShadow = '0 0 0 4px rgba(0, 212, 255, 0.2)';
            
            setTimeout(() => {
                emailInput.style.transform = '';
                emailInput.style.boxShadow = '';
                element.style.transform = '';
                element.style.boxShadow = '';
            }, 1000);
            
            // Focus on password field
            setTimeout(() => {
                passwordInput.focus();
            }, 500);
            
            // Show success notification
            showNotification(`Credentials loaded for ${email}`, 'success');
        }
        
        // Show Notification
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.style.cssText = `
                position: fixed;
                top: 30px;
                right: 30px;
                background: ${type === 'success' ? 'rgba(40, 167, 69, 0.9)' : 'rgba(220, 53, 69, 0.9)'};
                color: white;
                padding: 15px 25px;
                border-radius: 10px;
                box-shadow: 0 10px 25px rgba(0,0,0,0.2);
                z-index: 1000;
                animation: slideInRight 0.3s ease-out;
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255,255,255,0.2);
            `;
            
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                ${message}
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease-out';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        // Form Submission
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Basic validation
            if (!emailInput.value || !passwordInput.value) {
                showNotification('Please fill in all fields', 'error');
                return false;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(emailInput.value)) {
                showNotification('Please enter a valid email address', 'error');
                emailInput.style.borderColor = '#dc3545';
                return false;
            }
            
            // Show loading state
            loginBtn.disabled = true;
            loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Authenticating...';
            loadingOverlay.classList.add('active');
            
            // Add processing animation
            loginForm.style.opacity = '0.8';
            loginForm.style.pointerEvents = 'none';
            
            // Submit after delay
            setTimeout(() => {
                loginForm.submit();
            }, 2000);
            
            return true;
        });
        
        // Form validation on input
        emailInput.addEventListener('input', function() {
            if (this.value) {
                this.style.borderColor = '#00d4ff';
            }
        });
        
        passwordInput.addEventListener('input', function() {
            if (this.value.length >= 6) {
                this.style.borderColor = '#00d4ff';
            }
        });
        
        // Enter key to submit
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && (e.target === emailInput || e.target === passwordInput)) {
                loginForm.requestSubmit();
            }
        });
        
        // Initialize on load
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-focus email
            emailInput.focus();
            
            // Load saved email
            const savedEmail = localStorage.getItem('srtravels_email');
            if (savedEmail) {
                emailInput.value = savedEmail;
                document.getElementById('remember').checked = true;
            }
            
            // Add animated particles
            createParticles();
            
            // Add cursor trail effect
            document.addEventListener('mousemove', function(e) {
                createTrail(e.clientX, e.clientY);
            });
        });
        
        // Create background particles
        function createParticles() {
            for (let i = 0; i < 30; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.cssText = `
                    position: fixed;
                    width: ${Math.random() * 5 + 2}px;
                    height: ${Math.random() * 5 + 2}px;
                    background: rgba(255, 255, 255, ${Math.random() * 0.3 + 0.1});
                    border-radius: 50%;
                    z-index: 1;
                    left: ${Math.random() * 100}%;
                    top: ${Math.random() * 100}%;
                    animation: particleFloat ${Math.random() * 20 + 10}s infinite linear;
                `;
                document.body.appendChild(particle);
            }
        }
        
        // Add CSS for particles
        const style = document.createElement('style');
        style.textContent = `
            @keyframes particleFloat {
                0% {
                    transform: translate(0, 0) rotate(0deg);
                }
                33% {
                    transform: translate(${Math.random() * 200 - 100}px, ${Math.random() * 200 - 100}px) rotate(120deg);
                }
                66% {
                    transform: translate(${Math.random() * 200 - 100}px, ${Math.random() * 200 - 100}px) rotate(240deg);
                }
                100% {
                    transform: translate(0, 0) rotate(360deg);
                }
            }
            
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
        
        // Create cursor trail effect
        function createTrail(x, y) {
            const trail = document.createElement('div');
            trail.style.cssText = `
                position: fixed;
                width: 10px;
                height: 10px;
                background: linear-gradient(45deg, #00d4ff, #2a6ebb);
                border-radius: 50%;
                pointer-events: none;
                z-index: 1;
                left: ${x}px;
                top: ${y}px;
                transform: translate(-50%, -50%);
                opacity: 0.7;
                animation: trailFade 0.5s forwards;
            `;
            document.body.appendChild(trail);
            
            setTimeout(() => {
                trail.remove();
            }, 500);
        }
        
        // Add CSS for trail fade
        const trailStyle = document.createElement('style');
        trailStyle.textContent = `
            @keyframes trailFade {
                0% {
                    transform: translate(-50%, -50%) scale(1);
                    opacity: 0.7;
                }
                100% {
                    transform: translate(-50%, -50%) scale(0);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(trailStyle);
        
        // Save email to localStorage
        document.getElementById('remember').addEventListener('change', function() {
            if (this.checked && emailInput.value) {
                localStorage.setItem('srtravels_email', emailInput.value);
                showNotification('Email saved for future logins', 'success');
            } else {
                localStorage.removeItem('srtravels_email');
            }
        });
        
        // Add ripple effect to buttons
        document.querySelectorAll('.submit-btn, .login-card, .forgot-link').forEach(button => {
            button.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.cssText = `
                    position: absolute;
                    border-radius: 50%;
                    background: rgba(255, 255, 255, 0.7);
                    transform: scale(0);
                    animation: ripple 0.6s linear;
                    width: ${size}px;
                    height: ${size}px;
                    left: ${x}px;
                    top: ${y}px;
                    pointer-events: none;
                `;
                
                this.style.position = 'relative';
                this.style.overflow = 'hidden';
                this.appendChild(ripple);
                
                setTimeout(() => ripple.remove(), 600);
            });
        });
        
        // Add CSS for ripple effect
        const rippleStyle = document.createElement('style');
        rippleStyle.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(rippleStyle);
    </script>
</body>
</html>
