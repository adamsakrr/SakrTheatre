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
    $stmt = $conn->prepare("SELECT id, message, created_at 
                           FROM chat_messages 
                           WHERE user_id = :user_id 
                           AND is_from_user = 0 
                           AND is_read = 0 
                           ORDER BY created_at ASC");
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 