<?php
require_once 'config.php';

// Check admin permissions (only super admin should manage users)
if (!is_logged_in() || !is_admin()) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_user'])) {
        $user_id = intval($_POST['user_id']);
        $full_name = sanitize_input($_POST['full_name']);
        $email = sanitize_input($_POST['email']);
        $phone = sanitize_input($_POST['phone']);
        $user_type = sanitize_input($_POST['user_type']);
        
        $sql = "UPDATE users SET full_name = '$full_name', email = '$email', 
                phone = '$phone', user_type = '$user_type' WHERE id = $user_id";
        
        if (mysqli_query($conn, $sql)) {
            $success = "User updated successfully!";
            log_admin_activity("Updated user: $email");
        } else {
            $error = "Error updating user: " . mysqli_error($conn);
        }
    }
    
    if (isset($_POST['delete_user'])) {
        $user_id = intval($_POST['user_id']);
        
        // Check if user has bookings
        $check_sql = "SELECT COUNT(*) as count FROM bookings WHERE user_id = $user_id";
        $result = mysqli_query($conn, $check_sql);
        $row = mysqli_fetch_assoc($result);
        
        if ($row['count'] > 0) {
            $error = "Cannot delete user. There are bookings associated with this user.";
        } else {
            $sql = "DELETE FROM users WHERE id = $user_id AND id != 1"; // Prevent deleting main admin
            if (mysqli_query($conn, $sql)) {
                $success = "User deleted successfully!";
                log_admin_activity("Deleted user with ID: $user_id");
            } else {
                $error = "Error deleting user: " . mysqli_error($conn);
            }
        }
    }
    
    if (isset($_POST['reset_password'])) {
        $user_id = intval($_POST['user_id']);
        $new_password = 'password123'; // Default reset password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $sql = "UPDATE users SET password = '$hashed_password' WHERE id = $user_id";
        if (mysqli_query($conn, $sql)) {
            $success = "Password reset successfully! New password: password123";
            log_admin_activity("Reset password for user ID: $user_id");
        } else {
            $error = "Error resetting password: " . mysqli_error($conn);
        }
    }
}

// Get all users
$users_query = "SELECT * FROM users ORDER BY created_at DESC";
$users_result = mysqli_query($conn, $users_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - SR Travels</title>
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
                            <a class="nav-link active" href="admin-users.php">Users</a>
                            <a class="nav-link" href="admin-payments.php">Payments</a>
                            <a class="nav-link" href="admin-reports.php">Reports</a>
                        </nav>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-10">
                <h2 class="mb-4">Manage Users</h2>
                
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <!-- Users Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>User Type</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($user = mysqli_fetch_assoc($users_result)): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td><?php echo $user['username']; ?></td>
                                        <td><?php echo $user['full_name']; ?></td>
                                        <td><?php echo $user['email']; ?></td>
                                        <td><?php echo $user['phone']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $user['user_type'] == 'admin' ? 'danger' : 'success'; ?>">
                                                <?php echo ucfirst($user['user_type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo format_date($user['created_at']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary me-2" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editUserModal<?php echo $user['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if($user['id'] != 1 && $user['id'] != $_SESSION['user_id']): ?>
                                            <button class="btn btn-sm btn-outline-warning me-2" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#resetPasswordModal<?php echo $user['id']; ?>">
                                                <i class="fas fa-key"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteUserModal<?php echo $user['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    
                                    <!-- Edit User Modal -->
                                    <div class="modal fade" id="editUserModal<?php echo $user['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit User</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label">Full Name</label>
                                                            <input type="text" class="form-control" name="full_name" 
                                                                   value="<?php echo $user['full_name']; ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Email</label>
                                                            <input type="email" class="form-control" name="email" 
                                                                   value="<?php echo $user['email']; ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Phone</label>
                                                            <input type="text" class="form-control" name="phone" 
                                                                   value="<?php echo $user['phone']; ?>">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">User Type</label>
                                                            <select class="form-select" name="user_type" required>
                                                                <option value="user" <?php echo $user['user_type'] == 'user' ? 'selected' : ''; ?>>User</option>
                                                                <option value="admin" <?php echo $user['user_type'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                            </select>
                                                            <div class="form-text">
                                                                Note: Changing to admin will require additional setup in admin management.
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Reset Password Modal -->
                                    <div class="modal fade" id="resetPasswordModal<?php echo $user['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Reset Password</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <p>Are you sure you want to reset password for <strong><?php echo $user['full_name']; ?></strong>?</p>
                                                        <p class="text-warning">The password will be reset to: <strong>password123</strong></p>
                                                        <p class="text-danger">The user will need to change their password after login.</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="reset_password" class="btn btn-warning">Reset Password</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Delete User Modal -->
                                    <div class="modal fade" id="deleteUserModal<?php echo $user['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Delete User</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <p>Are you sure you want to delete user <strong><?php echo $user['full_name']; ?></strong>?</p>
                                                        <p class="text-danger">This action cannot be undone. All user data will be permanently deleted.</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="delete_user" class="btn btn-danger">Delete User</button>
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