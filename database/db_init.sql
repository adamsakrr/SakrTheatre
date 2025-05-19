CREATE TABLE IF NOT EXISTS users (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(30) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(50) NOT NULL,
    full_name VARCHAR(100),
    phone VARCHAR(15),
    address TEXT,
    age INT(3),
    role ENUM('user', 'admin', 'staff') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE IF NOT EXISTS shows (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    duration INT(3) NOT NULL,
    language VARCHAR(30),
    genre VARCHAR(30),
    age_rating VARCHAR(5),
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE IF NOT EXISTS showtimes (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    show_id INT(6) UNSIGNED,
    date DATE NOT NULL,
    time TIME NOT NULL,
    hall VARCHAR(30) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (show_id) REFERENCES shows(id) ON DELETE CASCADE
);


CREATE TABLE IF NOT EXISTS seats (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    showtime_id INT(6) UNSIGNED,
    seat_row CHAR(1) NOT NULL,
    seat_number INT(3) NOT NULL,
    category ENUM('Premium', 'Regular', 'Economy') DEFAULT 'Regular',
    is_booked BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (showtime_id) REFERENCES showtimes(id) ON DELETE CASCADE,
    UNIQUE(showtime_id, seat_row, seat_number)
);


CREATE TABLE IF NOT EXISTS bookings (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(6) UNSIGNED,
    showtime_id INT(6) UNSIGNED,
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('Pending', 'Completed', 'Failed') DEFAULT 'Pending',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (showtime_id) REFERENCES showtimes(id) ON DELETE CASCADE
);


CREATE TABLE IF NOT EXISTS booking_details (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id INT(6) UNSIGNED,
    seat_id INT(6) UNSIGNED,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (seat_id) REFERENCES seats(id) ON DELETE CASCADE
);


CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
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
);

CREATE TABLE IF NOT EXISTS booking_coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    coupon_id INT NOT NULL,
    discount_amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE
);

INSERT IGNORE INTO users (username, password, email, full_name, phone, address, role, age) 
VALUES ('admin', '$2y$10$WYrjXcZM.aWFYBGWDlpqJ.HnTCF3kYbQNHyBMcqN09K0QdtnQvZbq', 'admin@theatre.com', 'Admin User', '12345678', 'Admin Address', 'admin', 30);

INSERT IGNORE INTO users (username, password, email, full_name, phone, address, role, age) 
VALUES ('staff', '$2y$10$jC4XobO5LU9BXqPmp/HPb.zdD0VMUJzSPQOGAoTTUcJfrvJ8Qwn7i', 'staff@theatre.com', 'Staff User', '12345678', 'Staff Address', 'staff', 25);

CREATE TABLE IF NOT EXISTS support_conversations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(6) UNSIGNED NOT NULL,
    subject VARCHAR(100) NOT NULL,
    status ENUM('open', 'closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS support_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT UNSIGNED NOT NULL,
    sender_id INT(6) UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES support_conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
); 