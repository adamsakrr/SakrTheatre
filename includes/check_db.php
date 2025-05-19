<?php
echo "Database Connection Check\n";
echo "------------------------\n";

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "theatre_booking";

echo "Testing connection to 127.0.0.:8889...\n";
try {
    $conn = mysqli_connect($servername, $username, $password, $dbname,);
    if ($conn) {
        echo "SUCCESS: Connected to database via 127.0.0.1:8889\n";
        
        $result = mysqli_query($conn, "SHOW TABLES");
        if ($result) {
            echo "Tables found: ";
            $tables = [];
            while ($row = mysqli_fetch_row($result)) {
                $tables[] = $row[0];
            }
            echo implode(", ", $tables) . "\n";
            
            if (in_array('chat_sessions', $tables) && in_array('chat_messages', $tables)) {
                echo "SUCCESS: Chat tables exist\n";
                
                $result = mysqli_query($conn, "DESCRIBE chat_sessions");
                $columns = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $columns[] = $row['Field'] . ' (' . $row['Type'] . ')';
                }
                echo "chat_sessions columns: " . implode(", ", $columns) . "\n";
                
                $result = mysqli_query($conn, "DESCRIBE chat_messages");
                $columns = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $columns[] = $row['Field'] . ' (' . $row['Type'] . ')';
                }
                echo "chat_messages columns: " . implode(", ", $columns) . "\n";
            } else {
                echo "WARNING: Chat tables not found\n";
            }
        } else {
            echo "ERROR: Failed to list tables: " . mysqli_error($conn) . "\n";
        }
        
        mysqli_close($conn);
    } else {
        echo "ERROR: Failed to connect: " . mysqli_connect_error() . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\nMAMP Status Check\n";
echo "----------------\n";
exec('ps aux | grep -i mamp | grep -v grep', $output);
if (!empty($output)) {
    echo "MAMP processes found:\n";
    foreach ($output as $line) {
        echo $line . "\n";
    }
} else {
    echo "WARNING: No MAMP processes found. MAMP may not be running.\n";
}

echo "\nPHP Configuration\n";
echo "----------------\n";
echo "mysqli.default_socket: " . ini_get('mysqli.default_socket') . "\n";
echo "mysqli.default_port: " . ini_get('mysqli.default_port') . "\n";
echo "default_socket_timeout: " . ini_get('default_socket_timeout') . "\n";

echo "\nComplete.\n";
?> 