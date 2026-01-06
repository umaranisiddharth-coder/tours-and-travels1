// Common JavaScript Functions

// Toggle Seat Selection
function toggleSeat(seatElement, isAvailable) {
    if (!isAvailable) return;
    
    const seatNumber = seatElement.getAttribute('data-seat');
    const isSelected = seatElement.classList.contains('selected');
    
    if (!isSelected) {
        // Check max seats limit
        const selectedCount = document.querySelectorAll('.seat.selected').length;
        if (selectedCount >= 6) {
            showAlert('You can select up to 6 seats only.', 'warning');
            return;
        }
        
        seatElement.classList.remove('available', 'sleeper-upper', 'sleeper-lower');
        seatElement.classList.add('selected');
    } else {
        seatElement.classList.remove('selected');
        
        // Restore original class
        if (seatNumber.startsWith('U')) {
            seatElement.classList.add('sleeper-upper', 'available');
        } else if (seatNumber.startsWith('L')) {
            seatElement.classList.add('sleeper-lower', 'available');
        } else {
            seatElement.classList.add('available');
        }
    }
    
    updateBookingSummary();
}

// Update Booking Summary
function updateBookingSummary(seatPrice = 0) {
    const selectedSeats = document.querySelectorAll('.seat.selected');
    const seatCount = selectedSeats.length;
    
    // Update selected seats list
    const selectedSeatsList = document.getElementById('selectedSeatsList');
    if (selectedSeatsList) {
        if (seatCount === 0) {
            selectedSeatsList.innerHTML = '<span class="text-muted">No seats selected</span>';
        } else {
            const seatsArray = Array.from(selectedSeats).map(seat => seat.getAttribute('data-seat'));
            selectedSeatsList.textContent = seatsArray.join(', ');
        }
    }
    
    // Update fare calculation
    if (seatPrice > 0) {
        const baseFareAmount = seatCount * seatPrice;
        const taxAmount = baseFareAmount * 0.18;
        const totalAmount = baseFareAmount + taxAmount;
        
        if (document.getElementById('baseFare')) {
            document.getElementById('baseFare').textContent = baseFareAmount.toFixed(2);
            document.getElementById('taxes').textContent = taxAmount.toFixed(2);
            document.getElementById('totalAmount').textContent = totalAmount.toFixed(2);
        }
    }
    
    // Update proceed button
    const proceedBtn = document.getElementById('proceedBtn');
    if (proceedBtn) {
        proceedBtn.disabled = seatCount === 0;
        if (seatCount > 0) {
            proceedBtn.innerHTML = `<i class="fas fa-credit-card me-2"></i> Proceed to Pay (${seatCount} seat${seatCount > 1 ? 's' : ''})`;
        }
    }
    
    // Update hidden inputs
    const form = document.getElementById('seatForm');
    if (form) {
        let hiddenInputs = form.querySelectorAll('input[name="selected_seats[]"]');
        hiddenInputs.forEach(input => input.remove());
        
        Array.from(selectedSeats).forEach(seat => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected_seats[]';
            input.value = seat.getAttribute('data-seat');
            form.appendChild(input);
        });
    }
}

// Show Alert
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Add to page
    document.body.insertBefore(alertDiv, document.body.firstChild);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Format Currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR'
    }).format(amount);
}

// Validate Email
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Validate Phone
function validatePhone(phone) {
    const re = /^[6-9]\d{9}$/;
    return re.test(phone);
}

// Bus Tracking Simulation
class BusTracker {
    constructor(busId) {
        this.busId = busId;
        this.interval = null;
        this.progress = 0;
    }
    
    startTracking() {
        this.interval = setInterval(() => {
            this.progress += Math.random() * 5;
            if (this.progress > 100) this.progress = 100;
            
            this.updateUI();
            
            if (this.progress >= 100) {
                this.stopTracking();
            }
        }, 2000);
    }
    
    stopTracking() {
        if (this.interval) {
            clearInterval(this.interval);
        }
    }
    
    updateUI() {
        const progressBar = document.getElementById('journeyProgress');
        const progressText = document.getElementById('progressText');
        
        if (progressBar) {
            progressBar.style.width = `${this.progress}%`;
        }
        
        if (progressText) {
            progressText.textContent = `${Math.round(this.progress)}% Complete`;
        }
    }
}

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Set minimum date for date inputs
    const today = new Date().toISOString().split('T')[0];
    document.querySelectorAll('input[type="date"]').forEach(input => {
        input.min = today;
    });
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Print Ticket
    document.querySelectorAll('.print-ticket').forEach(button => {
        button.addEventListener('click', function() {
            window.print();
        });
    });
    
    // Demo payment auto-fill
    if (document.getElementById('demoPayment')) {
        document.getElementById('demoPayment').addEventListener('click', function() {
            document.getElementById('card_number').value = '4111111111111111';
            document.getElementById('expiry_date').value = '12/25';
            document.getElementById('cvv').value = '123';
            document.getElementById('card_name').value = 'Demo User';
            
            showAlert('Demo payment details filled. This is for testing only.', 'info');
        });
    }
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        toggleSeat,
        updateBookingSummary,
        showAlert,
        formatCurrency,
        validateEmail,
        validatePhone,
        BusTracker
    };
}