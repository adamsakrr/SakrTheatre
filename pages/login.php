<?php
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

include __DIR__ . '/../templates/header.php';

if (isLoggedIn()) {
    $_SESSION['message'] = "You are already logged in!";
    $_SESSION['message_type'] = "error";
    header("Location: /index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = "Username and password are required";
    } else {
        if (loginUser($username, $password)) {
            $_SESSION['message'] = "Login successful!";
            $_SESSION['message_type'] = "success";
            header("Location: /index.php");
            exit();
        } else {
            $error = "Invalid username or password";
        }
    }
}
?>

<div class="bg-light">
    <div class="container py-5">
        <h1 class="fw-bold">Welcome Back</h1>
        <p class="lead">Log in to manage your bookings and access your account</p>
    </div>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-4">
            <div class="card shadow border-0 rounded-3">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <div class="rounded-circle bg-primary-light mx-auto d-flex align-items-center justify-content-center" style="width: 88px; height: 88px;">
                            <i class="fas fa-user fa-2x text-primary"></i>
                        </div>
                        <h2 class="card-title mt-4 fw-bold">Login to Your Account</h2>
                    </div>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label for="username" class="form-label">Username</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-user text-primary"></i>
                                </span>
                                <input type="text" id="username" name="username" required class="form-control" placeholder="Enter your username">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock text-primary"></i>
                                </span>
                                <input type="password" id="password" name="password" required class="form-control" placeholder="Enter your password">
                            </div>
                        </div>
                        
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary py-3">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                        </div>
                    </form>
                    
                    <div class="mt-4 text-center">
                        <p class="mb-0">Don't have an account? <a href="/pages/register.php" class="fw-bold">Register here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?> 