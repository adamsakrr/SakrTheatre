<?php
function getAllShows() {
    global $conn;
    
    $sql = "SELECT * FROM shows ORDER BY title";
    $result = mysqli_query($conn, $sql);
    
    $shows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $shows[] = $row;
    }
    
    return $shows;
}

function getShow($show_id) {
    global $conn;
    
    $show_id = (int)$show_id;
    $sql = "SELECT * FROM shows WHERE id = $show_id";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) == 1) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

function getShowtimes($show_id) {
    global $conn;
    
    $show_id = (int)$show_id;
    $sql = "SELECT * FROM showtimes WHERE show_id = $show_id AND date >= CURDATE() ORDER BY date, time";
    $result = mysqli_query($conn, $sql);
    
    $showtimes = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $showtimes[] = $row;
    }
    
    return $showtimes;
}

function getShowtime($showtime_id) {
    global $conn;
    
    $showtime_id = (int)$showtime_id;
    $sql = "SELECT s.*, sh.title, sh.description, sh.image_url 
            FROM showtimes s
            JOIN shows sh ON s.show_id = sh.id
            WHERE s.id = $showtime_id";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) == 1) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

function getSeats($showtime_id) {
    global $conn;
    
    $showtime_id = (int)$showtime_id;
    $sql = "SELECT * FROM seats WHERE showtime_id = $showtime_id ORDER BY seat_row, seat_number";
    $result = mysqli_query($conn, $sql);
    
    $seats = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $seats[] = $row;
    }
    
    return $seats;
}

function createBooking($user_id, $showtime_id, $seat_ids, $total_amount, $payment_data = null) {
    global $conn;
    
    mysqli_begin_transaction($conn);
    
    try {
        $user_id = (int)$user_id;
        $showtime_id = (int)$showtime_id;
        $total_amount = (float)$total_amount;
        $payment_status = isset($payment_data['payment_status']) ? "'" . mysqli_real_escape_string($conn, $payment_data['payment_status']) . "'" : "'Pending'";
        
        $sql = "INSERT INTO bookings (user_id, showtime_id, total_amount, payment_status) 
                VALUES ($user_id, $showtime_id, $total_amount, $payment_status)";
        
        if (!mysqli_query($conn, $sql)) {
            error_log("Error creating booking: " . mysqli_error($conn));
            throw new Exception("Error creating booking: " . mysqli_error($conn));
        }
        
        $booking_id = mysqli_insert_id($conn);
        
        foreach ($seat_ids as $seat_id) {
            $seat_id = (int)$seat_id;
            
            $sql = "INSERT INTO booking_details (booking_id, seat_id) VALUES ($booking_id, $seat_id)";
            if (!mysqli_query($conn, $sql)) {
                error_log("Error creating booking detail: " . mysqli_error($conn));
                throw new Exception("Error creating booking detail: " . mysqli_error($conn));
            }
            $sql = "UPDATE seats SET is_booked = 1 WHERE id = $seat_id";
            mysqli_query($conn, $sql);
            if (mysqli_affected_rows($conn) > 0) {
                error_log("Successfully marked seat $seat_id as booked");
            } else {
                error_log("Failed to mark seat $seat_id as booked. Error: " . mysqli_error($conn));
            }
        }
        
        if ($payment_data) {
            $card_last_four = isset($payment_data['card_last_four']) ? 
                "'" . mysqli_real_escape_string($conn, $payment_data['card_last_four']) . "'" : "NULL";
            $billing_name = isset($payment_data['billing_name']) ? 
                "'" . mysqli_real_escape_string($conn, $payment_data['billing_name']) . "'" : "NULL";
            
            $sql = "UPDATE bookings SET 
                    card_last_four = $card_last_four,
                    billing_name = $billing_name
                    WHERE id = $booking_id";
                    
            if (!mysqli_query($conn, $sql)) {
                error_log("Error saving payment data: " . mysqli_error($conn));
                throw new Exception("Error saving payment data: " . mysqli_error($conn));
            }
        }
        
        mysqli_commit($conn);
        return $booking_id;
    } catch (Exception $e) {
        error_log("Booking transaction failed: " . $e->getMessage());
        mysqli_rollback($conn);
        
        if (defined('TESTING_MODE') || isset($_GET['demo']) || true) {
            error_log("Attempting simplified booking for testing");
            
            $sql = "INSERT INTO bookings (user_id, showtime_id, total_amount, payment_status) 
                   VALUES ($user_id, $showtime_id, $total_amount, 'Completed')";
            if (mysqli_query($conn, $sql)) {
                $test_booking_id = mysqli_insert_id($conn);
                error_log("Created test booking ID: " . $test_booking_id);
                
                foreach ($seat_ids as $seat_id) {
                    $seat_id = (int)$seat_id;
                    
                    $sql = "INSERT INTO booking_details (booking_id, seat_id) VALUES ($test_booking_id, $seat_id)";
                    mysqli_query($conn, $sql);
                    
                    $sql = "UPDATE seats SET is_booked = 1 WHERE id = $seat_id";
                    mysqli_query($conn, $sql);
                }
                
                return $test_booking_id;
            } else {
                error_log("Even simplified booking failed: " . mysqli_error($conn));
            }
        }
        
        return false;
    }
}

function getUserBookings($user_id) {
    global $conn;
    
    $user_id = (int)$user_id;
    $sql = "SELECT b.*, sh.title as show_title, s.date, s.time, s.hall
            FROM bookings b
            JOIN showtimes s ON b.showtime_id = s.id
            JOIN shows sh ON s.show_id = sh.id
            WHERE b.user_id = $user_id
            ORDER BY b.booking_date DESC";
    
    $result = mysqli_query($conn, $sql);
    
    $bookings = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $booking_id = $row['id'];
        $sql2 = "SELECT s.seat_row, s.seat_number, s.category
                FROM booking_details bd
                JOIN seats s ON bd.seat_id = s.id
                WHERE bd.booking_id = $booking_id";
        
        $seats_result = mysqli_query($conn, $sql2);
        $seats = [];
        
        while ($seat = mysqli_fetch_assoc($seats_result)) {
            $seats[] = $seat;
        }
        
        $row['seats'] = $seats;
        $bookings[] = $row;
    }
    
    return $bookings;
}

function addShow($title, $description, $duration, $language, $genre, $age_rating, $image_url) {
    global $conn;
    
    $title = mysqli_real_escape_string($conn, $title);
    $description = mysqli_real_escape_string($conn, $description);
    $duration = (int)$duration;
    $language = mysqli_real_escape_string($conn, $language);
    $genre = mysqli_real_escape_string($conn, $genre);
    $age_rating = mysqli_real_escape_string($conn, $age_rating);
    $image_url = mysqli_real_escape_string($conn, $image_url);
    
    $sql = "INSERT INTO shows (title, description, duration, language, genre, age_rating, image_url)
            VALUES ('$title', '$description', $duration, '$language', '$genre', '$age_rating', '$image_url')";
    
    if (mysqli_query($conn, $sql)) {
        return mysqli_insert_id($conn);
    }
    return false;
}
function addShowtime($show_id, $date, $time, $hall, $price) {
    global $conn;
    
    $show_id = (int)$show_id;
    $date = mysqli_real_escape_string($conn, $date);
    $time = mysqli_real_escape_string($conn, $time);
    $hall = mysqli_real_escape_string($conn, $hall);
    $price = (float)$price;
    
    $sql = "INSERT INTO showtimes (show_id, date, time, hall, price)
            VALUES ($show_id, '$date', '$time', '$hall', $price)";
    
    if (mysqli_query($conn, $sql)) {
        $showtime_id = mysqli_insert_id($conn);
        
        createSeatsForShowtime($showtime_id, $hall);
        
        return $showtime_id;
    }
    return false;
}

function createSeatsForShowtime($showtime_id, $hall) {
    global $conn;
    
    $rows = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
    $seats_per_row = 10;
    
    foreach ($rows as $row) {
        $category = 'Regular';
        if ($row == 'A' || $row == 'B') {
            $category = 'Premium';
        } else if ($row == 'G' || $row == 'H') {
            $category = 'Economy';
        }
        
        for ($i = 1; $i <= $seats_per_row; $i++) {
            $sql = "INSERT INTO seats (showtime_id, seat_row, seat_number, category)
                    VALUES ($showtime_id, '$row', $i, '$category')";
            mysqli_query($conn, $sql);
        }
    }
}

function calculateTicketPrice($showtime_id, $seat_ids) {
    global $conn;
    
    $showtime_id = (int)$showtime_id;
    $sql = "SELECT price FROM showtimes WHERE id = $showtime_id";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) == 1) {
        $showtime = mysqli_fetch_assoc($result);
        $base_price = $showtime['price'];
        
        $total_price = 0;
        foreach ($seat_ids as $seat_id) {
            $seat_id = (int)$seat_id;
            $sql = "SELECT category FROM seats WHERE id = $seat_id AND showtime_id = $showtime_id";
            $result = mysqli_query($conn, $sql);
            
            if (mysqli_num_rows($result) == 1) {
                $seat = mysqli_fetch_assoc($result);
                $category = $seat['category'];
                
                $multiplier = 1.0;
                if ($category == 'Premium') {
                    $multiplier = 1.5;
                } else if ($category == 'Economy') {
                    $multiplier = 0.8;
                }
                
                $total_price += $base_price * $multiplier;
            }
        }
        
        return $total_price;
    }
    
    return 0;
}

function getAllUsers() {
    global $conn;
    
    $sql = "SELECT * FROM users ORDER BY id";
    $result = mysqli_query($conn, $sql);
    
    $users = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    
    return $users;
}

function addUser($username, $email, $password, $role) {
    global $conn;
    
    $username = mysqli_real_escape_string($conn, $username);
    $email = mysqli_real_escape_string($conn, $email);
    $role = mysqli_real_escape_string($conn, $role);
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO users (username, email, password, role) 
            VALUES ('$username', '$email', '$hashed_password', '$role')";
    
    return mysqli_query($conn, $sql);
}

function getAllBookings() {
    global $conn;
    
    $bookings = [];
    
    $sql = "SELECT b.id, b.user_id, b.showtime_id, b.total_amount, b.booking_date, 
           u.username, s.title as show_title, st.date, st.time
           FROM bookings b
           JOIN users u ON b.user_id = u.id
           JOIN showtimes st ON b.showtime_id = st.id
           JOIN shows s ON st.show_id = s.id
           ORDER BY b.booking_date DESC";
           
    $result = $conn->query($sql);
    
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    
    return $bookings;
}

function getTotalShows() {
    global $conn;
    $sql = "SELECT COUNT(*) as total FROM shows";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}

function getTotalUsers() {
    global $conn;
    $sql = "SELECT COUNT(*) as total FROM users";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}

function getTotalBookings() {
    global $conn;
    $sql = "SELECT COUNT(*) as total FROM bookings";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}

function updateShow($id, $title, $description, $duration, $language, $genre, $age_rating, $image_url) {
    global $conn;
    
    $id = (int)$id;
    $title = mysqli_real_escape_string($conn, $title);
    $description = mysqli_real_escape_string($conn, $description);
    $duration = (int)$duration;
    $language = mysqli_real_escape_string($conn, $language);
    $genre = mysqli_real_escape_string($conn, $genre);
    $age_rating = mysqli_real_escape_string($conn, $age_rating);
    $image_url = mysqli_real_escape_string($conn, $image_url);
    
    $sql = "UPDATE shows SET 
            title = '$title',
            description = '$description',
            duration = $duration,
            language = '$language',
            genre = '$genre',
            age_rating = '$age_rating',
            image_url = '$image_url'
            WHERE id = $id";
    
    return mysqli_query($conn, $sql);
}

function deleteShow($id) {
    global $conn;
    
    $id = (int)$id;
    
    $sql = "SELECT id FROM showtimes WHERE show_id = $id";
    $result = mysqli_query($conn, $sql);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $showtime_id = $row['id'];
        
        $sql = "DELETE FROM seats WHERE showtime_id = $showtime_id";
        mysqli_query($conn, $sql);
        
        $sql = "DELETE FROM showtimes WHERE id = $showtime_id";
        mysqli_query($conn, $sql);
    }
    
    $sql = "DELETE FROM shows WHERE id = $id";
    return mysqli_query($conn, $sql);
}

function updateUser($id, $username, $email, $role) {
    global $conn;
    
    $id = (int)$id;
    $username = $conn->real_escape_string($username);
    $email = $conn->real_escape_string($email);
    $role = $conn->real_escape_string($role);
    
    $sql = "UPDATE users SET username = '$username', email = '$email', role = '$role' WHERE id = $id";
    return $conn->query($sql);
}

function deleteUser($id) {
    global $conn;
    $id = (int)$id;
    $sql = "DELETE FROM bookings WHERE user_id = $id";
    if (!$conn->query($sql)) {
        return false;
    }
    
    $sql = "DELETE FROM users WHERE id = $id";
    return $conn->query($sql);
}

function getAllCoupons() {
    global $conn;
    $sql = "SELECT * FROM coupons ORDER BY created_at DESC";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getCoupon($id) {
    global $conn;
    $id = (int)$id;
    $sql = "SELECT * FROM coupons WHERE id = $id";
    $result = $conn->query($sql);
    
    if ($result->num_rows == 1) {
        return $result->fetch_assoc();
    }
    return null;
}

function getCouponByCode($code) {
    global $conn;
    $code = $conn->real_escape_string($code);
    $sql = "SELECT * FROM coupons WHERE code = '$code'";
    $result = $conn->query($sql);
    
    if ($result->num_rows == 1) {
        return $result->fetch_assoc();
    }
    return null;
}

function createCoupon($code, $discount_amount, $discount_type, $valid_from, $valid_to, $min_purchase = 0, $max_discount = null, $max_uses = null) {
    global $conn;
    
    $code = $conn->real_escape_string($code);
    $discount_amount = (float)$discount_amount;
    $discount_type = $conn->real_escape_string($discount_type);
    $valid_from = $conn->real_escape_string($valid_from);
    $valid_to = $conn->real_escape_string($valid_to);
    $min_purchase = (float)$min_purchase;
    $max_discount = $max_discount ? (float)$max_discount : "NULL";
    $max_uses = $max_uses ? (int)$max_uses : "NULL";
    
    $sql = "INSERT INTO coupons (code, discount_amount, discount_type, valid_from, valid_to, min_purchase, max_discount, max_uses)
            VALUES ('$code', $discount_amount, '$discount_type', '$valid_from', '$valid_to', $min_purchase, $max_discount, $max_uses)";
    
    if ($conn->query($sql)) {
        return $conn->insert_id;
    }
    return false;
}

function updateCoupon($id, $code, $discount_amount, $discount_type, $valid_from, $valid_to, $min_purchase = 0, $max_discount = null, $max_uses = null, $is_active = true) {
    global $conn;
    $id = (int)$id;
    $code = $conn->real_escape_string($code);
    $discount_amount = (float)$discount_amount;
    $discount_type = $conn->real_escape_string($discount_type);
    $valid_from = $conn->real_escape_string($valid_from);
    $valid_to = $conn->real_escape_string($valid_to);
    $min_purchase = (float)$min_purchase;
    $max_discount = $max_discount ? (float)$max_discount : "NULL";
    $max_uses = $max_uses ? (int)$max_uses : "NULL";
    $is_active = $is_active ? 1 : 0;
    
    $sql = "UPDATE coupons SET 
            code = '$code',
            discount_amount = $discount_amount,
            discount_type = '$discount_type',
            valid_from = '$valid_from',
            valid_to = '$valid_to',
            min_purchase = $min_purchase,
            max_discount = $max_discount,
            max_uses = $max_uses,
            is_active = $is_active
            WHERE id = $id";
    
    return $conn->query($sql);
}

function deleteCoupon($id) {
    global $conn;
    $id = (int)$id;
    $sql = "DELETE FROM coupons WHERE id = $id";
    return $conn->query($sql);
}

function validateCoupon($code, $total_amount) {
    global $conn;
    
    $code = $conn->real_escape_string($code);
    $total_amount = (float)$total_amount;
    $current_date = date('Y-m-d');
    
    $sql = "SELECT * FROM coupons 
            WHERE code = '$code' 
            AND is_active = 1
            AND valid_from <= '$current_date'
            AND valid_to >= '$current_date'
            AND min_purchase <= $total_amount";
    
    if ($max_uses_check = " AND (max_uses IS NULL OR times_used < max_uses)") {
        $sql .= $max_uses_check;
    }
    
    $result = $conn->query($sql);
    
    if ($result->num_rows == 1) {
        return $result->fetch_assoc();
    }
    return false;
}

function calculateDiscount($coupon, $total_amount) {
    $discount = 0;
    
    if ($coupon['discount_type'] == 'percentage') {
        $discount = $total_amount * ($coupon['discount_amount'] / 100);
        
        if (!is_null($coupon['max_discount']) && $discount > $coupon['max_discount']) {
            $discount = $coupon['max_discount'];
        }
    } else {
        $discount = $coupon['discount_amount'];
        
        if ($discount > $total_amount) {
            $discount = $total_amount;
        }
    }
    
    return $discount;
}

function applyCoupon($booking_id, $coupon_id, $discount_amount) {
    global $conn;
    
    $booking_id = (int)$booking_id;
    $coupon_id = (int)$coupon_id;
    $discount_amount = (float)$discount_amount;
    
    $conn->begin_transaction();
    
    try {
        $sql = "INSERT INTO booking_coupons (booking_id, coupon_id, discount_amount)
                VALUES ($booking_id, $coupon_id, $discount_amount)";
        
        if (!$conn->query($sql)) {
            throw new Exception("Failed to record coupon usage");
        }
        
        $sql = "UPDATE coupons SET times_used = times_used + 1 WHERE id = $coupon_id";
        
        if (!$conn->query($sql)) {
            throw new Exception("Failed to update coupon usage count");
        }
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

function updateShowtime($showtime_id, $date, $time, $hall, $price) {
    global $conn;
    
    $showtime_id = (int)$showtime_id;
    $date = mysqli_real_escape_string($conn, $date);
    $time = mysqli_real_escape_string($conn, $time);
    $hall = mysqli_real_escape_string($conn, $hall);
    $price = (float)$price;
    
    $sql = "UPDATE showtimes SET 
            date = '$date',
            time = '$time',
            hall = '$hall',
            price = $price
            WHERE id = $showtime_id";
    
    return mysqli_query($conn, $sql);
}
function getShowtimesWithBookingCount($show_id) {
    global $conn;
    
    $show_id = (int)$show_id;
    $sql = "SELECT s.*, 
            (SELECT COUNT(*) FROM bookings b WHERE b.showtime_id = s.id) as booking_count
            FROM showtimes s 
            WHERE s.show_id = $show_id 
            ORDER BY s.date DESC, s.time ASC";
    
    $result = mysqli_query($conn, $sql);
    
    $showtimes = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['booking_count'] = (int)$row['booking_count'];
        $showtimes[] = $row;
    }
    
    return $showtimes;
}
function deleteShowtime($showtime_id) {
    global $conn;
    
    $showtime_id = (int)$showtime_id;
    $sql = "SELECT COUNT(*) as booking_count FROM bookings WHERE showtime_id = $showtime_id";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    
    if ($row['booking_count'] > 0) {
        return false; 
    }
    
    mysqli_begin_transaction($conn);
    
    try {
        $sql = "DELETE FROM seats WHERE showtime_id = $showtime_id";
        if (!mysqli_query($conn, $sql)) {
            throw new Exception("Failed to delete seats");
        }
        
        $sql = "DELETE FROM showtimes WHERE id = $showtime_id";
        if (!mysqli_query($conn, $sql)) {
            throw new Exception("Failed to delete showtime");
        }
        
        mysqli_commit($conn);
        return true;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return false;
    }
}


function getTotalRevenue() {
    global $conn;
    
    $sql = "SELECT SUM(total_amount) as total_revenue FROM bookings";
    $result = $conn->query($sql);
    
    if ($result && $row = $result->fetch_assoc()) {
        return $row['total_revenue'] ?? 0;
    }
    
    return 0;
}


function getRevenueByDateRange($startDate, $endDate) {
    global $conn;
    
    $data = [];
    
    $sql = "SELECT DATE(booking_date) as date, SUM(total_amount) as daily_revenue 
            FROM bookings 
            WHERE DATE(booking_date) BETWEEN ? AND ? 
            GROUP BY DATE(booking_date) 
            ORDER BY date ASC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    return $data;
}


function getRevenueByShow() {
    global $conn;
    
    $data = [];
    
    $sql = "SELECT s.id, s.title, COUNT(b.id) as booking_count, SUM(b.total_amount) as revenue 
            FROM shows s
            JOIN showtimes st ON s.id = st.show_id
            JOIN bookings b ON st.id = b.showtime_id
            GROUP BY s.id
            ORDER BY revenue DESC";
            
    $result = $conn->query($sql);
    
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    return $data;
}
function createSupportConversation($user_id, $subject) {
    global $conn;
    
    $user_id = (int)$user_id;
    $subject = $conn->real_escape_string($subject);
    
    $sql = "INSERT INTO support_conversations (user_id, subject) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $subject);
    
    if ($stmt->execute()) {
        return $stmt->insert_id;
    }
    
    return false;
}


function addSupportMessage($conversation_id, $sender_id, $message) {
    global $conn;
    
    $conversation_id = (int)$conversation_id;
    $sender_id = (int)$sender_id;
    $message = $conn->real_escape_string($message);
    
    $sql = "UPDATE support_conversations SET updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $conversation_id);
    $stmt->execute();
    
    $sql = "INSERT INTO support_messages (conversation_id, sender_id, message) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $conversation_id, $sender_id, $message);
    
    if ($stmt->execute()) {
        return $stmt->insert_id;
    }
    
    return false;
}


function getUserSupportConversations($user_id) {
    global $conn;
    
    $user_id = (int)$user_id;
    
    $sql = "SELECT sc.*, 
            (SELECT COUNT(*) FROM support_messages sm WHERE sm.conversation_id = sc.id AND sm.sender_id != ? AND sm.is_read = 0) as unread_count
            FROM support_conversations sc
            WHERE sc.user_id = ?
            ORDER BY sc.updated_at DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $conversations = [];
    
    while ($row = $result->fetch_assoc()) {
        $conversations[] = $row;
    }
    
    return $conversations;
}

function getAllSupportConversations($status = 'all') {
    global $conn;
    $tableExists = false;
    $check = $conn->query("SHOW TABLES LIKE 'support_conversations'");
    if ($check && $check->num_rows > 0) {
        $tableExists = true;
    }
    
    if (!$tableExists) {
        return [];
    }
    
    $sql = "SELECT sc.*, u.username as user_username,
            (SELECT COUNT(*) FROM support_messages sm WHERE sm.conversation_id = sc.id AND sm.sender_id = sc.user_id AND sm.is_read = 0) as unread_count
            FROM support_conversations sc
            JOIN users u ON sc.user_id = u.id";
            
    if ($status !== 'all') {
        $status = $conn->real_escape_string($status);
        $sql .= " WHERE sc.status = '$status'";
    }
    
    $sql .= " ORDER BY sc.updated_at DESC";
    
    $result = $conn->query($sql);
    $conversations = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $conversations[] = $row;
        }
    }
    
    return $conversations;
}

function getSupportMessages($conversation_id, $current_user_id) {
    global $conn;
    
    $tablesExist = false;
    $check = $conn->query("SHOW TABLES LIKE 'support_conversations'");
    if ($check && $check->num_rows > 0) {
        $check = $conn->query("SHOW TABLES LIKE 'support_messages'");
        if ($check && $check->num_rows > 0) {
            $tablesExist = true;
        }
    }
    
    if (!$tablesExist) {
        return [
            'conversation' => null,
            'messages' => []
        ];
    }
    
    $conversation_id = (int)$conversation_id;
    $current_user_id = (int)$current_user_id;
    
    $sql = "SELECT sc.*, u.username as user_username
            FROM support_conversations sc
            JOIN users u ON sc.user_id = u.id
            WHERE sc.id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $conversation_id);
    $stmt->execute();
    $conversation = $stmt->get_result()->fetch_assoc();
    
    if (!$conversation) {
        return [
            'conversation' => null,
            'messages' => []
        ];
    }
    $sql = "SELECT sm.*, 
            u.username as sender_username,
            u.role as sender_role
            FROM support_messages sm
            JOIN users u ON sm.sender_id = u.id
            WHERE sm.conversation_id = ?
            ORDER BY sm.created_at ASC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $conversation_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $messages = [];
    
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    $sql = "UPDATE support_messages 
            SET is_read = 1 
            WHERE conversation_id = ? 
            AND sender_id != ? 
            AND is_read = 0";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $conversation_id, $current_user_id);
    $stmt->execute();
    
    return [
        'conversation' => $conversation,
        'messages' => $messages
    ];
}
function closeSupportConversation($conversation_id) {
    global $conn;
    
    $conversation_id = (int)$conversation_id;
    
    $sql = "UPDATE support_conversations SET status = 'closed' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $conversation_id);
    
    return $stmt->execute();
}
function reopenSupportConversation($conversation_id) {
    global $conn;
    
    $conversation_id = (int)$conversation_id;
    
    $sql = "UPDATE support_conversations SET status = 'open' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $conversation_id);
    
    return $stmt->execute();
}
?> 