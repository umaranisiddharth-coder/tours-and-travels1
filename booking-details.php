
<?php
require_once 'config.php';

if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}

$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

// Get booking details
$query = "SELECT b.*, r.from_city, r.to_city, r.departure_time, r.arrival_time, r.fare,
                 bus.bus_name, bus.bus_number, bus.bus_type, bus.amenities,
                 u.full_name, u.email, u.phone,
                 p.payment_id, p.payment_method, p.payment_status, p.payment_date
          FROM bookings b
          JOIN bus_routes r ON b.route_id = r.id
          JOIN buses bus ON b.bus_id = bus.id
          JOIN users u ON b.user_id = u.id
          LEFT JOIN payments p ON b.id = p.booking_id
          WHERE b.id = $booking_id AND b.user_id = $user_id";

$result = mysqli_query($conn, $query);
$booking = mysqli_fetch_assoc($result);

if (!$booking) {
    $_SESSION['error'] = 'Booking not found or you do not have permission to view it.';
    header("Location: user.php");
    exit();
}
?>
<!DOCTYPE