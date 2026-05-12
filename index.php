<?php
require_once 'config.php';

// Get categories using PDO
try {
    $categories = getAllCategories($pdo);
} catch (Exception $e) {
    error_log("Error fetching categories: " . $e->getMessage());
    $categories = [];
}

// Get featured ads (latest 8 ads) using PDO
try {
    $featured_ads = getFeaturedAds($pdo, 8);
} catch (Exception $e) {
    error_log("Error fetching featured ads: " . $e->getMessage());
    $featured_ads = [];
}

// Get recent ads (latest 4 ads after featured) using PDO
try {
    $recent_ads = getRecentAds($pdo, 4);
} catch (Exception $e) {
    error_log("Error fetching recent ads: " . $e->getMessage());
    $recent_ads = [];
}

// Get current user if logged in
$current_user = getCurrentUser();

// Get all locations for dropdown
try {
    $locations = getAllLocations($pdo);
} catch (Exception $e) {
    error_log("Error fetching locations: " . $e->getMessage());
    $locations = [];
}

// Check if category is selected
$selected_category = null;
$category_ads = [];
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Check if search is performed
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$search_location = isset($_GET['location']) ? trim($_GET['location']) : '';
$search_results = [];
$is_searching = !empty($search_query) || !empty($search_location);

if ($category_id > 0) {
    try {
        $selected_category = getCategoryById($pdo, $category_id);
        if ($selected_category) {
            $category_ads = getAdsByCategory($pdo, $category_id, 12);
        }
    } catch (Exception $e) {
        error_log("Error fetching category data: " . $e->getMessage());
        $selected_category = null;
        $category_ads = [];
    }
} elseif ($is_searching) {
    try {
        $search_results = searchAds($pdo, $search_query, $search_location, 12);
    } catch (Exception $e) {
        error_log("Error searching ads: " . $e->getMessage());
        $search_results = [];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php 
        if ($is_searching) {
            echo 'Hasil Pencarian';
            if (!empty($search_query)) echo ' - ' . htmlspecialchars($search_query);
            if (!empty($search_location)) echo ' di ' . htmlspecialchars($search_location);
            echo ' - OLX Clone';
        } elseif ($selected_category) {
            echo htmlspecialchars($selected_category['name']) . ' - OLX Clone';
        } else {
            echo 'OLX Clone - Jual Beli Online Terpercaya';
        }
    ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/index.css">
</head>
<body>
    <!-- Navbar -->
    <?php include 'navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1 class="hero-title">Jual Beli Online Terpercaya</h1>
            <p class="hero-subtitle">Temukan berbagai produk berkualitas dengan harga terbaik di seluruh Indonesia</p>
        </div>
    </section>

    <!-- Search Container -->
    <div class="search-container">
        <div class="search-input-group">
            <input type="text" class="search-input" placeholder="Cari apa saja di OLX Clone..." value="<?php echo htmlspecialchars($search_query); ?>">
            <select class="search-input" id="location_select">
                <option value="">Semua Lokasi</option>
                <?php if (!empty($locations)): ?>
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?php echo htmlspecialchars($loc['location']); ?>" 
                                <?php echo ($search_location === $loc['location']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($loc['location']); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <button class="search-btn" type="button">
                <i class="bi bi-search"></i> Cari
            </button>
        </div>
    </div>

    <!-- Categories Section -->
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title">Kategori Populer</h2>
                <p class="section-subtitle">Jelajahi berbagai kategori produk favorit</p>
            </div>
            <a href="#" class="view-all-btn">
                Lihat Semua <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        
        <div class="categories-grid">
            <?php if (!empty($categories)): ?>
                <?php 
                // Limit display to first 6 categories
                $limited_categories = array_slice($categories, 0, 6);
                foreach ($limited_categories as $category): ?>
                    <a href="index.php?category=<?php echo $category['id']; ?>" class="category-card text-decoration-none" data-category-id="<?php echo $category['id']; ?>">
                        <div class="category-icon">
                            <?php if (!empty($category['icon'])): ?>
                                <i class="<?php echo htmlspecialchars($category['icon']); ?>"></i>
                            <?php else: ?>
                                <i class="bi bi-grid"></i>
                            <?php endif; ?>
                        </div>
                        <h6 class="category-name"><?php echo htmlspecialchars($category['name']); ?></h6>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Default categories if no data in database -->
                <a href="#" class="category-card text-decoration-none">
                    <div class="category-icon">
                        <i class="bi bi-car-front"></i>
                    </div>
                    <h6 class="category-name">Mobil</h6>
                </a>
                <a href="#" class="category-card text-decoration-none">
                    <div class="category-icon">
                        <i class="bi bi-bicycle"></i>
                    </div>
                    <h6 class="category-name">Motor</h6>
                </a>
                <a href="#" class="category-card text-decoration-none">
                    <div class="category-icon">
                        <i class="bi bi-house"></i>
                    </div>
                    <h6 class="category-name">Properti</h6>
                </a>
                <a href="#" class="category-card text-decoration-none">
                    <div class="category-icon">
                        <i class="bi bi-phone"></i>
                    </div>
                    <h6 class="category-name">Elektronik</h6>
                </a>
                <a href="#" class="category-card text-decoration-none">
                    <div class="category-icon">
                        <i class="bi bi-laptop"></i>
                    </div>
                    <h6 class="category-name">Komputer</h6>
                </a>
                <a href="#" class="category-card text-decoration-none">
                    <div class="category-icon">
                        <i class="bi bi-bag"></i>
                    </div>
                    <h6 class="category-name">Fashion</h6>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Featured Ads Section -->
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title">
                    <?php 
                    if ($is_searching) {
                        echo 'Hasil Pencarian';
                        if (!empty($search_query)) echo ': ' . htmlspecialchars($search_query);
                        if (!empty($search_location)) echo ' di ' . htmlspecialchars($search_location);
                    } elseif ($selected_category) {
                        echo htmlspecialchars($selected_category['name']);
                    } else {
                        echo 'Iklan Terpopuler';
                    }
                    ?>
                </h2>
                <p class="section-subtitle">
                    <?php 
                    if ($is_searching) {
                        echo 'Menampilkan ' . count($search_results) . ' hasil pencarian';
                    } elseif ($selected_category) {
                        echo 'Produk dalam kategori ' . htmlspecialchars($selected_category['name']);
                    } else {
                        echo 'Produk paling diminati minggu ini';
                    }
                    ?>
                </p>
            </div>
            <?php if (!$selected_category && !$is_searching): ?>
            <a href="#" class="view-all-btn">
                Lihat Semua <i class="bi bi-arrow-right"></i>
            </a>
            <?php endif; ?>
        </div>
        
        <div class="ads-grid" id="featured-ads">
            <?php 
            $display_ads = [];
            if ($is_searching) {
                $display_ads = $search_results;
            } elseif ($selected_category) {
                $display_ads = $category_ads;
            } else {
                $display_ads = $featured_ads;
            }
            
            if (!empty($display_ads)): 
            ?>
                <?php foreach ($display_ads as $ad): ?>
                    <a href="detail.php?id=<?php echo $ad['id']; ?>" class="ad-card text-decoration-none">
                        <div class="ad-image-container">
                            <?php if (!empty($ad['image'])): ?>
                                <img src="<?php echo htmlspecialchars($ad['image']); ?>" class="ad-image" alt="<?php echo htmlspecialchars($ad['title']); ?>">
                            <?php else: ?>
                                <img src="https://placehold.co/300x200" class="ad-image" alt="<?php echo htmlspecialchars($ad['title']); ?>">
                            <?php endif; ?>
                            <span class="ad-badge">
                                <?php 
                                if ($is_searching) {
                                    echo 'Hasil';
                                } elseif ($selected_category) {
                                    echo 'Kategori';
                                } else {
                                    echo 'Populer';
                                }
                                ?>
                            </span>
                        </div>
                        <div class="ad-content">
                            <h5 class="ad-title"><?php echo htmlspecialchars($ad['title']); ?></h5>
                            <p class="ad-price"><?php echo formatPrice($ad['price']); ?></p>
                            <p class="ad-location">
                                <i class="bi bi-geo-alt"></i> 
                                <?php echo !empty($ad['location']) ? htmlspecialchars($ad['location']) : 'Lokasi tidak diketahui'; ?>
                            </p>
                            <div class="ad-meta">
                                <?php echo timeAgo($ad['created_at']); ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <?php if ($is_searching): ?>
                    <div class="col-12 text-center py-5">
                        <i class="bi bi-search" style="font-size: 3rem; color: var(--gray-400);"></i>
                        <h4 class="mt-3 text-muted">Tidak ada hasil untuk pencarian ini</h4>
                        <p class="text-muted">Coba dengan kata kunci atau lokasi yang berbeda</p>
                    </div>
                <?php elseif ($selected_category): ?>
                    <div class="col-12 text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 3rem; color: var(--gray-400);"></i>
                        <h4 class="mt-3 text-muted">Belum ada iklan dalam kategori ini</h4>
                        <p class="text-muted">Menjadi yang pertama memasang iklan di kategori <?php echo htmlspecialchars($selected_category['name']); ?></p>
                    </div>
                <?php else: ?>
                    <!-- Sample ads if no data in database -->
                    <a href="#" class="ad-card text-decoration-none">
                        <div class="ad-image-container">
                            <img src="https://placehold.co/300x200" class="ad-image" alt="Product Image">
                            <span class="ad-badge">Populer</span>
                        </div>
                        <div class="ad-content">
                            <h5 class="ad-title">Honda Vario 125 2021</h5>
                            <p class="ad-price">Rp 15.500.000</p>
                            <p class="ad-location"><i class="bi bi-geo-alt"></i> Jakarta Selatan</p>
                            <div class="ad-meta">2 hari yang lalu</div>
                        </div>
                    </a>
                    <a href="#" class="ad-card text-decoration-none">
                        <div class="ad-image-container">
                            <img src="https://placehold.co/300x200" class="ad-image" alt="Product Image">
                            <span class="ad-badge">Populer</span>
                        </div>
                        <div class="ad-content">
                            <h5 class="ad-title">iPhone 13 Pro Max</h5>
                            <p class="ad-price">Rp 12.000.000</p>
                            <p class="ad-location"><i class="bi bi-geo-alt"></i> Bandung</p>
                            <div class="ad-meta">1 minggu yang lalu</div>
                        </div>
                    </a>
                    <a href="#" class="ad-card text-decoration-none">
                        <div class="ad-image-container">
                            <img src="https://placehold.co/300x200" class="ad-image" alt="Product Image">
                            <span class="ad-badge">Populer</span>
                        </div>
                        <div class="ad-content">
                            <h5 class="ad-title">MacBook Air M1 2020</h5>
                            <p class="ad-price">Rp 8.500.000</p>
                            <p class="ad-location"><i class="bi bi-geo-alt"></i> Surabaya</p>
                            <div class="ad-meta">3 hari yang lalu</div>
                        </div>
                    </a>
                    <a href="#" class="ad-card text-decoration-none">
                        <div class="ad-image-container">
                            <img src="https://placehold.co/300x200" class="ad-image" alt="Product Image">
                            <span class="ad-badge">Populer</span>
                        </div>
                        <div class="ad-content">
                            <h5 class="ad-title">Toyota Avanza 2019</h5>
                            <p class="ad-price">Rp 145.000.000</p>
                            <p class="ad-location"><i class="bi bi-geo-alt"></i> Depok</p>
                            <div class="ad-meta">5 hari yang lalu</div>
                        </div>
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$selected_category && !$is_searching): ?>
    <!-- Recent Ads Section -->
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title">Iklan Terbaru</h2>
                <p class="section-subtitle">Produk yang baru ditambahkan</p>
            </div>
            <a href="#" class="view-all-btn">
                Lihat Semua <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        
        <div class="ads-grid" id="recent-ads">
            <?php if (!empty($recent_ads)): ?>
                <?php foreach ($recent_ads as $ad): ?>
                    <a href="detail.php?id=<?php echo $ad['id']; ?>" class="ad-card text-decoration-none">
                        <div class="ad-image-container">
                            <?php if (!empty($ad['image'])): ?>
                                <img src="<?php echo htmlspecialchars($ad['image']); ?>" class="ad-image" alt="<?php echo htmlspecialchars($ad['title']); ?>">
                            <?php else: ?>
                                <img src="https://placehold.co/300x200" class="ad-image" alt="<?php echo htmlspecialchars($ad['title']); ?>">
                            <?php endif; ?>
                            <span class="ad-badge">Baru</span>
                        </div>
                        <div class="ad-content">
                            <h5 class="ad-title"><?php echo htmlspecialchars($ad['title']); ?></h5>
                            <p class="ad-price"><?php echo formatPrice($ad['price']); ?></p>
                            <p class="ad-location">
                                <i class="bi bi-geo-alt"></i> 
                                <?php echo !empty($ad['location']) ? htmlspecialchars($ad['location']) : 'Lokasi tidak diketahui'; ?>
                            </p>
                            <div class="ad-meta">
                                <?php echo timeAgo($ad['created_at']); ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Sample recent ads if no data in database -->
                <a href="#" class="ad-card text-decoration-none">
                    <div class="ad-image-container">
                        <img src="https://placehold.co/300x200" class="ad-image" alt="Product Image">
                        <span class="ad-badge">Baru</span>
                    </div>
                    <div class="ad-content">
                        <h5 class="ad-title">Samsung Galaxy S21</h5>
                        <p class="ad-price">Rp 6.500.000</p>
                        <p class="ad-location"><i class="bi bi-geo-alt"></i> Tangerang</p>
                        <div class="ad-meta">Baru saja</div>
                    </div>
                </a>
                <a href="#" class="ad-card text-decoration-none">
                    <div class="ad-image-container">
                        <img src="https://placehold.co/300x200" class="ad-image" alt="Product Image">
                        <span class="ad-badge">Baru</span>
                    </div>
                    <div class="ad-content">
                        <h5 class="ad-title">PlayStation 5</h5>
                        <p class="ad-price">Rp 7.200.000</p>
                        <p class="ad-location"><i class="bi bi-geo-alt"></i> Bekasi</p>
                        <div class="ad-meta">1 jam yang lalu</div>
                    </div>
                </a>
                <a href="#" class="ad-card text-decoration-none">
                    <div class="ad-image-container">
                        <img src="https://placehold.co/300x200" class="ad-image" alt="Product Image">
                        <span class="ad-badge">Baru</span>
                    </div>
                    <div class="ad-content">
                        <h5 class="ad-title">IKEA Sofa 3 Seater</h5>
                        <p class="ad-price">Rp 2.800.000</p>
                        <p class="ad-location"><i class="bi bi-geo-alt"></i> Jakarta Pusat</p>
                        <div class="ad-meta">2 jam yang lalu</div>
                    </div>
                </a>
                <a href="#" class="ad-card text-decoration-none">
                    <div class="ad-image-container">
                        <img src="https://placehold.co/300x200" class="ad-image" alt="Product Image">
                        <span class="ad-badge">Baru</span>
                    </div>
                    <div class="ad-content">
                        <h5 class="ad-title">Yamaha NMAX 2022</h5>
                        <p class="ad-price">Rp 28.500.000</p>
                        <p class="ad-location"><i class="bi bi-geo-alt"></i> Bogor</p>
                        <div class="ad-meta">3 jam yang lalu</div>
                    </div>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Load More Button -->
    <div class="load-more-container">
        <button class="load-more-btn">
            <i class="bi bi-arrow-clockwise"></i> Muat Lebih Banyak
        </button>
    </div>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <!-- Floating Action Button -->
    <div class="floating-btn">
        <i class="bi bi-plus-lg fs-4"></i>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Category card click handler
            const categoryCards = document.querySelectorAll('.category-card');
            categoryCards.forEach(card => {
                card.addEventListener('click', function(e) {
                    e.preventDefault();
                    const categoryId = this.getAttribute('data-category-id');
                    if (categoryId) {
                        window.location.href = `index.php?category=${categoryId}`;
                    }
                });
            });

            // Search functionality
            const searchInput = document.querySelector('input[placeholder*="Cari apa saja"]');
            const locationSelect = document.getElementById('location_select');
            const searchButton = document.querySelector('.search-btn');
            
            searchButton.addEventListener('click', function() {
                const searchTerm = searchInput.value.trim();
                const location = locationSelect.value.trim();
                
                // Build URL parameters
                const params = new URLSearchParams();
                if (searchTerm) params.append('q', searchTerm);
                if (location) params.append('location', location);
                
                // Redirect to index.php with search parameters
                const queryString = params.toString();
                window.location.href = `index.php${queryString ? '?' + queryString : ''}`;
            });

            // Allow Enter key for search
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    searchButton.click();
                }
            });
            
            // Handle location change
            locationSelect.addEventListener('change', function() {
                // Auto-search when location changes (optional)
                // searchButton.click();
            });

            // Floating button click handler
            const floatingBtn = document.querySelector('.floating-btn');
            floatingBtn.addEventListener('click', function() {
                // Redirect to post ad page
                window.location.href = 'post-ads.php';
            });

            // Load more functionality
            const loadMoreBtn = document.querySelector('.load-more-btn');
            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', function() {
                    console.log('Load more ads');
                    // TODO: Implement load more functionality
                });
            }
        });
    </script>
</body>
</html>
