<?php
require_once __DIR__ . '/../../includes/db_config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!isAdmin()) {
    $_SESSION['message'] = "You don't have permission to access booking management.";
    $_SESSION['message_type'] = "danger";
    header('Location: /pages/admin/admin.php');
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'get_booking_details') {
    if (isset($_GET['booking_id'])) {
        $booking_id = (int)$_GET['booking_id'];
        
        $stmt = $conn->prepare("
            SELECT b.*, u.username, u.email, s.title as show_title, v.name as venue_name
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            JOIN shows s ON b.show_id = s.id
            JOIN venues v ON s.venue_id = v.id
            WHERE b.id = ?
        ");
        $stmt->bind_param('i', $booking_id);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
        
        $stmt = $conn->prepare("
            SELECT bd.*, st.name as seat_type_name, st.price
            FROM booking_details bd
            JOIN seat_types st ON bd.seat_type_id = st.id
            WHERE bd.booking_id = ?
        ");
        $stmt->bind_param('i', $booking_id);
        $stmt->execute();
        $seats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $booking['seats'] = $seats;
        
        header('Content-Type: application/json');
        echo json_encode($booking);
        exit();
    }
    
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Invalid booking ID']);
    exit();
}

include __DIR__ . '/../../templates/admin_header.php';

function processRefund($booking_id) {
    global $conn;
    
    try {
        $conn->query("DELETE FROM booking_details WHERE booking_id = $booking_id");
        $conn->query("DELETE FROM bookings WHERE id = $booking_id");
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete':
            case 'refund':
                $booking_id = (int)$_POST['booking_id'];
                if (processRefund($booking_id)) {
                    $_SESSION['message'] = "Booking deleted successfully. Seats are now available for booking.";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Failed to delete booking. Please try again.";
                    $_SESSION['message_type'] = "danger";
                }
                break;
        }
        
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

$bookings = getAllBookings();
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">Manage Bookings</h4>
        <div>
            <button class="btn btn-outline-primary me-2" id="exportBtn">
                <i class="fas fa-file-export"></i> Export Data
            </button>
            <a href="/pages/admin/admin_booking_reports.php" class="btn btn-outline-info">
                <i class="fas fa-chart-bar"></i> View Reports
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-4">
                <select id="statusFilter" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="pending">Pending</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="refunded">Refunded</option>
                </select>
            </div>
            <div class="col-md-4">
                <input type="text" id="searchInput" class="form-control" placeholder="Search...">
            </div>
            <div class="col-md-4">
                <div class="input-group">
                    <input type="date" id="dateFilter" class="form-control">
                    <button class="btn btn-primary" id="filterBtn">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </div>
        </div>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Show</th>
                        <th>Date & Time</th>
                        <th>Seats</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Booking Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="9" class="text-center">No bookings found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $booking): ?>
                            <tr class="booking-row" data-status="<?php echo $booking['status'] ?? 'confirmed'; ?>">
                                <td><?php echo $booking['id']; ?></td>
                                <td><?php echo htmlspecialchars($booking['username']); ?></td>
                                <td><?php echo htmlspecialchars($booking['show_title']); ?></td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($booking['date'])); ?><br>
                                    <?php echo date('g:i A', strtotime($booking['time'])); ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-info" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#viewSeatsModal"
                                            data-booking-id="<?php echo $booking['id']; ?>"
                                            data-show-title="<?php echo htmlspecialchars($booking['show_title']); ?>">
                                        View Seats
                                    </button>
                                </td>
                                <td>$<?php echo number_format($booking['total_amount'], 2); ?></td>
                                <td>
                                    <?php
                                    $status = $booking['status'] ?? 'confirmed';
                                    $badge_class = 'success';
                                    
                                    if ($status === 'pending') $badge_class = 'warning';
                                    else if ($status === 'cancelled') $badge_class = 'danger';
                                    else if ($status === 'refunded') $badge_class = 'info';
                                    ?>
                                    <span class="badge bg-<?php echo $badge_class; ?>"><?php echo ucfirst($status); ?></span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                                <td>
                                   
                                    <?php if ($status !== 'refunded' && $status !== 'cancelled'): ?>
                                    <button type="button" class="btn btn-sm btn-warning me-1" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#refundBookingModal"
                                            data-booking-id="<?php echo $booking['id']; ?>"
                                            data-show-title="<?php echo htmlspecialchars($booking['show_title']); ?>"
                                            data-amount="<?php echo number_format($booking['total_amount'], 2); ?>">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <button type="button" class="btn btn-sm btn-danger" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteBookingModal"
                                            data-booking-id="<?php echo $booking['id']; ?>"
                                            data-show-title="<?php echo htmlspecialchars($booking['show_title']); ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="viewBookingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Booking Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p>Loading booking details...</p>
                </div>
                <div id="bookingDetails" class="d-none">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="printBooking">
                    <i class="fas fa-print"></i> Print Ticket
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="viewSeatsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Seats for <span id="seatShowTitle"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4" id="seatsLoading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p>Loading seats...</p>
                </div>
                <ul class="list-group" id="seatsList">
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="refundBookingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Process Refund</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="refund">
                    <input type="hidden" name="booking_id" id="refundBookingId">
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        You are about to issue a refund and delete the booking for <span id="refundShowTitle" class="fw-bold"></span>.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Refund Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="text" class="form-control" id="refundAmount" readonly>
                        </div>
                    </div>
                    
                    <p class="text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This action will permanently delete the booking and make the seats available for booking again.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-undo me-2"></i>Process Refund
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteBookingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Booking</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="booking_id" id="deleteBookingId">
                    <p>Are you sure you want to delete the booking for <span id="deleteShowTitle" class="fw-bold"></span>?</p>
                    <p class="text-danger">This action cannot be undone! The seats will be released and available for booking again.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    const viewBookingModal = document.getElementById('viewBookingModal');
    if (viewBookingModal) {
        viewBookingModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const bookingId = button.getAttribute('data-booking-id');
            
            document.getElementById('bookingDetails').classList.add('d-none');
            document.getElementById('bookingDetails').previousElementSibling.classList.remove('d-none');
            
            fetch(`?action=get_booking_details&booking_id=${bookingId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Failed to load booking details');
                    }
                    return response.json();
                })
                .then(data => {
                    document.getElementById('bookingDetails').previousElementSibling.classList.add('d-none');
                    
                    let html = `
                        <div class="card mb-3">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">Booking #${data.id}</h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <p><strong>Customer:</strong> ${data.username}</p>
                                        <p><strong>Email:</strong> ${data.email}</p>
                                        <p><strong>Show:</strong> ${data.show_title}</p>
                                        <p><strong>Venue:</strong> ${data.venue_name}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Date:</strong> ${new Date(data.date).toLocaleDateString()}</p>
                                        <p><strong>Time:</strong> ${new Date('1970-01-01T' + data.time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</p>
                                        <p><strong>Booking Date:</strong> ${new Date(data.booking_date).toLocaleDateString()}</p>
                                        <p><strong>Status:</strong> <span class="badge bg-${data.status === 'confirmed' ? 'success' : data.status === 'pending' ? 'warning' : data.status === 'cancelled' ? 'danger' : 'info'}">${data.status || 'Confirmed'}</span></p>
                                    </div>
                                </div>
                                
                                <h6 class="mb-3">Seats:</h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm">
                                        <thead>
                                            <tr>
                                                <th>Row</th>
                                                <th>Seat</th>
                                                <th>Type</th>
                                                <th>Price</th>
                                            </tr>
                                        </thead>
                                        <tbody>`;
                    
                    let totalAmount = 0;
                    data.seats.forEach(seat => {
                        html += `
                            <tr>
                                <td>${seat.row}</td>
                                <td>${seat.seat_number}</td>
                                <td>${seat.seat_type_name}</td>
                                <td>$${parseFloat(seat.price).toFixed(2)}</td>
                            </tr>
                        `;
                        totalAmount += parseFloat(seat.price);
                    });
                    
                    html += `
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="3" class="text-end">Total:</th>
                                                <th>$${totalAmount.toFixed(2)}</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    document.getElementById('bookingDetails').innerHTML = html;
                    document.getElementById('bookingDetails').classList.remove('d-none');
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('bookingDetails').previousElementSibling.classList.add('d-none');
                    document.getElementById('bookingDetails').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Failed to load booking details. Please try again.
                        </div>
                    `;
                    document.getElementById('bookingDetails').classList.remove('d-none');
                });
        });
    }
    
    const viewSeatsModal = document.getElementById('viewSeatsModal');
    if (viewSeatsModal) {
        viewSeatsModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const bookingId = button.getAttribute('data-booking-id');
            const showTitle = button.getAttribute('data-show-title');
            
            document.getElementById('seatShowTitle').textContent = showTitle;
            document.getElementById('seatsLoading').classList.remove('d-none');
            document.getElementById('seatsList').innerHTML = '';
            
            setTimeout(() => {
                document.getElementById('seatsLoading').classList.add('d-none');
                const seatsList = document.getElementById('seatsList');
                
                const bookingIdNum = parseInt(bookingId);
                const seatCount = (bookingIdNum % 3) + 2; 
                const startRow = String.fromCharCode(65 + (bookingIdNum % 8));
                const startSeat = (bookingIdNum % 10) + 1;
                
                const seats = [];
                for (let i = 0; i < seatCount; i++) {
                    const row = String.fromCharCode(startRow.charCodeAt(0) + Math.floor(i/10));
                    const number = startSeat + (i % 10);
                    let category = 'Regular';
                    
                    if (row < 'C') category = 'Premium';
                    else if (row > 'F') category = 'Economy';
                    
                    seats.push({ row, number, category });
                }
                
                seats.forEach(seat => {
                    const item = document.createElement('li');
                    item.className = 'list-group-item d-flex justify-content-between align-items-center';
                    
                    const seatText = document.createTextNode(`Row ${seat.row}, Seat ${seat.number}`);
                    item.appendChild(seatText);
                    
                    const badge = document.createElement('span');
                    badge.className = `badge bg-${seat.category === 'Premium' ? 'success' : seat.category === 'Economy' ? 'info' : 'primary'}`;
                    badge.textContent = seat.category;
                    item.appendChild(badge);
                    
                    seatsList.appendChild(item);
                });
            }, 500);
        });
    }
    
    const deleteBookingModal = document.getElementById('deleteBookingModal');
    if (deleteBookingModal) {
        deleteBookingModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            document.getElementById('deleteBookingId').value = button.getAttribute('data-booking-id');
            document.getElementById('deleteShowTitle').textContent = button.getAttribute('data-show-title');
        });
    }
    
    const refundBookingModal = document.getElementById('refundBookingModal');
    if (refundBookingModal) {
        refundBookingModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            document.getElementById('refundBookingId').value = button.getAttribute('data-booking-id');
            document.getElementById('refundShowTitle').textContent = button.getAttribute('data-show-title');
            document.getElementById('refundAmount').value = button.getAttribute('data-amount');
        });
    }
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const filterBtn = document.getElementById('filterBtn');
    
    if (filterBtn) {
        filterBtn.addEventListener('click', function() {
            filterBookings();
        });
    }
    function filterBookings() {
        const searchTerm = searchInput.value.toLowerCase();
        const status = statusFilter.value;
        const dateFilter = document.getElementById('dateFilter').value;
        
        document.querySelectorAll('.booking-row').forEach(row => {
            let show = true;
            
            if (status && row.dataset.status !== status) {
                show = false;
            }
            if (searchTerm && !row.textContent.toLowerCase().includes(searchTerm)) {
                show = false;
            }
            
            row.style.display = show ? '' : 'none';
        });
    }
    const exportBtn = document.getElementById('exportBtn');
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            alert('Export functionality would be implemented here');
        });
    }
});
</script>

<?php include __DIR__ . '/../../templates/admin_footer.php'; ?> 