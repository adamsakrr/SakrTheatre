<?php
require_once __DIR__ . '/../../includes/db_config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!hasAdminAccess()) {
    header('Location: /index.php');
    exit();
}

include __DIR__ . '/../../templates/admin_header.php';

$status = isset($_GET['status']) ? $_GET['status'] : 'active';
if (!in_array($status, ['active', 'closed', 'all'])) {
    $status = 'active';
}

$query = "SELECT cs.*, u.username, u.email 
          FROM chat_sessions cs 
          LEFT JOIN users u ON cs.user_id = u.id";

if ($status !== 'all') {
    $safeStatus = $conn->real_escape_string($status);
    $query .= " WHERE cs.status = '$safeStatus'";
}

$query .= " ORDER BY cs.updated_at DESC";
$result = $conn->query($query);

$sessions = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $userId = (int)$row['user_id']; 
        $unreadQuery = "SELECT COUNT(*) as unread_count 
                       FROM chat_messages 
                       WHERE user_id = ? 
                       AND is_from_user = 1 
                       AND is_read = 0";
        $stmt = $conn->prepare($unreadQuery);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $unreadResult = $stmt->get_result();
        $unreadRow = $unreadResult->fetch_assoc();
        
        $row['unread_count'] = $unreadRow['unread_count'];
        $sessions[] = $row;
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Customer Chat Support</h3>
                    <div class="mt-2">
                        <div class="btn-group" role="group">
                            <a href="?status=active" class="btn btn-sm <?php echo $status === 'active' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                Active Chats
                            </a>
                            <a href="?status=closed" class="btn btn-sm <?php echo $status === 'closed' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                Closed Chats
                            </a>
                            <a href="?status=all" class="btn btn-sm <?php echo $status === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                All Chats
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['message_type'] === 'success' ? 'success' : 'danger'; ?>">
                            <?php 
                                echo $_SESSION['message']; 
                                unset($_SESSION['message']);
                                unset($_SESSION['message_type']);
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($sessions)): ?>
                        <div class="alert alert-info">
                            No chat sessions found.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Session ID</th>
                                        <th>User</th>
                                        <th>Status</th>
                                        <th>Unread</th>
                                        <th>Created</th>
                                        <th>Last Updated</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sessions as $session): ?>
                                        <tr class="<?php echo $session['unread_count'] > 0 ? 'table-warning' : ''; ?>">
                                            <td><?php echo substr($session['session_id'], 0, 10); ?>...</td>
                                            <td>
                                                <?php if (isset($session['username'])): ?>
                                                    <?php echo htmlspecialchars($session['username']); ?>
                                                    <br><small><?php echo htmlspecialchars($session['email'] ?? 'No email'); ?></small>
                                                <?php else: ?>
                                                    Guest (ID: <?php echo $session['user_id']; ?>)
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $session['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                                                    <?php echo ucfirst($session['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($session['unread_count'] > 0): ?>
                                                    <span class="badge bg-danger"><?php echo $session['unread_count']; ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">0</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($session['created_at'])); ?></td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($session['updated_at'])); ?></td>
                                            <td>
                                                <a href="admin_chat_conversation.php?id=<?php echo urlencode($session['session_id']); ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-comments"></i> View
                                                </a>
                                                
                                                <?php if ($session['status'] === 'active'): ?>
                                                    <form method="POST" action="admin_chat_update.php" class="d-inline">
                                                        <input type="hidden" name="action" value="close">
                                                        <input type="hidden" name="session_id" value="<?php echo $session['session_id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to close this chat session?')">
                                                            <i class="fas fa-times-circle"></i> Close
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" action="admin_chat_update.php" class="d-inline">
                                                        <input type="hidden" name="action" value="reopen">
                                                        <input type="hidden" name="session_id" value="<?php echo $session['session_id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-success">
                                                            <i class="fas fa-redo"></i> Reopen
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/admin_footer.php'; ?> 