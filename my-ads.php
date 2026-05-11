<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect("login.php?redirect=" . urlencode('my-ads.php'));
}

// Get current user
$current_user = getCurrentUser();

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $ad_id = (int)$_GET['id'];
    
    if (deleteAd($pdo, $ad_id, $current_user['id'])) {
        setFlashMessage('success', 'Iklan berhasil dihapus!');
    } else {
        setFlashMessage('error', 'Gagal menghapus iklan. Silakan coba lagi.');
    }
    
    redirect('my-ads.php');
}

// Get user's ads
try {
    $user_ads = getUserAds($pdo, $current_user['id']);
} catch (Exception $e) {
    error_log("Error fetching user ads: " . $e->getMessage());
    $user_ads = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iklan Saya - OLX Clone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/my-ads.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1 class="page-title">Iklan Saya</h1>
            <p class="page-subtitle">Kelola semua iklan yang Anda posting dengan mudah</p>
        </div>
    </div>

    <!-- My Ads Content -->
    <div class="container">
        <div class="my-ads-container">
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($user_ads); ?></div>
                    <div class="stat-label">Total Iklan</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php 
                        $recent_ads = array_filter($user_ads, function($ad) {
                            return strtotime($ad['created_at']) > strtotime('-7 days');
                        });
                        echo count($recent_ads);
                        ?>
                    </div>
                    <div class="stat-label">7 Hari Terakhir</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php 
                        $total_value = array_sum(array_column($user_ads, 'price'));
                        echo formatPrice($total_value);
                        ?>
                    </div>
                    <div class="stat-label">Total Nilai</div>
                </div>
            </div>

            <!-- Ads Grid -->
            <?php if (!empty($user_ads)): ?>
                <div class="ads-grid">
                    <?php foreach ($user_ads as $ad): ?>
                        <div class="ad-card">
                            <img src="<?php echo !empty($ad['image']) ? htmlspecialchars($ad['image']) : 'https://placehold.co/300x200'; ?>" 
                                 alt="<?php echo htmlspecialchars($ad['title']); ?>" class="ad-image">
                            
                            <div class="ad-content">
                                <h3 class="ad-title"><?php echo htmlspecialchars($ad['title']); ?></h3>
                                <div class="ad-price"><?php echo formatPrice($ad['price']); ?></div>
                                
                                <div class="ad-meta">
                                    <span class="ad-category"><?php echo htmlspecialchars($ad['category_name']); ?></span>
                                    <span class="ad-date">
                                        <i class="bi bi-clock"></i> 
                                        <?php echo timeAgo($ad['created_at']); ?>
                                    </span>
                                </div>
                                
                                <div class="ad-actions">
                                    <a href="detail.php?id=<?php echo $ad['id']; ?>" class="btn-action btn-edit">
                                        <i class="bi bi-eye"></i> Lihat
                                    </a>
                                    <a href="edit-ads.php?id=<?php echo $ad['id']; ?>" class="btn-action btn-edit">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="my-ads.php?action=delete&id=<?php echo $ad['id']; ?>" 
                                       class="btn-action btn-delete"
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus iklan ini?')">
                                        <i class="bi bi-trash"></i> Hapus
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="bi bi-inbox"></i>
                    </div>
                    <h2 class="empty-title">Belum Ada Iklan</h2>
                    <p class="empty-description">
                        Anda belum memposting iklan apa pun. Mulai jual produk Anda sekarang!
                    </p>
                    <a href="post-ads.php" class="btn-primary-action">
                        <i class="bi bi-plus-circle"></i> Pasang Iklan Baru
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6">
                    <div class="footer-brand">
                        <i class="bi bi-shop"></i> OLX Clone
                    </div>
                    <p class="mb-3">Platform jual beli online terpercaya di Indonesia. Mudah, aman, dan cepat untuk transaksi Anda.</p>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="social-link"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="social-link"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="social-link"><i class="bi bi-youtube"></i></a>
                        <a href="#" class="social-link"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h6 class="text-uppercase mb-3 fw-bold">Tentang</h6>
                    <ul class="footer-links">
                        <li><a href="#">Tentang Kami</a></li>
                        <li><a href="#">Karir</a></li>
                        <li><a href="#">Press</a></li>
                        <li><a href="#">Blog</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h6 class="text-uppercase mb-3 fw-bold">Layanan</h6>
                    <ul class="footer-links">
                        <li><a href="#">Cara Jual</a></li>
                        <li><a href="#">Cara Beli</a></li>
                        <li><a href="#">Keamanan</a></li>
                        <li><a href="#">Bantuan</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h6 class="text-uppercase mb-3 fw-bold">Legal</h6>
                    <ul class="footer-links">
                        <li><a href="#">Syarat & Ketentuan</a></li>
                        <li><a href="#">Kebijakan Privasi</a></li>
                        <li><a href="#">Kebijakan Cookie</a></li>
                        <li><a href="#">Disclaimer</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h6 class="text-uppercase mb-3 fw-bold">Kontak</h6>
                    <ul class="footer-links">
                        <li><a href="#"><i class="bi bi-envelope"></i> support@olxclone.com</a></li>
                        <li><a href="#"><i class="bi bi-telephone"></i> 0800-123-456</a></li>
                        <li><a href="#"><i class="bi bi-whatsapp"></i> +62 812-3456-7890</a></li>
                        <li><a href="#"><i class="bi bi-geo-alt"></i> Jakarta, Indonesia</a></li>
                    </ul>
                </div>
            </div>
            <hr class="border-white my-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">&copy; 2024 OLX Clone. Hak Cipta Dilindungi.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">Dibuat dengan <i class="bi bi-heart-fill text-danger"></i> di Indonesia</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
