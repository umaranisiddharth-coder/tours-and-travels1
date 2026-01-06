<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SR Tours & Travels - Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2a6ebb;
            --secondary-color: #ff7e36;
            --seater-color: #4a90e2;
            --sleeper-color: #8e44ad;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            color: #333;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.8rem;
            color: var(--primary-color) !important;
        }
        
        .navbar-brand span {
            color: var(--secondary-color);
        }
        
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), 
                        url('https://images.unsplash.com/photo-1524492412937-b28074a5d7da?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 150px 0 100px;
            margin-top: 76px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #1a5ca8;
            border-color: #1a5ca8;
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .section-title {
            position: relative;
            margin-bottom: 3rem;
        }
        
        .section-title:after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 60px;
            height: 4px;
            background-color: var(--secondary-color);
        }
        
        .bus-type-card {
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s;
            height: 100%;
        }
        
        .bus-type-card:hover {
            transform: translateY(-10px);
        }
        
        .bus-type-card.seater {
            border-top: 5px solid var(--seater-color);
        }
        
        .bus-type-card.sleeper {
            border-top: 5px solid var(--sleeper-color);
        }
        
        .bus-type-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            color: white;
        }
        
        .seater-badge {
            background-color: var(--seater-color);
        }
        
        .sleeper-badge {
            background-color: var(--sleeper-color);
        }
        
        .feature-card {
            text-align: center;
            padding: 30px 20px;
            border-radius: 10px;
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        /* Admin Login Card */
        .admin-login-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, #1a365d 100%);
            border-radius: 15px;
            color: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .admin-login-header {
            background: rgba(0,0,0,0.2);
            padding: 20px;
            text-align: center;
        }
        
        .admin-login-form .form-control {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
        }
        
        .admin-login-form .form-control::placeholder {
            color: rgba(255,255,255,0.6);
        }
        
        .admin-login-form .form-control:focus {
            background: rgba(255,255,255,0.15);
            border-color: var(--secondary-color);
            color: white;
            box-shadow: 0 0 0 0.25rem rgba(255, 126, 54, 0.25);
        }
        
        .admin-credentials {
            background: rgba(0,0,0,0.2);
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            font-size: 0.9rem;
        }
        
        footer {
            background-color: #1a365d;
            color: white;
            padding: 60px 0 30px;
        }
        
        @media (max-width: 768px) {
            .hero-section {
                padding: 100px 0 60px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">SR<span>TOURS</span>TRAVELS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="booking.php">Book Bus</a></li>
                    <li class="nav-item"><a class="nav-link" href="bus-tracking.php">Bus Tracking</a></li>
                    <?php if(is_logged_in()): ?>
                        <?php if(is_admin()): ?>
                            <li class="nav-item"><a class="nav-link" href="admin.php">Admin</a></li>
                        <?php else: ?>
                            <li class="nav-item"><a class="nav-link" href="user.php">Dashboard</a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                        <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Experience Comfortable Travel Across India</h1>
                    <p class="lead mb-4">Choose between our premium Seater and Sleeper buses. Safe, comfortable, and affordable travel experiences with real-time tracking.</p>
                    <a href="booking.php" class="btn btn-secondary btn-lg me-3">Book Your Journey</a>
                    <a href="#bus-types" class="btn btn-outline-light btn-lg">Explore Bus Types</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Bus Types -->
    <section id="bus-types" class="py-5">
        <div class="container">
            <h2 class="section-title">Our Bus Types</h2>
            <div class="row mt-4">
                <div class="col-md-6 mb-4">
                    <div class="bus-type-card seater card">
                        <div class="card-body p-4">
                            <div class="position-relative">
                                <h3 class="card-title">Seater Buses</h3>
                                <span class="seater-badge bus-type-badge">SEATER</span>
                            </div>
                            <p class="card-text mt-3">Perfect for short to medium distance journeys with comfortable reclining seats.</p>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Reclining seats with leg space</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Individual charging points</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Air conditioning</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Onboard entertainment</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Complimentary water bottle</li>
                            </ul>
                            <div class="mt-4">
                                <h4 class="text-primary">Starting from: ₹500</h4>
                                <a href="booking.php?type=seater" class="btn btn-primary mt-2">Book Seater Bus</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="bus-type-card sleeper card">
                        <div class="card-body p-4">
                            <div class="position-relative">
                                <h3 class="card-title">Sleeper Buses</h3>
                                <span class="sleeper-badge bus-type-badge">SLEEPER</span>
                            </div>
                            <p class="card-text mt-3">Ideal for overnight journeys with comfortable berths for a good night's sleep.</p>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Upper and lower berths</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Individual reading lights</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Privacy curtains</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Storage space</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Complimentary blanket</li>
                            </ul>
                            <div class="mt-4">
                                <h4 class="text-primary">Starting from: ₹800</h4>
                                <a href="booking.php?type=sleeper" class="btn btn-primary mt-2">Book Sleeper Bus</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="section-title text-center">Why Choose SR Travels</h2>
            <div class="row mt-5">
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="feature-card">
                        <div class="mb-3">
                            <i class="fas fa-shield-alt fa-3x text-primary"></i>
                        </div>
                        <h5>Safe Travel</h5>
                        <p class="text-muted">Your safety is our priority with well-maintained vehicles and trained drivers.</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="feature-card">
                        <div class="mb-3">
                            <i class="fas fa-rupee-sign fa-3x text-primary"></i>
                        </div>
                        <h5>Best Price</h5>
                        <p class="text-muted">We offer competitive prices without compromising on quality and comfort.</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="feature-card">
                        <div class="mb-3">
                            <i class="fas fa-headset fa-3x text-primary"></i>
                        </div>
                        <h5>24/7 Support</h5>
                        <p class="text-muted">Our customer support team is available round the clock to assist you.</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="feature-card">
                        <div class="mb-3">
                            <i class="fas fa-map-marker-alt fa-3x text-primary"></i>
                        </div>
                        <h5>Real-time Tracking</h5>
                        <p class="text-muted">Track your bus in real-time and get live updates about your journey.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Admin Login Section -->
    <?php if(!is_logged_in()): ?>
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="admin-login-card">
                        <div class="admin-login-header">
                            <h3><i class="fas fa-user-shield me-2"></i> Admin Login</h3>
                            <p class="mb-0">Access the administration panel</p>
                        </div>
                        
                        <div class="p-4">
                            <form class="admin-login-form" method="POST" action="login.php">
                                <div class="mb-3">
                                    <label for="admin_email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="admin_email" name="email" 
                                           placeholder="Enter admin email" required>
                                </div>
                                <div class="mb-3">
                                    <label for="admin_password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="admin_password" name="password" 
                                           placeholder="Enter password" required>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-sign-in-alt me-2"></i> Login as Admin
                                    </button>
                                </div>
                            </form>
                            
                            <div class="admin-credentials">
                                <p class="mb-1"><strong>Demo Credentials:</strong></p>
                                <p class="mb-1">Email: admin@srtravels.com</p>
                                <p class="mb-0">Password: password123</p>
                            </div>
                            
                            <div class="text-center mt-3">
                                <p class="mb-0">
                                    <small>
                                        <a href="login.php" class="text-white">Regular User Login</a> | 
                                        <a href="register.php" class="text-white">Register New Account</a>
                                    </small>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Popular Routes -->
    <section class="py-5">
        <div class="container">
            <h2 class="section-title">Popular Routes</h2>
            <div class="row mt-4">
                <?php
                $routes_query = "SELECT r.*, b.bus_type FROM bus_routes r 
                                JOIN buses b ON r.bus_id = b.id 
                                ORDER BY r.id LIMIT 4";
                $routes_result = mysqli_query($conn, $routes_query);
                while($route = mysqli_fetch_assoc($routes_result)):
                ?>
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $route['from_city']; ?> to <?php echo $route['to_city']; ?></h5>
                            <p class="card-text">
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i> 
                                    <?php echo format_time($route['departure_time']); ?> - 
                                    <?php echo format_time($route['arrival_time']); ?>
                                </small>
                            </p>
                            <p class="card-text">
                                <span class="badge <?php echo $route['bus_type'] == 'seater' ? 'bg-info' : 'bg-warning'; ?>">
                                    <?php echo ucfirst($route['bus_type']); ?>
                                </span>
                            </p>
                            <h4 class="text-primary">₹<?php echo $route['fare']; ?></h4>
                            <a href="booking.php?from=<?php echo $route['from_city']; ?>&to=<?php echo $route['to_city']; ?>&type=<?php echo $route['bus_type']; ?>" 
                               class="btn btn-primary btn-sm">Book Now</a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-lg-4">
                    <h4 class="mb-4">SR TOURS & TRAVELS</h4>
                    <p>Your trusted partner for exploring incredible India. We provide safe, comfortable, and affordable travel experiences across the country.</p>
                    <div class="social-icons mt-4">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h5 class="mb-4">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="index.php" class="text-white text-decoration-none">Home</a></li>
                        <li class="mb-2"><a href="booking.php" class="text-white text-decoration-none">Book Bus</a></li>
                        <li class="mb-2"><a href="bus-tracking.php" class="text-white text-decoration-none">Bus Tracking</a></li>
                        <?php if(!is_logged_in()): ?>
                        <li class="mb-2"><a href="#admin-login" class="text-white text-decoration-none">Admin Login</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h5 class="mb-4">Contact Us</h5>
                    <p><i class="fas fa-map-marker-alt me-2"></i> SR Travels, Pune, India</p>
                    <p><i class="fas fa-phone me-2"></i> +91 9356437871</p>
                    <p><i class="fas fa-envelope me-2"></i> srtravels@gmail.com</p>
                </div>
                <div class="col-lg-3">
                    <h5 class="mb-4">Newsletter</h5>
                    <p>Subscribe to get special offers and updates</p>
                    <div class="input-group">
                        <input type="email" class="form-control" placeholder="Your Email">
                        <button class="btn btn-primary" type="button">Subscribe</button>
                    </div>
                </div>
            </div>
            <hr class="mt-4">
            <div class="row">
                <div class="col-md-12 text-center">
                    <p>&copy; 2025 SR TOURS & TRAVELS. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Scroll to admin login section
        document.querySelectorAll('a[href="#admin-login"]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector('.admin-login-card').scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
        
        // Auto-fill admin credentials on click
        document.addEventListener('DOMContentLoaded', function() {
            const adminEmail = document.getElementById('admin_email');
            const adminPassword = document.getElementById('admin_password');
            
            if (adminEmail && adminPassword) {
                // Auto-fill demo credentials (optional - you can remove this if you don't want auto-fill)
                adminEmail.addEventListener('click', function() {
                    if (!this.value) {
                        this.value = 'admin@srtravels.com';
                        adminPassword.value = 'password123';
                    }
                });
            }
        });
    </script>
</body>
</html>