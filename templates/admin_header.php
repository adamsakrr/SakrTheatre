<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sakr Theatre Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="/css/admin.css" rel="stylesheet">
    <link rel="icon" href="stbsfavicon.png" type="image/png">
</head>
<body>
    <div class="wrapper">
        
        <nav id="sidebar" class="bg-dark">
            <div class="sidebar-header">
                <h3><i class="fas fa-theater-masks me-2"></i> STBS Admin</h3>
            </div>

            <ul class="list-unstyled components">
                <li>
                    <a href="/pages/admin/admin.php">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="/pages/admin/admin_shows.php">
                        <i class="fas fa-film"></i> Manage Shows
                    </a>
                </li>
                <li>
                    <a href="/pages/admin/admin_users.php">
                        <i class="fas fa-users"></i> Manage Users
                    </a>
                </li>
                <li>
                    <a href="/pages/admin/admin_bookings.php">
                        <i class="fas fa-ticket-alt"></i> Manage Bookings
                    </a>
                </li>
                <li>
                    <a href="/pages/admin/admin_coupons.php">
                        <i class="fas fa-tags"></i> Manage Coupons
                    </a>
                </li>
                <li>
                    <a href="/pages/admin/admin_chat.php">
                        <i class="fas fa-comments"></i> Customer Chat
                        <?php 
                        try {
                            $chatUnreadStmt = $conn->prepare("SELECT COUNT(*) as count FROM chat_messages WHERE is_from_user = 1 AND is_read = 0");
                            if ($chatUnreadStmt) {
                                $chatUnreadStmt->execute();
                                $chatUnreadResult = $chatUnreadStmt->get_result();
                                $chatUnreadRow = $chatUnreadResult->fetch_assoc();
                                $chatUnreadCount = $chatUnreadRow['count'];
                                if ($chatUnreadCount > 0): 
                        ?>
                        <span class="badge bg-danger"><?php echo $chatUnreadCount; ?></span>
                        <?php 
                                endif;
                                $chatUnreadStmt->close();
                            }
                        } catch (Exception $e) {
                        }
                        ?>
                    </a>
                </li>
                <li>
                    <a href="/index.php">
                        <i class="fas fa-arrow-left"></i> Back to Site
                    </a>
                </li>
            </ul>
            
            <div class="sidebar-footer">
                <p>Sakr Theatre Booking System</p>
                <p class="small">Admin Panel v1.0</p>
            </div>
        </nav>

        <div id="content">
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-dark">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <div class="ms-auto d-flex align-items-center">
                        <div class="admin-user-info me-3">
                            <span class="admin-username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                            <span class="admin-role">Administrator</span>
                        </div>
                        <a href="/pages/logout.php" class="btn btn-outline-danger">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </nav>
            <div class="container-fluid">
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['message'];
                        unset($_SESSION['message']);
                        unset($_SESSION['message_type']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?> 