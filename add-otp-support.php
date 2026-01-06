<?php
// File: add-otp-support.php
echo "<h2>Adding OTP Support to Database</h2>";

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'srtravels';

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Add OTP fields to users table
$sql = "ALTER TABLE users 
        ADD COLUMN otp_code VARCHAR(10) NULL,
        ADD COLUMN otp_expiry DATETIME NULL,
        ADD COLUMN otp_attempts INT DEFAULT 0,
        ADD COLUMN last_otp_sent DATETIME NULL";

if (mysqli_query($conn, $sql)) {
    echo "Successfully added OTP support to users table.<br>";
} else {
    echo "Error adding OTP columns: " . mysqli_error($conn) . "<br>";
}

// Create OTP logs table for security
$sql = "CREATE TABLE IF NOT EXISTS otp_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) NOT NULL,
    otp_code VARCHAR(10) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    status ENUM('sent', 'verified', 'failed', 'expired') DEFAULT 'sent',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status)
)";

if (mysqli_query($conn, $sql)) {
    echo "Created OTP logs table.<br>";
} else {
    echo "Error creating OTP logs table: " . mysqli_error($conn) . "<br>";
}

echo "<h3 style='color: green;'>OTP support added successfully!</h3>";
echo "<p><a href='forgot-password.php' class='btn btn-primary'>Go to Password Reset</a></p>";
?>