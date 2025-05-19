<?php
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: /pages/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 3 || strlen($username) > 30) {
        $errors[] = "Username must be between 3 and 30 characters";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    $sql = "SELECT * FROM users WHERE (username = ? OR email = ?) AND id != ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $username, $email, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if ($user['username'] === $username) {
            $errors[] = "Username already taken";
        }
        if ($user['email'] === $email) {
            $errors[] = "Email already in use";
        }
    }
    
    if (empty($errors)) {
        $sql = "UPDATE users SET username = ?, email = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $username, $email, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Profile updated successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error updating profile: " . $conn->error;
            $_SESSION['message_type'] = "danger";
        }
    } else {
        $_SESSION['message'] = implode("<br>", $errors);
        $_SESSION['message_type'] = "danger";
    }
    header('Location: /pages/profile.php');
    exit();
} else {
    header('Location: /pages/profile.php');
    exit();
} 