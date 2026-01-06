<?php
require_once 'config.php';

// Check admin permissions
if (!is_logged_in() || !is_admin() || !has_permission('manage_routes')) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_route'])) {
        $bus_id = intval($_POST['bus_id']);
        $from_city = sanitize_input($_POST['from_city']);
        $to_city = sanitize_input($_POST['to_city']);
        $departure_time = sanitize_input($_POST['departure_time']);
        $arrival_time = sanitize_input($_POST['arrival_time']);
        $duration = sanitize_input($_POST['duration']);
        $fare = floatval($_POST['fare']);
        $frequency = sanitize_input($_POST['frequency']);
        $created_by = $_SESSION['admin_id'];
        
        $sql = "INSERT INTO bus_routes (bus_id, from_city, to_city, departure_time, 
                arrival_time, duration, fare, frequency, created_by) 
                VALUES ($bus_id, '$from_city', '$to_city', '$departure_time', 
                '$arrival_time', '$duration', $fare, '$frequency', $created_by)";
        
        if (mysqli_query($conn, $sql)) {
            $success = "Route added successfully!";
            log_admin_activity("Added new route: $from_city to $to_city");
        } else {
            $error = "Error adding route: " . mysqli_error($conn);
        }
    }
    
    if (isset($_POST['update_route'])) {
        $route_id = intval($_POST['route_id']);
        $bus_id = intval($_POST['bus_id']);
        $from_city = sanitize_input($_POST['from_city']);
        $to_city = sanitize_input($_POST['to_city']);
        $departure_time = sanitize_input($_POST['departure_time']);
        $arrival_time = sanitize_input($_POST['arrival_time']);
        $duration = sanitize_input($_POST['duration']);
        $fare = floatval($_POST['fare']);
        $frequency = sanitize_input($_POST['frequency']);
        
        $sql = "UPDATE bus_routes SET bus_id = $bus_id, from_city = '$from_city', 
                to_city = '$to_city', departure_time = '$departure_time', 
                arrival_time = '$arrival_time', duration = '$duration', 
                fare = $fare, frequency = '$frequency' WHERE id = $route_id";
        
        if (mysqli_query($conn, $sql)) {
            $success = "Route updated successfully!";
            log_admin_activity("Updated route: $from_city to $to_city");
        } else {
            $error = "Error updating route: " . mysqli_error($conn);
        }
    }
    
    if (isset($_POST['delete_route'])) {
        $route_id = intval($_POST['route_id']);
        
        // Check if route has active bookings
        $check_sql = "SELECT COUNT(*) as count FROM bookings WHERE route_id = $route_id AND booking_status != 'cancelled'";
        $result = mysqli_query($conn, $check_sql);
        $row = mysqli_fetch_assoc($result);
        
        if ($row['count'] > 0) {
            $error = "Cannot delete route. There are active bookings for this route.";
        } else {
            $sql = "DELETE FROM bus_routes WHERE id = $route_id";
            if (mysqli_query($conn, $sql)) {
                $success = "Route deleted successfully!";
                log_admin_activity("Deleted route with ID: $route_id");
            } else {
                $error = "Error deleting route: " . mysqli_error($conn);
            }
        }
    }
}

// Get all routes with bus details
$routes_query = "SELECT r.*, b.bus_number, b.bus_name, b.bus_type 
                 FROM bus_routes r 
                 JOIN buses b ON r.bus_id = b.id 
                 ORDER BY r.created_at DESC";
$routes_result = mysqli_query($conn, $routes_query);

// Get all active buses for dropdown
$buses_query = "SELECT * FROM buses WHERE status = 'active' ORDER BY bus_number";
$buses_result = mysqli_query($conn, $buses_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Routes - SR Travels</title>
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
                            <a class="nav-link active" href="admin-routes.php">Manage Routes</a>
                            <a class="nav-link" href="admin-bookings.php">Bookings</a>
                            <a class="nav-link" href="admin-users.php">Users</a>
                            <a class="nav-link" href="admin-payments.php">Payments</a>
                            <a class="nav-link" href="admin-reports.php">Reports</a>
                        </nav>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-10">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Manage Routes</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRouteModal">
                        <i class="fas fa-plus me-2"></i> Add New Route
                    </button>
                </div>
                
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <!-- Routes Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>From</th>
                                        <th>To</th>
                                        <th>Bus</th>
                                        <th>Time</th>
                                        <th>Duration</th>
                                        <th>Fare</th>
                                        <th>Frequency</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($route = mysqli_fetch_assoc($routes_result)): ?>
                                    <tr>
                                        <td><?php echo $route['id']; ?></td>
                                        <td><?php echo $route['from_city']; ?></td>
                                        <td><?php echo $route['to_city']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $route['bus_type'] == 'seater' ? 'info' : 'warning'; ?>">
                                                <?php echo $route['bus_name']; ?> (<?php echo $route['bus_number']; ?>)
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo format_time($route['departure_time']); ?> - 
                                            <?php echo format_time($route['arrival_time']); ?>
                                        </td>
                                        <td><?php echo $route['duration']; ?></td>
                                        <td>₹<?php echo $route['fare']; ?></td>
                                        <td><?php echo $route['frequency']; ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary me-2" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editRouteModal<?php echo $route['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteRouteModal<?php echo $route['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    
                                    <!-- Edit Route Modal -->
                                    <div class="modal fade" id="editRouteModal<?php echo $route['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <form method="POST" action="">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Route</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="route_id" value="<?php echo $route['id']; ?>">
                                                        <div class="row">
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">From City</label>
                                                                <input type="text" class="form-control" name="from_city" 
                                                                       value="<?php echo $route['from_city']; ?>" required>
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">To City</label>
                                                                <input type="text" class="form-control" name="to_city" 
                                                                       value="<?php echo $route['to_city']; ?>" required>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Bus</label>
                                                                <select class="form-select" name="bus_id" required>
                                                                    <?php 
                                                                    mysqli_data_seek($buses_result, 0);
                                                                    while($bus = mysqli_fetch_assoc($buses_result)):
                                                                    ?>
                                                                    <option value="<?php echo $bus['id']; ?>" 
                                                                            <?php echo $bus['id'] == $route['bus_id'] ? 'selected' : ''; ?>>
                                                                        <?php echo $bus['bus_name']; ?> (<?php echo $bus['bus_number']; ?>)
                                                                    </option>
                                                                    <?php endwhile; ?>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Frequency</label>
                                                                <input type="text" class="form-control" name="frequency" 
                                                                       value="<?php echo $route['frequency']; ?>" placeholder="Daily, Weekly, etc.">
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Departure Time</label>
                                                                <input type="time" class="form-control" name="departure_time" 
                                                                       value="<?php echo $route['departure_time']; ?>" required>
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Arrival Time</label>
                                                                <input type="time" class="form-control" name="arrival_time" 
                                                                       value="<?php echo $route['arrival_time']; ?>" required>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Duration</label>
                                                                <input type="text" class="form-control" name="duration" 
                                                                       value="<?php echo $route['duration']; ?>" placeholder="6h" required>
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Fare (₹)</label>
                                                                <input type="number" step="0.01" class="form-control" name="fare" 
                                                                       value="<?php echo $route['fare']; ?>" min="0" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="update_route" class="btn btn-primary">Update Route</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Delete Route Modal -->
                                    <div class="modal fade" id="deleteRouteModal<?php echo $route['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Delete Route</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="route_id" value="<?php echo $route['id']; ?>">
                                                        <p>Are you sure you want to delete route <strong><?php echo $route['from_city']; ?> to <?php echo $route['to_city']; ?></strong>?</p>
                                                        <p class="text-danger">This action cannot be undone.</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="delete_route" class="btn btn-danger">Delete Route</button>
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

    <!-- Add Route Modal -->
    <div class="modal fade" id="addRouteModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Route</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">From City</label>
                                <input type="text" class="form-control" name="from_city" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">To City</label>
                                <input type="text" class="form-control" name="to_city" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Bus</label>
                                <select class="form-select" name="bus_id" required>
                                    <option value="">Select Bus</option>
                                    <?php 
                                    mysqli_data_seek($buses_result, 0);
                                    while($bus = mysqli_fetch_assoc($buses_result)):
                                    ?>
                                    <option value="<?php echo $bus['id']; ?>">
                                        <?php echo $bus['bus_name']; ?> (<?php echo $bus['bus_number']; ?>)
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Frequency</label>
                                <input type="text" class="form-control" name="frequency" placeholder="Daily, Weekly, etc.">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Departure Time</label>
                                <input type="time" class="form-control" name="departure_time" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Arrival Time</label>
                                <input type="time" class="form-control" name="arrival_time" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Duration</label>
                                <input type="text" class="form-control" name="duration" placeholder="6h" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fare (₹)</label>
                                <input type="number" step="0.01" class="form-control" name="fare" min="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_route" class="btn btn-primary">Add Route</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>