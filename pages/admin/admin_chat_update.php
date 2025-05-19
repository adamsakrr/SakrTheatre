<?php
require_once __DIR__ . '/../../includes/db_config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!hasAdminAccess()) {
    header('Location: /index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['session_id'])) {
    $action = $_POST['action'];
    $sessionId = $_POST['session_id'];
    
    if (!in_array($action, ['close', 'reopen'])) {
        $_SESSION['message'] = "Invalid action.";
        $_SESSION['message_type'] = "error";
        header('Location: admin_chat.php');
        exit();
    }
    
    $checkQuery = "SELECT session_id FROM chat_sessions WHERE session_id = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("s", $sessionId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['message'] = "Chat session not found.";
        $_SESSION['message_type'] = "error";
        header('Location: admin_chat.php');
        exit();
    }
    
    $status = ($action === 'close') ? 'closed' : 'active';
    $updateQuery = "UPDATE chat_sessions SET status = ? WHERE session_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("ss", $status, $sessionId);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Chat session " . ($action === 'close' ? "closed" : "reopened") . " successfully.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Failed to update chat session.";
        $_SESSION['message_type'] = "error";
    }
} else {
    $_SESSION['message'] = "Invalid request.";
    $_SESSION['message_type'] = "error";
}

header('Location: admin_chat.php');
exit(); 