<?php
require_once 'config.php';

// Check admin permissions
if (!is_logged_in() || !is_admin()) {
    header("Location: login.php");
    exit();
}

// Handle booking status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_status'])) {
        $booking_id = intval($_POST['booking_id']);
        $status = sanitize_input($_POST['status']);
        
        $sql = "UPDATE bookings SET booking_status = '$status' WHERE id = $booking_id";
        if (mysqli_query($conn, $sql)) {
            $success = "Booking status updated successfully!";
            log_admin_activity("Updated booking status for ID: $booking_id to $status");
        } else {
            $error = "Error updating booking status: " . mysqli_error($conn);
        }
    }
    
    if (isset($_POST['cancel_booking'])) {
        $booking_id = intval($_POST['booking_id']);
        
        // Update booking status to cancelled
        $sql = "UPDATE bookings SET booking_status = 'cancelled' WHERE id = $booking_id";
        if (mysqli_query($conn, $sql)) {
            // Free up the seats
            $free_seats_sql = "UPDATE seat_availability SET is_available = TRUE, booking_id = NULL WHERE booking_id = $booking_id";
            mysqli_query($conn, $free_seats_sql);
            
            $success = "Booking cancelled successfully!";
            log_admin_activity("Cancelled booking ID: $booking_id");
        } else {
            $error = "Error cancelling booking: " . mysqli_error($conn);
        }
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? sanitize_input($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize_input($_GET['date_to']) : '';
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

// Build query with filters
$query = "SELECT b.*, u.full_name, u.email, u.phone, 
                 r.from_city, r.to_city, r.departure_time, r.fare,
                 bus.bus_name, bus.bus_number
          FROM bookings b
          JOIN users u ON b.user_id = u.id
          JOIN bus_routes r ON b.route_id = r.id
          JOIN buses bus ON b.bus_id = bus.id
          WHERE 1=1";

if ($status_filter) {
    $query .= " AND b.booking_status = '$status_filter'";
}

if ($date_from) {
    $query .= " AND b.booking_date >= '$date_from'";
}

if ($date_to) {
    $query .= " AND b.booking_date <= '$date_to 23:59:59'";
}

if ($search) {
    $query .= " AND (b.booking_id LIKE '%$search%' OR u.full_name LIKE '%$search%' OR u.email LIKE '%$search%')";
}

$query .= " ORDER BY b.booking_date DESC";

$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - SR Travels</title>
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
                <a href="admin.php" class="btn btn-outline-light btn-sm me-2">Dashboard</a>
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
                            <a class="nav-link" href="admin.php">Dashboard</a>
                            <a class="nav-link" href="admin-buses.php">Manage Buses</a>
                            <a class="nav-link" href="admin-routes.php">Manage Routes</a>
                            <a class="nav-link active" href="admin-bookings.php">Bookings</a>
                            <a class="nav-link" href="admin-users.php">Users</a>
                            <a class="nav-link" href="admin-payments.php">Payments</a>
                            <a class="nav-link" href="admin-reports.php">Reports</a>
                        </nav>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-10">
                <h2 class="mb-4">Manage Bookings</h2>
                
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if(isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <!-- Filter Form -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="">All Status</option>
                                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">From Date</label>
                                <input type="date" class="form-control" name="date_from" value="<?php echo $date_from; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">To Date</label>
                                <input type="date" class="form-control" name="date_to" value="<?php echo $date_to; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Search</label>
                                <input type="text" class="form-control" name="search" value="<?php echo $search; ?>" 
                                       placeholder="Booking ID, Name, Email">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="admin-bookings.php" class="btn btn-outline-secondary">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Bookings Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Booking ID</th>
                                        <th>Passenger</th>
                                        <th>Route</th>
                                        <th>Travel Date</th>
                                        <th>Bus</th>
                                        <th>Seats</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($booking = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?php echo $booking['booking_id']; ?></td>
                                        <td>
                                            <div><strong><?php echo $booking['full_name']; ?></strong></div>
                                            <small class="text-muted"><?php echo $booking['email']; ?></small>
                                        </td>
                                        <td>
                                            <?php echo $booking['from_city']; ?> → <?php echo $booking['to_city']; ?><br>
                                            <small><?php echo format_time($booking['departure_time']); ?></small>
                                        </td>
                                        <td><?php echo format_date($booking['travel_date']); ?></td>
                                        <td>
                                            <?php echo $booking['bus_name']; ?><br>
                                            <small><?php echo $booking['bus_number']; ?></small>
                                        </td>
                                        <td>
                                            <?php echo $booking['seats_booked']; ?><br>
                                            <small><?php echo $booking['total_seats']; ?> seat(s)</small>
                                        </td>
                                        <td>₹<?php echo $booking['total_amount']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $booking['booking_status'] == 'confirmed' ? 'success' : 
                                                     ($booking['booking_status'] == 'pending' ? 'warning' : 
                                                     ($booking['booking_status'] == 'completed' ? 'info' : 'danger')); 
                                            ?>">
                                                <?php echo ucfirst($booking['booking_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $booking['payment_status'] == 'paid' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($booking['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary me-2" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#viewModal<?php echo $booking['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-info me-2" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#statusModal<?php echo $booking['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if($booking['booking_status'] != 'cancelled'): ?>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#cancelModal<?php echo $booking['id']; ?>">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    
                                    <!-- View Booking Modal -->
                                    <div class="modal fade" id="viewModal<?php echo $booking['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Booking Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <h6>Passenger Information</h6>
                                                            <p><strong>Name:</strong> <?php echo $booking['full_name']; ?></p>
                                                            <p><strong>Email:</strong> <?php echo $booking['email']; ?></p>
                                                            <p><strong>Phone:</strong> <?php echo $booking['phone']; ?></p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <h6>Booking Information</h6>
                                                            <p><strong>Booking ID:</strong> <?php echo $booking['booking_id']; ?></p>
                                                            <p><strong>Booking Date:</strong> <?php echo format_date($booking['booking_date']) . ' ' . format_time($booking['booking_date']); ?></p>
                                                            <p><strong>Travel Date:</strong> <?php echo format_date($booking['travel_date']); ?></p>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <h6>Journey Details</h6>
                                                            <p><strong>Route:</strong> <?php echo $booking['from_city']; ?> to <?php echo $booking['to_city']; ?></p>
                                                            <p><strong>Departure:</strong> <?php echo format_time($booking['departure_time']); ?></p>
                                                            <p><strong>Bus:</strong> <?php echo $booking['bus_name']; ?> (<?php echo $booking['bus_number']; ?>)</p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <h6>Fare Details</h6>
                                                            <p><strong>Seats:</strong> <?php echo $booking['seats_booked']; ?></p>
                                                            <p><strong>Base Fare:</strong> ₹<?php echo $booking['fare']; ?> per seat</p>
                                                            <p><strong>Total Amount:</strong> ₹<?php echo $booking['total_amount']; ?></p>
                                                            <p><strong>Payment Status:</strong> <?php echo ucfirst($booking['payment_status']); ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Status Update Modal -->
                                    <div class="modal fade" id="statusModal<?php echo $booking['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Update Booking Status</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label">Booking Status</label>
                                                            <select class="form-select" name="status" required>
                                                                <option value="pending" <?php echo $booking['booking_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                                <option value="confirmed" <?php echo $booking['booking_status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                                <option value="completed" <?php echo $booking['booking_status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                                <option value="cancelled" <?php echo $booking['booking_status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Cancel Booking Modal -->
                                    <div class="modal fade" id="cancelModal<?php echo $booking['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Cancel Booking</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                        <p>Are you sure you want to cancel booking <strong><?php echo $booking['booking_id']; ?></strong>?</p>
                                                        <p class="text-danger">This will free up the booked seats and mark the booking as cancelled.</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
                                                        <button type="submit" name="cancel_booking" class="btn btn-danger">Yes, Cancel</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>