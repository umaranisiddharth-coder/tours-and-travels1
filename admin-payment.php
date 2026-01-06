<?php
require_once 'config.php';

// Check admin permissions
if (!is_logged_in() || !is_admin()) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

// Handle manual booking creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_booking'])) {
    $user_id = intval($_POST['user_id']);
    $route_id = intval($_POST['route_id']);
    $travel_date = sanitize_input($_POST['travel_date']);
    $seats = sanitize_input($_POST['seats']);
    $total_amount = floatval($_POST['total_amount']);
    $payment_status = sanitize_input($_POST['payment_status']);
    
    // Validate seats
    $seat_array = explode(',', $seats);
    $total_seats = count($seat_array);
    
    // Check seat availability
    $available = true;
    $bus_id = 0;
    
    $route_query = "SELECT bus_id FROM bus_routes WHERE id = $route_id";
    $route_result = mysqli_query($conn, $route_query);
    if ($route_result && mysqli_num_rows($route_result) > 0) {
        $route = mysqli_fetch_assoc($route_result);
        $bus_id = $route['bus_id'];
        
        foreach ($seat_array as $seat) {
            $seat = trim($seat);
            $check_sql = "SELECT * FROM seat_availability 
                         WHERE bus_id = $bus_id 
                         AND travel_date = '$travel_date' 
                         AND seat_number = '$seat' 
                         AND is_available = FALSE";
            $check_result = mysqli_query($conn, $check_sql);
            if (mysqli_num_rows($check_result) > 0) {
                $available = false;
                $error = "Seat $seat is already booked for this date.";
                break;
            }
        }
    }
    
    if ($available) {
        // Generate booking ID
        $booking_id = generate_booking_id();
        
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Create booking
            $booking_sql = "INSERT INTO bookings (booking_id, user_id, bus_id, route_id, travel_date, 
                            seats_booked, total_seats, total_amount, booking_status, payment_status, payment_method) 
                            VALUES ('$booking_id', $user_id, $bus_id, $route_id, '$travel_date', 
                            '$seats', $total_seats, $total_amount, 'confirmed', '$payment_status', 'admin_manual')";
            
            if (mysqli_query($conn, $booking_sql)) {
                $booking_insert_id = mysqli_insert_id($conn);
                
                // Mark seats as booked
                foreach ($seat_array as $seat) {
                    $seat = trim($seat);
                    $seat_type = strpos($seat, 'U') === 0 ? 'sleeper_upper' : 
                                (strpos($seat, 'L') === 0 ? 'sleeper_lower' : 'seater');
                    
                    $seat_query = "INSERT INTO seat_availability (bus_id, travel_date, seat_number, seat_type, is_available, booking_id) 
                                  VALUES ($bus_id, '$travel_date', '$seat', '$seat_type', FALSE, $booking_insert_id)";
                    mysqli_query($conn, $seat_query);
                }
                
                // Create payment record if paid
                if ($payment_status == 'paid') {
                    $payment_id = 'ADM-PAY-' . time() . '-' . rand(1000, 9999);
                    $payment_query = "INSERT INTO payments (booking_id, payment_id, amount, payment_method, payment_status) 
                                     VALUES ($booking_insert_id, '$payment_id', $total_amount, 'admin_manual', 'completed')";
                    mysqli_query($conn, $payment_query);
                }
                
                // Commit transaction
                mysqli_commit($conn);
                
                $success = "Booking created successfully! Booking ID: $booking_id";
                log_admin_activity("Created manual booking: $booking_id for user ID: $user_id");
                
                // Reset form
                unset($_POST);
            } else {
                throw new Exception("Error creating booking: " . mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Booking creation failed: " . $e->getMessage();
        }
    }
}

// Get all users for dropdown
$users_query = "SELECT id, username, email, full_name FROM users WHERE user_type = 'user' ORDER BY full_name";
$users_result = mysqli_query($conn, $users_query);

// Get all routes for dropdown
$routes_query = "SELECT r.*, b.bus_name, b.bus_number FROM bus_routes r 
                 JOIN buses b ON r.bus_id = b.id 
                 WHERE b.status = 'active' 
                 ORDER BY r.from_city, r.to_city";
$routes_result = mysqli_query($conn, $routes_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Manual Booking - SR Travels Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .seat-layout {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin: 20px 0;
            max-width: 300px;
        }
        
        .seat {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #dee2e6;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .seat.selected {
            background-color: #28a745;
            color: white;
            border-color: #28a745;
        }
        
        .seat.booked {
            background-color: #dc3545;
            color: white;
            border-color: #dc3545;
            cursor: not-allowed;
        }
        
        .seat.available:hover {
            background-color: #e9ecef;
        }
    </style>
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
                            <a class="nav-link active" href="admin-payment.php">Create Booking</a>
                            <a class="nav-link" href="admin-bookings.php">View Bookings</a>
                            <a class="nav-link" href="admin-users.php">Users</a>
                            <a class="nav-link" href="admin-payments.php">Payments</a>
                            <a class="nav-link" href="admin-reports.php">Reports</a>
                        </nav>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-10">
                <h2 class="mb-4">Create Manual Booking</h2>
                
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body">
                                <form method="POST" action="" id="bookingForm">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Select User</label>
                                            <select class="form-select" name="user_id" id="user_id" required>
                                                <option value="">Select a user</option>
                                                <?php while($user = mysqli_fetch_assoc($users_result)): ?>
                                                <option value="<?php echo $user['id']; ?>" 
                                                    <?php echo isset($_POST['user_id']) && $_POST['user_id'] == $user['id'] ? 'selected' : ''; ?>>
                                                    <?php echo $user['full_name']; ?> (<?php echo $user['email']; ?>)
                                                </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Select Route</label>
                                            <select class="form-select" name="route_id" id="route_id" required>
                                                <option value="">Select a route</option>
                                                <?php mysqli_data_seek($routes_result, 0); ?>
                                                <?php while($route = mysqli_fetch_assoc($routes_result)): ?>
                                                <option value="<?php echo $route['id']; ?>" 
                                                    data-fare="<?php echo $route['fare']; ?>"
                                                    data-bus="<?php echo $route['bus_id']; ?>"
                                                    <?php echo isset($_POST['route_id']) && $_POST['route_id'] == $route['id'] ? 'selected' : ''; ?>>
                                                    <?php echo $route['from_city']; ?> to <?php echo $route['to_city']; ?> 
                                                    (<?php echo $route['bus_name']; ?> - ₹<?php echo $route['fare']; ?>)
                                                </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Travel Date</label>
                                            <input type="date" class="form-control" name="travel_date" id="travel_date" 
                                                   value="<?php echo isset($_POST['travel_date']) ? $_POST['travel_date'] : date('Y-m-d'); ?>" 
                                                   min="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Seats (comma-separated)</label>
                                            <input type="text" class="form-control" name="seats" id="seats" 
                                                   value="<?php echo isset($_POST['seats']) ? $_POST['seats'] : ''; ?>" 
                                                   placeholder="e.g., A1,A2,B3" required>
                                            <div class="form-text">Enter seat numbers separated by commas</div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Total Amount (₹)</label>
                                            <input type="number" class="form-control" name="total_amount" id="total_amount" 
                                                   value="<?php echo isset($_POST['total_amount']) ? $_POST['total_amount'] : ''; ?>" 
                                                   step="0.01" min="0" required>
                                            <div class="form-text">Amount will be auto-calculated based on seats</div>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Payment Status</label>
                                            <select class="form-select" name="payment_status" required>
                                                <option value="paid" <?php echo isset($_POST['payment_status']) && $_POST['payment_status'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                                <option value="pending" <?php echo isset($_POST['payment_status']) && $_POST['payment_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <button type="submit" name="create_booking" class="btn btn-primary btn-lg">
                                            <i class="fas fa-check-circle me-2"></i> Create Booking
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                                            <i class="fas fa-redo me-2"></i> Reset
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Booking Summary</h5>
                                <div id="bookingSummary">
                                    <p class="text-muted">Select a route and enter seat details to see summary</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mt-4">
                            <div class="card-body">
                                <h5 class="card-title">Quick Actions</h5>
                                <div class="d-grid gap-2">
                                    <a href="admin-bookings.php" class="btn btn-outline-primary">
                                        <i class="fas fa-list me-2"></i> View All Bookings
                                    </a>
                                    <a href="admin-users.php" class="btn btn-outline-success">
                                        <i class="fas fa-users me-2"></i> Manage Users
                                    </a>
                                    <a href="admin-payments.php" class="btn btn-outline-info">
                                        <i class="fas fa-credit-card me-2"></i> Payment Records
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Available Seats Section -->
                <div class="card mt-4" id="seatMapSection" style="display: none;">
                    <div class="card-body">
                        <h5 class="card-title">Available Seats</h5>
                        <p id="seatInfo" class="text-muted"></p>
                        <div id="seatMap" class="seat-layout"></div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearSeatSelection()">
                                <i class="fas fa-times me-1"></i> Clear Selection
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedSeats = [];
        let seatPrice = 0;
        
        function updateBookingSummary() {
            const routeSelect = document.getElementById('route_id');
            const travelDate = document.getElementById('travel_date').value;
            const seatsInput = document.getElementById('seats');
            const amountInput = document.getElementById('total_amount');
            
            if (routeSelect.value && travelDate && seatsInput.value) {
                const selectedOption = routeSelect.options[routeSelect.selectedIndex];
                const routeName = selectedOption.text;
                const fare = parseFloat(selectedOption.getAttribute('data-fare'));
                const busId = selectedOption.getAttribute('data-bus'));
                
                const seats = seatsInput.value.split(',').map(s => s.trim()).filter(s => s);
                const totalSeats = seats.length;
                const totalAmount = fare * totalSeats;
                
                seatPrice = fare;
                
                // Update amount field
                amountInput.value = totalAmount;
                
                // Update summary
                document.getElementById('bookingSummary').innerHTML = `
                    <p><strong>Route:</strong> ${routeName}</p>
                    <p><strong>Travel Date:</strong> ${travelDate}</p>
                    <p><strong>Selected Seats:</strong> ${seats.join(', ')}</p>
                    <p><strong>Number of Seats:</strong> ${totalSeats}</p>
                    <p><strong>Fare per Seat:</strong> ₹${fare}</p>
                    <p><strong>Total Amount:</strong> ₹${totalAmount}</p>
                `;
                
                // Show seat map and load available seats
                if (busId && travelDate) {
                    loadAvailableSeats(busId, travelDate);
                }
            } else {
                document.getElementById('bookingSummary').innerHTML = 
                    '<p class="text-muted">Select a route and enter seat details to see summary</p>';
            }
        }
        
        function loadAvailableSeats(busId, travelDate) {
            fetch(`get-available-seats.php?bus_id=${busId}&travel_date=${travelDate}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displaySeatMap(data.seats);
                    } else {
                        document.getElementById('seatInfo').textContent = 'Error loading seat information';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('seatInfo').textContent = 'Error loading seat information';
                });
        }
        
        function displaySeatMap(seats) {
            const seatMap = document.getElementById('seatMap');
            seatMap.innerHTML = '';
            
            // Sample seat layout - in real app, this would come from database
            const seatLayout = [
                'U1', 'U2', 'U3', 'U4',
                'L1', 'L2', 'L3', 'L4',
                'A1', 'A2', 'A3', 'A4',
                'B1', 'B2', 'B3', 'B4'
            ];
            
            seatLayout.forEach(seatNumber => {
                const seatDiv = document.createElement('div');
                seatDiv.className = 'seat';
                seatDiv.textContent = seatNumber;
                
                // Check if seat is booked
                const isBooked = seats.some(s => s.seat_number === seatNumber && !s.is_available);
                
                if (isBooked) {
                    seatDiv.className += ' booked';
                    seatDiv.title = 'Already booked';
                } else {
                    seatDiv.className += ' available';
                    seatDiv.onclick = () => toggleSeat(seatNumber, seatDiv);
                }
                
                seatMap.appendChild(seatDiv);
            });
            
            document.getElementById('seatMapSection').style.display = 'block';
            document.getElementById('seatInfo').textContent = `Click on available seats to select. Price per seat: ₹${seatPrice}`;
        }
        
        function toggleSeat(seatNumber, seatElement) {
            const seatsInput = document.getElementById('seats');
            let currentSeats = seatsInput.value.split(',').map(s => s.trim()).filter(s => s);
            
            const index = currentSeats.indexOf(seatNumber);
            
            if (index === -1) {
                // Add seat
                currentSeats.push(seatNumber);
                seatElement.classList.add('selected');
                selectedSeats.push(seatNumber);
            } else {
                // Remove seat
                currentSeats.splice(index, 1);
                seatElement.classList.remove('selected');
                selectedSeats = selectedSeats.filter(s => s !== seatNumber);
            }
            
            seatsInput.value = currentSeats.join(', ');
            updateBookingSummary();
        }
        
        function clearSeatSelection() {
            selectedSeats = [];
            document.getElementById('seats').value = '';
            document.querySelectorAll('.seat.selected').forEach(seat => {
                seat.classList.remove('selected');
            });
    
            updateBookingSummary();
        }
        
        function resetForm() {
            document.getElementById('bookingForm').reset();
            selectedSeats = [];
            document.getElementById('bookingSummary').innerHTML = 
                '<p class="text-muted">Select a route and enter seat details to see summary</p>';
            document.getElementById('seatMapSection').style.display = 'none';
        }
        
        // Event listeners
        document.getElementById('route_id').addEventListener('change', updateBookingSummary);
        document.getElementById('travel_date').addEventListener('change', updateBookingSummary);
        document.getElementById('seats').addEventListener('input', updateBookingSummary);
        
        // Initial update
        updateBookingSummary();
    </script>
</body>
</html>