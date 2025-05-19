<?php
require_once __DIR__ . '/../../includes/db_config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!isAdmin()) {
    $_SESSION['message'] = "You don't have permission to access user management.";
    $_SESSION['message_type'] = "danger";
    header('Location: /pages/admin/admin.php');
    exit();
}

include __DIR__ . '/../../templates/admin_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $username = $_POST['username'];
                $email = $_POST['email'];
                $password = $_POST['password'];
                $role = $_POST['role'];
                
                if (addUser($username, $email, $password, $role)) {
                    $_SESSION['message'] = "User added successfully!";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Error adding user!";
                    $_SESSION['message_type'] = "danger";
                }
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $username = $_POST['username'];
                $email = $_POST['email'];
                $role = $_POST['role'];
                
                if (updateUser($id, $username, $email, $role)) {
                    $_SESSION['message'] = "User updated successfully!";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Error updating user!";
                    $_SESSION['message_type'] = "danger";
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                if (deleteUser($id)) {
                    $_SESSION['message'] = "User deleted successfully!";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Error deleting user!";
                    $_SESSION['message_type'] = "danger";
                }
                break;
        }
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}
$users = getAllUsers();
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">Manage Users</h4>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-plus"></i> Add New User
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editUserModal"
                                        data-id="<?php echo $user['id']; ?>"
                                        data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                        data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                        data-role="<?php echo htmlspecialchars($user['role']); ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteUserModal"
                                        data-id="<?php echo $user['id']; ?>"
                                        data-username="<?php echo htmlspecialchars($user['username']); ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="user">User</option>
                            <option value="staff">Staff</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="edit_username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_role" class="form-label">Role</label>
                        <select class="form-select" id="edit_role" name="role" required>
                            <option value="user">User</option>
                            <option value="staff">Staff</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="deleteUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_id">
                    <p>Are you sure you want to delete user <span id="delete_username" class="fw-bold"></span>?</p>
                    <p class="text-danger">This action cannot be undone!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editUserModal = document.getElementById('editUserModal');
    editUserModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        const username = button.getAttribute('data-username');
        const email = button.getAttribute('data-email');
        const role = button.getAttribute('data-role');
        
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_username').value = username;
        document.getElementById('edit_email').value = email;
        document.getElementById('edit_role').value = role;
    });
    const deleteUserModal = document.getElementById('deleteUserModal');
    deleteUserModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        const username = button.getAttribute('data-username');
        
        document.getElementById('delete_id').value = id;
        document.getElementById('delete_username').textContent = username;
    });
});
</script>

<?php include __DIR__ . '/../../templates/admin_footer.php'; ?> 