<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Test</h1>";

echo "<h2>Testing Direct MySQL Connection</h2>";

echo "<h3>TCP Connection (127.0.0.1:3306)</h3>";
try {
    $conn_tcp = mysqli_connect("localhost", "root", "", "theatre_booking", 3306);
    if ($conn_tcp) {
        echo "<p style='color:green'>✓ TCP connection successful!</p>";
        mysqli_close($conn_tcp);
    } else {
        echo "<p style='color:red'>✗ TCP connection failed: " . mysqli_connect_error() . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ TCP connection error: " . $e->getMessage() . "</p>";
}

echo "<h3>Socket Connections</h3>";
$socket_paths = [
    '/tmp/mysql.sock',
    '/var/mysql/mysql.sock',
    '/var/run/mysqld/mysqld.sock',
    '/opt/homebrew/var/mysql/mysql.sock', 
    '/opt/homebrew/var/run/mysqld/mysqld.sock'
];

foreach ($socket_paths as $socket) {
    echo "<p>Testing socket: $socket</p>";
    if (file_exists($socket)) {
        echo "<p>Socket file exists ✓</p>";
        try {
            $conn_socket = mysqli_connect("localhost", "root", "root", "theatre_booking", null, $socket);
            if ($conn_socket) {
                echo "<p style='color:green'>✓ Connection successful using $socket!</p>";
                mysqli_close($conn_socket);
                break;
            } else {
                echo "<p style='color:red'>✗ Connection failed: " . mysqli_connect_error() . "</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color:red'>✗ Connection error: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>Socket file does not exist ✗</p>";
    }
}

echo "<h2>Testing db_config.php</h2>";
try {
    require_once __DIR__ . '/db_config.php';
    if ($conn) {
        echo "<p style='color:green'>✓ Connection successful using db_config.php!</p>";
        
        echo "<h3>Checking Database Structure</h3>";
        $result = mysqli_query($conn, "SHOW TABLES");
        if ($result) {
            if (mysqli_num_rows($result) > 0) {
                echo "<p>Tables found:</p>";
                echo "<ul>";
                while ($row = mysqli_fetch_row($result)) {
                    echo "<li>" . $row[0] . "</li>";
                }
                echo "</ul>";
            } else {
                echo "<p>No tables found in the database.</p>";
            }
        } else {
            echo "<p style='color:red'>Error querying tables: " . mysqli_error($conn) . "</p>";
        }
    } else {
        echo "<p style='color:red'>✗ Connection failed using db_config.php</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error with db_config.php: " . $e->getMessage() . "</p>";
}

echo "<h2>MySQL Service Status</h2>";
if (function_exists('exec')) {
    echo "<p>Checking if MySQL is running:</p>";
    $output = [];
    $return_var = 0;
    
    exec('ps aux | grep -i mysql | grep -v grep', $output, $return_var);
    
    if (!empty($output)) {
        echo "<p style='color:green'>✓ MySQL process found!</p>";
        echo "<pre>" . implode("\n", $output) . "</pre>";
    } else {
        echo "<p style='color:red'>✗ No MySQL process found. The MySQL server may not be running.</p>";
        echo "<p>Try starting MySQL with one of these commands:</p>";
        echo "<code>sudo systemctl start mysql</code> (Linux)<br>";
        echo "<code>sudo service mysql start</code> (Debian/Ubuntu)<br>";
        echo "<code>sudo brew services start mysql</code> (macOS with Homebrew)<br>";
    }
} else {
    echo "<p>Cannot check MySQL service status (exec function is disabled).</p>";
}

echo "<h2>PHP MySQL Extension Check</h2>";
if (extension_loaded('mysqli')) {
    echo "<p style='color:green'>✓ MySQLi extension is loaded.</p>";
} else {
    echo "<p style='color:red'>✗ MySQLi extension is not loaded. Please check your PHP configuration.</p>";
}

echo "<p>PHP version: " . phpversion() . "</p>";
?> 