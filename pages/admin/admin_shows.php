<?php
require_once __DIR__ . '/../../includes/db_config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!hasAdminAccess()) {
    header('Location: /index.php');
    exit();
}

include __DIR__ . '/../../templates/admin_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $title = $_POST['title'];
                $description = $_POST['description'];
                $duration = (int)$_POST['duration'];
                $language = $_POST['language'];
                $genre = $_POST['genre'];
                $age_rating = $_POST['age_rating'];
                
                $image_url = '';
                if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == 0) {
                    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                    $filename = $_FILES['image_file']['name'];
                    $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    
                    if (in_array($file_ext, $allowed)) {
                        $new_filename = 'show_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $file_ext;
                        $upload_path = '../../uploads/shows/' . $new_filename;
                        
                        if (move_uploaded_file($_FILES['image_file']['tmp_name'], $upload_path)) {
                            $image_url = '/uploads/shows/' . $new_filename;
                        }
                    }
                } elseif (!empty($_POST['image_url'])) {
                    $image_url = $_POST['image_url'];
                }
                
                if (addShow($title, $description, $duration, $language, $genre, $age_rating, $image_url)) {
                    $_SESSION['message'] = "Show added successfully!";
        $_SESSION['message_type'] = "success";
    } else {
                    $_SESSION['message'] = "Error adding show!";
                    $_SESSION['message_type'] = "danger";
                }
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $title = $_POST['title'];
                $description = $_POST['description'];
                $duration = (int)$_POST['duration'];
                $language = $_POST['language'];
                $genre = $_POST['genre'];
                $age_rating = $_POST['age_rating'];
                
                $current_show = getShow($id);
                $image_url = $current_show['image_url'];
                
                if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == 0) {
                    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                    $filename = $_FILES['image_file']['name'];
                    $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    
                    if (in_array($file_ext, $allowed)) {
                        $new_filename = 'show_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $file_ext;
                        $upload_path = '../../uploads/shows/' . $new_filename;
                        
                        if (move_uploaded_file($_FILES['image_file']['tmp_name'], $upload_path)) {
                            if (!empty($current_show['image_url']) && strpos($current_show['image_url'], '/uploads/shows/') === 0) {
                                $old_file = '../../' . $current_show['image_url'];
                                if (file_exists($old_file)) {
                                    unlink($old_file);
                                }
                            }
                            
                            $image_url = '/uploads/shows/' . $new_filename;
                        }
                    }
                } elseif (!empty($_POST['image_url'])) {
                    $image_url = $_POST['image_url'];
                }
                
                if (updateShow($id, $title, $description, $duration, $language, $genre, $age_rating, $image_url)) {
            $_SESSION['message'] = "Show updated successfully!";
            $_SESSION['message_type'] = "success";
        } else {
                    $_SESSION['message'] = "Error updating show!";
                    $_SESSION['message_type'] = "danger";
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                if (deleteShow($id)) {
                    $_SESSION['message'] = "Show deleted successfully!";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Error deleting show!";
                    $_SESSION['message_type'] = "danger";
                }
                break;
                
            case 'add_showtime':
                $show_id = (int)$_POST['show_id'];
                $date = $_POST['date'];
                $time = $_POST['time'];
                $hall = $_POST['hall'];
                $price = (float)$_POST['price'];
                
                if (addShowtime($show_id, $date, $time, $hall, $price)) {
                    $_SESSION['message'] = "Showtime added successfully!";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Error adding showtime!";
                    $_SESSION['message_type'] = "danger";
                }
                break;
                
            case 'delete_showtime':
                $showtime_id = (int)$_POST['showtime_id'];
                
                $sql = "SELECT COUNT(*) as booking_count FROM bookings WHERE showtime_id = $showtime_id";
                $result = mysqli_query($conn, $sql);
                $row = mysqli_fetch_assoc($result);
                
                if ($row['booking_count'] > 0) {
                    $_SESSION['message'] = "Cannot delete showtime: There are existing bookings for this showtime.";
                    $_SESSION['message_type'] = "danger";
    } else {
                    $sql = "DELETE FROM seats WHERE showtime_id = $showtime_id";
                    mysqli_query($conn, $sql);
                    
                    $sql = "DELETE FROM showtimes WHERE id = $showtime_id";
                    if (mysqli_query($conn, $sql)) {
                        $_SESSION['message'] = "Showtime deleted successfully!";
            $_SESSION['message_type'] = "success";
        } else {
                        $_SESSION['message'] = "Error deleting showtime!";
                        $_SESSION['message_type'] = "danger";
                    }
                }
                break;
        }
        
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

$shows = getAllShows();
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">Manage Shows</h4>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addShowModal">
            <i class="fas fa-plus"></i> Add New Show
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
            <thead>
                <tr>
                        <th>ID</th>
                        <th>Image</th>
                    <th>Title</th>
                    <th>Genre</th>
                    <th>Duration</th>
                    <th>Language</th>
                    <th>Age Rating</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                    <?php if (empty($shows)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No shows found</td>
                        </tr>
                    <?php else: ?>
                <?php foreach ($shows as $show): ?>
                    <tr>
                                <td><?php echo $show['id']; ?></td>
                                <td>
                                    <?php if (!empty($show['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($show['image_url']); ?>" alt="<?php echo htmlspecialchars($show['title']); ?>" width="50" height="50" class="rounded">
                                    <?php else: ?>
                                        <div class="bg-light d-flex align-items-center justify-content-center rounded" style="width: 50px; height: 50px;">
                                            <i class="fas fa-film text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($show['title']); ?></td>
                                <td><?php echo htmlspecialchars($show['genre']); ?></td>
                        <td><?php echo $show['duration']; ?> mins</td>
                                <td><?php echo htmlspecialchars($show['language']); ?></td>
                                <td><?php echo htmlspecialchars($show['age_rating']); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-primary me-1" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editShowModal"
                                                data-id="<?php echo $show['id']; ?>"
                                                data-title="<?php echo htmlspecialchars($show['title']); ?>"
                                                data-description="<?php echo htmlspecialchars($show['description']); ?>"
                                                data-duration="<?php echo $show['duration']; ?>"
                                                data-language="<?php echo htmlspecialchars($show['language']); ?>"
                                                data-genre="<?php echo htmlspecialchars($show['genre']); ?>"
                                                data-age-rating="<?php echo htmlspecialchars($show['age_rating']); ?>"
                                                data-image-url="<?php echo htmlspecialchars($show['image_url']); ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger me-1" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteShowModal"
                                                data-id="<?php echo $show['id']; ?>"
                                                data-title="<?php echo htmlspecialchars($show['title']); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-success me-1"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#manageShowtimesModal"
                                                data-id="<?php echo $show['id']; ?>"
                                                data-title="<?php echo htmlspecialchars($show['title']); ?>">
                                            <i class="fas fa-clock"></i> Showtimes
                                        </button>
                                        <a href="/pages/show_details.php?id=<?php echo $show['id']; ?>" class="btn btn-sm btn-info" target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                    <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addShowModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Show</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Show Image</label>
                            <div class="input-group mb-2">
                                <input type="file" class="form-control" id="image_file" name="image_file" accept="image/*">
                            </div>
                            <div class="input-group">
                                <span class="input-group-text">or URL</span>
                                <input type="url" class="form-control" id="image_url" name="image_url" placeholder="Image URL (optional)">
                            </div>
                            <div class="form-text">Upload an image or provide an image URL</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="duration" class="form-label">Duration (minutes)</label>
                            <input type="number" class="form-control" id="duration" name="duration" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="language" class="form-label">Language</label>
                            <input type="text" class="form-control" id="language" name="language" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="genre" class="form-label">Genre</label>
                            <input type="text" class="form-control" id="genre" name="genre" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="age_rating" class="form-label">Age Rating</label>
                            <select class="form-select" id="age_rating" name="age_rating" required>
                                <option value="G">G</option>
                                <option value="PG">PG</option>
                                <option value="PG-13">PG-13</option>
                                <option value="R">R</option>
                                <option value="NC-17">NC-17</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Show</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editShowModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Show</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="edit_title" name="title" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Show Image</label>
                            <div class="input-group mb-2">
                                <input type="file" class="form-control" id="edit_image_file" name="image_file" accept="image/*">
                            </div>
                            <div class="input-group">
                                <span class="input-group-text">or URL</span>
                                <input type="url" class="form-control" id="edit_image_url" name="image_url" placeholder="Image URL (optional)">
                            </div>
                            <div class="form-text">Leave empty to keep current image</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_duration" class="form-label">Duration (minutes)</label>
                            <input type="number" class="form-control" id="edit_duration" name="duration" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_language" class="form-label">Language</label>
                            <input type="text" class="form-control" id="edit_language" name="language" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_genre" class="form-label">Genre</label>
                            <input type="text" class="form-control" id="edit_genre" name="genre" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_age_rating" class="form-label">Age Rating</label>
                            <select class="form-select" id="edit_age_rating" name="age_rating" required>
                                <option value="G">G</option>
                                <option value="PG">PG</option>
                                <option value="PG-13">PG-13</option>
                                <option value="R">R</option>
                                <option value="NC-17">NC-17</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Show</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteShowModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the show "<span id="delete_title"></span>"?</p>
                <p class="text-danger">This will also delete all showtimes, seats, and booking data associated with this show!</p>
            </div>
            <div class="modal-footer">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" id="delete_id" name="id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="manageShowtimesModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manage Showtimes for "<span id="showtime_show_title"></span>"</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0">Add New Showtime</h6>
                            </div>
                            <div class="card-body">
                                <form id="addShowtimeForm" method="POST" action="">
                                    <input type="hidden" name="action" value="add_showtime">
                                    <input type="hidden" id="showtime_show_id" name="show_id">
                                    
                                    <div class="mb-3">
                                        <label for="showtime_date" class="form-label">Date</label>
                                        <input type="date" class="form-control" id="showtime_date" name="date" required min="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="showtime_time" class="form-label">Time</label>
                                        <input type="time" class="form-control" id="showtime_time" name="time" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="showtime_hall" class="form-label">Hall</label>
                                        <select class="form-select" id="showtime_hall" name="hall" required>
                                            <option value="Hall A">Hall A</option>
                                            <option value="Hall B">Hall B</option>
                                            <option value="Hall C">Hall C</option>
                                            <option value="IMAX">IMAX</option>
                                            <option value="VIP">VIP</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="showtime_price" class="form-label">Base Price ($)</label>
                                        <input type="number" class="form-control" id="showtime_price" name="price" step="0.01" min="0" required>
                                        <div class="form-text">
                                            Premium seats: 150% of base price<br>
                                            Regular seats: 100% of base price<br>
                                            Economy seats: 80% of base price
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary w-100">Add Showtime</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-dark text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Current Showtimes</h6>
                                    <button type="button" class="btn btn-sm btn-outline-light" id="refreshShowtimes">
                                        <i class="fas fa-sync-alt"></i> Refresh
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="showtimesListContainer">
                                    <div class="text-center p-5">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mt-2">Loading showtimes...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteShowtimeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete Showtime</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this showtime?</p>
                <p class="text-danger">This will delete all seats for this showtime. This action cannot be undone.</p>
                <p>If there are any existing bookings for this showtime, the deletion will fail.</p>
            </div>
            <div class="modal-footer">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="delete_showtime">
                    <input type="hidden" id="delete_showtime_id" name="showtime_id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editShowModal = document.getElementById('editShowModal');
    if (editShowModal) {
        editShowModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            
            document.getElementById('edit_id').value = button.getAttribute('data-id');
            document.getElementById('edit_title').value = button.getAttribute('data-title');
            document.getElementById('edit_description').value = button.getAttribute('data-description');
            document.getElementById('edit_duration').value = button.getAttribute('data-duration');
            document.getElementById('edit_language').value = button.getAttribute('data-language');
            document.getElementById('edit_genre').value = button.getAttribute('data-genre');
            document.getElementById('edit_age_rating').value = button.getAttribute('data-age-rating');
            document.getElementById('edit_image_url').value = button.getAttribute('data-image-url');
        });
    }
    const deleteShowModal = document.getElementById('deleteShowModal');
    if (deleteShowModal) {
        deleteShowModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            
            document.getElementById('delete_id').value = button.getAttribute('data-id');
            document.getElementById('delete_title').textContent = button.getAttribute('data-title');
        });
    }
    
    const manageShowtimesModal = document.getElementById('manageShowtimesModal');
    if (manageShowtimesModal) {
        manageShowtimesModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const showId = button.getAttribute('data-id');
            const showTitle = button.getAttribute('data-title');
            
            document.getElementById('showtime_show_id').value = showId;
            document.getElementById('showtime_show_title').textContent = showTitle;
            
            document.getElementById('showtime_date').min = new Date().toISOString().split('T')[0];
            
            loadShowtimes(showId);
        });
    }
    
    const refreshButton = document.getElementById('refreshShowtimes');
    if (refreshButton) {
        refreshButton.addEventListener('click', function() {
            const showId = document.getElementById('showtime_show_id').value;
            loadShowtimes(showId);
        });
    }
    
    function loadShowtimes(showId) {
        const container = document.getElementById('showtimesListContainer');
        
        container.innerHTML = `
            <div class="text-center p-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading showtimes...</p>
            </div>
        `;
        
        fetch(`/pages/admin/get_showtimes.php?show_id=${showId}`)
            .then(response => response.json())
            .then(data => {
                if (data.length === 0) {
                    container.innerHTML = `
                        <div class="alert alert-info text-center" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            No showtimes found for this show. Add your first showtime using the form.
                        </div>
                    `;
                    return;
                }
                
                const showtimesByDate = {};
                data.forEach(showtime => {
                    const date = showtime.date;
                    if (!showtimesByDate[date]) {
                        showtimesByDate[date] = [];
                    }
                    showtimesByDate[date].push(showtime);
                });
                
                let html = '';
                
                const sortedDates = Object.keys(showtimesByDate).sort();
                
                sortedDates.forEach(date => {
                    const formattedDate = new Date(date).toLocaleDateString('en-US', { 
                        weekday: 'long', 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric' 
                    });
                    
                    html += `
                        <div class="mb-4">
                            <h6 class="bg-light p-2 rounded">${formattedDate}</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Time</th>
                                            <th>Hall</th>
                                            <th>Base Price</th>
                                            <th>Bookings</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                    `;
                    
                    showtimesByDate[date].sort((a, b) => a.time.localeCompare(b.time));
                    
                    showtimesByDate[date].forEach(showtime => {
                        const formattedTime = new Date(`2000-01-01T${showtime.time}`).toLocaleTimeString('en-US', {
                            hour: 'numeric',
                            minute: '2-digit',
                            hour12: true
                        });
                        
                        const showtimeDate = new Date(`${date}T${showtime.time}`);
                        const now = new Date();
                        const isPast = showtimeDate < now;
                        
                        html += `
                            <tr${isPast ? ' class="text-muted"' : ''}>
                                <td>${formattedTime}</td>
                                <td>${showtime.hall}</td>
                                <td>$${parseFloat(showtime.price).toFixed(2)}</td>
                                <td>
                                    ${showtime.booking_count}
                                    ${showtime.booking_count > 0 
                                        ? `<span class="badge bg-success ms-1">
                                            <i class="fas fa-ticket-alt"></i>
                                          </span>` 
                                        : ''
                                    }
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-danger${showtime.booking_count > 0 ? ' disabled' : ''}"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteShowtimeModal"
                                        data-id="${showtime.id}"
                                        ${showtime.booking_count > 0 ? 'disabled title="Cannot delete: has bookings"' : ''}>
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    
                    html += `
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
                });
                
                container.innerHTML = html;
                const deleteButtons = container.querySelectorAll('[data-bs-target="#deleteShowtimeModal"]');
                deleteButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        document.getElementById('delete_showtime_id').value = this.getAttribute('data-id');
                    });
                });
            })
            .catch(error => {
                console.error('Error loading showtimes:', error);
                container.innerHTML = `
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error loading showtimes. Please try again.
                    </div>
                `;
            });
    }
});
</script>

<?php include __DIR__ . '/../../templates/admin_footer.php'; ?> 