<?php
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

include __DIR__ . '/../templates/header.php';

$shows = getAllShows();

$genres = [];
$languages = [];
foreach ($shows as $show) {
    if (!empty($show['genre']) && !in_array($show['genre'], $genres)) {
        $genres[] = $show['genre'];
    }
    if (!empty($show['language']) && !in_array($show['language'], $languages)) {
        $languages[] = $show['language'];
    }
}

$sort = $_GET['sort'] ?? 'default';

switch ($sort) {
    case 'title_asc':
        usort($shows, function($a, $b) {
            return strcmp($a['title'], $b['title']);
        });
        break;
    case 'title_desc':
        usort($shows, function($a, $b) {
            return strcmp($b['title'], $a['title']);
        });
        break;
    case 'duration_asc':
        usort($shows, function($a, $b) {
            return $a['duration'] - $b['duration'];
        });
        break;
    case 'duration_desc':
        usort($shows, function($a, $b) {
            return $b['duration'] - $a['duration'];
        });
        break;

}
?>

<div class="bg-light">
    <div class="container py-5">
        <h1 class="display-5 fw-bold">Current Shows</h1>
        <p class="lead">Explore our exciting lineup of shows and live performances</p>
    </div>
</div>

<div class="container py-5">
    <div class="filter-bar mb-4 p-4">
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-search text-primary"></i>
                    </span>
                    <input type="text" id="searchInput" class="form-control" placeholder="Search shows by title, description, genre...">
                    <button class="btn btn-primary" type="button" id="searchBtn">
                        <i class="fas fa-search me-2"></i>Search
                    </button>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <select id="genreFilter" class="form-select">
                    <option value="">All Genres</option>
                    <?php foreach ($genres as $genre): ?>
                        <option value="<?php echo htmlspecialchars($genre); ?>"><?php echo htmlspecialchars($genre); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <select id="languageFilter" class="form-select">
                    <option value="">All Languages</option>
                    <?php foreach ($languages as $language): ?>
                        <option value="<?php echo htmlspecialchars($language); ?>"><?php echo htmlspecialchars($language); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-6">
                <div class="d-flex align-items-center">
                    <span class="me-2 text-muted">Sort by:</span>
                    <div class="dropdown sort-options">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php 
                            switch($sort) {
                                case 'title_asc': echo 'Title (A-Z)'; break;
                                case 'title_desc': echo 'Title (Z-A)'; break;
                                case 'duration_asc': echo 'Duration (Shortest)'; break;
                                case 'duration_desc': echo 'Duration (Longest)'; break;
                                default: echo 'Default'; break;
                            }
                            ?>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="sortDropdown">
                            <li><a class="dropdown-item <?php echo $sort == 'default' ? 'active' : ''; ?>" href="?sort=default">Default</a></li>
                            <li><a class="dropdown-item <?php echo $sort == 'title_asc' ? 'active' : ''; ?>" href="?sort=title_asc">Title (A-Z)</a></li>
                            <li><a class="dropdown-item <?php echo $sort == 'title_desc' ? 'active' : ''; ?>" href="?sort=title_desc">Title (Z-A)</a></li>
                            <li><a class="dropdown-item <?php echo $sort == 'duration_asc' ? 'active' : ''; ?>" href="?sort=duration_asc">Duration (Shortest)</a></li>
                            <li><a class="dropdown-item <?php echo $sort == 'duration_desc' ? 'active' : ''; ?>" href="?sort=duration_desc">Duration (Longest)</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-6 text-md-end">
                <div id="resultCount" class="mt-2 text-muted">
                    Showing <?php echo count($shows); ?> shows
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4" id="showsContainer">
        <?php if (empty($shows)): ?>
            <div class="col-12 text-center py-5">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <h5 class="mb-2">No shows available at this time</h5>
                    <p class="mb-0">Please check back later for upcoming performances</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($shows as $show): ?>
                <div class="col-lg-4 col-md-6 show-item" 
                     data-genre="<?php echo htmlspecialchars($show['genre']); ?>"
                     data-language="<?php echo htmlspecialchars($show['language']); ?>">
                    <div class="card h-100">
                        <?php if (!empty($show['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($show['image_url']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($show['title']); ?>">
                        <?php else: ?>
                            <div class="card-img-top d-flex align-items-center justify-content-center">
                                <i class="fas fa-theater-masks fa-3x text-primary"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($show['title']); ?></h5>
                            <p class="card-text"><?php echo substr(htmlspecialchars($show['description']), 0, 120); ?>...</p>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge badge-primary">
                                    <i class="fas fa-film me-1"></i> <?php echo htmlspecialchars($show['genre']); ?>
                                </span>
                                <span class="badge badge-secondary">
                                    <i class="fas fa-clock me-1"></i> <?php echo $show['duration']; ?> mins
                                </span>
                                <span class="badge badge-secondary">
                                    <i class="fas fa-globe me-1"></i> <?php echo htmlspecialchars($show['language']); ?>
                                </span>
                                <?php if (!empty($show['age_rating'])): ?>
                                <span class="badge badge-secondary">
                                    <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($show['age_rating']); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="/pages/show_details.php?id=<?php echo $show['id']; ?>" class="btn btn-primary w-100">
                                <i class="fas fa-info-circle me-2"></i> View Details
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    const genreFilter = document.getElementById('genreFilter');
    const languageFilter = document.getElementById('languageFilter');
    const showItems = document.querySelectorAll('.show-item');
    const resultCount = document.getElementById('resultCount');
    
    function filterShows() {
        const searchTerm = searchInput.value.trim().toLowerCase();
        const selectedGenre = genreFilter.value;
        const selectedLanguage = languageFilter.value;
        
        let visibleCount = 0;
        
        showItems.forEach(show => {
            const title = show.querySelector('.card-title').textContent.toLowerCase();
            const description = show.querySelector('.card-text').textContent.toLowerCase();
            const genre = show.getAttribute('data-genre').toLowerCase();
            const language = show.getAttribute('data-language').toLowerCase();
            
            const matchesSearch = searchTerm === '' || 
                                  title.includes(searchTerm) || 
                                  description.includes(searchTerm) ||
                                  genre.includes(searchTerm);
            const matchesGenre = selectedGenre === '' || genre === selectedGenre.toLowerCase();
            const matchesLanguage = selectedLanguage === '' || language === selectedLanguage.toLowerCase();
            
            if (matchesSearch && matchesGenre && matchesLanguage) {
                show.style.display = 'block';
                visibleCount++;
            } else {
                show.style.display = 'none';
            }
        });
        resultCount.textContent = `Showing ${visibleCount} show${visibleCount !== 1 ? 's' : ''}`;
    }
    searchBtn.addEventListener('click', filterShows);
    searchInput.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            filterShows();
        }
    });
    
    genreFilter.addEventListener('change', filterShows);
    languageFilter.addEventListener('change', filterShows);
    
    const initialCount = showItems.length;
    resultCount.textContent = `Showing ${initialCount} show${initialCount !== 1 ? 's' : ''}`;
});
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?> 