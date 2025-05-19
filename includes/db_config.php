<?php
$servername = "localhost"; 

$username = "root";
$password = "";
$dbname = "theatre_booking";

$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' ||
           strpos($_SERVER['SCRIPT_NAME'], 'chat_ajax.php') !== false;

try {
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    
    if (!$conn) {
        throw new Exception("Connection failed: " . mysqli_connect_error());
    }
    
    mysqli_set_charset($conn, "utf8mb4");
    mysqli_query($conn, "SET SESSION wait_timeout = 86400");
    
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    
    if ($is_ajax && !defined('HANDLE_ERROR_SILENTLY')) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed. Please try again later.'
        ]);
        exit;
    }
    else if (!defined('HANDLE_ERROR_SILENTLY')) {
        echo "<div style='color:red;padding:10px;margin:10px;border:1px solid red;'>
              Database connection error. Please try again later or contact support.
              </div>";
    }
    $conn = false;
}

function ensure_db_connected() {
    global $conn, $servername, $username, $password, $dbname;
    
    if (!$conn || !mysqli_ping($conn)) {
        try {
            $conn = mysqli_connect($servername, $username, $password, $dbname);
            
            if ($conn) {
                mysqli_set_charset($conn, "utf8mb4");
                return $conn;
            } else {
                error_log("Database reconnection failed: " . mysqli_connect_error());
                return false;
            }
        } catch (Exception $e) {
            error_log("Database reconnection error: " . $e->getMessage());
            return false;
        }
    }
    
    return $conn;
}
?> 