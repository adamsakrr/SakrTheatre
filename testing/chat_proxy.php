<?php


$_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';

header('Content-Type: application/json');

ini_set('display_errors', 0);
ini_set('mysqli.default_port', '8889');

$action = isset($_POST['action']) ? $_POST['action'] : 'init';
$session_id = isset($_POST['session_id']) ? $_POST['session_id'] : '';
$message = isset($_POST['message']) ? $_POST['message'] : '';
$last_timestamp = isset($_POST['last_timestamp']) ? $_POST['last_timestamp'] : 0;

ob_start();

try {
    $servername = "localhost";
    
    $username = "root";
    $password = "";
    $dbname = "theatre_booking";
    
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    
    if (!$conn) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }
    
    mysqli_close($conn);
    $_POST['action'] = $action;
    if (!empty($session_id)) $_POST['session_id'] = $session_id;
    if (!empty($message)) $_POST['message'] = $message;
    if (!empty($last_timestamp)) $_POST['last_timestamp'] = $last_timestamp;
    
    require_once __DIR__ . '/includes/chat_ajax.php';
    
} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Proxy error: ' . $e->getMessage()
    ]);
}
if (ob_get_level() > 0) {
    ob_end_flush();
}
?> 