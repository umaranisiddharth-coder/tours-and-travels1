
<?php
require_once 'config.php';

// Check admin permissions
if (!is_logged_in() || !is_admin()) {
    header("Location: login.php");
    exit();
}

// Default date range (last 30 days)
$start_date = isset($_GET['start_date']) ? sanitize_input($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? sanitize_input($_GET['end_date']) : date('Y-m-d');
$report_type = isset($_GET['report_type']) ? sanitize_input($_GET['report_type']) : 'overview';

// Overview Report
$total_bookings = 0;
$total_revenue = 0;
$total_users = 0;
$total_buses = 0;

// Revenue Report
$daily_revenue = [];
$monthly_revenue = [];

// Bus Performance
$bus_performance = [];

// Route Performance
$route_performance = [];

// Get overview statistics
$overview_query = "SELECT 
                    (SELECT COUNT(*) FROM bookings WHERE DATE(booking_date) BETWEEN '$start_date' AND '$end_date') as total_bookings,
                    (SELECT SUM(total_amount) FROM bookings WHERE payment_status = 'paid' AND DATE(booking_date) BETWEEN '$start_date' AND '$end_date') as total_revenue,
                    (SELECT COUNT(*) FROM users WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date') as new_users,
                    (SELECT COUNT(*) FROM buses) as total_buses";

$overview_result = mysqli_query($conn, $overview_query);
if ($overview_result) {
    $overview = mysqli_fetch_assoc($overview_result);
    $total_bookings = $overview['total_bookings'] ?: 0;
    $total_revenue = $overview['total_revenue'] ?: 0;
    $total_users = $overview['new_users'] ?: 0;
    $total_buses = $overview['total_buses'] ?: 0;
}

// Get daily revenue for chart
$daily_query = "SELECT DATE(booking_date) as date, SUM(total_amount) as revenue 
                FROM bookings 
                WHERE payment_status = 'paid' 
                AND booking_date BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'
                GROUP BY DATE(booking_date) 
                ORDER BY date";

$daily_result = mysqli_query($conn, $daily_query);
while ($row = mysqli_fetch_assoc($daily_result)) {
    $daily_revenue[] = $row;
}

// Get monthly revenue
$monthly_query = "SELECT DATE_FORMAT(booking_date, '%Y-%m') as month, SUM(total_amount) as revenue 
                  FROM bookings 
                  WHERE payment_status = 'paid' 
                  GROUP BY DATE_FORMAT(booking_date, '%Y-%m') 
                  ORDER BY month DESC 
                  LIMIT 6";

$monthly_result = mysqli_query($conn, $monthly_query);
while ($row = mysqli_fetch_assoc($monthly_result)) {
    $monthly_revenue[] = $row;
}

// Get bus performance
$bus_query = "SELECT b.bus_name, b.bus_number, b.bus_type,
                     COUNT(bk.id) as total_bookings,
                     SUM(bk.total_amount) as total_revenue,
                     AVG(bk.total_amount) as avg_revenue
              FROM buses b
              LEFT JOIN bookings bk ON b.id = bk.bus_id 
              AND bk.booking_date BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'
              GROUP BY b.id
              ORDER BY total_revenue DESC";

$bus_result = mysqli_query($conn, $bus_query);
while ($row = mysqli_fetch_assoc($bus_result)) {
    $bus_performance[] = $row;
}

// Get route performance
$route_query = "SELECT r.from_city, r.to_city,
                       COUNT(bk.id) as total_bookings,
                       SUM(bk.total_amount) as total_revenue,
                       AVG(bk.total_amount) as avg_revenue
                FROM bus_routes r
                LEFT JOIN bookings bk ON r.id = bk.route_id 
                AND bk.booking_date BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'
                GROUP BY r.id
                ORDER BY total_revenue DESC 
                LIMIT 10";

$route_result = mysqli_query($conn, $route_query);
while ($row = mysqli_fetch_assoc($route_result)) {
    $route_performance[] = $row;
}

// Get booking status distribution
$status_query = "SELECT booking_status, COUNT(*) as count 
                 FROM bookings 
                 WHERE booking_date BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'
                 GROUP BY booking_status";
$status_result = mysqli_query($conn, $status_query);
$status_distribution = [];
while ($row = mysqli_fetch_assoc($status_result)) {
    $status_distribution[] = $row;
}

// Get payment status distribution
$payment_query = "SELECT payment_status, COUNT(*) as count 
                  FROM bookings 
                  WHERE booking_date BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'
                  GROUP BY payment_status";
$payment_result = mysqli_query($conn, $payment_query);
$payment_distribution = [];
while ($row = mysqli_fetch_assoc($payment_result)) {
    $payment_distribution[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - SR Travels Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            color: white;
            text-align: center;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .stat-card h3 {
            font-size: 2rem;
            margin-bottom: 5px;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 30px;
        }
        
        .report-table {
            font-size: 0.9rem;
        }
        
        .report-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .percentage-badge {
            font-size: 0.8rem;
            padding: 3px 8px;
            border-radius: 10px;
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
                            <a class="nav-link" href="admin-booking.php">Create Booking</a>
                            <a class="nav-link" href="admin-bookings.php">View Bookings</a>
                            <a class="nav-link" href="admin-users.php">Users</a>
                            <a class="nav-link" href="admin-payments.php">Payments</a>
                            <a class="nav-link active" href="admin-reports.php">Reports</a>
                        </nav>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-10">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Analytics Reports</h2>
                    <div class="btn-group">
                        <a href="admin-reports.php?export=pdf&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
                           class="btn btn-outline-danger">
                            <i class="fas fa-file-pdf me-2"></i> Export PDF
                        </a>
                        <a href="admin-reports.php?export=excel&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
                           class="btn btn-outline-success">
                            <i class="fas fa-file-excel me-2"></i> Export Excel
                        </a>
                    </div>
                </div>
                
                <!-- Date Range Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Report Type</label>
                                <select class="form-select" name="report_type">
                                    <option value="overview" <?php echo $report_type == 'overview' ? 'selected' : ''; ?>>Overview</option>
                                    <option value="revenue" <?php echo $report_type == 'revenue' ? 'selected' : ''; ?>>Revenue</option>
                                    <option value="performance" <?php echo $report_type == 'performance' ? 'selected' : ''; ?>>Performance</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter me-2"></i> Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i class="fas fa-ticket-alt"></i>
                            <h3><?php echo $total_bookings; ?></h3>
                            <p>Total Bookings</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <i class="fas fa-rupee-sign"></i>
                            <h3>₹<?php echo number_format($total_revenue, 2); ?></h3>
                            <p>Total Revenue</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <i class="fas fa-users"></i>
                            <h3><?php echo $total_users; ?></h3>
                            <p>New Users</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                            <i class="fas fa-bus"></i>
                            <h3><?php echo $total_buses; ?></h3>
                            <p>Active Buses</p>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Section -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Daily Revenue Trend</h5>
                                <div class="chart-container">
                                    <canvas id="revenueChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Booking Status</h5>
                                <div class="chart-container">
                                    <canvas id="statusChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Performance Tables -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Top Performing Buses</h5>
                                <div class="table-responsive">
                                    <table class="table table-hover report-table">
                                        <thead>
                                            <tr>
                                                <th>Bus</th>
                                                <th>Type</th>
                                                <th>Bookings</th>
                                                <th>Revenue</th>
                                                <th>Avg/Booking</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($bus_performance as $bus): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo $bus['bus_name']; ?></strong><br>
                                                    <small class="text-muted"><?php echo $bus['bus_number']; ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo $bus['bus_type'] == 'seater' ? 'bg-info' : 'bg-warning'; ?>">
                                                        <?php echo ucfirst($bus['bus_type']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $bus['total_bookings'] ?: 0; ?></td>
                                                <td>₹<?php echo number_format($bus['total_revenue'] ?: 0, 2); ?></td>
                                                <td>₹<?php echo number_format($bus['avg_revenue'] ?: 0, 2); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Top Performing Routes</h5>
                                <div class="table-responsive">
                                    <table class="table table-hover report-table">
                                        <thead>
                                            <tr>
                                                <th>Route</th>
                                                <th>Bookings</th>
                                                <th>Revenue</th>
                                                <th>Avg/Booking</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($route_performance as $route): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo $route['from_city']; ?> → <?php echo $route['to_city']; ?></strong>
                                                </td>
                                                <td><?php echo $route['total_bookings'] ?: 0; ?></td>
                                                <td>₹<?php echo number_format($route['total_revenue'] ?: 0, 2); ?></td>
                                                <td>₹<?php echo number_format($route['avg_revenue'] ?: 0, 2); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Monthly Revenue -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title">Monthly Revenue (Last 6 Months)</h5>
                        <div class="table-responsive">
                            <table class="table table-hover report-table">
                                <thead>
                                    <tr>
                                        <th>Month</th>
                                        <th>Revenue</th>
                                        <th>Growth</th>
                                        <th>Trend</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $prev_revenue = 0;
                                    foreach(array_reverse($monthly_revenue) as $index => $month): 
                                        $growth = $prev_revenue > 0 ? (($month['revenue'] - $prev_revenue) / $prev_revenue) * 100 : 0;
                                        $prev_revenue = $month['revenue'];
                                    ?>
                                    <tr>
                                        <td><?php echo date('F Y', strtotime($month['month'] . '-01')); ?></td>
                                        <td>₹<?php echo number_format($month['revenue'], 2); ?></td>
                                        <td>
                                            <span class="badge <?php echo $growth >= 0 ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo number_format($growth, 1); ?>%
                                            </span>
                                        </td>
                                        <td>
                                            <i class="fas fa-arrow-<?php echo $growth >= 0 ? 'up text-success' : 'down text-danger'; ?>"></i>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: [<?php echo implode(',', array_map(function($item) { return "'" . date('d M', strtotime($item['date'])) . "'"; }, $daily_revenue)); ?>],
                datasets: [{
                    label: 'Daily Revenue (₹)',
                    data: [<?php echo implode(',', array_map(function($item) { return $item['revenue']; }, $daily_revenue)); ?>],
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₹' + value;
                            }
                        }
                    }
                }
            }
        });
        
        // Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: [<?php echo implode(',', array_map(function($item) { return "'" . ucfirst($item['booking_status']) . "'"; }, $status_distribution)); ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_map(function($item) { return $item['count']; }, $status_distribution)); ?>],
                    backgroundColor: [
                        '#28a745', // confirmed
                        '#ffc107', // pending
                        '#dc3545', // cancelled
                        '#17a2b8'  // completed
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
