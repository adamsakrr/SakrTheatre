<?php
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'You must be logged in to use the chat']);
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'User';
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'init':
        initChat($conn, $userId, $userName);
        break;
    case 'send':
        sendMessage($conn, $userId);
        break;
    case 'get':
        getMessages($conn, $userId);
        break;
    case 'close':
        closeChat($conn, $userId);
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function initChat($conn, $userId, $userName) {
    $stmt = $conn->prepare("SELECT session_id FROM chat_sessions WHERE user_id = ? AND status = 'active' LIMIT 1");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $sessionId = $row['session_id'];
    } else {
        $sessionId = 'chat_' . uniqid();
        $stmt = $conn->prepare("INSERT INTO chat_sessions (session_id, user_id, status, created_at, updated_at) VALUES (?, ?, 'active', NOW(), NOW())");
        $stmt->bind_param('si', $sessionId, $userId);
        $stmt->execute();
        
        $welcomeMsg = "Hello $userName! How can we help you today?";
        $stmt = $conn->prepare("INSERT INTO chat_messages (user_id, message, is_from_user, is_read) VALUES (?, ?, 0, 1)");
        $stmt->bind_param('is', $userId, $welcomeMsg);
        $stmt->execute();
    }
    
    echo json_encode([
        'success' => true,
        'session_id' => $sessionId
    ]);
}

function sendMessage($conn, $userId) {
    $message = trim($_POST['message'] ?? '');
    $sessionId = trim($_POST['session_id'] ?? '');
    
    if (empty($message) || empty($sessionId)) {
        echo json_encode(['error' => 'Message and session ID are required']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT id FROM chat_sessions WHERE session_id = ? AND user_id = ? AND status = 'active'");
    $stmt->bind_param('si', $sessionId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        echo json_encode(['error' => 'Invalid session']);
        return;
    }
    
    $stmt = $conn->prepare("INSERT INTO chat_messages (user_id, message, is_from_user, is_read) VALUES (?, ?, 1, 0)");
    $stmt->bind_param('is', $userId, $message);
    
    if ($stmt->execute()) {
        $newMessageId = $conn->insert_id;
        
        $conn->query("UPDATE chat_sessions SET updated_at = NOW() WHERE session_id = '$sessionId'");
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'message_id' => $newMessageId,
            'time' => date('H:i')
        ]);
    } else {
        echo json_encode(['error' => 'Failed to send message']);
    }
}

function getMessages($conn, $userId) {
    $lastId = intval($_POST['last_id'] ?? 0);
    $sessionId = trim($_POST['session_id'] ?? '');
    
    if (empty($sessionId)) {
        echo json_encode(['error' => 'Session ID is required']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT id FROM chat_sessions WHERE session_id = ? AND user_id = ?");
    $stmt->bind_param('si', $sessionId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        echo json_encode(['error' => 'Invalid session']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT id, message, is_from_user, created_at FROM chat_messages 
                           WHERE user_id = ? AND id > ? 
                           ORDER BY created_at ASC");
    $stmt->bind_param('ii', $userId, $lastId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = [
            'id' => $row['id'],
            'message' => $row['message'],
            'is_from_user' => (bool)$row['is_from_user'],
            'time' => date('H:i', strtotime($row['created_at']))
        ];
    }
    
    $conn->query("UPDATE chat_messages SET is_read = 1 WHERE user_id = $userId AND is_from_user = 0");
    
    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
}

function closeChat($conn, $userId) {
    $sessionId = trim($_POST['session_id'] ?? '');
    
    if (empty($sessionId)) {
        echo json_encode(['error' => 'Session ID is required']);
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