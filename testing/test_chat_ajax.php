<?php
header('Content-Type: text/plain');

echo "Chat AJAX Direct Test\n";
echo "====================\n\n";

echo "PHP Version: " . phpversion() . "\n";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n\n";

$_POST['action'] = 'init';
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';

ob_start();

try {
    echo "Attempting to include chat_ajax.php...\n";
    
    require_once __DIR__ . '/includes/chat_ajax.php';
    
    echo "chat_ajax.php included successfully.\n";
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

$output = ob_get_clean();

echo "\nRaw Output:\n";
echo "----------\n";
echo $output . "\n\n";

echo "Parsed Output:\n";
echo "-------------\n";
if (!empty($output)) {
    try {
        $json = json_decode($output, true);
        if ($json !== null) {
            echo "Success: " . ($json['success'] ? 'true' : 'false') . "\n";
            if (isset($json['message'])) {
                echo "Message: " . $json['message'] . "\n";
            }
            if (isset($json['session_id'])) {
                echo "Session ID: " . $json['session_id'] . "\n";
            }
            if (isset($json['welcome_message'])) {
                echo "Welcome: " . $json['welcome_message'] . "\n";
            }
            if (isset($json['error'])) {
                echo "Error: " . $json['error'] . "\n";
            }
        } else {
            echo "Failed to parse JSON response.\n";
            echo "JSON error: " . json_last_error_msg() . "\n";
        }
    } catch (Exception $e) {
        echo "Exception parsing JSON: " . $e->getMessage() . "\n";
    }
} else {
    echo "No output received.\n";
}

echo "\nComplete.\n";
?> 