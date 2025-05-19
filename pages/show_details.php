<?php
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

include __DIR__ . '/../templates/header.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "Invalid show ID";
    $_SESSION['message_type'] = "error";
    header("Location: shows.php");
    exit();
}

$show_id = (int)$_GET['id'];
$show = getShow($show_id);

if (!$show) {
    $_SESSION['message'] = "Show not found";
    $_SESSION['message_type'] = "error";
    header("Location: shows.php");
    exit();
}

$showtimes = getShowtimes($show_id);
?>

<div class="container">
    <div class="row">
        <div class="col-md-4">
            <?php if (!empty($show['image_url'])): ?>
                <img src="<?php echo htmlspecialchars($show['image_url']); ?>" class="img-fluid" alt="<?php echo htmlspecialchars($show['title']); ?>">
            <?php endif; ?>
        </div>
        
        <div class="col-md-8">
            <h1><?php echo htmlspecialchars($show['title']); ?></h1>
            <p class="lead"><?php echo htmlspecialchars($show['description']); ?></p>
            
            <div class="show-details">
                <p><strong>Duration:</strong> <?php echo $show['duration']; ?> minutes</p>
                <p><strong>Language:</strong> <?php echo htmlspecialchars($show['language']); ?></p>
                <p><strong>Genre:</strong> <?php echo htmlspecialchars($show['genre']); ?></p>
                <?php if (!empty($show['age_rating'])): ?>
                    <p><strong>Age Rating:</strong> <?php echo htmlspecialchars($show['age_rating']); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-12">
            <h2>Available Showtimes</h2>
            
            <?php if (empty($showtimes)): ?>
                <p>No showtimes available at this time.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Hall</th>
                                <th>Price</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($showtimes as $showtime): ?>
                                <tr>
                                    <td><?php echo date('F j, Y', strtotime($showtime['date'])); ?></td>
                                    <td><?php echo date('g:i A', strtotime($showtime['time'])); ?></td>
                                    <td><?php echo htmlspecialchars($showtime['hall']); ?></td>
                                    <td>$<?php echo number_format($showtime['price'], 2); ?></td>
                                    <td>
                                        <a href="/pages/select_seats.php?id=<?php echo $showtime['id']; ?>" class="btn btn-primary">Select Seats</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<p><a href="shows.php" class="btn">Back to Shows</a></p>

<?php
include __DIR__ . '/../templates/footer.php';
?> 