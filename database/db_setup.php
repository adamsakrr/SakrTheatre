<?php
$servername = "localhost";
$username = "root";
$password = "";

$conn = mysqli_connect($servername, $username, $password);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$sql = "CREATE DATABASE IF NOT EXISTS theatre_booking";
if (mysqli_query($conn, $sql)) {
    echo "Database created successfully<br>";
} else {
    echo "Error creating database: " . mysqli_error($conn) . "<br>";
}

mysqli_select_db($conn, "theatre_booking");

$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(30) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(50) NOT NULL,
    full_name VARCHAR(100),
    phone VARCHAR(15),
    address TEXT,
    age INT(3),
    role ENUM('user', 'admin','staff') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "Users table created successfully<br>";
} else {
    echo "Error creating Users table: " . mysqli_error($conn) . "<br>";
}

$sql = "CREATE TABLE IF NOT EXISTS shows (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    duration INT(3) NOT NULL,
    language VARCHAR(30),
    genre VARCHAR(30),
    age_rating VARCHAR(5),
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "Shows table created successfully<br>";
} else {
    echo "Error creating Shows table: " . mysqli_error($conn) . "<br>";
}

$sql = "CREATE TABLE IF NOT EXISTS showtimes (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    show_id INT(6) UNSIGNED,
    date DATE NOT NULL,
    time TIME NOT NULL,
    hall VARCHAR(30) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (show_id) REFERENCES shows(id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $sql)) {
    echo "Showtimes table created successfully<br>";
} else {
    echo "Error creating Showtimes table: " . mysqli_error($conn) . "<br>";
}

$sql = "CREATE TABLE IF NOT EXISTS seats (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    showtime_id INT(6) UNSIGNED,
    seat_row CHAR(1) NOT NULL,
    seat_number INT(3) NOT NULL,
    category ENUM('Premium', 'Regular', 'Economy') DEFAULT 'Regular',
    is_booked BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (showtime_id) REFERENCES showtimes(id) ON DELETE CASCADE,
    UNIQUE(showtime_id, seat_row, seat_number)
)";

if (mysqli_query($conn, $sql)) {
    echo "Seats table created successfully<br>";
} else {
    echo "Error creating Seats table: " . mysqli_error($conn) . "<br>";
}

$sql = "CREATE TABLE IF NOT EXISTS bookings (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(6) UNSIGNED,
    showtime_id INT(6) UNSIGNED,
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('Pending', 'Completed', 'Failed') DEFAULT 'Pending',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (showtime_id) REFERENCES showtimes(id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $sql)) {
    echo "Bookings table created successfully<br>";
} else {
    echo "Error creating Bookings table: " . mysqli_error($conn) . "<br>";
}

$sql = "CREATE TABLE IF NOT EXISTS booking_details (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id INT(6) UNSIGNED,
    seat_id INT(6) UNSIGNED,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (seat_id) REFERENCES seats(id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $sql)) {
    echo "Booking_Details table created successfully<br>";
} else {
    echo "Error creating Booking_Details table: " . mysqli_error($conn) . "<br>";
}

$sql = "CREATE TABLE IF NOT EXISTS coupons (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    discount_amount DECIMAL(10,2) NOT NULL,
    discount_type ENUM('percentage', 'fixed') NOT NULL,
    min_purchase DECIMAL(10,2) DEFAULT 0,
    max_discount DECIMAL(10,2) DEFAULT NULL,
    valid_from DATE NOT NULL,
    valid_to DATE NOT NULL,
    times_used INT DEFAULT 0,
    max_uses INT DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "Coupons table created successfully<br>";
} else {
    echo "Error creating Coupons table: " . mysqli_error($conn) . "<br>";
}

$sql = "CREATE TABLE IF NOT EXISTS booking_coupons (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id INT(6) UNSIGNED NOT NULL,
    coupon_id INT UNSIGNED NOT NULL,
    discount_amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $sql)) {
    echo "Booking_Coupons table created successfully<br>";
} else {
    echo "Error creating Booking_Coupons table: " . mysqli_error($conn) . "<br>";
}

$sql = "CREATE TABLE IF NOT EXISTS chat_sessions (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    session_id VARCHAR(64) NOT NULL UNIQUE,
    user_id INT(6) UNSIGNED NULL,
    status ENUM('active', 'closed') DEFAULT 'active',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
)";

if (mysqli_query($conn, $sql)) {
    echo "Chat sessions table created successfully<br>";
} else {
    echo "Error creating chat sessions table: " . mysqli_error($conn) . "<br>";
}

$sql = "CREATE TABLE IF NOT EXISTS chat_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(6) UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    is_from_user BOOLEAN NOT NULL DEFAULT 1,
    is_read BOOLEAN NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $sql)) {
    echo "Chat messages table created successfully<br>";
} else {
    echo "Error creating chat messages table: " . mysqli_error($conn) . "<br>";
}



if (mysqli_query($conn, $sql)) {
    echo "Support messages table created successfully<br>";
} else {
    echo "Error creating support messages table: " . mysqli_error($conn) . "<br>";
}

$hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
$sql = "INSERT IGNORE INTO users (username, password, email, full_name, phone, address, role, age) 
        VALUES ('admin', '$hashed_password', 'admin@theatre.com', 'Admin User', '12345678', 'Admin Address', 'admin', 30)";

if (mysqli_query($conn, $sql)) {
    echo "Default admin user created successfully<br>";
} else {
    echo "Error creating default admin user: " . mysqli_error($conn) . "<br>";
}

$hashed_password = password_hash('staff123', PASSWORD_DEFAULT);
$sql = "INSERT IGNORE INTO users (username, password, email, full_name, phone, address, role, age) 
        VALUES ('staff', '$hashed_password', 'staff@theatre.com', 'Staff User', '12345678', 'Staff Address', 'staff', 25)";

if (mysqli_query($conn, $sql)) {
    echo "Default staff user created successfully<br>";
} else {
    echo "Error creating default staff user: " . mysqli_error($conn) . "<br>";
}

mysqli_close($conn);
echo "Database setup completed";
?> 