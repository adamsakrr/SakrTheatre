<?php
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

include __DIR__ . '/../templates/header.php';

if (isLoggedIn()) {
    $_SESSION['message'] = "You are already logged in!";
    $_SESSION['message_type'] = "error";
    header("Location: index.php");
    exit();
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $address = $_POST['address'] ?? '';
    $age = $_POST['age'] ?? '';
    
    if (empty($username) || empty($password) || empty($confirm_password) || empty($email) || empty($phone) || empty($full_name) || empty($age)) {
        $error = "All required fields must be filled";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif (!preg_match("/^[0-9]{8}$/", $phone)) {
        $error = "Phone number must be 8 digits";
    } elseif (!is_numeric($age) || $age < 1 || $age > 120) {
        $error = "Age must be a valid number between 1 and 120";
    } else {
        $query = "SELECT * FROM users WHERE username = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $error = "Username already exists";
        } else {
            if (registerUser($username, $password, $email, $full_name, $phone, $address, $age)) {
                $_SESSION['message'] = "Registration successful! Please login.";
                $_SESSION['message_type'] = "success";
                header("Location: /pages/login.php");
                exit();
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<div class="bg-light">
    <div class="container py-5">
        <h1 class="fw-bold">Create an Account</h1>
        <p class="lead">Join us to book tickets and enjoy exclusive benefits</p>
    </div>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow border-0 rounded-3">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <div class="rounded-circle bg-primary-light mx-auto d-flex align-items-center justify-content-center" style="width: 88px; height: 88px;">
                            <i class="fas fa-user-plus fa-2x text-primary"></i>
                        </div>
                        <h2 class="card-title mt-4 fw-bold">Join Our Theatre Community</h2>
                        <p class="text-muted">Get access to exclusive theatre experiences and easy booking</p>
                        <p class="text-muted small">Fields marked with <span class="text-danger">*</span> are required</p>
                    </div>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user text-primary"></i>
                                    </span>
                                    <input type="text" id="username" name="username" required class="form-control" placeholder="Choose a username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-id-card text-primary"></i>
                                    </span>
                                    <input type="text" id="full_name" name="full_name" required class="form-control" placeholder="Enter your full name" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-envelope text-primary"></i>
                                    </span>
                                    <input type="email" id="email" name="email" required class="form-control" placeholder="Enter your email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-phone text-primary"></i>
                                    </span>
                                    <input type="tel" id="phone" name="phone" required class="form-control" placeholder="Enter your phone number" pattern="[0-9]{8}" title="Please enter a valid 8 digit phone number" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                </div>
                                <br>
                                <div class="form-text">Phone number must be 8 digits with no spaces or dashes</div>
                            </div>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label for="age" class="form-label">Age <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-birthday-cake text-primary"></i>
                                    </span>
                                    <input type="number" id="age" name="age" required class="form-control" placeholder="Enter your age" min="1" max="120" value="<?php echo isset($_POST['age']) ? htmlspecialchars($_POST['age']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="address" class="form-label">Address</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-home text-primary"></i>
                                    </span>
                                    <input type="text" id="address" name="address" class="form-control" placeholder="Enter your address" value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock text-primary"></i>
                                    </span>
                                    <input type="password" id="password" name="password" required class="form-control" placeholder="Create a password" minlength="8">
                                </div>
                                <br>
                                <div class="form-text">Password must be at least 8 characters long</div>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock text-primary"></i>
                                    </span>
                                    <input type="password" id="confirm_password" name="confirm_password" required class="form-control" placeholder="Confirm your password" minlength="8">
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary py-3">
                                <i class="fas fa-user-plus me-2"></i>Create Account
                            </button>
                        </div>
                    </form>
                    
                    <div class="mt-4 text-center">
                        <p class="mb-0">Already have an account? <a href="/pages/login.php" class="fw-bold">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?> 