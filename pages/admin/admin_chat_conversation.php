<?php
require_once __DIR__ . '/../../includes/db_config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!hasAdminAccess()) {
    header('Location: /index.php');
    exit();
}

$session_id = isset($_GET['id']) ? $_GET['id'] : '';
if (empty($session_id)) {
    header('Location: admin_chat.php');
    exit();
}

$sessionQuery = "SELECT cs.*, u.username, u.email 
               FROM chat_sessions cs 
               LEFT JOIN users u ON cs.user_id = u.id
               WHERE cs.session_id = ?";

$stmt = $conn->prepare($sessionQuery);
$stmt->bind_param("s", $session_id);
$stmt->execute();
$sessionResult = $stmt->get_result();

if ($sessionResult->num_rows === 0) {
    $_SESSION['message'] = "Chat session not found.";
    $_SESSION['message_type'] = "error";
    header('Location: admin_chat.php');
    exit();
}

$session = $sessionResult->fetch_assoc();
$userId = $session['user_id'];

$messagesQuery = "SELECT cm.*, 
                 u.username, u.email, u.role
                 FROM chat_messages cm
                 LEFT JOIN users u ON cm.user_id = u.id
                 WHERE cm.user_id = ?
                 ORDER BY cm.created_at ASC";

$stmt = $conn->prepare($messagesQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$messagesResult = $stmt->get_result();

$messages = [];
while ($row = $messagesResult->fetch_assoc()) {
    if ($row['is_from_user'] == 0) {
        $row['sender_name'] = 'Admin';
        $row['sender_role'] = 'admin';
    } else {
        $row['sender_name'] = $row['username'] ? $row['username'] : 'Guest User';
        $row['sender_role'] = $row['role'] ? $row['role'] : 'user';
    }
    $messages[] = $row;
}

$updateQuery = "UPDATE chat_messages SET is_read = 1 WHERE user_id = ? AND is_from_user = 1 AND is_read = 0";
$stmt = $conn->prepare($updateQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && $session['status'] === 'active') {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $insertQuery = "INSERT INTO chat_messages (user_id, message, is_from_user, is_read, created_at)
                      VALUES (?, ?, 0, 0, NOW())";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("is", $userId, $message);
        
        if ($stmt->execute()) {
            $updateQuery = "UPDATE chat_sessions SET updated_at = NOW() WHERE session_id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("s", $session_id);
            $stmt->execute();
            
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit();
        } else {
            $error = "Failed to send message. Please try again.";
        }
    } else {
        $error = "Message cannot be empty.";
    }
}

include __DIR__ . '/../../templates/admin_header.php';
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <a href="admin_chat.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Chat List
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title">
                            Chat Session: <?php echo substr($session_id, 0, 10); ?>...
                        </h3>
                        <span class="badge <?php echo $session['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                            <?php echo ucfirst($session['status']); ?>
                        </span>
                    </div>
                    <div class="mt-2">
                        <small>
                            User: 
                            <?php if (isset($session['username'])): ?>
                                <strong><?php echo htmlspecialchars($session['username']); ?></strong>
                                (<?php echo htmlspecialchars($session['email'] ?? 'No email'); ?>)
                            <?php else: ?>
                                Guest (ID: <?php echo $session['user_id']; ?>)
                            <?php endif; ?>
                            | Created: <?php echo date('M j, Y g:i A', strtotime($session['created_at'])); ?>
                        </small>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="chat-messages p-3" style="height: 400px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; border: 1px solid #eee; border-radius: 4px; background-color: #f9f9f9;">
                        <?php if (empty($messages)): ?>
                            <div class="text-center text-muted py-5">
                                <p>No messages in this chat session.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($messages as $message): ?>
                                <?php 
                                    $isFromAdmin = ($message['is_from_user'] === '0');
                                    $messageClass = $isFromAdmin ? 'admin-message' : 'user-message';
                                    $alignment = $isFromAdmin ? 'text-end justify-content-end' : 'text-start justify-content-start';
                                ?>
                                <div class="chat-message d-flex <?php echo $alignment; ?>" style="width: 100%;">
                                    <div class="message-bubble d-inline-block <?php echo $messageClass; ?>" style="max-width: 75%; padding: 10px 15px; border-radius: 18px; <?php echo $isFromAdmin ? 'background-color: #007bff; color: white;' : 'background-color: #e4e6eb; color: #333;'; ?>">
                                        <div class="message-content">
                                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                        </div>
                                        <div class="message-info" style="font-size: 0.75rem; margin-top: 5px; <?php echo $isFromAdmin ? 'color: rgba(255,255,255,0.7);' : 'color: #666;'; ?>">
                                            <span class="sender">
                                                <?php if ($isFromAdmin): ?>
                                                    <i class="fas fa-user-shield"></i> Admin
                                                <?php else: ?>
                                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($message['sender_name']); ?>
                                                <?php endif; ?>
                                            </span>
                                            <span class="time">
                                                <?php echo date('g:i A', strtotime($message['created_at'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($session['status'] === 'active'): ?>
                        <div class="reply-form mt-4">
                            <form method="POST" action="">
                                <div class="form-group mb-3">
                                    <label for="message" class="form-label">Your Reply</label>
                                    <textarea name="message" id="message" class="form-control" rows="4" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Send Message
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mt-4">
                            <i class="fas fa-info-circle"></i> This chat session is closed. 
                            <a href="admin_chat.php" class="alert-link">Return to chat list</a> to reopen it.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/js/admin-chat.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatContainer = document.querySelector('.chat-messages');
    chatContainer.scrollTop = chatContainer.scrollHeight;
});
</script>

<?php include __DIR__ . '/../../templates/admin_footer.php'; ?> 