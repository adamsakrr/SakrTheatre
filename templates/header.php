<?php
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sakr Theatre Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="/images/STBS-favicon.png">
    
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">


</head>
<body<?php echo isLoggedIn() ? ' data-user-id="' . $_SESSION['user_id'] . '"' : ''; ?>>
    <header class="sticky-top">
        <nav class="navbar navbar-expand-lg navbar-light">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="/index.php">
                    <i class="fas fa-theater-masks me-2 text-primary"></i>
                    <div>
                        <span class="fw-bold">STBS</span>
                        <span class="brand-tagline">Sakr Theatre</span>
                    </div>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="/index.php">
                                <i class="fas fa-home me-1"></i> Home
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'shows.php' ? 'active' : ''; ?>" href="/pages/shows.php">
                                <i class="fas fa-film me-1"></i> Shows
                            </a>
                        </li>
                        <?php if (isLoggedIn()): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'active' : ''; ?>" href="/pages/bookings.php">
                                    <i class="fas fa-ticket-alt me-1"></i> My Bookings
                                </a>
                            </li>
                            <?php if (hasAdminAccess()): ?>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-user-shield me-1"></i> Admin
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="adminDropdown">
                                        <li><a class="dropdown-item" href="/pages/admin/admin.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                                        <li><a class="dropdown-item" href="/pages/admin/admin_shows.php"><i class="fas fa-film me-2"></i>Manage Shows</a></li>
                                        <li><a class="dropdown-item" href="/pages/admin/admin_chat.php"><i class="fas fa-headset me-2"></i>Support Messages</a></li>
                                        <?php if (isAdmin()): ?>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item" href="/pages/admin/admin_users.php"><i class="fas fa-users me-2"></i>Manage Users</a></li>
                                            <li><a class="dropdown-item" href="/pages/admin/admin_bookings.php"><i class="fas fa-ticket-alt me-2"></i>Manage Bookings</a></li>
                                            <li><a class="dropdown-item" href="/pages/admin/admin_coupons.php"><i class="fas fa-tags me-2"></i>Manage Coupons</a></li>
                                        <?php endif; ?>
                                    </ul>
                                </li>
                            <?php endif; ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user-circle me-1"></i> Account
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                                    <li><a class="dropdown-item" href="/pages/profile.php"><i class="fas fa-id-card me-2"></i>My Profile</a></li>
                                    
                                    <li><button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                    <i class="fas fa-user-edit me-2"></i>Edit Profile</a></li>
                                    <li><button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                    <i class="fas fa-key me-2"></i>Change Password</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="/pages/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'login.php' ? 'active' : ''; ?>" href="/pages/login.php">
                                    <i class="fas fa-sign-in-alt me-1"></i> Login
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'register.php' ? 'active' : ''; ?>" href="/pages/register.php">
                                    <i class="fas fa-user-plus me-1"></i> Register
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <?php if (isset($_SESSION['message'])): ?>
    <div class="container mt-3">
        <div class="alert alert-<?php echo $_SESSION['message_type'] == 'error' ? 'danger' : $_SESSION['message_type']; ?> alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas <?php echo $_SESSION['message_type'] == 'error' ? 'fa-exclamation-circle' : ($_SESSION['message_type'] == 'success' ? 'fa-check-circle' : 'fa-info-circle'); ?> me-2"></i>
            <?php 
            echo $_SESSION['message'];
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    <?php endif; ?>

    <div class="container1">
        
    </div>
</body>
</html> 