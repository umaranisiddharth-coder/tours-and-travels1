<?php
require_once 'config.php';

// Check admin permissions
if (!is_logged_in() || !is_admin() || !has_permission('manage_buses')) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_bus'])) {
        $bus_number = sanitize_input($_POST['bus_number']);
        $bus_name = sanitize_input($_POST['bus_name']);
        $bus_type = sanitize_input($_POST['bus_type']);
        $total_seats = intval($_POST['total_seats']);
        $amenities = sanitize_input($_POST['amenities']);
        $status = sanitize_input($_POST['status']);
        
        // Calculate available seats
        $available_seats = $total_seats;
        
        $sql = "INSERT INTO buses (bus_number, bus_name, bus_type, total_seats, available_seats, amenities, status) 
                VALUES ('$bus_number', '$bus_name', '$bus_type', $total_seats, $available_seats, '$amenities', '$status')";
        
        if (mysqli_query($conn, $sql)) {
            $success = "Bus added successfully!";
            log_admin_activity("Added new bus: $bus_number");
        } else {
            $error = "Error adding bus: " . mysqli_error($conn);
        }
    }
    
    if (isset($_POST['update_bus'])) {
        $bus_id = intval($_POST['bus_id']);
        $bus_number = sanitize_input($_POST['bus_number']);
        $bus_name = sanitize_input($_POST['bus_name']);
        $bus_type = sanitize_input($_POST['bus_type']);
        $total_seats = intval($_POST['total_seats']);
        $amenities = sanitize_input($_POST['amenities']);
        $status = sanitize_input($_POST['status']);
        
        $sql = "UPDATE buses SET bus_number = '$bus_number', bus_name = '$bus_name', 
                bus_type = '$bus_type', total_seats = $total_seats, amenities = '$amenities', 
                status = '$status' WHERE id = $bus_id";
        
        if (mysqli_query($conn, $sql)) {
            $success = "Bus updated successfully!";
            log_admin_activity("Updated bus: $bus_number");
        } else {
            $error = "Error updating bus: " . mysqli_error($conn);
        }
    }
    
    if (isset($_POST['delete_bus'])) {
        $bus_id = intval($_POST['bus_id']);
        
        // Check if bus has active bookings
        $check_sql = "SELECT COUNT(*) as count FROM bookings WHERE bus_id = $bus_id AND booking_status != 'cancelled'";
        $result = mysqli_query($conn, $check_sql);
        $row = mysqli_fetch_assoc($result);
        
        if ($row['count'] > 0) {
            $error = "Cannot delete bus. There are active bookings for this bus.";
        } else {
            $sql = "DELETE FROM buses WHERE id = $bus_id";
            if (mysqli_query($conn, $sql)) {
                $success = "Bus deleted successfully!";
                log_admin_activity("Deleted bus with ID: $bus_id");
            } else {
                $error = "Error deleting bus: " . mysqli_error($conn);
            }
        }
    }
}

// Get all buses
$buses_query = "SELECT * FROM buses ORDER BY created_at DESC";
$buses_result = mysqli_query($conn, $buses_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Buses - SR Travels</title>
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
                            <a class="nav-link active" href="admin-buses.php">Manage Buses</a>
                            <a class="nav-link" href="admin-routes.php">Manage Routes</a>
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
                    <h2>Manage Buses</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBusModal">
                        <i class="fas fa-plus me-2"></i> Add New Bus
                    </button>
                </div>
                
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <!-- Buses Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Bus Number</th>
                                        <th>Bus Name</th>
                                        <th>Type</th>
                                        <th>Seats</th>
                                        <th>Available</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($bus = mysqli_fetch_assoc($buses_result)): ?>
                                    <tr>
                                        <td><?php echo $bus['id']; ?></td>
                                        <td><?php echo $bus['bus_number']; ?></td>
                                        <td><?php echo $bus['bus_name']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $bus['bus_type'] == 'seater' ? 'info' : 'warning'; ?>">
                                                <?php echo ucfirst($bus['bus_type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $bus['total_seats']; ?></td>
                                        <td><?php echo $bus['available_seats']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $bus['status'] == 'active' ? 'success' : 
                                                     ($bus['status'] == 'maintenance' ? 'warning' : 'danger'); 
                                            ?>">
                                                <?php echo ucfirst($bus['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary me-2" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editBusModal<?php echo $bus['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteBusModal<?php echo $bus['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    
                                    <!-- Edit Bus Modal -->
                                    <div class="modal fade" id="editBusModal<?php echo $bus['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Bus</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="bus_id" value="<?php echo $bus['id']; ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label">Bus Number</label>
                                                            <input type="text" class="form-control" name="bus_number" 
                                                                   value="<?php echo $bus['bus_number']; ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Bus Name</label>
                                                            <input type="text" class="form-control" name="bus_name" 
                                                                   value="<?php echo $bus['bus_name']; ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Bus Type</label>
                                                            <select class="form-select" name="bus_type" required>
                                                                <option value="seater" <?php echo $bus['bus_type'] == 'seater' ? 'selected' : ''; ?>>Seater</option>
                                                                <option value="sleeper" <?php echo $bus['bus_type'] == 'sleeper' ? 'selected' : ''; ?>>Sleeper</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Total Seats</label>
                                                            <input type="number" class="form-control" name="total_seats" 
                                                                   value="<?php echo $bus['total_seats']; ?>" min="1" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Amenities</label>
                                                            <textarea class="form-control" name="amenities" rows="3"><?php echo $bus['amenities']; ?></textarea>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Status</label>
                                                            <select class="form-select" name="status" required>
                                                                <option value="active" <?php echo $bus['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                                                <option value="inactive" <?php echo $bus['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                                <option value="maintenance" <?php echo $bus['status'] == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="update_bus" class="btn btn-primary">Update Bus</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Delete Bus Modal -->
                                    <div class="modal fade" id="deleteBusModal<?php echo $bus['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Delete Bus</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="bus_id" value="<?php echo $bus['id']; ?>">
                                                        <p>Are you sure you want to delete bus <strong><?php echo $bus['bus_name']; ?></strong> (<?php echo $bus['bus_number']; ?>)?</p>
                                                        <p class="text-danger">This action cannot be undone. All associated routes and bookings will be affected.</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="delete_bus" class="btn btn-danger">Delete Bus</button>
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

    <!-- Add Bus Modal -->
    <div class="modal fade" id="addBusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Bus</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Bus Number</label>
                            <input type="text" class="form-control" name="bus_number" placeholder="SRT-S001" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bus Name</label>
                            <input type="text" class="form-control" name="bus_name" placeholder="Volvo AC Seater" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bus Type</label>
                            <select class="form-select" name="bus_type" required>
                                <option value="seater">Seater</option>
                                <option value="sleeper">Sleeper</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Total Seats</label>
                            <input type="number" class="form-control" name="total_seats" value="40" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amenities</label>
                            <textarea class="form-control" name="amenities" rows="3" 
                                      placeholder="AC, WiFi, Charging Points, Water Bottle"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_bus" class="btn btn-primary">Add Bus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>