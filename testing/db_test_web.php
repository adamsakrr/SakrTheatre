<?php
header('Content-Type: text/plain');

echo "Database Web Connection Test\n";
echo "===========================\n";

echo "PHP Version: " . phpversion() . "\n";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "PHP SAPI: " . php_sapi_name() . "\n";

$servername = "localhost";

$username = "root";
$password = "";
$dbname = "theatre_booking";

echo "\nTesting connection to 127.0.0.1:8889...\n";
try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    if ($conn) {
        echo "SUCCESS: Connected to database via 127.0.0.1:8889\n";
        mysqli_close($conn);
    } else {
        echo "ERROR: Failed to connect: " . mysqli_connect_error() . "\n";
    }
} catch (mysqli_sql_exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
}

echo "\nPHP MySQL Configuration\n";
echo "----------------------\n";
echo "mysqli.default_socket = " . ini_get('mysqli.default_socket') . "\n";
echo "mysqli.default_port = " . ini_get('mysqli.default_port') . "\n";
echo "mysqli.default_host = " . ini_get('mysqli.default_host') . "\n";
echo "extension_dir = " . ini_get('extension_dir') . "\n";
echo "open_basedir = " . ini_get('open_basedir') . "\n";

echo "\nmysqli extension loaded: " . (extension_loaded('mysqli') ? 'Yes' : 'No') . "\n";

echo "\ninclude_path = " . get_include_path() . "\n";

echo "\nTesting include of db_config.php...\n";
try {
    define('HANDLE_ERROR_SILENTLY', true);
    
    require_once __DIR__ . '/includes/db_config.php';
    
    if (isset($conn) && $conn) {
        echo "SUCCESS: db_config.php included and connection established\n";
    } else {
        echo "ERROR: db_config.php included but connection failed\n";
    }
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
}

echo "\nComplete.\n";
?> 