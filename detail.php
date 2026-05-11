<?php
require_once 'config.php';

// Get ad ID from URL
$ad_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get current user if logged in
$current_user = getCurrentUser();

// Get ad details using PDO
try {
    $ad = getAdById($pdo, $ad_id);
    
    if (!$ad) {
        // Redirect to home if ad not found
        redirect("index.php");
    }
} catch (Exception $e) {
    error_log("Error fetching ad details: " . $e->getMessage());
    redirect("index.php");
}

// Get seller information using PDO
try {
    $seller = getUserById($pdo, $ad['user_id']);
} catch (Exception $e) {
    error_log("Error fetching seller info: " . $e->getMessage());
    $seller = null;
}

// Get ad images using PDO
try {
    $images = getAdImages($pdo, $ad_id);
} catch (Exception $e) {
    error_log("Error fetching ad images: " . $e->getMessage());
    $images = [];
}
// Get related ads (same category, different ad) using PDO
try {
    $related_ads = getRelatedAds($pdo, $ad['category_id'], $ad_id, 4);
} catch (Exception $e) {
    error_log("Error fetching related ads: " . $e->getMessage());
    $related_ads = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($ad['title']); ?> - OLX Clone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/detail.css">
</head>
<body>
    <!-- Navbar -->
    <?php include 'navbar.php'; ?>

    <!-- Breadcrumb -->
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
                <li class="breadcrumb-item"><a href="index.php?category=<?php echo $ad['category_id']; ?>"><?php echo htmlspecialchars($ad['category_name']); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($ad['title']); ?></li>
            </ol>
        </nav>
    </div>

    <!-- Product Detail -->
    <div class="container">
        <div class="row">
            <!-- Left Column - Images -->
            <div class="col-lg-8">
                <div class="product-detail-container">
                    <div class="image-gallery">
                        <img id="mainImage" src="<?php echo !empty($images[0]['image_path']) ? htmlspecialchars($images[0]['image_path']) : 'https://placehold.co/600x500'; ?>" 
                             alt="<?php echo htmlspecialchars($ad['title']); ?>" class="main-image">
                        
                        <?php if (count($images) > 1): ?>
                            <div class="image-thumbnails">
                                <?php foreach ($images as $image): ?>
                                    <img src="<?php echo htmlspecialchars($image['image_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($ad['title']); ?>" 
                                         class="thumbnail"
                                         onclick="changeMainImage(this.src)">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Product Details -->
                <div class="details-section">
                    <h2 class="section-title">Deskripsi Produk</h2>
                    <div class="description-text" id="productDescription">
                        <?php 
                        if (!empty($ad['description'])) {
                            $description = htmlspecialchars($ad['description']);
                            $short_description = substr($description, 0, 200);
                            if (strlen($description) > 200) {
                                echo '<span id="shortDesc">' . nl2br($short_description) . '...</span>';
                                echo '<span id="fullDesc" style="display: none;">' . nl2br($description) . '</span>';
                                echo '<button type="button" class="btn btn-link read-more-btn" id="readMoreBtn">Lihat Selengkapnya</button>';
                            } else {
                                echo nl2br($description);
                            }
                        } else {
                            echo 'Tidak ada deskripsi tersedia.';
                        }
                        ?>
                    </div>
                    
                    <h3 class="section-title">Detail Produk</h3>
                    <div class="details-grid">
                        <div class="detail-item">
                            <span class="detail-label">Kategori</span>
                            <span class="detail-value"><?php echo htmlspecialchars($ad['category_name']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Lokasi</span>
                            <span class="detail-value"><?php echo !empty($ad['location']) ? htmlspecialchars($ad['location']) : 'Tidak disebutkan'; ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Diposting</span>
                            <span class="detail-value"><?php echo date('d M Y, H:i', strtotime($ad['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Info & Actions -->
            <div class="col-lg-4">
                <!-- Product Info -->
                <div class="product-detail-container">
                    <h1 class="product-title"><?php echo htmlspecialchars($ad['title']); ?></h1>
                    <div class="product-price"><?php echo formatPrice($ad['price']); ?></div>
                    
                    <div class="product-meta">
                        <div class="meta-item">
                            <i class="bi bi-geo-alt"></i>
                            <span><?php echo !empty($ad['location']) ? htmlspecialchars($ad['location']) : 'Lokasi tidak diketahui'; ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="bi bi-clock"></i>
                            <span><?php echo timeAgo($ad['created_at']); ?></span>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="#" class="btn-action btn-primary-action">
                            <i class="bi bi-chat-dots"></i> Chat Penjual
                        </a>
                        <a href="#" class="btn-action btn-secondary-action">
                            <i class="bi bi-heart"></i> Simpan
                        </a>
                    </div>
                </div>

                <!-- Seller Info -->
                <div class="seller-card">
                    <div class="seller-avatar">
                        <?php echo strtoupper(substr($ad['user_name'], 0, 2)); ?>
                    </div>
                    <h3 class="seller-name"><?php echo htmlspecialchars($ad['user_name']); ?></h3>
                    <span class="seller-badge">Penjual Terpercaya</span>
                    
                    <div class="contact-buttons">
                        <?php if ($current_user): ?>
                            <?php if (!empty($seller['whatsapp'])): ?>
                                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $seller['whatsapp']); ?>?text=Halo,%20saya%20tertarik%20dengan%20produk%20anda:%20<?php echo urlencode($ad['title']); ?>" 
                                       class="btn-contact btn-whatsapp" target="_blank">
                                    <i class="bi bi-whatsapp"></i> WhatsApp
                                </a>
                            <?php else: ?>
                                <a href="https://wa.me/6281234567890?text=Halo,%20saya%20tertarik%20dengan%20produk%20anda:%20<?php echo urlencode($ad['title']); ?>" 
                                       class="btn-contact btn-whatsapp" target="_blank">
                                    <i class="bi bi-whatsapp"></i> WhatsApp
                                </a>
                            <?php endif; ?>
                             
                            <a href="tel:<?php echo !empty($seller['whatsapp']) ? '6281234567890' : preg_replace('/[^0-9]/', '', $seller['whatsapp']); ?>" class="btn-contact btn-phone">
                                <i class="bi bi-telephone"></i> Telepon
                            </a>
                        <?php else: ?>
                            <a href="login.php?redirect=<?php echo urlencode('detail.php?id=' . $ad_id); ?>" class="btn-contact btn-whatsapp">
                                <i class="bi bi-whatsapp"></i> WhatsApp (Login Dulu)
                            </a>
                            <a href="login.php?redirect=<?php echo urlencode('detail.php?id=' . $ad_id); ?>" class="btn-contact btn-phone">
                                <i class="bi bi-telephone"></i> Telepon (Login Dulu)
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Products -->
        <?php if (!empty($related_ads)): ?>
            <div class="related-section">
                <h2 class="section-title">Produk Terkait</h2>
                <div class="related-grid">
                    <?php foreach ($related_ads as $related_ad): ?>
                        <a href="detail.php?id=<?php echo $related_ad['id']; ?>" class="related-card">
                            <img src="<?php echo !empty($related_ad['image']) ? htmlspecialchars($related_ad['image']) : 'https://placehold.co/300x200'; ?>" 
                                 alt="<?php echo htmlspecialchars($related_ad['title']); ?>" class="related-image">
                            <div class="related-content">
                                <h4 class="related-title"><?php echo htmlspecialchars($related_ad['title']); ?></h4>
                                <div class="related-price"><?php echo formatPrice($related_ad['price']); ?></div>
                                <div class="related-location">
                                    <i class="bi bi-geo-alt"></i> 
                                    <?php echo !empty($related_ad['location']) ? htmlspecialchars($related_ad['location']) : 'Lokasi tidak diketahui'; ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function changeMainImage(src) {
            document.getElementById('mainImage').src = src;
            
            // Update active thumbnail
            const thumbnails = document.querySelectorAll('.thumbnail');
            thumbnails.forEach(thumb => {
                thumb.classList.remove('active');
                if (thumb.src === src) {
                    thumb.classList.add('active');
                }
            });
        }
        
        // Read more/less functionality
        function toggleDescription() {
            const shortDesc = document.getElementById('shortDesc');
            const fullDesc = document.getElementById('fullDesc');
            const readMoreBtn = document.getElementById('readMoreBtn');
            
            if (shortDesc && fullDesc && readMoreBtn) {
                if (fullDesc.style.display === 'none') {
                    // Show full description
                    shortDesc.style.display = 'none';
                    fullDesc.style.display = 'inline';
                    readMoreBtn.textContent = 'Lihat Lebih Sedikit';
                } else {
                    // Show short description
                    shortDesc.style.display = 'inline';
                    fullDesc.style.display = 'none';
                    readMoreBtn.textContent = 'Lihat Selengkapnya';
                }
            }
        }
        
        // Set first thumbnail as active on page load
        document.addEventListener('DOMContentLoaded', function() {
            const firstThumbnail = document.querySelector('.thumbnail');
            if (firstThumbnail) {
                firstThumbnail.classList.add('active');
            }
            
            // Add click event to read more button
            const readMoreBtn = document.getElementById('readMoreBtn');
            if (readMoreBtn) {
                readMoreBtn.addEventListener('click', toggleDescription);
            }
        });
    </script>
</body>
</html>
