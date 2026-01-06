[file name]: admin-manage.php
[file content begin]
<?php
require_once 'config.php';

// Check if user is super admin
if (!is_logged_in() || !is_super_admin()) {
    header("Location: login.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_admin'])) {
        $user_id = intval($_POST['user_id']);
        $admin_role = sanitize_input($_POST['admin_role']);
        $permissions = isset($_POST['permissions']) ? implode(',', $_POST['permissions']) : '';
        
        $sql = "INSERT INTO admin (user_id, admin_role, permissions) 
                VALUES ($user_id, '$admin_role', '$permissions')";
        if (mysqli_query($conn, $sql)) {
            log_admin_activity("Added new admin with ID: " . mysqli_insert_id($conn));
            $success = "Admin added successfully!";
        } else {
            $error = "Error adding admin: " . mysqli_error($conn);
        }
    }
    
    if (isset($_POST['update_admin'])) {
        $admin_id = intval($_POST['admin_id']);
        $admin_role = sanitize_input($_POST['admin_role']);
        $permissions = isset($_POST['permissions']) ? implode(',', $_POST['permissions']) : '';
        $status = sanitize_input($_POST['status']);
        
        $sql = "UPDATE admin SET admin_role = '$admin_role', permissions = '$permissions', status = '$status' 
                WHERE id = $admin_id";
        if (mysqli_query($conn, $sql)) {
            log_admin_activity("Updated admin with ID: $admin_id");
            $success = "Admin updated successfully!";
        } else {
            $error = "Error updating admin: " . mysqli_error($conn);
        }
    }
    
    if (isset($_POST['delete_admin'])) {
        $admin_id = intval($_POST['admin_id']);
        
        $sql = "DELETE FROM admin WHERE id = $admin_id AND admin_role != 'super_admin'";
        if (mysqli_query($conn, $sql)) {
            log_admin_activity("Deleted admin with ID: $admin_id");
            $success = "Admin deleted successfully!";
        } else {
            $error = "Error deleting admin: " . mysqli_error($conn);
        }
    }
}

// Get all admins with user details
$admins_query = "SELECT a.*, u.username, u.email, u.full_name, u.phone 
                 FROM admin a 
                 JOIN users u ON a.user_id = u.id 
                 ORDER BY a.created_at DESC";
$admins_result = mysqli_query($conn, $admins_query);

// Get all users who are not admins (for adding new admin)
$users_query = "SELECT u.* FROM users u 
                WHERE u.user_type = 'admin' 
                AND u.id NOT IN (SELECT user_id FROM admin)";
$users_result = mysqli_query($conn, $users_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admins - SR Travels</title>
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
                            <a class="nav-link" href="admin-bookings.php">Bookings</a>
                            <a class="nav-link" href="admin-users.php">Users</a>
                            <a class="nav-link active" href="admin-manage.php">Manage Admins</a>
                            <a class="nav-link" href="admin-payments.php">Payments</a>
                            <a class="nav-link" href="admin-reports.php">Reports</a>
                        </nav>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-10">
                <h2 class="mb-4">Manage Admins</h2>
                
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if(isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <!-- Add Admin Form -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Add New Admin</h5>
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Select User</label>
                                    <select class="form-select" name="user_id" required>
                                        <option value="">Select a user</option>
                                        <?php while($user = mysqli_fetch_assoc($users_result)): ?>
                                        <option value="<?php echo $user['id']; ?>">
                                            <?php echo $user['full_name']; ?> (<?php echo $user['email']; ?>)
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Admin Role</label>
                                    <select class="form-select" name="admin_role" required>
                                        <option value="operator">Operator</option>
                                        <option value="manager">Manager</option>
                                        <option value="super_admin">Super Admin</option>
                                    </select>
                                </div>
                                <div class="col-md-5 mb-3">
                                    <label class="form-label">Permissions</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="all" id="permAll">
                                        <label class="form-check-label" for="permAll">All Permissions</label>
                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="permissions[]" value="manage_buses" id="permBuses">
                                                <label class="form-check-label" for="permBuses">Manage Buses</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="permissions[]" value="manage_routes" id="permRoutes">
                                                <label class="form-check-label" for="permRoutes">Manage Routes</label>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="permissions[]" value="manage_users" id="permUsers">
                                                <label class="form-check-label" for="permUsers">Manage Users</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="permissions[]" value="view_reports" id="permReports">
                                                <label class="form-check-label" for="permReports">View Reports</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" name="add_admin" class="btn btn-primary">Add Admin</button>
                        </form>
                    </div>
                </div>
                
                <!-- Admins List -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Admin List</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Last Login</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($admin = mysqli_fetch_assoc($admins_result)): ?>
                                    <tr>
                                        <td><?php echo $admin['id']; ?></td>
                                        <td><?php echo $admin['full_name']; ?></td>
                                        <td><?php echo $admin['email']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $admin['admin_role'] == 'super_admin' ? 'danger' : 
                                                     ($admin['admin_role'] == 'manager' ? 'warning' : 'info'); 
                                            ?>">
                                                <?php echo ucfirst($admin['admin_role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $admin['status'] == 'active' ? 'success' : 
                                                     ($admin['status'] == 'inactive' ? 'warning' : 'danger'); 
                                            ?>">
                                                <?php echo ucfirst($admin['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo $admin['last_login'] ? format_date($admin['last_login']) . ' ' . format_time($admin['last_login']) : 'Never'; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $admin['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if($admin['admin_role'] != 'super_admin'): ?>
                                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $admin['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    
                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editModal<?php echo $admin['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Admin</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label">Admin Role</label>
                                                            <select class="form-select" name="admin_role" required>
                                                                <option value="operator" <?php echo $admin['admin_role'] == 'operator' ? 'selected' : ''; ?>>Operator</option>
                                                                <option value="manager" <?php echo $admin['admin_role'] == 'manager' ? 'selected' : ''; ?>>Manager</option>
                                                                <option value="super_admin" <?php echo $admin['admin_role'] == 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Status</label>
                                                            <select class="form-select" name="status" required>
                                                                <option value="active" <?php echo $admin['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                                                <option value="inactive" <?php echo $admin['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                                <option value="suspended" <?php echo $admin['status'] == 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="update_admin" class="btn btn-primary">Update</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Delete Modal -->
                                    <div class="modal fade" id="deleteModal<?php echo $admin['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Delete Admin</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                        <p>Are you sure you want to delete admin <strong><?php echo $admin['full_name']; ?></strong>?</p>
                                                        <p class="text-danger">This action cannot be undone.</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="delete_admin" class="btn btn-danger">Delete</button>
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
    <script>
        // Check/Uncheck all permissions
        document.getElementById('permAll').addEventListener('change', function() {
            var checkboxes = document.querySelectorAll('input[name="permissions[]"]');
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = this.checked;
            }
        });
    </script>
</body>
</html>
[file content end]