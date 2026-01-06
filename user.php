<?php
require_once 'config.php';

if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user bookings
$bookings_query = "SELECT b.*, r.from_city, r.to_city, r.departure_time, r.arrival_time, 
                          bus.bus_name, bus.bus_type 
                   FROM bookings b 
                   JOIN bus_routes r ON b.route_id = r.id 
                   JOIN buses bus ON b.bus_id = bus.id 
                   WHERE b.user_id = $user_id 
                   ORDER BY b.booking_date DESC";
$bookings_result = mysqli_query($conn, $bookings_query);

// Get upcoming trips
$upcoming_query = "SELECT b.*, r.from_city, r.to_city, r.departure_time, bus.bus_name 
                   FROM bookings b 
                   JOIN bus_routes r ON b.route_id = r.id 
                   JOIN buses bus ON b.bus_id = bus.id 
                   WHERE b.user_id = $user_id 
                   AND b.travel_date >= CURDATE() 
                   AND b.booking_status = 'confirmed' 
                   ORDER BY b.travel_date ASC 
                   LIMIT 3";
$upcoming_result = mysqli_query($conn, $upcoming_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - SR Travels</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="index.php">SR<span class="text-warning">TRAVELS</span></a>
            <div class="d-flex align-items-center">
                <span class="me-3">Welcome, <?php echo $_SESSION['full_name']; ?></span>
                <a href="logout.php" class="btn btn-outline-primary btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3 mb-4">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-user-circle fa-4x text-primary"></i>
                        </div>
                        <h5><?php echo $_SESSION['full_name']; ?></h5>
                        <p class="text-muted"><?php echo $_SESSION['username']; ?></p>
                        <hr>
                        <div class="d-grid gap-2">
                            <a href="user.php" class="btn btn-primary">Dashboard</a>
                            <a href="booking.php" class="btn btn-outline-primary">Book New Trip</a>
                            <a href="bus-tracking.php" class="btn btn-outline-primary">Track Bus</a>
                            <a href="user-profile.php" class="btn btn-outline-primary">Edit Profile</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9">
                <!-- Upcoming Trips -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Upcoming Trips</h5>
                        <?php if(mysqli_num_rows($upcoming_result) > 0): ?>
                            <div class="row">
                                <?php while($trip = mysqli_fetch_assoc($upcoming_result)): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6><?php echo $trip['from_city']; ?> to <?php echo $trip['to_city']; ?></h6>
                                            <p class="mb-1"><small><?php echo format_date($trip['travel_date']); ?></small></p>
                                            <p class="mb-1"><small><?php echo format_time($trip['departure_time']); ?></small></p>
                                            <p class="mb-2"><small><?php echo $trip['bus_name']; ?></small></p>
                                            <span class="badge bg-success">Confirmed</span>
                                            <a href="booking-details.php?id=<?php echo $trip['id']; ?>" class="btn btn-sm btn-outline-primary mt-2">View Details</a>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No upcoming trips. <a href="booking.php">Book a new trip!</a></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Booking History -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Booking History</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Booking ID</th>
                                        <th>Route</th>
                                        <th>Date</th>
                                        <th>Seats</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($booking = mysqli_fetch_assoc($bookings_result)): ?>
                                    <tr>
                                        <td><?php echo $booking['booking_id']; ?></td>
                                        <td><?php echo $booking['from_city']; ?> to <?php echo $booking['to_city']; ?></td>
                                        <td><?php echo format_date($booking['travel_date']); ?></td>
                                        <td><?php echo $booking['seats_booked']; ?></td>
                                        <td>₹<?php echo $booking['total_amount']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $booking['booking_status'] == 'confirmed' ? 'success' : 
                                                     ($booking['booking_status'] == 'pending' ? 'warning' : 'danger'); 
                                            ?>">
                                                <?php echo ucfirst($booking['booking_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="booking-details.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                            <?php if($booking['booking_status'] == 'pending'): ?>
                                                <a href="payment.php?booking_id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-warning">Pay Now</a>
                                            <?php endif; ?>
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>