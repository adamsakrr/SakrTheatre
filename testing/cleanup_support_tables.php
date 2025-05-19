<?php
require_once __DIR__ . '/../includes/db_config.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

function tableExists($conn, $tableName) {
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    return $result && $result->num_rows > 0;
}

$supportTables = ['support_messages', 'support_conversations'];
foreach ($supportTables as $table) {
    if (tableExists($conn, $table)) {
        if ($table === 'support_messages') {
            $conn->query("ALTER TABLE support_messages DROP FOREIGN KEY support_messages_ibfk_1");
            $conn->query("ALTER TABLE support_messages DROP FOREIGN KEY support_messages_ibfk_2");
        }
        
        $sql = "DROP TABLE $table";
        if ($conn->query($sql) === TRUE) {
            echo "Table $table deleted successfully<br>";
        } else {
            echo "Error deleting table $table: " . $conn->error . "<br>";
        }
    } else {
        echo "Table $table does not exist<br>";
    }
}

$chatTables = ['chat_sessions', 'chat_messages'];
$chatTablesStatus = [];

foreach ($chatTables as $table) {
    $exists = tableExists($conn, $table);
    $chatTablesStatus[$table] = $exists;
    
    echo "Table $table " . ($exists ? "exists" : "does not exist") . "<br>";
    
    if ($exists) {
        $result = $conn->query("SELECT COUNT(*) as count FROM $table");
        $row = $result->fetch_assoc();
        echo "- $table has " . $row['count'] . " records<br>";
        
        $structure = $conn->query("DESCRIBE $table");
        echo "- Structure:<br>";
        echo "<pre>";
        while ($field = $structure->fetch_assoc()) {
            echo "  " . $field['Field'] . " - " . $field['Type'] . " " . 
                 ($field['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . 
                 ($field['Key'] === 'PRI' ? ' PRIMARY KEY' : '') . "<br>";
        }
        echo "</pre>";
    }
}

if ($chatTablesStatus['chat_messages']) {
    $messages = $conn->query("SELECT * FROM chat_messages LIMIT 5");
    if ($messages && $messages->num_rows > 0) {
        echo "<h3>Sample Chat Messages:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Message</th><th>From User</th><th>Read</th><th>Created</th></tr>";
        
        while ($msg = $messages->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $msg['id'] . "</td>";
            echo "<td>" . $msg['user_id'] . "</td>";
            echo "<td>" . htmlspecialchars(substr($msg['message'], 0, 50)) . "...</td>";
            echo "<td>" . ($msg['is_from_user'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . ($msg['is_read'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . $msg['created_at'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No chat messages found in the database.</p>";
    }
}

echo "<p><a href='/pages/admin/admin_support.php'>Go to Admin Support Page</a></p>";
?> 