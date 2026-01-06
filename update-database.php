<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'srtravels';

// Create connection
$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "<h2>Updating Database for SR Travels</h2>";

// Check if reset_token column exists
$check_sql = "SHOW COLUMNS FROM users LIKE 'reset_token'";
$result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($result) == 0) {
    // Add reset token fields if they don't exist
    $sql = "ALTER TABLE users 
            ADD COLUMN reset_token VARCHAR(255) NULL,
            ADD COLUMN reset_token_expiry DATETIME NULL";
    
    if (mysqli_query($conn, $sql)) {
        echo "Added reset token fields to users table.<br>";
    } else {
        echo "Error adding columns: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "Reset token columns already exist.<br>";
}

// Create password_resets table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS password_resets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    INDEX idx_token (token),
    INDEX idx_email (email)
)";

if (mysqli_query($conn, $sql)) {
    echo "Created password_resets table (if it didn't exist).<br>";
} else {
    echo "Error creating table: " . mysqli_error($conn) . "<br>";
}

echo "<h3 style='color: green;'>Database update completed!</h3>";
echo "<p><a href='forgot-password.php'>Test Password Reset</a></p>";
?>