<?php
require_once 'includes/db_config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

include 'templates/header.php';


$shows = getAllShows();
$featuredShows = array_slice($shows, 0, 3); 
?>


<section class="hero">
    <div class="container">
        <h1>Experience the Magic of Theatre</h1>
        <p>Book your tickets for the latest shows and enjoy an unforgettable experience.</p>
        <a href="/pages/shows.php" class="btn btn-primary btn-lg">Browse Shows</a>
    </div>
</section>

<div class="container">
    
    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Featured Shows</h2>
            <a href="/pages/shows.php" class="btn btn-outline-primary custom-btn">View All Shows</a>
        </div>
        
        <div class="row">
            <?php foreach ($featuredShows as $show): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <?php if (!empty($show['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($show['image_url']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($show['title']); ?>">
                        <?php else: ?>
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                <i class="fas fa-theater-masks fa-4x text-muted"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($show['title']); ?></h5>
                            <p class="card-text"><?php echo substr(htmlspecialchars($show['description']), 0, 100); ?>...</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i> <?php echo $show['duration']; ?> mins
                                </small>
                                <small class="text-muted">
                                    <i class="fas fa-film1 me-1"></i> <?php echo htmlspecialchars($show['genre']); ?>
                                </small>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-top-0">
                            <a href="/pages/show_details.php?id=<?php echo $show['id']; ?>" class="btn btn-primary btn-sm">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <section class="mb-5">
        <h2 class="text-center mb-4">How It Works</h2>
        <div class="row text-center">
            <div class="col-md-4 mb-4">
                <div class="p-4 bg-light rounded-circle d-inline-flex justify-content-center align-items-center mb-3" style="width: 100px; height: 100px;">
                    <i class="fas fa-search fa-3x text-primary"></i>
                </div>
                <h4>Browse Shows</h4>
                <p>Explore our collection of exciting shows and performances.</p>
            </div>
            <div class="col-md-4 mb-4">
                <div class="p-4 bg-light rounded-circle d-inline-flex justify-content-center align-items-center mb-3" style="width: 100px; height: 100px;">
                    <i class="fas fa-chair fa-3x text-primary"></i>
                </div>
                <h4>Select Seats</h4>
                <p>Choose your preferred seats from our interactive seating plan.</p>
            </div>
            <div class="col-md-4 mb-4">
                <div class="p-4 bg-light rounded-circle d-inline-flex justify-content-center align-items-center mb-3" style="width: 100px; height: 100px;">
                    <i class="fas fa-ticket-alt fa-3x text-primary"></i>
                </div>
                <h4>Book Tickets</h4>
                <p>Secure your tickets online and get ready for the show!</p>
            </div>
        </div>
    </section>
    <section class="text-center mb-5 p-5 bg-light rounded">
        <h2>Ready to Experience Live Theatre?</h2>
        <p class="lead mb-4">Register now to book tickets and enjoy the magic of live performances.</p>
        <div class="d-flex justify-content-center">
            <?php if (!isLoggedIn()): ?>
                <a href="/pages/register.php" class="btn btn-primary me-2">Register Now</a>
                <a href="/pages/login.php" class="btn btn-outline-primary">Login</a>
            <?php else: ?>
                <a href="/pages/shows.php" class="btn btn-primary">Browse Shows</a>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php include 'templates/footer.php'; ?> 