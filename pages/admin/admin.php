<?php
require_once __DIR__ . '/../../includes/db_config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!hasAdminAccess()) {
    header('Location: /index.php');
    exit();
}

include __DIR__ . '/../../templates/admin_header.php';

$totalShows = getTotalShows();
$totalUsers = getTotalUsers();
$totalBookings = getTotalBookings();
$totalRevenue = getTotalRevenue();

$recentBookings = getAllBookings();
$recentBookings = array_slice($recentBookings, 0, 5);

$lastWeekStart = date('Y-m-d', strtotime('-7 days'));
$today = date('Y-m-d');
$revenueData = getRevenueByDateRange($lastWeekStart, $today);

$revenueByShow = getRevenueByShow();
$topShows = array_slice($revenueByShow, 0, 5);
$isStaffOnly = isStaff() && !isAdmin();
?>

<div class="row">
    <div class="col-lg-12 mb-4">
        <?php if ($isStaffOnly): ?>
            <div class="alert alert-warning">
                <i class="fas fa-info-circle me-2"></i> You are logged in as a staff member. You have limited administrative privileges.
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> Welcome to the admin dashboard. Here you can manage shows, users, and bookings.
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase">Total Shows</h6>
                        <h1 class="display-4"><?php echo $totalShows; ?></h1>
                    </div>
                    <i class="fas fa-film fa-3x"></i>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <a href="/pages/admin/admin_shows.php" class="text-white">View Details</a>
                <i class="fas fa-arrow-circle-right"></i>
            </div>
        </div>
    </div>
    
    <?php if (!$isStaffOnly): ?>
    <div class="col-md-3 mb-4">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase">Total Users</h6>
                        <h1 class="display-4"><?php echo $totalUsers; ?></h1>
                    </div>
                    <i class="fas fa-users fa-3x"></i>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <a href="/pages/admin/admin_users.php" class="text-white">View Details</a>
                <i class="fas fa-arrow-circle-right"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card bg-danger text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase">Total Bookings</h6>
                        <h1 class="display-4"><?php echo $totalBookings; ?></h1>
                    </div>
                    <i class="fas fa-ticket-alt fa-3x"></i>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <a href="/pages/admin/admin_bookings.php" class="text-white">View Details</a>
                <i class="fas fa-arrow-circle-right"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card bg-info text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase">Total Revenue</h6>
                        <h1 class="display-4">$<?php echo number_format($totalRevenue, 0); ?></h1>
                    </div>
                    <i class="fas fa-dollar-sign fa-3x"></i>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <span class="text-white">All Time</span>
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="col-md-9">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Staff Dashboard</h5>
                <p class="card-text">Welcome to the staff dashboard. As a staff member, you have access to:</p>
                <ul>
                    <li>Manage shows and showtimes</li>
                    <li>Handle customer support requests</li>
                </ul>
                <p>For additional permissions, please contact an administrator.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="row">
    <?php if (!$isStaffOnly): ?>
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Revenue Last 7 Days</h5>
            </div>
            <div class="card-body">
                <?php if (empty($revenueData)): ?>
                    <div class="alert alert-info">No revenue data available for the selected period.</div>
                <?php else: ?>
                    <canvas id="revenueChart" height="200"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Top Shows by Revenue</h5>
            </div>
            <div class="card-body">
                <?php if (empty($topShows)): ?>
                    <div class="alert alert-info">No revenue data available.</div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($topShows as $show): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($show['title']); ?></h6>
                                        <small class="text-muted"><?php echo $show['booking_count']; ?> bookings</small>
                                    </div>
                                    <span class="badge bg-success rounded-pill">
                                        $<?php echo number_format($show['revenue'], 2); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="row">
    <?php if (!$isStaffOnly): ?>
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Bookings</h5>
                <a href="/pages/admin/admin_bookings.php" class="btn btn-sm btn-primary">
                    View All
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Show</th>
                                <th>Date</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentBookings)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No bookings found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentBookings as $booking): ?>
                                    <tr>
                                        <td><?php echo $booking['id']; ?></td>
                                        <td><?php echo htmlspecialchars($booking['username']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['show_title']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($booking['date'])); ?></td>
                                        <td>$<?php echo number_format($booking['total_amount'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="col-md-<?php echo $isStaffOnly ? '12' : '6'; ?> mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <a href="/pages/admin/admin_shows.php" class="btn btn-primary w-100 py-3">
                            <i class="fas fa-film fa-2x mb-2"></i><br>
                            Manage Shows
                        </a>
                    </div>
                    <div class="col-sm-6">
                        <a href="/pages/admin/admin_chat.php" class="btn btn-warning w-100 py-3">
                            <i class="fas fa-headset fa-2x mb-2"></i><br>
                            Support Messages
                        </a>
                    </div>
                    <?php if (!$isStaffOnly): ?>
                    <div class="col-sm-6">
                        <a href="/pages/admin/admin_users.php" class="btn btn-success w-100 py-3">
                            <i class="fas fa-users fa-2x mb-2"></i><br>
                            Manage Users
                        </a>
                    </div>
                    <div class="col-sm-6">
                        <a href="/pages/admin/admin_bookings.php" class="btn btn-danger w-100 py-3">
                            <i class="fas fa-ticket-alt fa-2x mb-2"></i><br>
                            Manage Bookings
                        </a>
                    </div>
                    <div class="col-sm-6">
                        <a href="/pages/admin/admin_coupons.php" class="btn btn-info w-100 py-3">
                            <i class="fas fa-tags fa-2x mb-2"></i><br>
                            Manage Coupons
                        </a>
                    </div>
                    <?php endif; ?>
                    <div class="col-sm-6">
                        <a href="/index.php" class="btn btn-secondary w-100 py-3">
                            <i class="fas fa-home fa-2x mb-2"></i><br>
                            Back to Site
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($revenueData) && !$isStaffOnly): ?>
    const revenueChartCtx = document.getElementById('revenueChart').getContext('2d');
    const revenueChart = new Chart(revenueChartCtx, {
        type: 'bar',
        data: {
            labels: [
                <?php 
                foreach ($revenueData as $data) {
                    echo "'" . date('M j', strtotime($data['date'])) . "', ";
                }
                ?>
            ],
            datasets: [{
                label: 'Daily Revenue ($)',
                data: [
                    <?php 
                    foreach ($revenueData as $data) {
                        echo $data['daily_revenue'] . ", ";
                    }
                    ?>
                ],
                backgroundColor: 'rgba(0, 123, 255, 0.5)',
                borderColor: 'rgba(0, 123, 255, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        drawBorder: false
                    },
                    ticks: {
                        callback: function(value) {
                            return '$' + value;
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '$' + context.raw.toFixed(2);
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>
});
</script>

<?php include __DIR__ . '/../../templates/admin_footer.php'; ?>