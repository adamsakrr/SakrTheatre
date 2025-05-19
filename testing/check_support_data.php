<?php

require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (isset($_SESSION['user_id']) && !isAdmin()) {
    header('Location: /index.php');
    exit();
}
ini_set('display_errors', 1);
error_reporting(E_ALL);
$conversations = [];
$conversationQuery = $conn->query("SELECT * FROM support_conversations ORDER BY updated_at DESC");
if ($conversationQuery && $conversationQuery->num_rows > 0) {
    while ($row = $conversationQuery->fetch_assoc()) {
        $conversations[] = $row;
    }
}
$messages = [];
$messageQuery = $conn->query("SELECT * FROM support_messages ORDER BY created_at ASC");
if ($messageQuery && $messageQuery->num_rows > 0) {
    while ($row = $messageQuery->fetch_assoc()) {
        $messages[] = $row;
    }
}
$users = [];
$userQuery = $conn->query("SELECT id, username, role FROM users");
if ($userQuery && $userQuery->num_rows > 0) {
    while ($row = $userQuery->fetch_assoc()) {
        $users[$row['id']] = $row;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Data Checker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        pre {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
        }
        .message {
            border-left: 4px solid #ddd;
            padding: 10px;
            margin-bottom: 10px;
        }
        .admin-message {
            border-left-color: #007bff;
            background-color: #f0f7ff;
        }
        .user-message {
            border-left-color: #28a745;
            background-color: #f0fff5;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <h1>Support Data Checker</h1>
        
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-header">Database Tables</div>
                    <div class="card-body">
                        <?php
                        $tables = ['support_conversations', 'support_messages'];
                        foreach ($tables as $table) {
                            $exists = $conn->query("SHOW TABLES LIKE '$table'")->num_rows > 0;
                            echo '<div class="mb-2">';
                            echo $exists 
                                ? "<span class='badge bg-success'>Table $table exists</span>" 
                                : "<span class='badge bg-danger'>Table $table does not exist</span>";
                            echo '</div>';
                        }
                        ?>
                        
                        <?php if (empty($conversations)): ?>
                            <div class="alert alert-warning mt-3">
                                No support conversations found in the database. 
                                <a href="/database/create_support_tables.php" class="alert-link">Run the setup script</a> to create tables and add sample data.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (!empty($conversations)): ?>
            <div class="row">
                <div class="col-md-12 mb-4">
                    <div class="card">
                        <div class="card-header">Support Conversations (<?php echo count($conversations); ?>)</div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>User</th>
                                            <th>Subject</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Updated</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($conversations as $conversation): ?>
                                            <tr>
                                                <td><?php echo $conversation['id']; ?></td>
                                                <td>
                                                    <?php
                                                    $userId = $conversation['user_id'];
                                                    echo isset($users[$userId]) 
                                                        ? htmlspecialchars($users[$userId]['username']) . " (ID: {$userId})" 
                                                        : "Unknown User (ID: {$userId})";
                                                    ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($conversation['subject']); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $conversation['status'] === 'open' ? 'bg-success' : 'bg-secondary'; ?>">
                                                        <?php echo ucfirst($conversation['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $conversation['created_at']; ?></td>
                                                <td><?php echo $conversation['updated_at']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 mb-4">
                    <div class="card">
                        <div class="card-header">Support Messages (<?php echo count($messages); ?>)</div>
                        <div class="card-body">
                            <?php
                            $groupedMessages = [];
                            foreach ($messages as $message) {
                                $convId = $message['conversation_id'];
                                if (!isset($groupedMessages[$convId])) {
                                    $groupedMessages[$convId] = [];
                                }
                                $groupedMessages[$convId][] = $message;
                            }
                            
                            foreach ($groupedMessages as $convId => $convMessages):
                                $conversation = null;
                                foreach ($conversations as $conv) {
                                    if ($conv['id'] == $convId) {
                                        $conversation = $conv;
                                        break;
                                    }
                                }
                            ?>
                                <div class="conversation-block mb-4">
                                    <h4 class="mb-3">
                                        Conversation ID: <?php echo $convId; ?> 
                                        <?php if ($conversation): ?>
                                            - <?php echo htmlspecialchars($conversation['subject']); ?>
                                        <?php endif; ?>
                                    </h4>
                                    
                                    <?php foreach ($convMessages as $message): ?>
                                        <?php 
                                            $sender = isset($users[$message['sender_id']]) 
                                                ? $users[$message['sender_id']] 
                                                : ['username' => 'Unknown', 'role' => 'unknown'];
                                            $isAdmin = ($sender['role'] === 'admin' || $sender['role'] === 'staff');
                                        ?>
                                        <div class="message <?php echo $isAdmin ? 'admin-message' : 'user-message'; ?>">
                                            <div class="message-content">
                                                <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                            </div>
                                            <div class="message-info small text-muted mt-2">
                                                <div>From: <?php echo htmlspecialchars($sender['username']); ?> (<?php echo ucfirst($sender['role']); ?>)</div>
                                                <div>
                                                    Sent: <?php echo $message['created_at']; ?> |
                                                    <?php echo $message['is_read'] ? 'Read' : 'Unread'; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <hr>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-12">
                <a href="/pages/admin/admin_support.php" class="btn btn-primary">Go to Support Admin</a>
                <a href="/database/create_support_tables.php" class="btn btn-success">Run Setup Script</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 