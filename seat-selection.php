<?php
require_once 'config.php';

if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}

$route_id = isset($_GET['route_id']) ? intval($_GET['route_id']) : 0;
$travel_date = isset($_GET['travel_date']) ? sanitize_input($_GET['travel_date']) : date('Y-m-d');

// Get route details
$route_query = "SELECT r.*, b.bus_number, b.bus_name, b.bus_type, b.total_seats 
                FROM bus_routes r 
                JOIN buses b ON r.bus_id = b.id 
                WHERE r.id = $route_id";
$route_result = mysqli_query($conn, $route_query);
$route = mysqli_fetch_assoc($route_result);

if (!$route) {
    die("Invalid route selected.");
}

// Get booked seats for this bus on selected date
$booked_seats_query = "SELECT seat_number FROM seat_availability 
                      WHERE bus_id = {$route['bus_id']} 
                      AND travel_date = '$travel_date' 
                      AND is_available = FALSE";
$booked_seats_result = mysqli_query($conn, $booked_seats_query);
$booked_seats = [];
while($seat = mysqli_fetch_assoc($booked_seats_result)) {
    $booked_seats[] = $seat['seat_number'];
}

// Handle seat selection
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $selected_seats = isset($_POST['selected_seats']) ? $_POST['selected_seats'] : [];
    
    if (count($selected_seats) == 0) {
        $error = "Please select at least one seat.";
    } else {
        // Store selected seats in session and redirect to payment
        $_SESSION['selected_seats'] = $selected_seats;
        $_SESSION['route_id'] = $route_id;
        $_SESSION['travel_date'] = $travel_date;
        $_SESSION['total_amount'] = count($selected_seats) * $route['fare'];
        
        header("Location: payment.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Seats - SR Travels</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .seat-layout-container {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 30px;
        }
        
        .bus-front {
            text-align: center;
            padding: 15px;
            background: #333;
            color: white;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        
        .seat-row {
            display: flex;
            justify-content: center;
            margin-bottom: 15px;
        }
        
        .seat {
            width: 45px;
            height: 45px;
            margin: 0 5px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-weight: 500;
            user-select: none;
            font-size: 0.9rem;
        }
        
        .seat.available {
            background: #28a745;
            color: white;
        }
        
        .seat.booked {
            background: #dc3545;
            color: white;
            cursor: not-allowed;
        }
        
        .seat.selected {
            background: #ffc107;
            color: #333;
        }
        
        .seat.aisle {
            visibility: hidden;
            width: 40px;
        }
        
        .seat.driver {
            background: #6c757d;
            color: white;
            cursor: default;
        }
        
        /* Sleeper specific seats */
        .seat.sleeper-upper {
            background: #3498db;
        }
        
        .seat.sleeper-lower {
            background: #2ecc71;
        }
        
        .booking-summary {
            position: sticky;
            top: 20px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            margin-right: 15px;
        }
        
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 3px;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="index.php">SR<span class="text-warning">TRAVELS</span></a>
            <div class="d-flex align-items-center">
                <a href="booking.php" class="btn btn-outline-secondary btn-sm">Back to Buses</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <h2 class="mb-4">Select Your Seats</h2>
        
        <div class="row">
            <div class="col-lg-8">
                <!-- Bus Information -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5><?php echo $route['from_city']; ?> to <?php echo $route['to_city']; ?></h5>
                                <p class="mb-1"><i class="fas fa-bus"></i> <?php echo $route['bus_name']; ?></p>
                                <p class="mb-1"><i class="fas fa-calendar"></i> <?php echo format_date($travel_date); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><i class="fas fa-clock"></i> <?php echo format_time($route['departure_time']); ?> - <?php echo format_time($route['arrival_time']); ?></p>
                                <p class="mb-1"><i class="fas fa-tag"></i> ₹<?php echo $route['fare']; ?> per seat</p>
                                <span class="badge <?php echo $route['bus_type'] == 'seater' ? 'bg-info' : 'bg-warning'; ?>">
                                    <?php echo ucfirst($route['bus_type']); ?> Bus
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Seat Layout -->
                <div class="seat-layout-container">
                    <?php if($route['bus_type'] == 'seater'): ?>
                        <!-- Seater Bus Layout -->
                        <div class="bus-front">
                            <i class="fas fa-bus"></i> Front Entrance
                        </div>
                        
                        <form id="seatForm" method="POST">
                            <div id="seatMap">
                                <?php
                                // Generate seater layout (2x2, 10 rows = 40 seats)
                                $rows = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
                                
                                foreach($rows as $row) {
                                    echo '<div class="seat-row">';
                                    for($i = 1; $i <= 4; $i++) {
                                        $seat_number = $row . $i;
                                        $is_booked = in_array($seat_number, $booked_seats);
                                        
                                        echo '<div class="seat ' . ($is_booked ? 'booked' : 'available') . '" 
                                                  data-seat="' . $seat_number . '"
                                                  onclick="toggleSeat(this, ' . ($is_booked ? 'false' : 'true') . ')">';
                                        echo $seat_number;
                                        echo '</div>';
                                        
                                        // Add aisle after seat 2
                                        if($i == 2) {
                                            echo '<div class="seat aisle"></div>';
                                        }
                                    }
                                    echo '</div>';
                                }
                                
                                // Driver seat
                                echo '<div class="seat-row"><div class="seat driver">DRIVER</div></div>';
                                ?>
                            </div>
                            
                            <!-- Seat Legend -->
                            <div class="d-flex flex-wrap mt-4">
                                <div class="legend-item">
                                    <div class="legend-color" style="background: #28a745;"></div>
                                    <span>Available</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background: #dc3545;"></div>
                                    <span>Booked</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background: #ffc107;"></div>
                                    <span>Selected</span>
                                </div>
                            </div>
                        </form>
                        
                    <?php else: ?>
                        <!-- Sleeper Bus Layout -->
                        <div class="bus-front">
                            <i class="fas fa-bed"></i> Sleeper Bus Layout
                        </div>
                        
                        <form id="seatForm" method="POST">
                            <div id="seatMap">
                                <?php
                                // Generate sleeper layout (12 bays, each with upper and lower berth)
                                for($bay = 1; $bay <= 12; $bay++) {
                                    echo '<div class="seat-row">';
                                    
                                    // Upper berth
                                    $upper_seat = 'U' . $bay;
                                    $upper_booked = in_array($upper_seat, $booked_seats);
                                    echo '<div class="seat sleeper-upper ' . ($upper_booked ? 'booked' : 'available') . '" 
                                              data-seat="' . $upper_seat . '"
                                              onclick="toggleSeat(this, ' . ($upper_booked ? 'false' : 'true') . ')">';
                                    echo $upper_seat;
                                    echo '</div>';
                                    
                                    // Space
                                    echo '<div class="seat aisle" style="width: 20px;"></div>';
                                    
                                    // Lower berth
                                    $lower_seat = 'L' . $bay;
                                    $lower_booked = in_array($lower_seat, $booked_seats);
                                    echo '<div class="seat sleeper-lower ' . ($lower_booked ? 'booked' : 'available') . '" 
                                              data-seat="' . $lower_seat . '"
                                              onclick="toggleSeat(this, ' . ($lower_booked ? 'false' : 'true') . ')">';
                                    echo $lower_seat;
                                    echo '</div>';
                                    
                                    echo '</div>';
                                }
                                ?>
                            </div>
                            
                            <!-- Sleeper Legend -->
                            <div class="d-flex flex-wrap mt-4">
                                <div class="legend-item">
                                    <div class="legend-color" style="background: #3498db;"></div>
                                    <span>Upper Berth</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background: #2ecc71;"></div>
                                    <span>Lower Berth</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background: #ffc107;"></div>
                                    <span>Selected</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background: #dc3545;"></div>
                                    <span>Booked</span>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Booking Summary -->
            <div class="col-lg-4">
                <div class="booking-summary card">
                    <div class="card-body">
                        <h5 class="card-title">Booking Summary</h5>
                        <div class="mb-3">
                            <p><strong>Route:</strong> <?php echo $route['from_city']; ?> to <?php echo $route['to_city']; ?></p>
                            <p><strong>Date:</strong> <?php echo format_date($travel_date); ?></p>
                            <p><strong>Time:</strong> <?php echo format_time($route['departure_time']); ?></p>
                            <p><strong>Bus Type:</strong> <?php echo ucfirst($route['bus_type']); ?></p>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-3">
                            <h6>Selected Seats:</h6>
                            <div id="selectedSeatsList" class="mb-2">No seats selected</div>
                            <small class="text-muted">Max 6 seats per booking</small>
                        </div>
                        
                        <div class="mb-3">
                            <h6>Fare Details:</h6>
                            <div class="d-flex justify-content-between">
                                <span>Base Fare:</span>
                                <span>₹<span id="baseFare">0</span></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Taxes (18%):</span>
                                <span>₹<span id="taxes">0</span></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Total Amount:</span>
                                <span>₹<span id="totalAmount">0</span></span>
                            </div>
                        </div>
                        
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" form="seatForm" class="btn btn-primary" id="proceedBtn" disabled>
                                <i class="fas fa-credit-card me-2"></i> Proceed to Payment
                            </button>
                            <a href="booking.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Back to Buses
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedSeats = [];
        const maxSeats = 6;
        const seatPrice = <?php echo $route['fare']; ?>;
        
        function toggleSeat(seatElement, isAvailable) {
            if (!isAvailable) return;
            
            const seatNumber = seatElement.getAttribute('data-seat');
            const index = selectedSeats.indexOf(seatNumber);
            
            if (index === -1) {
                // Add seat
                if (selectedSeats.length >= maxSeats) {
                    alert(`You can select up to ${maxSeats} seats only.`);
                    return;
                }
                selectedSeats.push(seatNumber);
                seatElement.classList.remove('available', 'sleeper-upper', 'sleeper-lower');
                seatElement.classList.add('selected');
            } else {
                // Remove seat
                selectedSeats.splice(index, 1);
                seatElement.classList.remove('selected');
                
                // Restore original class
                if (seatNumber.startsWith('U')) {
                    seatElement.classList.add('sleeper-upper', 'available');
                } else if (seatNumber.startsWith('L')) {
                    seatElement.classList.add('sleeper-lower', 'available');
                } else {
                    seatElement.classList.add('available');
                }
            }
            
            updateBookingSummary();
        }
        
        function updateBookingSummary() {
            const selectedSeatsList = document.getElementById('selectedSeatsList');
            const seatCount = selectedSeats.length;
            
            // Update selected seats display
            if (seatCount === 0) {
                selectedSeatsList.innerHTML = '<span class="text-muted">No seats selected</span>';
            } else {
                selectedSeatsList.innerHTML = selectedSeats.join(', ');
            }
            
            // Update fare calculation
            const baseFareAmount = seatCount * seatPrice;
            const taxAmount = baseFareAmount * 0.18;
            const totalAmount = baseFareAmount + taxAmount;
            
            document.getElementById('baseFare').textContent = baseFareAmount;
            document.getElementById('taxes').textContent = Math.round(taxAmount);
            document.getElementById('totalAmount').textContent = Math.round(totalAmount);
            
            // Update proceed button
            const proceedBtn = document.getElementById('proceedBtn');
            proceedBtn.disabled = seatCount === 0;
            
            // Add hidden inputs for selected seats
            const form = document.getElementById('seatForm');
            let hiddenInputs = form.querySelectorAll('input[name="selected_seats[]"]');
            hiddenInputs.forEach(input => input.remove());
            
            selectedSeats.forEach(seat => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_seats[]';
                input.value = seat;
                form.appendChild(input);
            });
        }
        
        // Initialize
        updateBookingSummary();
    </script>
</body>
</html>