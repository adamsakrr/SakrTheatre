<?php
require_once '../includes/db_config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to use the chat'
    ]);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $stmt = $conn->prepare("UPDATE chat_messages 
                           SET is_read = 1 
                           WHERE user_id = :user_id 
                           AND is_from_user = 0 
                           AND is_read = 0");
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Messages marked as read'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 