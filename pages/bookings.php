<?php
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

include __DIR__ . '/../templates/header.php';

if (!isLoggedIn()) {
    $_SESSION['message'] = "Please login to view your bookings";
    $_SESSION['message_type'] = "error";
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$bookings = getUserBookings($user_id);
?>

<div class="container">
    <h1>My Bookings</h1>
    
    <?php if (empty($bookings)): ?>
        <div class="alert alert-info">
            You haven't made any bookings yet. <a href="/pages/shows.php">Browse shows</a> to book tickets.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Show</th>
                        <th>Date & Time</th>
                        <th>Seats</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td><?php echo $booking['id']; ?></td>
                            <td><?php echo htmlspecialchars($booking['show_title']); ?></td>
                            <td>
                                <?php echo date('F j, Y', strtotime($booking['date'])); ?><br>
                                <?php echo date('g:i A', strtotime($booking['time'])); ?>
                            </td>
                            <td>
                                <?php 
                                $seat_texts = [];
                                foreach ($booking['seats'] as $seat) {
                                    $seat_texts[] = $seat['seat_row'] . $seat['seat_number'] . ' (' . $seat['category'] . ')';
                                }
                                echo implode(', ', $seat_texts);
                                ?>
                            </td>
                            <td>$<?php echo number_format($booking['total_amount'], 2); ?></td>
                            <td>
                                <span class="badge bg-success">Confirmed</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?> 