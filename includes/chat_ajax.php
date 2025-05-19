<?php

header('Content-Type: application/json');

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $message = "Fatal Error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line'];
        error_log($message);
        echo json_encode([
            'success' => false,
            'message' => 'An unexpected error occurred. Please try again later.',
            'error' => $message
        ]);
    }
});

ob_start();

$debug = true;

error_reporting(E_ALL);
ini_set('display_errors', 0);

function sendJsonResponse($success, $message = '', $data = []) {
    header('Content-Type: application/json');
    $response = ['success' => $success];
    if (!empty($message)) {
        $response['message'] = $message;
    }
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    echo json_encode($response);
    exit;
}

function main() {
    global $debug;
    try {
        define('HANDLE_ERROR_SILENTLY', true);
        require_once __DIR__ . '/db_config.php';
        require_once __DIR__ . '/auth.php';
        if (!isLoggedIn()) {
            sendJsonResponse(false, "User must be logged in to use chat", ['require_login' => true]);
            return;
        }
        $conn = ensure_db_connected();
        if (!$conn) {
            throw new Exception("Database connection failed: Connection could not be established");
        }
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        switch ($action) {
            case 'init':
                $tables_created = createChatTables($conn);
                initChat($conn, $userId);
                break;
            case 'send':
                sendMessage($conn, $userId);
                break;
            case 'getNew':
                getNewMessages($conn);
                break;
            case 'close':
                closeChat($conn);
                break;
            default:
                sendJsonResponse(false, 'Invalid action');
                break;
        }
    } catch (Exception $e) {
        $errorOutput = ob_get_clean();
        error_log("Chat error: " . $e->getMessage() . "\n" . $errorOutput);
        $errorMessage = $debug ? 
            "Error: " . $e->getMessage() : 
            "Failed to connect to chat service. Please refresh the page and try again.";
        $sqlError = '';
        if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) {
            $sqlError = mysqli_error($GLOBALS['conn']);
        }
        $debugData = $debug ? 
            ['debug' => $errorOutput, 'sql_error' => $sqlError] : 
            [];
        sendJsonResponse(false, $errorMessage, $debugData);
    }
}

main();

ob_end_clean();

function createChatTables($conn) {
    try {
        $tables_exist = true;
        $result = $conn->query("SHOW TABLES LIKE 'chat_sessions'");
        $tables_exist = $tables_exist && ($result && $result->num_rows > 0);
        $result = $conn->query("SHOW TABLES LIKE 'chat_messages'");
        $tables_exist = $tables_exist && ($result && $result->num_rows > 0);
        if ($tables_exist) {
            return true;
        }
        $sql = "CREATE TABLE IF NOT EXISTS chat_sessions (
            id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            session_id VARCHAR(64) NOT NULL UNIQUE,
            user_id INT(6) UNSIGNED NULL,
            status ENUM('active', 'closed') DEFAULT 'active',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )";
        $result1 = $conn->query($sql);
        if (!$result1) {
            throw new Exception("Failed to create chat_sessions table: " . $conn->error);
        }
        $sql = "CREATE TABLE IF NOT EXISTS chat_messages (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT(6) UNSIGNED NOT NULL,
            message TEXT NOT NULL,
            is_from_user BOOLEAN NOT NULL DEFAULT 1,
            is_read BOOLEAN NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        $result2 = $conn->query($sql);
        if (!$result2) {
            throw new Exception("Failed to create chat_messages table: " . $conn->error);
        }
        return true;
    } catch (Exception $e) {
        error_log("Error creating chat tables: " . $e->getMessage());
        return false;
    }
}

function initChat($conn, $userId) {
    if (!$conn) {
        sendJsonResponse(false, "Database connection is not available. Please refresh and try again.");
        return;
    }
    if (!$userId) {
        sendJsonResponse(false, "You must be logged in to use the chat");
        return;
    }
    try {
        $stmt = $conn->prepare("SELECT session_id FROM chat_sessions WHERE user_id = ? AND status = 'active' ORDER BY updated_at DESC LIMIT 1");
        if (!$stmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $existingSessionId = $row['session_id'];
            sendJsonResponse(true, '', [
                'session_id' => $existingSessionId,
                'welcome_message' => null,
                'is_existing_session' => true
            ]);
            return;
        }
        $sessionId = uniqid('chat_', true);
        $testResult = $conn->query("SELECT 1");
        if (!$testResult) {
            throw new Exception("Database connection test failed: " . $conn->error);
        }
        $stmt = $conn->prepare("INSERT INTO chat_sessions (session_id, user_id, status, created_at, updated_at) VALUES (?, ?, 'active', NOW(), NOW())");
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        $stmt->bind_param('si', $sessionId, $userId);
        if (!$stmt->execute()) {
            throw new Exception("Failed to create chat session: " . $stmt->error);
        }
        $welcomeMessage = "Support ... Hello! Welcome to Theatre Admin Support. How can I help you today?";
        $supportUserId = 1;
        $msgStmt = $conn->prepare("INSERT INTO chat_messages (user_id, message, is_from_user, is_read, created_at) VALUES (?, ?, 0, 0, NOW())");
        if (!$msgStmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        $msgStmt->bind_param('is', $supportUserId, $welcomeMessage);
        if (!$msgStmt->execute()) {
            throw new Exception("Failed to add welcome message: " . $msgStmt->error);
        }
        sendJsonResponse(true, '', [
            'session_id' => $sessionId,
            'welcome_message' => $welcomeMessage,
            'is_existing_session' => false
        ]);
    } catch (Exception $e) {
        error_log("Chat initialization error: " . $e->getMessage());
        sendJsonResponse(false, "Could not initialize chat. Please check your database connection and try again.");
    }
}

function sendMessage($conn, $userId) {
    $sessionId = isset($_POST['session_id']) ? $_POST['session_id'] : '';
    $message = isset($_POST['message']) ? $_POST['message'] : '';
    if (!$userId) {
        sendJsonResponse(false, "You must be logged in to send messages");
        return;
    }
    if (strpos($message, "Support ...") === 0) {
        $message = substr($message, strlen("Support ..."));
    }
    if (empty($sessionId) || empty($message)) {
        sendJsonResponse(false, 'Missing required parameters');
    }
    $timestamp = time();

    
    try {
        $stmt = $conn->prepare("SELECT id FROM chat_sessions WHERE session_id = ? LIMIT 1");
        if (!$stmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }
        
        $stmt->bind_param('s', $sessionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            $stmt = $conn->prepare("INSERT INTO chat_sessions (session_id, user_id, status, created_at, updated_at) VALUES (?, ?, 'active', NOW(), NOW())");
            if (!$stmt) {
                throw new Exception("Database prepare error: " . $conn->error);
            }
            
            $stmt->bind_param('si', $sessionId, $userId);
            $stmt->execute();
        }
        
        $stmt = $conn->prepare("INSERT INTO chat_messages (user_id, message, is_from_user, is_read, created_at) VALUES (?, ?, 1, 0, NOW())");
        if (!$stmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }
        
        if ($userId == 1) {
            if (strpos($message, "Support ...") !== 0) {
                $message = "Support ... " . $message;
            }
        }
        
        $stmt->bind_param('is', $userId, $message);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to send message: " . $stmt->error);
        }
        
        $stmt = $conn->prepare("UPDATE chat_sessions SET updated_at = NOW() WHERE session_id = ?");
        if ($stmt) {
            $stmt->bind_param('s', $sessionId);
            $stmt->execute();
        }
        
        sendJsonResponse(true, '', ['timestamp' => $timestamp]);
        
    } catch (Exception $e) {
        error_log("Chat message error: " . $e->getMessage());
        sendJsonResponse(false, "Could not send message. Please try again.");
    }
}

function getNewMessages($conn) {
    $sessionId = isset($_POST['session_id']) ? $_POST['session_id'] : '';
    $lastTimestamp = isset($_POST['last_timestamp']) ? (int)$_POST['last_timestamp'] : 0;
    
    if (!isset($_SESSION['user_id'])) {
        sendJsonResponse(false, 'You must be logged in to retrieve messages');
        return;
    }
    
    $currentUserId = $_SESSION['user_id'];
    
    if (empty($sessionId)) {
        sendJsonResponse(false, 'Missing session ID');
    }
    
    try {
        $sessionStmt = $conn->prepare("SELECT id, user_id FROM chat_sessions WHERE session_id = ? LIMIT 1");
        if (!$sessionStmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $sessionStmt->bind_param('s', $sessionId);
        $sessionStmt->execute();
        $sessionResult = $sessionStmt->get_result();
        
        if ($sessionResult->num_rows === 0) {
            throw new Exception("Chat session not found");
        }
        
        $sessionRow = $sessionResult->fetch_assoc();
        $sessionId = $sessionRow['id'];
        $userId = $sessionRow['user_id'];
        
        if ($userId != $currentUserId) {
            sendJsonResponse(false, 'You can only access your own chat sessions');
            return;
        }
        
        $stmt = $conn->prepare("SELECT 
                                    id, user_id, message, is_from_user, UNIX_TIMESTAMP(created_at) as timestamp
                                FROM chat_messages 
                                WHERE user_id = ? AND UNIX_TIMESTAMP(created_at) > ? 
                                ORDER BY created_at ASC");
        
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $stmt->bind_param('ii', $userId, $lastTimestamp);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $message = $row['message'];
            if (!$row['is_from_user'] && strpos($message, "Support ...") !== 0) {
                $message = "Support ... " . $message;
            }
            
            $messages[] = [
                'id' => $row['id'],
                'sender' => $row['is_from_user'] ? 'user' : 'support',
                'message' => $message,
                'timestamp' => (int)$row['timestamp']
            ];
        }
        
        sendJsonResponse(true, '', ['messages' => $messages]);
        
    } catch (Exception $e) {
        error_log("Get messages error: " . $e->getMessage());
        sendJsonResponse(false, "Could not retrieve messages. Please try again.");
    }
}

function closeChat($conn) {
    $sessionId = isset($_POST['session_id']) ? $_POST['session_id'] : '';
    
    if (!isset($_SESSION['user_id'])) {
        sendJsonResponse(false, 'You must be logged in to close a chat session');
        return;
    }
    
    $currentUserId = $_SESSION['user_id'];
    
    if (empty($sessionId)) {
        sendJsonResponse(false, 'Missing session ID');
    }
    
    try {
        $checkStmt = $conn->prepare("SELECT id FROM chat_sessions WHERE session_id = ? AND user_id = ? LIMIT 1");
        if (!$checkStmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $checkStmt->bind_param('si', $sessionId, $currentUserId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows === 0) {
            sendJsonResponse(false, 'You can only close your own chat sessions');
            return;
        }
        
        $stmt = $conn->prepare("UPDATE chat_sessions SET status = 'closed' WHERE session_id = ?");
        
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $stmt->bind_param('s', $sessionId);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to close chat session: " . $stmt->error);
        }
        
        sendJsonResponse(true, 'Chat session closed successfully');
        
    } catch (Exception $e) {
        error_log("Close chat error: " . $e->getMessage());
        sendJsonResponse(false, "Could not close chat session. Please try again.");
    }
}
?>
