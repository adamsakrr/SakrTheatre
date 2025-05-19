<?php
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

logoutUser();

$_SESSION['message'] = "You have been logged out successfully!";
$_SESSION['message_type'] = "success";

header("Location: /index.php");
exit();
?> 