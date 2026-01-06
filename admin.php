<?php
require_once 'config.php';

// Check if user is admin
if (!is_logged_in() || !is_admin()) {
    header("Location: login.php");
    exit();
}

// Get statistics
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users"))['count'];
$total_buses = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM buses"))['count'];
$seater_buses = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM buses WHERE bus_type = 'seater'"))['count'];
$sleeper_buses = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM buses WHERE bus_type = 'sleeper'"))['count'];
$today_bookings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM bookings WHERE DATE(booking_date) = CURDATE()"))['count'];
$total_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as total FROM bookings WHERE payment_status = 'paid'"))['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SR Travels</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">SR<span class="text-warning">TRAVELS</span> ADMIN</a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3">Welcome, <?php echo $_SESSION['full_name']; ?></span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-2">
                <div class="card">
                    <div class="card-body">
                        <nav class="nav flex-column">
                            <a class="nav-link active" href="admin.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                            <a class="nav-link" href="admin-buses.php">
                                <i class="fas fa-bus me-2"></i> Manage Buses
                            </a>
                            <a class="nav-link" href="admin-routes.php">
                                <i class="fas fa-route me-2"></i> Manage Routes
                            </a>
                            <a class="nav-link" href="admin-bookings.php">
                                <i class="fas fa-ticket-alt me-2"></i> Bookings
                            </a>
                            <a class="nav-link" href="admin-users.php">
                                <i class="fas fa-users me-2"></i> Users
                            </a>
                            <a class="nav-link" href="admin-payments.php">
                                <i class="fas fa-credit-card me-2"></i> Payments
                            </a>
                        </nav>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-10">
                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Total Users</h6>
                                        <h3><?php echo $total_users; ?></h3>
                                    </div>
                                    <i class="fas fa-users fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Total Buses</h6>
                                        <h3><?php echo $total_buses; ?></h3>
                                    </div>
                                    <i class="fas fa-bus fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Today's Bookings</h6>
                                        <h3><?php echo $today_bookings; ?></h3>
                                    </div>
                                    <i class="fas fa-ticket-alt fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Total Revenue</h6>
                                        <h3>₹<?php echo number_format($total_revenue, 2); ?></h3>
                                    </div>
                                    <i class="fas fa-rupee-sign fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Bus Type Distribution -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Bus Type Distribution</h5>
                                <div class="d-flex align-items-center mt-3">
                                    <div class="me-4">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="bg-info rounded me-2" style="width: 20px; height: 20px;"></div>
                                            <span>Seater Buses: <?php echo $seater_buses; ?></span>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-warning rounded me-2" style="width: 20px; height: 20px;"></div>
                                            <span>Sleeper Buses: <?php echo $sleeper_buses; ?></span>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="progress" style="width: 200px; height: 200px;">
                                            <?php
                                            $seater_percentage = $total_buses > 0 ? ($seater_buses / $total_buses) * 100 : 0;
                                            $sleeper_percentage = $total_buses > 0 ? ($sleeper_buses / $total_buses) * 100 : 0;
                                            ?>
                                            <div class="progress-bar bg-info" role="progressbar" 
                                                 style="width: <?php echo $seater_percentage; ?>%" 
                                                 aria-valuenow="<?php echo $seater_percentage; ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100"></div>
                                            <div class="progress-bar bg-warning" role="progressbar" 
                                                 style="width: <?php echo $sleeper_percentage; ?>%" 
                                                 aria-valuenow="<?php echo $sleeper_percentage; ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Recent Bookings</h5>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Booking ID</th>
                                                <th>User</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $recent_bookings = mysqli_query($conn, 
                                                "SELECT b.booking_id, u.full_name, b.total_amount, b.booking_status 
                                                 FROM bookings b 
                                                 JOIN users u ON b.user_id = u.id 
                                                 ORDER BY b.booking_date DESC LIMIT 5");
                                            while($booking = mysqli_fetch_assoc($recent_bookings)):
                                            ?>
                                            <tr>
                                                <td><?php echo $booking['booking_id']; ?></td>
                                                <td><?php echo $booking['full_name']; ?></td>
                                                <td>₹<?php echo $booking['total_amount']; ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $booking['booking_status'] == 'confirmed' ? 'success' : 
                                                             ($booking['booking_status'] == 'pending' ? 'warning' : 'danger'); 
                                                    ?>">
                                                        <?php echo ucfirst($booking['booking_status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Quick Actions</h5>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="admin-buses.php?action=add" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i> Add New Bus
                                    </a>
                                    <a href="admin-routes.php?action=add" class="btn btn-success">
                                        <i class="fas fa-route me-2"></i> Add New Route
                                    </a>
                                    <a href="admin-bookings.php" class="btn btn-info">
                                        <i class="fas fa-ticket-alt me-2"></i> View All Bookings
                                    </a>
                                    <a href="admin-users.php" class="btn btn-warning">
                                        <i class="fas fa-users me-2"></i> Manage Users
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
