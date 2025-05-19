<?php
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: /pages/login.php');
    exit();
}
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = $user_id";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($result);

$bookings = getUserBookings($user_id);

$sql = "SELECT * FROM coupons 
        WHERE is_active = 1 
        AND valid_from <= CURDATE() 
        AND valid_to >= CURDATE()
        AND (max_uses IS NULL OR times_used < max_uses)
        ORDER BY valid_to ASC";
$result = mysqli_query($conn, $sql);
$active_coupons = [];
while ($row = mysqli_fetch_assoc($result)) {
    $active_coupons[] = $row;
}

include __DIR__ . '/../templates/header.php';
?>

<style>
@media (max-width: 991px) {
    .avatar-placeholder {
        width: 80px !important;
        height: 80px !important;
        font-size: 2rem !important;
    }
}

@media (max-width: 767px) {
    .list-group-item .d-flex {
        flex-direction: column;
        align-items: flex-start !important;
    }
    
    .list-group-item .d-flex small {
        margin-top: 5px;
    }
}

@media (max-width: 575px) {
    .coupon-details {
        font-size: 0.7rem !important;
    }
}
</style>

<div class="container my-5">
    <div>
        <div class="col shadow-lg">
            <div class="card1 shadow-sm">
                <div class="card-header bg-light">
                    
                </div>
                <div class="card1 ">
                    <div class="text-center mb-4">
                        <div class="avatar-placeholder rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 100px; height: 100px; font-size: 2.5rem;">
                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                        </div>
                        <h5 class="mb-0"><?php echo htmlspecialchars($user['username']); ?></h5>
                        <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                    
                    <div class="mb-4">
                        <span class="text-muted small">Account created:</span>
                        <p class="mb-0"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                    </div>
                    
                    <div class="d-grid gap-3">
                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                            <i class="fas fa-user-edit me-2"></i>Edit Profile
                        </button>
                        <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                            <i class="fas fa-key me-2"></i>Change Password
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>


<div class="modal fade" id="editProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title">Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="update_profile.php" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="change_password.php" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    <div class="mb-4">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const copyButtons = document.querySelectorAll('.copy-coupon');
    copyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const code = this.getAttribute('data-code');
            navigator.clipboard.writeText(code).then(() => {
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check"></i> Copied!';
                
                setTimeout(() => {
                    this.innerHTML = originalText;
                }, 2000);
            });
        });
    });
});
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?> 