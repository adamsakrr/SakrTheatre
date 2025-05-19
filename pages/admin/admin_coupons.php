<?php
require_once __DIR__ . '/../../includes/db_config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!isAdmin()) {
    $_SESSION['message'] = "You don't have permission to access coupon management.";
    $_SESSION['message_type'] = "danger";
    header('Location: /pages/admin/admin.php');
    exit();
}

include __DIR__ . '/../../templates/admin_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $code = $_POST['code'];
                $discount_amount = (float)$_POST['discount_amount'];
                $discount_type = $_POST['discount_type'];
                $valid_from = $_POST['valid_from'];
                $valid_to = $_POST['valid_to'];
                $min_purchase = !empty($_POST['min_purchase']) ? (float)$_POST['min_purchase'] : 0;
                $max_discount = !empty($_POST['max_discount']) ? (float)$_POST['max_discount'] : null;
                $max_uses = !empty($_POST['max_uses']) ? (int)$_POST['max_uses'] : null;
                
                if (createCoupon($code, $discount_amount, $discount_type, $valid_from, $valid_to, $min_purchase, $max_discount, $max_uses)) {
                    $_SESSION['message'] = "Coupon created successfully!";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Error creating coupon!";
                    $_SESSION['message_type'] = "danger";
                }
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $code = $_POST['code'];
                $discount_amount = (float)$_POST['discount_amount'];
                $discount_type = $_POST['discount_type'];
                $valid_from = $_POST['valid_from'];
                $valid_to = $_POST['valid_to'];
                $min_purchase = !empty($_POST['min_purchase']) ? (float)$_POST['min_purchase'] : 0;
                $max_discount = !empty($_POST['max_discount']) ? (float)$_POST['max_discount'] : null;
                $max_uses = !empty($_POST['max_uses']) ? (int)$_POST['max_uses'] : null;
                $is_active = isset($_POST['is_active']) ? true : false;
                
                if (updateCoupon($id, $code, $discount_amount, $discount_type, $valid_from, $valid_to, $min_purchase, $max_discount, $max_uses, $is_active)) {
                    $_SESSION['message'] = "Coupon updated successfully!";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Error updating coupon!";
                    $_SESSION['message_type'] = "danger";
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                if (deleteCoupon($id)) {
                    $_SESSION['message'] = "Coupon deleted successfully!";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Error deleting coupon!";
                    $_SESSION['message_type'] = "danger";
                }
                break;
        }
        
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

$coupons = getAllCoupons();
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">Manage Coupons</h4>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCouponModal">
            <i class="fas fa-plus"></i> Add New Coupon
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Code</th>
                        <th>Discount</th>
                        <th>Valid Period</th>
                        <th>Usage</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($coupons)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No coupons found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($coupons as $coupon): ?>
                            <tr>
                                <td><?php echo $coupon['id']; ?></td>
                                <td>
                                    <span class="badge bg-dark"><?php echo htmlspecialchars($coupon['code']); ?></span>
                                </td>
                                <td>
                                    <?php if ($coupon['discount_type'] == 'percentage'): ?>
                                        <?php echo $coupon['discount_amount']; ?>%
                                        <?php if (!empty($coupon['max_discount'])): ?>
                                            <small class="text-muted d-block">Max: $<?php echo number_format($coupon['max_discount'], 2); ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        $<?php echo number_format($coupon['discount_amount'], 2); ?>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($coupon['min_purchase']) && $coupon['min_purchase'] > 0): ?>
                                        <small class="text-muted d-block">Min purchase: $<?php echo number_format($coupon['min_purchase'], 2); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($coupon['valid_from'])); ?> - 
                                    <?php echo date('M d, Y', strtotime($coupon['valid_to'])); ?>
                                </td>
                                <td>
                                    <?php echo $coupon['times_used']; ?> / 
                                    <?php echo !empty($coupon['max_uses']) ? $coupon['max_uses'] : '∞'; ?>
                                </td>
                                <td>
                                    <?php if ($coupon['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    $today = new DateTime();
                                    $valid_from = new DateTime($coupon['valid_from']);
                                    $valid_to = new DateTime($coupon['valid_to']);
                                    
                                    if ($today < $valid_from): ?>
                                        <span class="badge bg-warning text-dark">Not Started</span>
                                    <?php elseif ($today > $valid_to): ?>
                                        <span class="badge bg-secondary">Expired</span>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($coupon['max_uses']) && $coupon['times_used'] >= $coupon['max_uses']): ?>
                                        <span class="badge bg-info">Depleted</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary me-1" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editCouponModal"
                                            data-id="<?php echo $coupon['id']; ?>"
                                            data-code="<?php echo htmlspecialchars($coupon['code']); ?>"
                                            data-discount-amount="<?php echo $coupon['discount_amount']; ?>"
                                            data-discount-type="<?php echo $coupon['discount_type']; ?>"
                                            data-min-purchase="<?php echo $coupon['min_purchase']; ?>"
                                            data-max-discount="<?php echo $coupon['max_discount']; ?>"
                                            data-valid-from="<?php echo $coupon['valid_from']; ?>"
                                            data-valid-to="<?php echo $coupon['valid_to']; ?>"
                                            data-max-uses="<?php echo $coupon['max_uses']; ?>"
                                            data-is-active="<?php echo $coupon['is_active']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteCouponModal"
                                            data-id="<?php echo $coupon['id']; ?>"
                                            data-code="<?php echo htmlspecialchars($coupon['code']); ?>">
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
<div class="modal fade" id="addCouponModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Coupon</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="code" class="form-label">Coupon Code</label>
                            <input type="text" class="form-control" id="code" name="code" required>
                            <div class="form-text">Must be unique. Uppercase letters and numbers recommended.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="row">
                                <div class="col-md-8">
                                    <label for="discount_amount" class="form-label">Discount Amount</label>
                                    <input type="number" class="form-control" id="discount_amount" name="discount_amount" step="0.01" min="0" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="discount_type" class="form-label">Type</label>
                                    <select class="form-select" id="discount_type" name="discount_type" required>
                                        <option value="percentage">Percentage (%)</option>
                                        <option value="fixed">Fixed Amount ($)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="valid_from" class="form-label">Valid From</label>
                            <input type="date" class="form-control" id="valid_from" name="valid_from" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="valid_to" class="form-label">Valid To</label>
                            <input type="date" class="form-control" id="valid_to" name="valid_to" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="min_purchase" class="form-label">Minimum Purchase ($)</label>
                            <input type="number" class="form-control" id="min_purchase" name="min_purchase" step="0.01" min="0">
                            <div class="form-text">Leave empty for no minimum</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="max_discount" class="form-label">Maximum Discount ($)</label>
                            <input type="number" class="form-control" id="max_discount" name="max_discount" step="0.01" min="0">
                            <div class="form-text">For percentage discounts only</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="max_uses" class="form-label">Maximum Uses</label>
                            <input type="number" class="form-control" id="max_uses" name="max_uses" min="1">
                            <div class="form-text">Leave empty for unlimited</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Coupon</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editCouponModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Coupon</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_code" class="form-label">Coupon Code</label>
                            <input type="text" class="form-control" id="edit_code" name="code" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="row">
                                <div class="col-md-8">
                                    <label for="edit_discount_amount" class="form-label">Discount Amount</label>
                                    <input type="number" class="form-control" id="edit_discount_amount" name="discount_amount" step="0.01" min="0" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="edit_discount_type" class="form-label">Type</label>
                                    <select class="form-select" id="edit_discount_type" name="discount_type" required>
                                        <option value="percentage">Percentage (%)</option>
                                        <option value="fixed">Fixed Amount ($)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_valid_from" class="form-label">Valid From</label>
                            <input type="date" class="form-control" id="edit_valid_from" name="valid_from" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_valid_to" class="form-label">Valid To</label>
                            <input type="date" class="form-control" id="edit_valid_to" name="valid_to" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="edit_min_purchase" class="form-label">Minimum Purchase ($)</label>
                            <input type="number" class="form-control" id="edit_min_purchase" name="min_purchase" step="0.01" min="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_max_discount" class="form-label">Maximum Discount ($)</label>
                            <input type="number" class="form-control" id="edit_max_discount" name="max_discount" step="0.01" min="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_max_uses" class="form-label">Maximum Uses</label>
                            <input type="number" class="form-control" id="edit_max_uses" name="max_uses" min="1">
                        </div>
                    </div>
                    
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                        <label class="form-check-label" for="edit_is_active">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Coupon</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteCouponModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Coupon</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_id">
                    <p>Are you sure you want to delete the coupon <span id="delete_code" class="fw-bold"></span>?</p>
                    <p class="text-danger">This action cannot be undone!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Coupon</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('valid_from').value = today;
    const thirtyDaysFromNow = new Date();
    thirtyDaysFromNow.setDate(thirtyDaysFromNow.getDate() + 30);
    document.getElementById('valid_to').value = thirtyDaysFromNow.toISOString().split('T')[0];
    
    const editCouponModal = document.getElementById('editCouponModal');
    if (editCouponModal) {
        editCouponModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            
            document.getElementById('edit_id').value = button.getAttribute('data-id');
            document.getElementById('edit_code').value = button.getAttribute('data-code');
            document.getElementById('edit_discount_amount').value = button.getAttribute('data-discount-amount');
            document.getElementById('edit_discount_type').value = button.getAttribute('data-discount-type');
            document.getElementById('edit_min_purchase').value = button.getAttribute('data-min-purchase') || '';
            document.getElementById('edit_max_discount').value = button.getAttribute('data-max-discount') || '';
            document.getElementById('edit_valid_from').value = button.getAttribute('data-valid-from');
            document.getElementById('edit_valid_to').value = button.getAttribute('data-valid-to');
            document.getElementById('edit_max_uses').value = button.getAttribute('data-max-uses') || '';
            document.getElementById('edit_is_active').checked = button.getAttribute('data-is-active') === '1';
        });
    }
    
    const deleteCouponModal = document.getElementById('deleteCouponModal');
    if (deleteCouponModal) {
        deleteCouponModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            document.getElementById('delete_id').value = button.getAttribute('data-id');
            document.getElementById('delete_code').textContent = button.getAttribute('data-code');
        });
    }
});
</script>

<?php include __DIR__ . '/../../templates/admin_footer.php'; ?> 