<?php
session_start();

function registerUser($username, $password, $email) {
    global $conn;
    
    $username = mysqli_real_escape_string($conn, $username);
    $email = mysqli_real_escape_string($conn, $email);
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO users (username, password, email) VALUES ('$username', '$hashed_password', '$email')";
    
    if (mysqli_query($conn, $sql)) {
        return true;
    } else {
        return false;
    }
}

function loginUser($username, $password) {
    global $conn;
    
    $username = mysqli_real_escape_string($conn, $username);
    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            return true;
        }
    }
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

function isStaff() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'staff';
}

function hasAdminAccess() {
    return isAdmin() || isStaff();
}

function hasStaffPermission($feature) {
    if (!isStaff()) {
        return false;
    }
    
    $allowedFeatures = [
        'shows',
        'support', 
        'dashboard'
    ];
    
    return in_array($feature, $allowedFeatures);
}

function logoutUser() {
    session_unset();
    session_destroy();
}

function getCurrentUser() {
    global $conn;
    
    if (!isLoggedIn()) {
        return null;
    }
    
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT id, username, email, full_name, phone, address, age, role FROM users WHERE id = $user_id";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) == 1) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}
?> 