<?php
// File: forgot-password.php (OTP Version)
require_once 'config.php';

$error = '';
$success = '';
$show_otp_form = false;
$email = '';

// Clean expired OTPs on page load
clean_expired_otps();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['send_otp'])) {
        // Step 1: Request OTP
        $email = sanitize_input($_POST['email']);
        
        // Check if email exists
        if (email_exists($email)) {
            // Rate limiting check
            if (!can_send_otp($email)) {
                $error = "Please wait 60 seconds before requesting another OTP.";
            } else {
                // Generate OTP
                $otp = generate_otp();
                $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                
                // Store OTP in database
                $update_query = "UPDATE users SET 
                                otp_code = '$otp', 
                                otp_expiry = '$expiry',
                                otp_attempts = 0,
                                last_otp_sent = NOW()
                                WHERE email = '$email'";
                
                if (mysqli_query($conn, $update_query)) {
                    // Send OTP email
                    if (send_otp_email($email, $otp)) {
                        $success = "OTP has been sent to your email! It will expire in 10 minutes.";
                        $show_otp_form = true;
                        
                        // Store email in session for verification
                        $_SESSION['reset_email'] = $email;
                        
                        // Log OTP sent
                        log_otp_activity($email, $otp, 'sent');
                    } else {
                        $error = "Failed to send OTP email. Please try again.";
                    }
                } else {
                    $error = "Error generating OTP: " . mysqli_error($conn);
                }
            }
        } else {
            $error = "No account found with that email address.";
        }
        
    } elseif (isset($_POST['verify_otp'])) {
        // Step 2: Verify OTP
        $email = $_SESSION['reset_email'];
        $otp = sanitize_input($_POST['otp']);
        
        if (validate_otp($email, $otp)) {
            // OTP verified successfully
            // Generate reset token for password reset
            $token = generate_token();
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $update_query = "UPDATE users SET 
                            reset_token = '$token', 
                            reset_token_expiry = '$expiry',
                            otp_code = NULL,
                            otp_expiry = NULL
                            WHERE email = '$email'";
            
            if (mysqli_query($conn, $update_query)) {
                // Redirect to reset password page
                header("Location: reset-password.php?token=$token");
                exit();
            }
        } else {
            $error = "Invalid or expired OTP. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - SR Travels</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px 0;
        }
        
        .password-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .brand-logo {
            color: #2a6ebb;
            font-weight: 700;
            font-size: 2rem;
        }
        
        .brand-logo span {
            color: #ff7e36;
        }
        
        .otp-input {
            letter-spacing: 10px;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
        }
        
        .timer {
            font-size: 14px;
            color: #666;
            margin-top: 10px;
        }
        
        .resend-link {
            cursor: pointer;
            color: #667eea;
            text-decoration: underline;
        }
        
        .resend-link:disabled {
            color: #ccc;
            cursor: not-allowed;
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
                        <p class="text-muted">
                            <?php echo $show_otp_form ? 'Verify OTP' : 'Reset Your Password'; ?>
                        </p>
                    </div>
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($success && !$show_otp_form): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(!$show_otp_form): ?>
                        <!-- Step 1: Request OTP -->
                        <form method="POST" action="" id="otpRequestForm">
                            <div class="mb-4">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-2"></i>Email Address
                                </label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       placeholder="Enter your registered email" required
                                       value="<?php echo isset($email) ? $email : ''; ?>">
                                <div class="form-text">
                                    We'll send a 6-digit OTP to your email for verification.
                                </div>
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" name="send_otp" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Send OTP
                                </button>
                            </div>
                            
                            <div class="text-center">
                                <p class="text-muted mb-0">
                                    Remember your password? 
                                    <a href="login.php" class="text-decoration-none">
                                        <i class="fas fa-sign-in-alt me-1"></i>Back to Login
                                    </a>
                                </p>
                            </div>
                        </form>
                    <?php else: ?>
                        <!-- Step 2: Verify OTP -->
                        <div class="text-center mb-4">
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo $success; ?>
                            </div>
                            <p class="text-muted">
                                Enter the 6-digit OTP sent to <strong><?php echo $_SESSION['reset_email']; ?></strong>
                            </p>
                        </div>
                        
                        <form method="POST" action="" id="otpVerifyForm">
                            <div class="mb-4">
                                <label for="otp" class="form-label">
                                    <i class="fas fa-key me-2"></i>OTP Code
                                </label>
                                <input type="text" class="form-control otp-input" id="otp" name="otp" 
                                       placeholder="123456" maxlength="6" required
                                       pattern="[0-9]{6}" title="Enter 6-digit OTP">
                                <div class="form-text">
                                    <div class="timer">
                                        <i class="fas fa-clock me-1"></i>
                                        OTP expires in: <span id="countdown">10:00</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 mb-3">
                                <button type="submit" name="verify_otp" class="btn btn-success">
                                    <i class="fas fa-check-circle me-2"></i>Verify OTP
                                </button>
                                <button type="button" id="resendOtp" class="btn btn-outline-primary" disabled>
                                    <i class="fas fa-redo me-2"></i>
                                    <span id="resendText">Resend OTP (60s)</span>
                                </button>
                            </div>
                            
                            <div class="text-center">
                                <a href="forgot-password.php" class="text-decoration-none">
                                    <i class="fas fa-arrow-left me-1"></i> Use different email
                                </a>
                            </div>
                        </form>
                        
                        <div class="alert alert-info mt-4">
                            <h6><i class="fas fa-info-circle me-2"></i>Didn't receive OTP?</h6>
                            <ul class="mb-0">
                                <li>Check your spam/junk folder</li>
                                <li>Make sure you entered the correct email</li>
                                <li>Wait 60 seconds before requesting a new OTP</li>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if($show_otp_form): ?>
        // Countdown timer for OTP
        let countdown = 600; // 10 minutes in seconds
        const countdownElement = document.getElementById('countdown');
        const resendButton = document.getElementById('resendOtp');
        const resendText = document.getElementById('resendText');
        
        function updateCountdown() {
            const minutes = Math.floor(countdown / 60);
            const seconds = countdown % 60;
            
            countdownElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            if (countdown <= 0) {
                clearInterval(timer);
                countdownElement.textContent = "Expired!";
                countdownElement.style.color = "red";
                alert("OTP has expired. Please request a new one.");
                window.location.href = 'forgot-password.php';
            }
            
            countdown--;
        }
        
        // Timer for resend button (60 seconds)
        let resendCountdown = 60;
        function updateResendButton() {
            if (resendCountdown <= 0) {
                resendButton.disabled = false;
                resendText.textContent = "Resend OTP";
                resendButton.classList.remove('btn-outline-primary');
                resendButton.classList.add('btn-primary');
                clearInterval(resendTimer);
            } else {
                resendText.textContent = `Resend OTP (${resendCountdown}s)`;
                resendCountdown--;
            }
        }
        
        // Start timers
        const timer = setInterval(updateCountdown, 1000);
        const resendTimer = setInterval(updateResendButton, 1000);
        
        updateCountdown();
        updateResendButton();
        
        // Resend OTP functionality
        resendButton.addEventListener('click', function() {
            if (!resendButton.disabled) {
                resendButton.disabled = true;
                resendCountdown = 60;
                resendButton.classList.remove('btn-primary');
                resendButton.classList.add('btn-outline-primary');
                updateResendButton();
                
                // Submit form to resend OTP
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const emailInput = document.createElement('input');
                emailInput.type = 'hidden';
                emailInput.name = 'email';
                emailInput.value = '<?php echo $_SESSION['reset_email']; ?>';
                
                const submitInput = document.createElement('input');
                submitInput.type = 'hidden';
                submitInput.name = 'send_otp';
                submitInput.value = '1';
                
                form.appendChild(emailInput);
                form.appendChild(submitInput);
                document.body.appendChild(form);
                form.submit();
            }
        });
        
        // Auto-focus OTP input
        document.getElementById('otp').focus();
        
        // Auto-submit when 6 digits are entered
        document.getElementById('otp').addEventListener('input', function(e) {
            if (this.value.length === 6) {
                document.getElementById('otpVerifyForm').submit();
            }
        });
        <?php endif; ?>
        
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (!form.checkValidity()) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                    form.classList.add('was-validated');
                });
            });
        });
    </script>
</body>
</html>