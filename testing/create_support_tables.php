<?php
require_once __DIR__ . '/../includes/db_config.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);
function tableExists($conn, $tableName) {
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    return $result && $result->num_rows > 0;
}

if (!tableExists($conn, 'support_conversations')) {
    $sql = "CREATE TABLE support_conversations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        subject VARCHAR(255) NOT NULL,
        status ENUM('open', 'closed') NOT NULL DEFAULT 'open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";

    if ($conn->query($sql) === TRUE) {
        echo "Table support_conversations created successfully<br>";
    } else {
        echo "Error creating table support_conversations: " . $conn->error . "<br>";
    }
} else {
    echo "Table support_conversations already exists<br>";
}

if (!tableExists($conn, 'support_messages')) {
    $sql = "CREATE TABLE support_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        conversation_id INT NOT NULL,
        sender_id INT NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (conversation_id) REFERENCES support_conversations(id) ON DELETE CASCADE,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
    )";

    if ($conn->query($sql) === TRUE) {
        echo "Table support_messages created successfully<br>";
    } else {
        echo "Error creating table support_messages: " . $conn->error . "<br>";
    }
} else {
    echo "Table support_messages already exists<br>";
}

if (tableExists($conn, 'support_conversations') && tableExists($conn, 'support_messages')) {
    $result = $conn->query("SELECT COUNT(*) as count FROM support_conversations");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        $userResult = $conn->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
        if ($userResult && $userResult->num_rows > 0) {
            $adminUser = $userResult->fetch_assoc();
            $adminId = $adminUser['id'];
            
            $regularUserResult = $conn->query("SELECT id FROM users WHERE role = 'user' LIMIT 1");
            $regularUser = $regularUserResult->fetch_assoc();
            $userId = $regularUser ? $regularUser['id'] : $adminId;
            
            $conn->query("INSERT INTO support_conversations (user_id, subject, status) 
                         VALUES ($userId, 'Test Support Ticket', 'open')");
            
            $conversationId = $conn->insert_id;
            
            $conn->query("INSERT INTO support_messages (conversation_id, sender_id, message, is_read) 
                         VALUES ($conversationId, $userId, 'Hello, I need help with my booking.', 1)");
                         
            $conn->query("INSERT INTO support_messages (conversation_id, sender_id, message, is_read) 
                         VALUES ($conversationId, $adminId, 'Hi there, I would be happy to help you with your booking. What seems to be the issue?', 1)");
                         
            $conn->query("INSERT INTO support_messages (conversation_id, sender_id, message, is_read) 
                         VALUES ($conversationId, $userId, 'I cannot see my ticket after payment.', 0)");
            
            echo "Added sample support conversation and messages<br>";
        } else {
            echo "No admin user found to create sample data<br>";
        }
    } else {
        echo "Sample data already exists<br>";
    }
}

echo "<br>Done! <a href='/pages/admin/admin_support.php'>Go to Support Admin</a>";
?>