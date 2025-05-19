<?php
ob_start();

require_once __DIR__ . '/../templates/header.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    $_SESSION['message'] = "Please log in to continue with booking.";
    $_SESSION['message_type'] = "error";
    header('Location: /pages/login.php');
    exit();
}

if (!isset($_SESSION['booking_data'])) {
    header('Location: /pages/shows.php');
    exit();
}

$booking_data = $_SESSION['booking_data'];
$showtime = getShowtime($booking_data['showtime_id']);
$seats = getSeats($booking_data['showtime_id']);

if (!$showtime) {
    header('Location: /pages/shows.php');
    exit();
}

$coupon_message = '';
$coupon_type = '';
$applied_coupon = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_coupon'])) {
    $coupon_code = trim($_POST['coupon_code']);
    
    if (empty($coupon_code)) {
        $coupon_message = "Please enter a coupon code.";
        $coupon_type = "danger";
    } else {
        $validated_coupon = validateCoupon($coupon_code, $booking_data['total_amount']);
        
        if ($validated_coupon) {
            $discount = calculateDiscount($validated_coupon, $booking_data['total_amount']);
            
            $_SESSION['booking_data']['coupon_id'] = $validated_coupon['id'];
            $_SESSION['booking_data']['discount_amount'] = $discount;
            $_SESSION['booking_data']['discounted_total'] = $booking_data['total_amount'] - $discount;
            
            $booking_data = $_SESSION['booking_data'];
            $applied_coupon = $validated_coupon;
            
            $coupon_message = "Coupon applied successfully!";
            $coupon_type = "success";
        } else {
            $coupon_message = "Invalid or expired coupon code.";
            $coupon_type = "danger";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_booking'])) {
    $payment_valid = true;
    $payment_error = "";
    
    if (empty($_POST['card_number']) || !preg_match('/^\d{16}$/', $_POST['card_number'])) {
        $payment_valid = false;
        $payment_error = "Please enter a valid 16-digit credit card number.";
    } elseif (empty($_POST['card_exp_month']) || !preg_match('/^(0[1-9]|1[0-2])$/', $_POST['card_exp_month'])) {
        $payment_valid = false;
        $payment_error = "Please enter a valid expiration month (01-12).";
    } elseif (empty($_POST['card_exp_year']) || !preg_match('/^\d{4}$/', $_POST['card_exp_year'])) {
        $payment_valid = false;
        $payment_error = "Please enter a valid 4-digit expiration year.";
    } elseif (empty($_POST['card_cvv']) || !preg_match('/^\d{3,4}$/', $_POST['card_cvv'])) {
        $payment_valid = false;
        $payment_error = "Please enter a valid CVV code (3-4 digits).";
    } elseif (empty($_POST['billing_name'])) {
        $payment_valid = false;
        $payment_error = "Please enter the cardholder name.";
    }
    
    if ($payment_valid) {
        $current_year = (int)date('Y');
        $current_month = (int)date('m');
        $exp_year = (int)$_POST['card_exp_year'];
        $exp_month = (int)$_POST['card_exp_month'];
        
        if ($exp_year < $current_year || ($exp_year == $current_year && $exp_month < $current_month)) {
            $payment_valid = false;
            $payment_error = "The card has expired.";
        }
    }
    
    if ($payment_valid) {
        $final_amount = isset($booking_data['discounted_total']) ? $booking_data['discounted_total'] : $booking_data['total_amount'];
        
        $payment_data = [
            'card_last_four' => substr($_POST['card_number'], -4),
            'billing_name' => $_POST['billing_name'],
            'payment_status' => 'Completed'
        ];
        
        $booking_id = createBooking(
            $_SESSION['user_id'],
            $booking_data['showtime_id'],
            $booking_data['seats'],
            $final_amount,
            $payment_data
        );
        
        if ($booking_id) {
            if (isset($booking_data['coupon_id']) && isset($booking_data['discount_amount'])) {
                applyCoupon($booking_id, $booking_data['coupon_id'], $booking_data['discount_amount']);
            }
            
            unset($_SESSION['booking_data']);
            $_SESSION['message'] = "Payment successful! Your booking ID is: " . $booking_id;
            $_SESSION['message_type'] = "success";
            header('Location: /pages/bookings.php');
            exit();
        } else {
            $error = "Payment processing failed. Please try again.";
        }
    } else {
        $error = $payment_error;
    }
}
?>

<div class="container">
<h1>Confirm Booking</h1>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="card-title h5 mb-0">Booking Details</h2>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h3 class="h5">Show Information</h3>
                        <p><strong>Show:</strong> <?php echo htmlspecialchars($showtime['title']); ?></p>
                        <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($showtime['date'])); ?></p>
                        <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($showtime['time'])); ?></p>
                        <p><strong>Hall:</strong> <?php echo htmlspecialchars($showtime['hall']); ?></p>
</div>

                    <div class="mb-4">
                        <h3 class="h5">Selected Seats</h3>
                        <div class="row">
                            <?php foreach ($booking_data['seats'] as $seat_id): ?>
                                <?php foreach ($seats as $seat): ?>
                                    <?php if ($seat['id'] == $seat_id): ?>
                                        <div class="col-md-4 mb-2">
                                            <div class="card bg-light">
                                                <div class="card-body p-2 text-center">
                                                    <strong>
                                                        <?php echo $seat['seat_row'] . $seat['seat_number']; ?>
                                                    </strong>
                                                    <div>
                                                        <span class="badge bg-<?php echo $seat['category'] === 'Premium' ? 'success' : ($seat['category'] === 'Economy' ? 'info' : 'primary'); ?>">
                                                            <?php echo $seat['category']; ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h3 class="h5">Payment Information</h3>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> This is a demo payment form. In a real application, use a secure payment processor.
                        </div>
                        <form method="POST" action="" id="payment-form">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="billing_name" class="form-label">Cardholder Name</label>
                                    <input type="text" class="form-control" id="billing_name" name="billing_name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="card_number" class="form-label">Card Number</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" maxlength="16" required>
                                        <span class="input-group-text">
                                            <i class="fab fa-cc-visa me-1"></i>
                                            <i class="fab fa-cc-mastercard me-1"></i>
                                            <i class="fab fa-cc-amex"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="card_exp_month" class="form-label">Expiration Month</label>
                                    <select class="form-select" id="card_exp_month" name="card_exp_month" required>
                                        <option value="">Month</option>
                                        <?php for ($i = 1; $i <= 12; $i++): ?>
                                            <option value="<?php echo sprintf('%02d', $i); ?>"><?php echo sprintf('%02d', $i); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="card_exp_year" class="form-label">Expiration Year</label>
                                    <select class="form-select" id="card_exp_year" name="card_exp_year" required>
                                        <option value="">Year</option>
                                        <?php for ($i = date('Y'); $i <= date('Y') + 10; $i++): ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="card_cvv" class="form-label">CVV</label>
                                    <input type="text" class="form-control" id="card_cvv" name="card_cvv" maxlength="4" required>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title h5 mb-0">Payment Summary</h2>
                </div>
                <div class="card-body">
                    <?php if (!$applied_coupon): ?>
                        <form method="POST" action="" class="mb-4">
                            <div class="input-group mb-2">
                                <input type="text" class="form-control" name="coupon_code" placeholder="Enter coupon code">
                                <button type="submit" name="apply_coupon" class="btn btn-secondary">Apply</button>
                            </div>
                            <?php if ($coupon_message): ?>
                                <div class="alert alert-<?php echo $coupon_type; ?> py-2 small">
                                    <?php echo $coupon_message; ?>
                                </div>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>
                    
                    <div class="mb-4">
                        <div class="d-flex justify-content-between">
                            <span>Subtotal:</span>
                            <span>$<?php echo number_format($booking_data['total_amount'], 2); ?></span>
                        </div>
                        
                        <?php if ($applied_coupon): ?>
                            <div class="d-flex justify-content-between mt-2">
                                <span>
                                    Discount (<?php echo htmlspecialchars($applied_coupon['code']); ?>):
                                    <button type="button" class="btn btn-link btn-sm p-0 text-danger" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#removeCouponModal">
                                        <i class="fas fa-times-circle"></i>
                                    </button>
                                </span>
                                <span class="text-danger">-$<?php echo number_format($booking_data['discount_amount'], 2); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <hr>
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Total Amount:</span>
                            <span>
                                <?php if ($applied_coupon): ?>
                                    <span class="text-decoration-line-through me-2 text-muted">
                                        $<?php echo number_format($booking_data['total_amount'], 2); ?>
                                    </span>
                                    $<?php echo number_format($booking_data['discounted_total'], 2); ?>
                                <?php else: ?>
                                    $<?php echo number_format($booking_data['total_amount'], 2); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="terms" form="payment-form" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">terms and conditions</a>
                            </label>
                        </div>
                    </div>
                    <button type="submit" name="confirm_booking" form="payment-form" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-lock me-1"></i> Complete Payment
                    </button>
                    
                    <div class="mt-3 text-center">
                        <a href="/pages/select_seats.php?id=<?php echo $showtime['id']; ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Change Seats
                        </a>
                    </div>
                    
                    <div class="mt-4 text-center">
                        <div class="d-flex justify-content-center gap-2">
                            <i class="fab fa-cc-visa fa-2x text-primary"></i>
                            <i class="fab fa-cc-mastercard fa-2x text-danger"></i>
                            <i class="fab fa-cc-amex fa-2x text-info"></i>
                            <i class="fab fa-cc-discover fa-2x text-warning"></i>
                        </div>
                        <p class="text-muted small mt-2">Secure payment processing</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
        </div>
        
<div class="modal fade" id="removeCouponModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Remove Coupon</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to remove the applied coupon?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="">
                    <input type="hidden" name="remove_coupon" value="1">
                    <button type="submit" class="btn btn-danger">Remove Coupon</button>
                </form>
            </div>
        </div>
        </div>
    </div>
    
<div class="modal fade" id="termsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Terms and Conditions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>Booking Terms</h6>
                <p>By confirming this booking, you agree to the following terms:</p>
                <ul>
                    <li>Tickets are non-refundable once purchased</li>
                    <li>Exchanges may be permitted up to 24 hours before the show</li>
                    <li>You must arrive at least 15 minutes before the show starts</li>
                    <li>The management reserves the right to refuse entry if you arrive late</li>
                </ul>
                
                <h6>Privacy Policy</h6>
                <p>Your personal information will be handled according to our privacy policy.</p>
                
                <h6>Payment Terms</h6>
                <p>All payments are securely processed. Your card details are not stored on our servers.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Understand</button>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
<?php 
ob_end_flush(); 
?> 