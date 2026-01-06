<?php
require_once 'config.php';

if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}

$booking_id = isset($_GET['booking_id']) ? sanitize_input($_GET['booking_id']) : '';

// Get booking details for tracking
if ($booking_id) {
    $query = "SELECT b.*, r.from_city, r.to_city, r.departure_time, r.arrival_time, b.travel_date,
                     bus.bus_name, bus.bus_number, bus.bus_type
              FROM bookings b
              JOIN bus_routes r ON b.route_id = r.id
              JOIN buses bus ON b.bus_id = bus.id
              WHERE b.booking_id = '$booking_id' AND b.user_id = {$_SESSION['user_id']}";
    $result = mysqli_query($conn, $query);
    $booking = mysqli_fetch_assoc($result);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bus Tracking - SR Travels</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .tracking-map {
            height: 400px;
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .bus-icon {
            position: absolute;
            font-size: 3rem;
            animation: moveBus 10s linear infinite;
        }
        
        @keyframes moveBus {
            0% { left: 10%; transform: translateY(0px); }
            25% { transform: translateY(-10px); }
            50% { left: 50%; transform: translateY(0px); }
            75% { transform: translateY(-10px); }
            100% { left: 90%; transform: translateY(0px); }
        }
        
        .progress-container {
            height: 30px;
            background-color: #e9ecef;
            border-radius: 15px;
            overflow: hidden;
            margin: 20px 0;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            border-radius: 15px;
            transition: width 0.5s;
        }
        
        .stop-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .stop-item {
            padding: 10px 15px;
            border-left: 3px solid #dee2e6;
            margin-bottom: 10px;
        }
        
        .stop-item.active {
            border-left-color: #28a745;
            background-color: rgba(40, 167, 69, 0.1);
        }
        
        .stop-item.completed {
            border-left-color: #6c757d;
            opacity: 0.7;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="index.php">SR<span class="text-warning">TRAVELS</span></a>
            <div class="d-flex align-items-center">
                <a href="user.php" class="btn btn-outline-primary btn-sm me-2">Dashboard</a>
                <a href="booking.php" class="btn btn-primary btn-sm">Book Bus</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <h2 class="mb-4">Real-time Bus Tracking</h2>
        
        <?php if(isset($booking)): ?>
            <!-- Booking Details -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5><?php echo $booking['from_city']; ?> to <?php echo $booking['to_city']; ?></h5>
                            <p class="mb-1"><i class="fas fa-bus"></i> <?php echo $booking['bus_name']; ?> (<?php echo $booking['bus_number']; ?>)</p>
                            <p class="mb-1"><i class="fas fa-calendar"></i> <?php echo format_date($booking['travel_date']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><i class="fas fa-clock"></i> <?php echo format_time($booking['departure_time']); ?> - <?php echo format_time($booking['arrival_time']); ?></p>
                            <p class="mb-1"><i class="fas fa-chair"></i> Seats: <?php echo $booking['seats_booked']; ?></p>
                            <p class="mb-0"><i class="fas fa-ticket-alt"></i> Booking ID: <?php echo $booking['booking_id']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tracking Map -->
            <div class="tracking-map">
                <div class="text-center">
                    <i class="fas fa-map-marked-alt fa-5x mb-3"></i>
                    <h3>Live Bus Tracking</h3>
                    <p class="mb-0">Real-time location tracking enabled</p>
                </div>
                <div class="bus-icon">
                    <i class="fas fa-bus"></i>
                </div>
            </div>
            
            <!-- Journey Progress -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Journey Progress</h5>
                    <div class="progress-container">
                        <div class="progress-bar" id="journeyProgress" style="width: 65%;"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <span><?php echo $booking['from_city']; ?></span>
                        <span id="progressText">65% Complete</span>
                        <span><?php echo $booking['to_city']; ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Journey Stops -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Journey Stops</h5>
                            <div class="stop-list">
                                <?php
                                // Sample stops (in real app, these would come from database)
                                $stops = [
                                    ['name' => $booking['from_city'] . ' Bus Stand', 'time' => $booking['departure_time'], 'status' => 'completed'],
                                    ['name' => 'City Outskirts', 'time' => '08:45:00', 'status' => 'completed'],
                                    ['name' => 'Highway Toll Plaza', 'time' => '10:15:00', 'status' => 'completed'],
                                    ['name' => 'Midway Rest Stop', 'time' => '11:30:00', 'status' => 'active'],
                                    ['name' => 'Next City Entry', 'time' => '13:00:00', 'status' => 'upcoming'],
                                    ['name' => $booking['to_city'] . ' Bus Stand', 'time' => $booking['arrival_time'], 'status' => 'upcoming'],
                                ];
                                
                                foreach($stops as $stop):
                                    $status_class = $stop['status'] == 'active' ? 'active' : 
                                                   ($stop['status'] == 'completed' ? 'completed' : '');
                                ?>
                                <div class="stop-item <?php echo $status_class; ?>">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="mb-1"><?php echo $stop['name']; ?></h6>
                                        <small><?php echo format_time($stop['time']); ?></small>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-map-marker-alt me-2 <?php echo $stop['status'] == 'active' ? 'text-success' : 'text-muted'; ?>"></i>
                                        <small class="<?php echo $stop['status'] == 'active' ? 'text-success fw-bold' : 'text-muted'; ?>">
                                            <?php echo ucfirst($stop['status']); ?>
                                        </small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Bus Information</h5>
                            <div class="mb-3">
                                <p><strong>Bus Number:</strong> <?php echo $booking['bus_number']; ?></p>
                                <p><strong>Bus Type:</strong> <?php echo ucfirst($booking['bus_type']); ?></p>
                                <p><strong>Driver:</strong> Rajesh Kumar</p>
                                <p><strong>Contact:</strong> +91 98765 43210</p>
                            </div>
                            
                            <div class="mb-3">
                                <h6>Current Status:</h6>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i>
                                    On Time - Expected arrival: <?php echo format_time($booking['arrival_time']); ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <h6>Speed & Distance:</h6>
                                <p><i class="fas fa-tachometer-alt me-2"></i> Current Speed: 65 km/h</p>
                                <p><i class="fas fa-road me-2"></i> Distance Covered: 195 km / 300 km</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php else: ?>
            <!-- Track Booking Form -->
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <i class="fas fa-map-marker-alt fa-3x text-primary mb-3"></i>
                                <h3>Track Your Bus</h3>
                                <p class="text-muted">Enter your booking ID to track your bus in real-time</p>
                            </div>
                            
                            <form method="GET" action="">
                                <div class="mb-3">
                                    <label for="booking_id" class="form-label">Booking ID</label>
                                    <input type="text" class="form-control" id="booking_id" name="booking_id" 
                                           placeholder="Enter your booking ID (e.g., BK-20240320-ABC123)" required>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Track Bus</button>
                                </div>
                            </form>
                            
                            <div class="text-center mt-3">
                                <p class="mb-0">Don't have a booking ID? <a href="user.php">Check your bookings</a></p>
                                <p class="text-muted mt-2">
                                    <small>Sample Booking ID: BK-20240320-A1B2C3</small>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if(isset($booking)): ?>
        // Simulate live tracking updates
        let progress = 65;
        
        function updateTracking() {
            // Simulate progress increase
            if (progress < 100) {
                progress += Math.random() * 2;
                if (progress > 100) progress = 100;
                
                // Update progress bar
                document.getElementById('journeyProgress').style.width = progress + '%';
                document.getElementById('progressText').textContent = Math.round(progress) + '% Complete';
                
                // Update bus animation
                const bus = document.querySelector('.bus-icon');
                bus.style.left = progress + '%';
            }
        }
        
        // Update every 5 seconds
        setInterval(updateTracking, 5000);
        
        // Initial update
        updateTracking();
        <?php endif; ?>
    </script>
</body>
</html>