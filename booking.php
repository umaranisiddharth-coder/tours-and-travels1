<?php
require_once 'config.php';

if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}

// Get bus type from URL or default to all
$bus_type = isset($_GET['type']) ? sanitize_input($_GET['type']) : 'all';

// Search functionality
$from_city = isset($_GET['from']) ? sanitize_input($_GET['from']) : '';
$to_city = isset($_GET['to']) ? sanitize_input($_GET['to']) : '';
$travel_date = isset($_GET['date']) ? sanitize_input($_GET['date']) : date('Y-m-d');

// Build query
$query = "SELECT r.*, b.bus_number, b.bus_name, b.bus_type, b.total_seats, b.available_seats 
          FROM bus_routes r 
          JOIN buses b ON r.bus_id = b.id 
          WHERE b.status = 'active'";

if ($bus_type != 'all') {
    $query .= " AND b.bus_type = '$bus_type'";
}

if (!empty($from_city)) {
    $query .= " AND r.from_city LIKE '%$from_city%'";
}

if (!empty($to_city)) {
    $query .= " AND r.to_city LIKE '%$to_city%'";
}

$query .= " ORDER BY r.departure_time";

$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Bus - SR Travels</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --seater-color: #4a90e2;
            --sleeper-color: #8e44ad;
        }
        
        .bus-type-tabs {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #dee2e6;
        }
        
        .bus-type-tab {
            padding: 12px 30px;
            background: none;
            border: none;
            font-size: 1.1rem;
            font-weight: 500;
            color: #6c757d;
            position: relative;
            cursor: pointer;
        }
        
        .bus-type-tab.active {
            color: var(--primary-color);
        }
        
        .bus-type-tab.active:after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: var(--primary-color);
        }
        
        .bus-card {
            border-left: 5px solid transparent;
            transition: all 0.3s;
            margin-bottom: 20px;
        }
        
        .bus-card.seater {
            border-left-color: var(--seater-color);
        }
        
        .bus-card.sleeper {
            border-left-color: var(--sleeper-color);
        }
        
        .bus-type-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            color: white;
        }
        
        .seater-badge {
            background-color: var(--seater-color);
        }
        
        .sleeper-badge {
            background-color: var(--sleeper-color);
        }
        
        .seat-availability {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .available-high {
            background-color: #d4edda;
            color: #155724;
        }
        
        .available-low {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .available-none {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="index.php">SR<span class="text-warning">TRAVELS</span></a>
            <div class="d-flex align-items-center">
                <span class="me-3">Welcome, <?php echo $_SESSION['full_name']; ?></span>
                <a href="user.php" class="btn btn-outline-primary btn-sm">Dashboard</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <h2 class="mb-4">Book Your Bus Ticket</h2>
        
        <!-- Search Form -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">From City</label>
                            <input type="text" class="form-control" name="from" value="<?php echo $from_city; ?>" placeholder="Departure city">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">To City</label>
                            <input type="text" class="form-control" name="to" value="<?php echo $to_city; ?>" placeholder="Destination city">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Travel Date</label>
                            <input type="date" class="form-control" name="date" value="<?php echo $travel_date; ?>" min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Bus Type</label>
                            <select class="form-select" name="type">
                                <option value="all" <?php echo $bus_type == 'all' ? 'selected' : ''; ?>>All Types</option>
                                <option value="seater" <?php echo $bus_type == 'seater' ? 'selected' : ''; ?>>Seater</option>
                                <option value="sleeper" <?php echo $bus_type == 'sleeper' ? 'selected' : ''; ?>>Sleeper</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Search Buses</button>
                            <a href="booking.php" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Bus Type Tabs -->
        <div class="bus-type-tabs">
            <button class="bus-type-tab <?php echo $bus_type == 'all' ? 'active' : ''; ?>" onclick="window.location.href='booking.php'">
                All Buses
            </button>
            <button class="bus-type-tab <?php echo $bus_type == 'seater' ? 'active' : ''; ?>" onclick="window.location.href='booking.php?type=seater'">
                <i class="fas fa-chair me-2"></i> Seater Buses
            </button>
            <button class="bus-type-tab <?php echo $bus_type == 'sleeper' ? 'active' : ''; ?>" onclick="window.location.href='booking.php?type=sleeper'">
                <i class="fas fa-bed me-2"></i> Sleeper Buses
            </button>
        </div>
        
        <!-- Available Buses -->
        <?php if(mysqli_num_rows($result) > 0): ?>
            <div class="row">
                <?php while($bus = mysqli_fetch_assoc($result)): 
                    $availability_percentage = ($bus['available_seats'] / $bus['total_seats']) * 100;
                    $availability_class = $availability_percentage > 30 ? 'available-high' : 
                                         ($availability_percentage > 0 ? 'available-low' : 'available-none');
                    $availability_text = $availability_percentage > 30 ? 'High Availability' : 
                                        ($availability_percentage > 0 ? 'Low Availability' : 'Sold Out');
                ?>
                <div class="col-lg-6 mb-4">
                    <div class="bus-card <?php echo $bus['bus_type']; ?> card">
                        <div class="card-body">
                            <div class="position-relative">
                                <h5 class="card-title"><?php echo $bus['from_city']; ?> to <?php echo $bus['to_city']; ?></h5>
                                <span class="<?php echo $bus['bus_type'] == 'seater' ? 'seater-badge' : 'sleeper-badge'; ?> bus-type-badge">
                                    <?php echo strtoupper($bus['bus_type']); ?>
                                </span>
                            </div>
                            <p class="card-text">
                                <i class="fas fa-bus me-2"></i> <?php echo $bus['bus_name']; ?> (<?php echo $bus['bus_number']; ?>)
                            </p>
                            <p class="card-text">
                                <i class="fas fa-clock me-2"></i> 
                                <?php echo format_time($bus['departure_time']); ?> - 
                                <?php echo format_time($bus['arrival_time']); ?>
                                <span class="ms-3"><?php echo calculate_duration($bus['departure_time'], $bus['arrival_time']); ?></span>
                            </p>
                            <div class="row mt-3">
                                <div class="col-6">
                                    <h4 class="text-primary">₹<?php echo $bus['fare']; ?></h4>
                                    <small class="text-muted">per seat/berth</small>
                                </div>
                                <div class="col-6 text-end">
                                    <div class="seat-availability <?php echo $availability_class; ?>">
                                        <?php echo $availability_text; ?>
                                    </div>
                                    <p class="mt-1 mb-0">
                                        <small><?php echo $bus['available_seats']; ?> of <?php echo $bus['total_seats']; ?> seats available</small>
                                    </p>
                                </div>
                            </div>
                            <div class="mt-3">
                                <a href="seat-selection.php?route_id=<?php echo $bus['id']; ?>&travel_date=<?php echo $travel_date; ?>" 
                                   class="btn btn-primary <?php echo $bus['available_seats'] == 0 ? 'disabled' : ''; ?>">
                                    Select Seats
                                </a>
                                <button class="btn btn-outline-secondary" onclick="viewDetails(<?php echo $bus['id']; ?>)">
                                    View Details
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-bus fa-3x text-muted mb-3"></i>
                <h4>No buses found</h4>
                <p class="text-muted">No buses available for your search criteria. Please try different search parameters.</p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewDetails(routeId) {
            // In a real app, this would show bus details in a modal
            alert('Bus details would show here. Route ID: ' + routeId);
        }
    </script>
</body>
</html>