<?php
// File: reset-password.php (OTP Version)
require_once 'config.php';

$error = '';
$success = '';
$token = isset($_GET['token']) ? sanitize_input($_GET['token']) : '';

// Clean expired tokens
clean_expired_otps();

// Validate token
if ($token) {
    $query = "SELECT * FROM users WHERE reset_token = '$token' AND reset_token_expiry > NOW()";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 0) {
        $error = "Invalid or expired reset link.";
        $token = '';
    } else {
        $user = mysqli_fetch_assoc($result);
        $email = $user['email'];
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = sanitize_input($_POST['token']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate
    if (empty($password)) {
        $error = 'Please enter a new password.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match!';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long!';
    } else {
        // Check password strength
        $strength_errors = check_password_strength($password);
        if (!empty($strength_errors)) {
            $error = implode('<br>', $strength_errors);
        } else {
            // Check if token is still valid
            $query = "SELECT * FROM users WHERE reset_token = '$token' AND reset_token_expiry > NOW()";
            $result = mysqli_query($conn, $query);
            
            if (mysqli_num_rows($result) > 0) {
                $user = mysqli_fetch_assoc($result);
                
                // Hash new password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Update password and clear reset token
                $update_query = "UPDATE users SET password = '$hashed_password', 
                                reset_token = NULL, reset_token_expiry = NULL,
                                otp_code = NULL, otp_expiry = NULL,
                                otp_attempts = 0
                                WHERE id = {$user['id']}";
                
                if (mysqli_query($conn, $update_query)) {
                    // Send confirmation email
                    $subject = "Password Reset Successful - SR Travels";
                    $message = "
                    <html>
                    <body>
                        <h2>Password Reset Successful</h2>
                        <p>Your password has been successfully reset.</p>
                        <p>If you did not perform this action, please contact our support team immediately.</p>
                        <p>Date: " . date('Y-m-d H:i:s') . "</p>
                        <p>IP Address: " . $_SERVER['REMOTE_ADDR'] . "</p>
                    </body>
                    </html>";
                    
                    send_email($user['email'], $subject, $message);
                    
                    $success = 'Password has been reset successfully! You can now login with your new password.';
                    $token = ''; // Clear token after successful reset
                } else {
                    $error = 'Error resetting password: ' . mysqli_error($conn);
                }
            } else {
                $error = 'Invalid or expired reset link.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - SR Travels</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .password-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .brand-logo {
            color: #2a6ebb;
            font-weight: 700;
            font-size: 2rem;
        }
        
        .brand-logo span {
            color: #ff7e36;
        }
        
        .password-strength {
            margin-top: 5px;
            font-size: 12px;
        }
        
        .strength-bar {
            height: 5px;
            background: #ddd;
            border-radius: 5px;
            margin-top: 5px;
            overflow: hidden;
        }
        
        .strength-fill {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease;
        }
        
        .password-rules {
            font-size: 12px;
            color: #666;
        }
        
        .password-rules ul {
            padding-left: 20px;
            margin-bottom: 0;
        }
        
        .password-rules li.valid {
            color: green;
        }
        
        .password-rules li.invalid {
            color: red;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="password-card p-5">
                    <div class="text-center mb-4">
                        <h2 class="brand-logo">SR<span>TRAVELS</span></h2>
                        <p class="text-muted">Set New Password</p>
                    </div>
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $success; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="login.php" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>Go to Login
                            </a>
                        </div>
                    <?php elseif($token): ?>
                        <form method="POST" action="" id="resetForm">
                            <input type="hidden" name="token" value="<?php echo $token; ?>">
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>New Password
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button type="button" class="btn btn-outline-secondary toggle-password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="password-strength">
                                    <div id="strength-text">Password strength: <span id="strength-value">None</span></div>
                                    <div class="strength-bar">
                                        <div id="strength-fill" class="strength-fill"></div>
                                    </div>
                                </div>
                                <div class="password-rules mt-2" id="password-rules">
                                    <ul>
                                        <li id="rule-length" class="invalid">At least 8 characters</li>
                                        <li id="rule-uppercase" class="invalid">One uppercase letter</li>
                                        <li id="rule-lowercase" class="invalid">One lowercase letter</li>
                                        <li id="rule-number" class="invalid">One number</li>
                                        <li id="rule-special" class="invalid">One special character</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Confirm New Password
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <button type="button" class="btn btn-outline-secondary toggle-password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div id="password-match" class="form-text"></div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-key me-2"></i>Reset Password
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No valid reset link found. Please request a new password reset.
                        </div>
                        <div class="text-center">
                            <a href="forgot-password.php" class="btn btn-primary">
                                <i class="fas fa-redo me-2"></i>Request New Reset
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="text-center mt-3">
                        <a href="login.php" class="text-decoration-none">
                            <i class="fas fa-arrow-left me-1"></i> Back to Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength checker
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const strengthText = document.getElementById('strength-value');
        const strengthFill = document.getElementById('strength-fill');
        const passwordMatch = document.getElementById('password-match');
        
        // Password rules elements
        const ruleLength = document.getElementById('rule-length');
        const ruleUppercase = document.getElementById('rule-uppercase');
        const ruleLowercase = document.getElementById('rule-lowercase');
        const ruleNumber = document.getElementById('rule-number');
        const ruleSpecial = document.getElementById('rule-special');
        
        function checkPasswordStrength(password) {
            let strength = 0;
            
            // Check length
            if (password.length >= 8) {
                strength += 20;
                ruleLength.classList.remove('invalid');
                ruleLength.classList.add('valid');
            } else {
                ruleLength.classList.remove('valid');
                ruleLength.classList.add('invalid');
            }
            
            // Check uppercase
            if (/[A-Z]/.test(password)) {
                strength += 20;
                ruleUppercase.classList.remove('invalid');
                ruleUppercase.classList.add('valid');
            } else {
                ruleUppercase.classList.remove('valid');
                ruleUppercase.classList.add('invalid');
            }
            
            // Check lowercase
            if (/[a-z]/.test(password)) {
                strength += 20;
                ruleLowercase.classList.remove('invalid');
                ruleLowercase.classList.add('valid');
            } else {
                ruleLowercase.classList.remove('valid');
                ruleLowercase.classList.add('invalid');
            }
            
            // Check numbers
            if (/[0-9]/.test(password)) {
                strength += 20;
                ruleNumber.classList.remove('invalid');
                ruleNumber.classList.add('valid');
            } else {
                ruleNumber.classList.remove('valid');
                ruleNumber.classList.add('invalid');
            }
            
            // Check special characters
            if (/[^A-Za-z0-9]/.test(password)) {
                strength += 20;
                ruleSpecial.classList.remove('invalid');
                ruleSpecial.classList.add('valid');
            } else {
                ruleSpecial.classList.remove('valid');
                ruleSpecial.classList.add('invalid');
            }
            
            // Update strength bar
            strengthFill.style.width = strength + '%';
            
            // Update strength text
            if (strength >= 80) {
                strengthText.textContent = 'Strong';
                strengthText.style.color = 'green';
                strengthFill.style.backgroundColor = 'green';
            } else if (strength >= 60) {
                strengthText.textContent = 'Good';
                strengthText.style.color = 'orange';
                strengthFill.style.backgroundColor = 'orange';
            } else if (strength >= 40) {
                strengthText.textContent = 'Fair';
                strengthText.style.color = 'yellow';
                strengthFill.style.backgroundColor = 'yellow';
            } else {
                strengthText.textContent = 'Weak';
                strengthText.style.color = 'red';
                strengthFill.style.backgroundColor = 'red';
            }
        }
        
        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (confirmPassword === '') {
                passwordMatch.textContent = '';
                passwordMatch.style.color = '';
            } else if (password === confirmPassword) {
                passwordMatch.textContent = '✓ Passwords match';
                passwordMatch.style.color = 'green';
            } else {
                passwordMatch.textContent = '✗ Passwords do not match';
                passwordMatch.style.color = 'red';
            }
        }
        
        // Event listeners
        passwordInput.addEventListener('input', function() {
            checkPasswordStrength(this.value);
            checkPasswordMatch();
        });
        
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);
        
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentElement.querySelector('input');
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
        
        // Form validation
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return;
            }
            
            // Check if password meets all requirements
            const rules = document.querySelectorAll('.password-rules li.valid');
            if (rules.length < 5) {
                e.preventDefault();
                alert('Please ensure your password meets all requirements.');
                return;
            }
        });
    </script>
</body>
</html>