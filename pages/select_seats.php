<?php
ob_start();

require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

include __DIR__ . '/../templates/header.php';

if (!isLoggedIn()) {
    $_SESSION['message'] = "Please login to book tickets";
    $_SESSION['message_type'] = "error";
    header("Location: /pages/login.php");
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "Invalid showtime ID";
    $_SESSION['message_type'] = "error";
    header("Location: /pages/shows.php");
    exit();
}

$showtime_id = (int)$_GET['id'];
$showtime = getShowtime($showtime_id);

if (!$showtime) {
    $_SESSION['message'] = "Showtime not found";
    $_SESSION['message_type'] = "error";
    header("Location: /pages/shows.php");
    exit();
}

$stmt = $conn->prepare("SELECT s.id, s.seat_row, s.seat_number, s.category, s.is_booked,
                            CASE 
                                WHEN s.is_booked = 1 OR bd.id IS NOT NULL THEN 'booked' 
                                ELSE 'available' 
                            END AS status
                        FROM seats s
                        LEFT JOIN booking_details bd ON s.id = bd.seat_id
                        WHERE s.showtime_id = ?
                        ORDER BY s.seat_row, s.seat_number");
$stmt->bind_param("i", $showtime_id);
$stmt->execute();
$result = $stmt->get_result();
$seats = [];
while ($row = $result->fetch_assoc()) {
    $seats[] = $row;
}
$rows = [];
foreach ($seats as $seat) {
    $seatClass = 'seat ' . strtolower($seat['category']);
    if ($seat['status'] === 'booked') {
        $seatClass .= ' booked';
    }
    $rows[$seat['seat_row']][] = [
        'id' => $seat['id'],
        'number' => $seat['seat_number'],
        'category' => strtolower($seat['category']),
        'status' => $seat['status'],
        'class' => $seatClass
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['seats']) || empty($_POST['seats'])) {
        $error = "Please select at least one seat";
    } else {
        $selected_seats = $_POST['seats'];
        $total_amount = calculateTicketPrice($showtime_id, $selected_seats);
        
        $_SESSION['booking_data'] = [
            'showtime_id' => $showtime_id,
            'seats' => $selected_seats,
            'total_amount' => $total_amount
        ];
        
        header('Location: /pages/confirm_booking.php');
        exit();
    }
}
?>

<div class="bg-light">
    <div class="container py-5">
        <h1 class="fw-bold">Select Your Seats</h1>
        <p class="lead mb-0">
            <span class="fw-semibold"><?php echo htmlspecialchars($showtime['title']); ?></span> | 
            <?php echo date('F j, Y', strtotime($showtime['date'])); ?> at <?php echo date('g:i A', strtotime($showtime['time'])); ?>
        </p>
    </div>
</div>

<div class="container py-5">
    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="seating-chart">
                <div class="screen">Screen</div>
                
                <form method="POST" action="" id="seats-form">
                    <?php 
                    $current_row = "";
                    foreach ($rows as $row => $seatsInRow): 
                        if ($current_row != $row):
                            $current_row = $row;
                            if ($current_row !== ""): 
                                echo '</div>';
                            endif;
                            echo '<div class="seats-grid">';
                            echo '<div class="row-label">' . $current_row . '</div>';
                        endif;
                        
                        foreach ($seatsInRow as $seat):
                    ?>
                        <div class="seat-container">
                            <input type="checkbox" 
                                   id="seat_<?php echo $seat['id']; ?>" 
                                   name="seats[]" 
                                   value="<?php echo $seat['id']; ?>"
                                   <?php echo $seat['status'] === 'booked' ? 'disabled' : ''; ?>
                                   class="seat-checkbox">
                            <label for="seat_<?php echo $seat['id']; ?>" 
                                   class="<?php echo $seat['class']; ?>">
                                <?php echo $current_row . $seat['number']; ?>
                            </label>
                        </div>
                    <?php 
                        endforeach; 
                    endforeach; 
                    if ($current_row !== ""): 
                        echo '</div>';
                    endif;
                    ?>
                    
                    <div class="seat-legend">
                        <div class="legend-item">
                            <div class="seat premium"></div>
                            <span>Premium</span>
                        </div>
                        <div class="legend-item">
                            <div class="seat regular"></div>
                            <span>Regular</span>
                        </div>
                        <div class="legend-item">
                            <div class="seat economy"></div>
                            <span>Economy</span>
                        </div>
                        <div class="legend-item">
                            <div class="seat booked"></div>
                            <span>Booked</span>
                        </div>
                        <div class="legend-item">
                            <div class="seat selected"></div>
                            <span>Selected</span>
                        </div>
                    </div>
                    
                    <div class="d-md-none mt-4">
                        <button type="submit" form="seats-form" class="btn btn-primary d-block w-100">
                            <i class="fas fa-check-circle me-2"></i>Continue to Booking
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="col-lg-4">
                <div class="card shadow-sm booking-summary">
                    <div class="card-body">
                        <h3 class="card-title h5 fw-bold mb-4">Booking Summary</h3>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Show:</span>
                                <span class="fw-medium"><?php echo htmlspecialchars($showtime['title']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Date:</span>
                                <span class="fw-medium"><?php echo date('F j, Y', strtotime($showtime['date'])); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Time:</span>
                                <span class="fw-medium"><?php echo date('g:i A', strtotime($showtime['time'])); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Theatre:</span>
                                <span class="fw-medium"><?php echo isset($showtime['theatre_name']) ? htmlspecialchars($showtime['theatre_name']) : htmlspecialchars($showtime['hall'] ?? 'Main Theatre'); ?></span>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="h6 fw-bold mb-3">Selected Seats</h5>
                            <div id="selected-seats" class="mb-3">
                                <p class="text-muted mb-0">No seats selected</p>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold">Total:</span>
                                <span class="fw-bold" id="total-price">$0.00</span>
                            </div>
                        </div>
                        
                        <button type="submit" form="seats-form" class="btn btn-primary d-block w-100 py-3">
                            <i class="fas fa-check-circle me-2"></i>Continue to Booking
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.seating-chart {
    margin: 30px 0;
    text-align: center;
    position: relative;
}

.screen {
    height: 10px;
    background: var(--primary-color);
    border-radius: 50%;
    box-shadow: 0 0 15px rgba(99, 102, 241, 0.7);
    margin: 0 auto 40px;
    width: 70%;
    transform: perspective(200px) rotateX(-5deg);
    position: relative;
}

.screen::before {
    content: "SCREEN";
    position: absolute;
    bottom: -25px;
    left: 0;
    right: 0;
    text-align: center;
    color: var(--text-muted);
    font-size: 0.8rem;
    letter-spacing: 2px;
}

.seats-grid {
    display: grid;
    grid-template-columns: auto repeat(10, 1fr);
    gap: 10px;
    margin: 0 auto 15px;
    max-width: 650px;
    align-items: center;
}

.row-label {
    font-weight: bold;
    color: var(--text-muted);
    font-size: 0.9rem;
    width: 24px;
    height: 24px;
    line-height: 24px;
    text-align: center;
}

.seat-container {
    position: relative;
}

.seat-checkbox {
    position: absolute;
    opacity: 0;
    height: 0;
    width: 0;
}

.seat {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 600;
    margin: 0 auto;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.seat.premium {
    background-color: #fef3c7;
    color: #92400e;
}

.seat.regular {
    background-color: #e0e7ff;
    color: #4338ca;
}

.seat.economy {
    background-color: #dcfce7;
    color: #166534;
}

.seat.booked {
    background-color: #f3f4f6;
    color: #9ca3af;
    cursor: not-allowed;
    opacity: 0.6;
}

.seat.selected, .seat-checkbox:checked + .seat:not(.booked) {
    background-color: var(--primary-color);
    color: white;
    transform: scale(1.1);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.seat-legend {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 20px;
    margin-top: 30px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
}

.legend-item .seat {
    width: 24px;
    height: 24px;
    font-size: 0;
    margin: 0;
}

.booking-summary {
    border-radius: var(--border-radius);
    height: 100%;
}

.fw-medium {
    font-weight: 500;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.seat-checkbox');
    const selectedSeatsContainer = document.getElementById('selected-seats');
    const totalPriceElement = document.getElementById('total-price');
    
    
    const prices = {
        'premium': 20,
        'regular': 15,
        'economy': 10
    };
    
    function updateSummary() {
        const selectedSeats = [];
        let totalPrice = 0;
        
        checkboxes.forEach(checkbox => {
            if (checkbox.checked) {
                const seat = checkbox.nextElementSibling;
                const seatText = seat.textContent.trim();
                const category = seat.classList.contains('premium') ? 'premium' : 
                                (seat.classList.contains('regular') ? 'regular' : 'economy');
                const price = prices[category];
                
                selectedSeats.push({ 
                    text: seatText, 
                    category: category,
                    price: price
                });
                
                totalPrice += price;
            }
        });
        
        if (selectedSeats.length > 0) {
            let html = '';
            selectedSeats.forEach(seat => {
                html += `<div class="d-flex justify-content-between mb-2">
                    <span>${seat.text} (${seat.category.charAt(0).toUpperCase() + seat.category.slice(1)})</span>
                    <span>$${seat.price.toFixed(2)}</span>
                </div>`;
            });
            selectedSeatsContainer.innerHTML = html;
        } else {
            selectedSeatsContainer.innerHTML = '<p class="text-muted mb-0">No seats selected</p>';
        }
        
        totalPriceElement.textContent = `$${totalPrice.toFixed(2)}`;
    }
    
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSummary);
    });
    
    updateSummary();
});
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>


ob_end_flush();
?> 