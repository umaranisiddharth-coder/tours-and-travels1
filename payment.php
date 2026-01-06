<?php
require_once 'config.php';

if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}

// Check if seats are selected
if (!isset($_SESSION['selected_seats']) || !isset($_SESSION['route_id'])) {
    header("Location: booking.php");
    exit();
}

$route_id = $_SESSION['route_id'];
$travel_date = $_SESSION['travel_date'];
$selected_seats = $_SESSION['selected_seats'];
$total_amount = $_SESSION['total_amount'];

// Get route details
$route_query = "SELECT r.*, b.bus_name FROM bus_routes r 
                JOIN buses b ON r.bus_id = b.id 
                WHERE r.id = $route_id";
$route_result = mysqli_query($conn, $route_query);
$route = mysqli_fetch_assoc($route_result);

// Process payment
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_method = sanitize_input($_POST['payment_method']);
    $payment_id = 'PAY-' . time() . '-' . rand(1000, 9999);
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Generate booking ID
        $booking_id = generate_booking_id();
        
        // Create booking
        $booking_query = "INSERT INTO bookings (booking_id, user_id, bus_id, route_id, travel_date, 
                          seats_booked, total_seats, total_amount, payment_method) 
                          VALUES ('$booking_id', {$_SESSION['user_id']}, {$route['bus_id']}, $route_id, 
                          '$travel_date', '" . implode(',', $selected_seats) . "', 
                          " . count($selected_seats) . ", $total_amount, '$payment_method')";
        
        if (mysqli_query($conn, $booking_query)) {
            $booking_insert_id = mysqli_insert_id($conn);
            
            // Mark seats as booked
            foreach($selected_seats as $seat) {
                $seat_type = strpos($seat, 'U') === 0 ? 'sleeper_upper' : 
                            (strpos($seat, 'L') === 0 ? 'sleeper_lower' : 'seater');
                
                $seat_query = "INSERT INTO seat_availability (bus_id, travel_date, seat_number, seat_type, is_available, booking_id) 
                              VALUES ({$route['bus_id']}, '$travel_date', '$seat', '$seat_type', FALSE, $booking_insert_id) 
                              ON DUPLICATE KEY UPDATE is_available = FALSE, booking_id = $booking_insert_id";
                mysqli_query($conn, $seat_query);
            }
            
            // Create payment record
            $payment_query = "INSERT INTO payments (booking_id, payment_id, amount, payment_method, payment_status) 
                             VALUES ($booking_insert_id, '$payment_id', $total_amount, '$payment_method', 'completed')";
            mysqli_query($conn, $payment_query);
            
            // Update booking status
            $update_booking = "UPDATE bookings SET booking_status = 'confirmed', payment_status = 'paid' 
                              WHERE id = $booking_insert_id";
            mysqli_query($conn, $update_booking);
            
            // Commit transaction
            mysqli_commit($conn);
            
            // Clear session
            unset($_SESSION['selected_seats']);
            unset($_SESSION['route_id']);
            unset($_SESSION['travel_date']);
            unset($_SESSION['total_amount']);
            
            // Store booking ID for confirmation page
            $_SESSION['last_booking_id'] = $booking_insert_id;
            
            header("Location: booking-confirm.php");
            exit();
            
        } else {
            throw new Exception("Booking failed: " . mysqli_error($conn));
        }
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = "Payment failed. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - SR Travels</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .payment-option {
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .payment-option:hover, .payment-option.selected {
            border-color: #2a6ebb;
            background-color: rgba(42, 110, 187, 0.05);
        }
        
        .payment-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .payment-details {
            display: none;
        }
        
        .payment-details.active {
            display: block;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="index.php">SR<span class="text-warning">TRAVELS</span></a>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <i class="fas fa-lock fa-3x text-primary mb-3"></i>
                            <h3>Secure Payment</h3>
                            <p class="text-muted">Complete your booking with a secure payment</p>
                        </div>
                        
                        <!-- Booking Summary -->
                        <div class="alert alert-success mb-4">
                            <h5>Booking Summary</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Route:</strong> <?php echo $route['from_city']; ?> to <?php echo $route['to_city']; ?></p>
                                    <p><strong>Date:</strong> <?php echo format_date($travel_date); ?></p>
                                    <p><strong>Time:</strong> <?php echo format_time($route['departure_time']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Seats:</strong> <?php echo implode(', ', $selected_seats); ?></p>
                                    <p><strong>Bus:</strong> <?php echo $route['bus_name']; ?></p>
                                    <p><strong>Total Amount:</strong> ₹<?php echo $total_amount; ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <!-- Payment Methods -->
                            <h5 class="mb-3">Select Payment Method</h5>
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="payment-option" onclick="selectPayment('upi')">
                                        <div class="text-center">
                                            <div class="payment-icon text-primary">
                                                <i class="fas fa-mobile-alt"></i>
                                            </div>
                                            <h6>UPI</h6>
                                            <small class="text-muted">Google Pay, PhonePe, Paytm</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="payment-option" onclick="selectPayment('card')">
                                        <div class="text-center">
                                            <div class="payment-icon text-primary">
                                                <i class="fas fa-credit-card"></i>
                                            </div>
                                            <h6>Credit/Debit Card</h6>
                                            <small class="text-muted">Visa, MasterCard, RuPay</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="payment-option" onclick="selectPayment('netbanking')">
                                        <div class="text-center">
                                            <div class="payment-icon text-primary">
                                                <i class="fas fa-university"></i>
                                            </div>
                                            <h6>Net Banking</h6>
                                            <small class="text-muted">All major banks</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="payment-option" onclick="selectPayment('wallet')">
                                        <div class="text-center">
                                            <div class="payment-icon text-primary">
                                                <i class="fas fa-wallet"></i>
                                            </div>
                                            <h6>Wallet</h6>
                                            <small class="text-muted">Paytm, Amazon Pay</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Hidden payment method input -->
                            <input type="hidden" id="payment_method" name="payment_method" value="upi">
                            
                            <!-- Payment Details -->
                            <div id="paymentDetails" class="payment-details">
                                <!-- Will be populated by JavaScript -->
                            </div>
                            
                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-check-circle me-2"></i> Pay ₹<?php echo $total_amount; ?>
                                </button>
                                <a href="seat-selection.php?route_id=<?php echo $route_id; ?>&travel_date=<?php echo $travel_date; ?>" 
                                   class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i> Back to Seat Selection
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectPayment(method) {
            // Update selected payment method
            document.getElementById('payment_method').value = method;
            
            // Update UI
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
            
            // Show payment details
            showPaymentDetails(method);
        }
        
        function showPaymentDetails(method) {
            const detailsDiv = document.getElementById('paymentDetails');
            let html = '';
            
            switch(method) {
                case 'upi':
                    html = `
                        <div class="mb-3">
                            <label for="upi_id" class="form-label">UPI ID</label>
                            <input type="text" class="form-control" id="upi_id" placeholder="username@bank" required>
                        </div>
                        <div class="mb-3">
                            <button type="button" class="btn btn-outline-primary me-2" onclick="fillDemoUPI()">
                                <i class="fas fa-bolt"></i> Demo UPI
                            </button>
                        </div>
                    `;
                    break;
                    
                case 'card':
                    html = `
                        <div class="mb-3">
                            <label for="card_number" class="form-label">Card Number</label>
                            <input type="text" class="form-control" id="card_number" placeholder="1234 5678 9012 3456" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="expiry_date" class="form-label">Expiry Date</label>
                                <input type="text" class="form-control" id="expiry_date" placeholder="MM/YY" required>
                            </div>
                            <div class="col-md-6">
                                <label for="cvv" class="form-label">CVV</label>
                                <input type="text" class="form-control" id="cvv" placeholder="123" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="card_name" class="form-label">Name on Card</label>
                            <input type="text" class="form-control" id="card_name" placeholder="John Doe" required>
                        </div>
                    `;
                    break;
                    
                case 'netbanking':
                    html = `
                        <div class="mb-3">
                            <label for="bank" class="form-label">Select Bank</label>
                            <select class="form-select" id="bank" required>
                                <option value="">Choose your bank</option>
                                <option value="sbi">State Bank of India</option>
                                <option value="hdfc">HDFC Bank</option>
                                <option value="icici">ICICI Bank</option>
                                <option value="axis">Axis Bank</option>
                                <option value="kotak">Kotak Mahindra Bank</option>
                            </select>
                        </div>
                    `;
                    break;
                    
                case 'wallet':
                    html = `
                        <div class="mb-3">
                            <label class="form-label">Select Wallet</label>
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-outline-primary" onclick="selectWallet('paytm')">
                                    <i class="fab fa-paypal"></i> Paytm
                                </button>
                                <button type="button" class="btn btn-outline-primary" onclick="selectWallet('phonepe')">
                                    <i class="fas fa-mobile-alt"></i> PhonePe
                                </button>
                                <button type="button" class="btn btn-outline-primary" onclick="selectWallet('gpay')">
                                    <i class="fab fa-google"></i> Google Pay
                                </button>
                            </div>
                        </div>
                    `;
                    break;
            }
            
            detailsDiv.innerHTML = html;
            detailsDiv.classList.add('active');
        }
        
        function fillDemoUPI() {
            document.getElementById('upi_id').value = 'demo@upi';
        }
        
        function selectWallet(wallet) {
            alert(`Redirecting to ${wallet} payment...`);
        }
        
        // Initialize with UPI selected
        selectPayment('upi');
    </script>
</body>
</html>