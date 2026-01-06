
<?php
require_once 'config.php';

if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}

// Check if booking ID exists in session
if (!isset($_SESSION['last_booking_id'])) {
    $_SESSION['error'] = 'No booking found. Please make a booking first.';
    header("Location: booking.php");
    exit();
}

$booking_id = (int)$_SESSION['last_booking_id'];

// Get booking details with improved security
$query = "SELECT b.*, r.from_city, r.to_city, r.departure_time, r.arrival_time, r.fare,
                 bus.bus_name, bus.bus_number, bus.bus_type, bus.amenities,
                 u.full_name, u.email, u.phone,
                 p.payment_id, p.payment_method, p.payment_date
          FROM bookings b
          JOIN bus_routes r ON b.route_id = r.id
          JOIN buses bus ON b.bus_id = bus.id
          JOIN users u ON b.user_id = u.id
          LEFT JOIN payments p ON b.id = p.booking_id
          WHERE b.id = $booking_id AND b.user_id = {$_SESSION['user_id']}";

$result = mysqli_query($conn, $query);

if (!$result) {
    $_SESSION['error'] = 'Database error occurred. Please try again.';
    header("Location: user.php");
    exit();
}

$booking = mysqli_fetch_assoc($result);

if (!$booking) {
    $_SESSION['error'] = 'Booking not found or you do not have permission to view it.';
    header("Location: user.php");
    exit();
}

// Format seat numbers for display
$seats_array = explode(',', $booking['seats_booked']);
$formatted_seats = implode(', ', $seats_array);

// Calculate total fare for verification
$calculated_total = $booking['fare'] * $booking['total_seats'];

// Check if payment is verified
$payment_verified = ($booking['payment_status'] == 'paid' && !empty($booking['payment_id']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation - SR Travels</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .ticket {
            background: var(--primary-gradient);
            border-radius: 20px;
            color: white;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        
        .ticket::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--secondary-gradient);
        }
        
        .ticket-header {
            border-bottom: 2px dashed rgba(255,255,255,0.3);
            padding-bottom: 20px;
            margin-bottom: 20px;
            position: relative;
        }
        
        .ticket-details {
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 25px;
            backdrop-filter: blur(10px);
        }
        
        .qr-container {
            background: white;
            padding: 20px;
            border-radius: 15px;
            display: inline-block;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .ticket-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 0.9rem;
            padding: 8px 15px;
            border-radius: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
        }
        
        .feature-list li {
            padding: 8px 0;
            border-bottom: 1px dashed rgba(255,255,255,0.2);
        }
        
        .feature-list li:last-child {
            border-bottom: none;
        }
        
        .feature-list i {
            width: 25px;
            text-align: center;
            margin-right: 10px;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            
            .ticket {
                box-shadow: none;
                border: 2px solid #000;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-light bg-white shadow-sm no-print">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="index.php">SR<span class="text-warning">TRAVELS</span></a>
            <div class="d-flex align-items-center">
                <a href="user.php" class="btn btn-outline-primary btn-sm me-2">Dashboard</a>
                <a href="booking.php" class="btn btn-primary btn-sm">Book Another</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="text-center mb-5">
                    <div class="mb-4">
                        <i class="fas fa-check-circle fa-5x text-success"></i>
                        <div class="mt-3">
                            <span class="badge bg-success fs-6 px-4 py-2">
                                <i class="fas fa-shield-check me-2"></i>Payment Verified
                            </span>
                        </div>
                    </div>
                    <h1 class="display-5 fw-bold">Booking Confirmed!</h1>
                    <p class="lead">Your bus ticket has been successfully booked. Details are below.</p>
                    <p class="text-muted">Booking ID: <?php echo $booking['booking_id']; ?> | <?php echo format_date($booking['booking_date']) . ' at ' . format_time($booking['booking_date']); ?></p>
                </div>
                
                <!-- Success Alert -->
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle fa-2x me-3"></i>
                        <div>
                            <h5 class="alert-heading mb-1">Booking Successful!</h5>
                            <p class="mb-0">Your e-ticket has been sent to <?php echo $booking['email']; ?></p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                
                <!-- Ticket -->
                <div class="ticket">
                    <div class="ticket-header">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h2 class="fw-bold mb-2">SR TRAVELS</h2>
                                <p class="mb-0 opacity-75">E-Ticket | Digital Boarding Pass</p>
                            </div>
                            <div class="text-end">
                                <div class="ticket-badge bg-success">
                                    <i class="fas fa-check me-1"></i>CONFIRMED
                                </div>
                                <p class="mb-0 mt-3"><?php echo format_date($booking['booking_date']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="ticket-details">
                                <!-- Passenger Info -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <h6 class="text-uppercase opacity-75 mb-2">Passenger Details</h6>
                                        <h4 class="fw-bold"><?php echo $booking['full_name']; ?></h4>
                                        <div class="mt-3">
                                            <p class="mb-1"><i class="fas fa-envelope me-2"></i><?php echo $booking['email']; ?></p>
                                            <p class="mb-0"><i class="fas fa-phone me-2"></i><?php echo $booking['phone']; ?></p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-uppercase opacity-75 mb-2">Journey Details</h6>
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="me-3">
                                                <h5 class="fw-bold mb-0"><?php echo $booking['from_city']; ?></h5>
                                                <small>Departure</small>
                                            </div>
                                            <div class="flex-grow-1 text-center">
                                                <i class="fas fa-arrow-right fa-lg"></i>
                                            </div>
                                            <div>
                                                <h5 class="fw-bold mb-0"><?php echo $booking['to_city']; ?></h5>
                                                <small>Destination</small>
                                            </div>
                                        </div>
                                        <p class="mb-2"><i class="fas fa-calendar me-2"></i><?php echo format_date($booking['travel_date']); ?></p>
                                    </div>
                                </div>
                                
                                <!-- Journey Timings -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="p-3 bg-white bg-opacity-10 rounded">
                                            <h6 class="text-uppercase opacity-75 mb-2">Departure</h6>
                                            <h3 class="fw-bold text-warning"><?php echo format_time($booking['departure_time']); ?></h3>
                                            <p class="mb-0">Boarding starts 30 minutes before</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-3 bg-white bg-opacity-10 rounded">
                                            <h6 class="text-uppercase opacity-75 mb-2">Arrival</h6>
                                            <h3 class="fw-bold text-success"><?php echo format_time($booking['arrival_time']); ?></h3>
                                            <p class="mb-0">Estimated arrival time</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Bus & Seats -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-uppercase opacity-75 mb-2">Bus Details</h6>
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="me-3">
                                                <i class="fas fa-bus fa-2x"></i>
                                            </div>
                                            <div>
                                                <h5 class="fw-bold mb-1"><?php echo $booking['bus_name']; ?></h5>
                                                <p class="mb-0"><?php echo $booking['bus_number']; ?></p>
                                            </div>
                                        </div>
                                        <span class="badge <?php echo $booking['bus_type'] == 'seater' ? 'bg-info' : 'bg-warning'; ?> fs-6 px-3 py-2">
                                            <i class="fas fa-<?php echo $booking['bus_type'] == 'seater' ? 'chair' : 'bed'; ?> me-2"></i>
                                            <?php echo ucfirst($booking['bus_type']); ?> Bus
                                        </span>
                                        
                                        <?php if($booking['amenities']): ?>
                                        <div class="mt-3">
                                            <h6 class="text-uppercase opacity-75 mb-2">Amenities</h6>
                                            <p class="mb-0"><?php echo $booking['amenities']; ?></p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <h6 class="text-uppercase opacity-75 mb-2">Seat Information</h6>
                                        <div class="mb-3">
                                            <h3 class="fw-bold"><?php echo $formatted_seats; ?></h3>
                                            <p class="mb-0"><?php echo $booking['total_seats']; ?> seat(s) booked</p>
                                        </div>
                                        
                                        <div class="mt-4">
                                            <h6 class="text-uppercase opacity-75 mb-2">Fare Details</h6>
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Base Fare (<?php echo $booking['total_seats']; ?> seats × ₹<?php echo $booking['fare']; ?>)</span>
                                                <span>₹<?php echo $calculated_total; ?></span>
                                            </div>
                                            <?php if($calculated_total != $booking['total_amount']): ?>
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Taxes & Charges</span>
                                                <span>₹<?php echo number_format($booking['total_amount'] - $calculated_total, 2); ?></span>
                                            </div>
                                            <?php endif; ?>
                                            <hr class="my-2">
                                            <div class="d-flex justify-content-between fw-bold fs-5">
                                                <span>Total Amount</span>
                                                <span class="text-warning">₹<?php echo $booking['total_amount']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="text-center">
                                <!-- QR Code -->
                                <div class="qr-container mb-4">
                                    <div id="qrcode" style="width: 200px; height: 200px;"></div>
                                </div>
                                <p class="mb-3">
                                    <i class="fas fa-qrcode me-2"></i>Scan QR at boarding point
                                </p>
                                
                                <!-- Payment Info -->
                                <div class="p-3 bg-white bg-opacity-10 rounded mb-4">
                                    <h6 class="text-uppercase opacity-75 mb-2">Payment Info</h6>
                                    <?php if($payment_verified): ?>
                                    <div class="mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <span>Payment Verified</span>
                                    </div>
                                    <p class="mb-1">Payment ID: <?php echo $booking['payment_id']; ?></p>
                                    <p class="mb-0">Method: <?php echo ucfirst($booking['payment_method']); ?></p>
                                    <p class="mb-0">Date: <?php echo format_date($booking['payment_date']); ?></p>
                                    <?php else: ?>
                                    <div class="mb-2">
                                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                        <span>Payment Pending</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Important Notes -->
                                <div class="p-3 bg-white bg-opacity-10 rounded">
                                    <h6 class="text-uppercase opacity-75 mb-2">Important</h6>
                                    <ul class="feature-list text-start">
                                        <li><i class="fas fa-clock"></i> Arrive 30 mins before departure</li>
                                        <li><i class="fas fa-id-card"></i> Carry valid ID proof</li>
                                        <li><i class="fas fa-map-marker-alt"></i> Boarding: <?php echo $booking['from_city']; ?> Bus Stand</li>
                                        <li><i class="fas fa-headset"></i> Support: +91 9356437871</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="d-grid gap-2 d-md-flex justify-content-md-center no-print mb-5">
                    <button class="btn btn-primary btn-lg me-md-2" onclick="window.print()">
                        <i class="fas fa-print me-2"></i> Print Ticket
                    </button>
                    <a href="user.php" class="btn btn-outline-primary btn-lg me-md-2">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="bus-tracking.php?booking_id=<?php echo $booking['booking_id']; ?>" class="btn btn-success btn-lg">
                        <i class="fas fa-map-marker-alt me-2"></i> Track Bus
                    </a>
                    <button class="btn btn-outline-success btn-lg ms-md-2" onclick="downloadTicket()">
                        <i class="fas fa-download me-2"></i> Download
                    </button>
                </div>
                
                <!-- Next Steps -->
                <div class="card border-success mb-5 no-print">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i> Next Steps</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3 mb-3">
                                <div class="p-3 border rounded">
                                    <i class="fas fa-envelope-open-text fa-2x text-primary mb-2"></i>
                                    <h6>Check Email</h6>
                                    <p class="small text-muted mb-0">Your e-ticket has been sent to your email</p>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="p-3 border rounded">
                                    <i class="fas fa-map-marked-alt fa-2x text-success mb-2"></i>
                                    <h6>Save Location</h6>
                                    <p class="small text-muted mb-0">Save boarding point location on maps</p>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="p-3 border rounded">
                                    <i class="fas fa-bell fa-2x text-warning mb-2"></i>
                                    <h6>Set Reminder</h6>
                                    <p class="small text-muted mb-0">Set reminder for boarding time</p>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="p-3 border rounded">
                                    <i class="fas fa-share-alt fa-2x text-info mb-2"></i>
                                    <h6>Share Details</h6>
                                    <p class="small text-muted mb-0">Share booking with fellow travelers</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    
    <script>
        // Generate QR Code
        const qrData = `SRTRAVELS|Booking:<?php echo $booking['booking_id']; ?>|Passenger:<?php echo $booking['full_name']; ?>|From:<?php echo $booking['from_city']; ?>|To:<?php echo $booking['to_city']; ?>|Date:<?php echo $booking['travel_date']; ?>|Time:<?php echo $booking['departure_time']; ?>|Seats:<?php echo $formatted_seats; ?>`;
        new QRCode(document.getElementById("qrcode"), {
            text: qrData,
            width: 200,
            height: 200,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
        
        // Download Ticket as PDF
        function downloadTicket() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('p', 'mm', 'a4');
            const ticket = document.querySelector('.ticket');
            
            html2canvas(ticket, {
                scale: 2,
                backgroundColor: null
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const imgWidth = 190;
                const imgHeight = (canvas.height * imgWidth) / canvas.width;
                
                doc.addImage(imgData, 'PNG', 10, 10, imgWidth, imgHeight);
                doc.setFontSize(10);
                doc.text('SR Travels - E-Ticket | Booking ID: <?php echo $booking['booking_id']; ?>', 105, imgHeight + 20, { align: 'center' });
                doc.text('Downloaded on: <?php echo date("d M Y H:i:s"); ?>', 105, imgHeight + 25, { align: 'center' });
                
                doc.save('SRTravels-Ticket-<?php echo $booking['booking_id']; ?>.pdf');
                
                // Show success message
                showToast('Ticket downloaded successfully!', 'success');
            });
        }
        
        // Toast notification function
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type} border-0 position-fixed bottom-0 end-0 m-3`;
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            
            document.body.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            
            toast.addEventListener('hidden.bs.toast', function () {
                document.body.removeChild(toast);
            });
        }
        
        // Auto-close success alert after 5 seconds
        setTimeout(() => {
            const alert = document.querySelector('.alert-success');
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    </script>
</body>
</html>
<?php
// Clear the booking ID from session after displaying
unset($_SESSION['last_booking_id']);
?>
