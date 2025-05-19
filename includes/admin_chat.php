<?php
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

$adminId = $_SESSION['user_id'];

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'get_active_chats':
        getActiveChats($conn);
        break;
    case 'get_chat_messages':
        getChatMessages($conn);
        break;
    case 'send_reply':
        sendReply($conn);
        break;
    case 'close_chat':
        closeUserChat($conn);
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function getActiveChats($conn) {
    $query = "SELECT cs.id, cs.session_id, cs.user_id, cs.created_at, cs.updated_at, 
             u.username, 
             (SELECT COUNT(*) FROM chat_messages cm WHERE cm.user_id = cs.user_id AND cm.is_read = 0 AND cm.is_from_user = 1) as unread_count
             FROM chat_sessions cs
             JOIN users u ON cs.user_id = u.id
             WHERE cs.status = 'active'
             ORDER BY cs.updated_at DESC";
    
    $result = $conn->query($query);
    
    $chats = [];
    while ($row = $result->fetch_assoc()) {
        $chats[] = [
            'session_id' => $row['session_id'],
            'user_id' => $row['user_id'],
            'username' => $row['username'],
            'unread' => (int)$row['unread_count'],
            'last_activity' => date('Y-m-d H:i', strtotime($row['updated_at']))
        ];
    }
    
    echo json_encode([
        'success' => true,
        'chats' => $chats
    ]);
}

function getChatMessages($conn) {
    $userId = intval($_POST['user_id'] ?? 0);
    
    if (!$userId) {
        echo json_encode(['error' => 'User ID is required']);
        return;
    }
    
    $query = "SELECT id, message, is_from_user, created_at 
             FROM chat_messages 
             WHERE user_id = $userId 
             ORDER BY created_at ASC";
    
    $result = $conn->query($query);
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = [
            'id' => $row['id'],
            'message' => $row['message'],
            'is_from_user' => (bool)$row['is_from_user'],
            'time' => date('H:i', strtotime($row['created_at']))
        ];
    }
    
    $conn->query("UPDATE chat_messages SET is_read = 1 WHERE user_id = $userId AND is_from_user = 1");
    
    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
}

function sendReply($conn) {
    $userId = intval($_POST['user_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    
    if (!$userId || empty($message)) {
        echo json_encode(['error' => 'User ID and message are required']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT session_id FROM chat_sessions WHERE user_id = ? AND status = 'active'");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        echo json_encode(['error' => 'No active chat session found for this user']);
        return;
    }
    
    $stmt = $conn->prepare("INSERT INTO chat_messages (user_id, message, is_from_user, is_read) VALUES (?, ?, 0, 0)");
    $stmt->bind_param('is', $userId, $message);
    
    if ($stmt->execute()) {
        $sessionId = $result->fetch_assoc()['session_id'];
        $conn->query("UPDATE chat_sessions SET updated_at = NOW() WHERE session_id = '$sessionId'");
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'time' => date('H:i')
        ]);
    } else {
        echo json_encode(['error' => 'Failed to send reply']);
    }
}

function closeUserChat($conn) {
    $userId = intval($_POST['user_id'] ?? 0);
    $sessionId = trim($_POST['session_id'] ?? '');
    
    if (!$userId || empty($sessionId)) {
        echo json_encode(['error' => 'User ID and session ID are required']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT id FROM chat_sessions WHERE session_id = ? AND user_id = ? AND status = 'active'");
    $stmt->bind_param('si', $sessionId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        echo json_encode(['error' => 'Invalid session or already closed']);
        return;
    }
    
    $closingMessage = "This chat session has been closed by an administrator.";
    $stmt = $conn->prepare("INSERT INTO chat_messages (user_id, message, is_from_user, is_read) VALUES (?, ?, 0, 0)");
    $stmt->bind_param('is', $userId, $closingMessage);
    $stmt->execute();
    function getTotalRevenue() {
    global $conn;
    
    $sql = "SELECT SUM(total_amount) as total_revenue FROM bookings";
    $result = $conn->query($sql);
    
    if ($result && $row = $result->fetch_assoc()) {
        return $row['total_revenue'] ?? 0;
    }
    
    return 0;
}

function getRevenueByDateRange($startDate, $endDate) {
    global $conn;
    
    $sql = "SELECT DATE(booking_date) as date, SUM(total_amount) as revenue 
            FROM bookings 
            WHERE booking_date BETWEEN ? AND ? 
            GROUP BY DATE(booking_date) 
            ORDER BY DATE(booking_date) ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[$row['date']] = $row['revenue'];
    }
    
    return $data;
}

    $stmt = $conn->prepare("UPDATE chat_sessions SET status = 'closed' WHERE session_id = ?");
    $stmt->bind_param('s', $sessionId);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Chat session closed'
        ]);
    } else {
        echo json_encode(['error' => 'Failed to close chat session']);
    }
} 