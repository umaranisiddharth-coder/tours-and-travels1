<?php
echo "<h2>Setting up SR Travels Database</h2>";

// Database connection
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'srtravels';

// Create connection
$conn = mysqli_connect($host, $user, $pass);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Drop database if exists
echo "Dropping database if exists...<br>";
$sql = "DROP DATABASE IF EXISTS $dbname";
if (mysqli_query($conn, $sql)) {
    echo "Database dropped successfully.<br>";
} else {
    echo "Error dropping database: " . mysqli_error($conn) . "<br>";
}

// Create database
echo "Creating database...<br>";
$sql = "CREATE DATABASE $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if (mysqli_query($conn, $sql)) {
    echo "Database created successfully.<br>";
} else {
    echo "Error creating database: " . mysqli_error($conn) . "<br>";
}

// Select database
mysqli_select_db($conn, $dbname);

// Create users table with reset token fields
echo "Creating users table...<br>";
$sql = "CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    address TEXT,
    reset_token VARCHAR(255) NULL,
    reset_token_expiry DATETIME NULL,
    user_type ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if (mysqli_query($conn, $sql)) {
    echo "Users table created successfully.<br>";
} else {
    echo "Error creating users table: " . mysqli_error($conn) . "<br>";
}

// Create admin table
echo "Creating admin table...<br>";
$sql = "CREATE TABLE admin (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    admin_role ENUM('super_admin', 'manager', 'operator') DEFAULT 'operator',
    permissions TEXT,
    last_login DATETIME,
    login_count INT DEFAULT 0,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
if (mysqli_query($conn, $sql)) {
    echo "Admin table created successfully.<br>";
} else {
    echo "Error creating admin table: " . mysqli_error($conn) . "<br>";
}

// Create buses table
echo "Creating buses table...<br>";
$sql = "CREATE TABLE buses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bus_number VARCHAR(20) UNIQUE NOT NULL,
    bus_name VARCHAR(100) NOT NULL,
    bus_type ENUM('seater', 'sleeper') NOT NULL,
    total_seats INT NOT NULL,
    available_seats INT NOT NULL,
    amenities TEXT,
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if (mysqli_query($conn, $sql)) {
    echo "Buses table created successfully.<br>";
} else {
    echo "Error creating buses table: " . mysqli_error($conn) . "<br>";
}

// Create bus_routes table
echo "Creating bus_routes table...<br>";
$sql = "CREATE TABLE bus_routes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bus_id INT,
    from_city VARCHAR(100) NOT NULL,
    to_city VARCHAR(100) NOT NULL,
    departure_time TIME NOT NULL,
    arrival_time TIME NOT NULL,
    duration VARCHAR(20),
    frequency VARCHAR(50),
    fare DECIMAL(10,2) NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES admin(id) ON DELETE SET NULL
)";
if (mysqli_query($conn, $sql)) {
    echo "Bus routes table created successfully.<br>";
} else {
    echo "Error creating bus_routes table: " . mysqli_error($conn) . "<br>";
}

// Create bookings table
echo "Creating bookings table...<br>";
$sql = "CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id VARCHAR(20) UNIQUE NOT NULL,
    user_id INT,
    bus_id INT,
    route_id INT,
    travel_date DATE NOT NULL,
    seats_booked TEXT NOT NULL,
    total_seats INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    booking_status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    payment_method VARCHAR(50),
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (bus_id) REFERENCES buses(id),
    FOREIGN KEY (route_id) REFERENCES bus_routes(id)
)";
if (mysqli_query($conn, $sql)) {
    echo "Bookings table created successfully.<br>";
} else {
    echo "Error creating bookings table: " . mysqli_error($conn) . "<br>";
}

// Create other tables...
echo "Creating other tables...<br>";

$tables = [
    "CREATE TABLE seat_availability (
        id INT PRIMARY KEY AUTO_INCREMENT,
        bus_id INT,
        travel_date DATE NOT NULL,
        seat_number VARCHAR(10) NOT NULL,
        seat_type ENUM('seater', 'sleeper_upper', 'sleeper_lower') NOT NULL,
        is_available BOOLEAN DEFAULT TRUE,
        booking_id INT NULL,
        UNIQUE KEY unique_seat (bus_id, travel_date, seat_number),
        FOREIGN KEY (bus_id) REFERENCES buses(id),
        FOREIGN KEY (booking_id) REFERENCES bookings(id)
    )",
    
    "CREATE TABLE payments (
        id INT PRIMARY KEY AUTO_INCREMENT,
        booking_id INT,
        payment_id VARCHAR(50) UNIQUE,
        amount DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(50) NOT NULL,
        payment_status VARCHAR(50) NOT NULL,
        transaction_id VARCHAR(100),
        payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (booking_id) REFERENCES bookings(id)
    )",
    
    "CREATE TABLE bus_tracking (
        id INT PRIMARY KEY AUTO_INCREMENT,
        bus_id INT,
        current_location VARCHAR(100),
        latitude DECIMAL(10,8),
        longitude DECIMAL(11,8),
        speed DECIMAL(5,2),
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (bus_id) REFERENCES buses(id)
    )",
    
    "CREATE TABLE admin_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        admin_id INT,
        activity VARCHAR(255) NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (admin_id) REFERENCES admin(id) ON DELETE CASCADE
    )",
    
    "CREATE TABLE notifications (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NULL,
        admin_id INT NULL,
        title VARCHAR(100) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('info', 'success', 'warning', 'danger') DEFAULT 'info',
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (admin_id) REFERENCES admin(id) ON DELETE CASCADE
    )"
];

foreach ($tables as $tableSql) {
    if (mysqli_query($conn, $tableSql)) {
        echo "Table created successfully.<br>";
    } else {
        echo "Error creating table: " . mysqli_error($conn) . "<br>";
    }
}

// Insert sample data
echo "Inserting sample data...<br>";

// Hash password for users (admin123)
$hashed_password = '$2y$10$QPLXqQdQLQcXN34n98yPmOZXKJs44dVK.B/E1vgrBkv6sNzxPGFXy';

// Insert users
$sql = "INSERT INTO users (username, email, password, full_name, phone, user_type) VALUES
        ('admin', 'admin@srtravels.com', '$hashed_password', 'Admin User', '9876543210', 'admin'),
        ('sid_umarane', 'sid@gmail.com', '$hashed_password', 'Sid Umarane', '9876543211', 'user'),
        ('manager1', 'manager@srtravels.com', '$hashed_password', 'Manager User', '9876543212', 'admin')";
if (mysqli_query($conn, $sql)) {
    echo "Users inserted successfully.<br>";
} else {
    echo "Error inserting users: " . mysqli_error($conn) . "<br>";
}

// Insert admin records
$sql = "INSERT INTO admin (user_id, admin_role, permissions) VALUES
        (1, 'super_admin', 'all'),
        (3, 'manager', 'manage_buses,manage_routes,view_reports')";
if (mysqli_query($conn, $sql)) {
    echo "Admin records inserted successfully.<br>";
} else {
    echo "Error inserting admin records: " . mysqli_error($conn) . "<br>";
}

// Insert buses
$sql = "INSERT INTO buses (bus_number, bus_name, bus_type, total_seats, available_seats, amenities) VALUES
        ('SRT-S001', 'Volvo AC Seater', 'seater', 40, 40, 'AC, WiFi, Charging Points, Water Bottle, Blanket'),
        ('SRT-S002', 'Mercedes Seater', 'seater', 40, 40, 'AC, WiFi, Entertainment, Snacks'),
        ('SRT-SL001', 'Volvo AC Sleeper', 'sleeper', 24, 24, 'AC, WiFi, Blanket, Privacy Curtain, Reading Light'),
        ('SRT-SL002', 'Scania Sleeper', 'sleeper', 24, 24, 'AC, WiFi, Blanket, Water Bottle, Snacks')";
if (mysqli_query($conn, $sql)) {
    echo "Buses inserted successfully.<br>";
} else {
    echo "Error inserting buses: " . mysqli_error($conn) . "<br>";
}

// Insert routes
$sql = "INSERT INTO bus_routes (bus_id, from_city, to_city, departure_time, arrival_time, duration, fare, created_by) VALUES
        (1, 'Delhi', 'Jaipur', '08:00:00', '14:00:00', '6h', 600.00, 1),
        (1, 'Jaipur', 'Delhi', '15:00:00', '21:00:00', '6h', 600.00, 1),
        (2, 'Mumbai', 'Pune', '10:30:00', '16:00:00', '5.5h', 500.00, 1),
        (3, 'Delhi', 'Jaipur', '22:00:00', '06:00:00', '8h', 800.00, 1),
        (4, 'Bangalore', 'Chennai', '21:00:00', '05:00:00', '8h', 750.00, 1)";
if (mysqli_query($conn, $sql)) {
    echo "Routes inserted successfully.<br>";
} else {
    echo "Error inserting routes: " . mysqli_error($conn) . "<br>";
}

echo "<h3 style='color: green;'>Database setup completed successfully!</h3>";
echo "<p>You can now <a href='login.php'>login</a> using:</p>";
echo "<ul>";
echo "<li><strong>Super Admin:</strong> admin@srtravels.com / admin123</li>";
echo "<li><strong>Manager:</strong> manager@srtravels.com / admin123</li>";
echo "<li><strong>User:</strong> sid@gmail.com / admin123</li>";
echo "</ul>";

mysqli_close($conn);
?>