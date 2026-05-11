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
    <style>
        :root {
            --primary-color: #002f34;
            --secondary-color: #00a896;
            --accent-color: #ff6b00;
            --light-bg: #f5f5f5;
            --white: #ffffff;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;
            --shadow-sm: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
            --shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
            --shadow-lg: 0 1rem 3rem rgba(0,0,0,0.175);
            --border-radius: 0.5rem;
            --border-radius-lg: 1rem;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--light-bg);
            color: var(--gray-800);
            line-height: 1.6;
            font-weight: 400;
        }
        
        /* Navbar */
        .navbar {
            background-color: var(--white);
            border-bottom: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1030;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.75rem;
            color: var(--primary-color) !important;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .navbar-brand i {
            font-size: 2rem;
            color: var(--secondary-color);
        }
        
        .navbar-nav .nav-link {
            color: var(--gray-700) !important;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .navbar-nav .nav-link:hover {
            color: var(--primary-color) !important;
            background-color: var(--gray-100);
        }
        
        .navbar-nav .nav-link.active {
            color: var(--secondary-color) !important;
            background-color: rgba(0, 168, 150, 0.1);
        }
        
        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--white);
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            font-size: 1.125rem;
            opacity: 0.9;
        }
        
        /* My Ads Container */
        .my-ads-container {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--gray-100);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            text-align: center;
            border: 2px solid transparent;
            transition: var(--transition);
        }
        
        .stat-card:hover {
            border-color: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--gray-600);
            font-weight: 500;
        }
        
        /* Ads Grid */
        .ads-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .ad-card {
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            transition: var(--transition);
            position: relative;
        }
        
        .ad-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow);
            border-color: var(--secondary-color);
        }
        
        .ad-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: var(--gray-100);
        }
        
        .ad-content {
            padding: 1.5rem;
        }
        
        .ad-title {
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 0.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .ad-price {
            color: var(--accent-color);
            font-weight: 700;
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }
        
        .ad-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            color: var(--gray-600);
        }
        
        .ad-category {
            background: var(--gray-100);
            color: var(--gray-700);
            padding: 0.25rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .ad-date {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .ad-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-action {
            flex: 1;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            font-size: 0.875rem;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        
        .btn-edit {
            background: var(--secondary-color);
            color: var(--white);
        }
        
        .btn-edit:hover {
            background: #008f7f;
            color: var(--white);
            transform: translateY(-1px);
        }
        
        .btn-delete {
            background: var(--gray-500);
            color: var(--white);
        }
        
        .btn-delete:hover {
            background: var(--gray-600);
            color: var(--white);
            transform: translateY(-1px);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray-600);
        }
        
        .empty-icon {
            font-size: 4rem;
            color: var(--gray-400);
            margin-bottom: 1rem;
        }
        
        .empty-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--gray-700);
        }
        
        .empty-description {
            margin-bottom: 2rem;
        }
        
        .btn-primary-action {
            background: var(--secondary-color);
            color: var(--white);
            padding: 0.75rem 2rem;
            border-radius: var(--border-radius);
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
        }
        
        .btn-primary-action:hover {
            background: #008f7f;
            color: var(--white);
            transform: translateY(-2px);
        }
        
        /* Footer */
        .footer {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 3rem 0 1.5rem;
            margin-top: 4rem;
        }
        
        .footer-brand {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--white);
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
        }
        
        .footer-links li {
            margin-bottom: 0.5rem;
        }
        
        .footer-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: var(--transition);
        }
        
        .footer-links a:hover {
            color: var(--white);
            text-decoration: none;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .ads-grid {
                grid-template-columns: 1fr;
            }
            
            .my-ads-container {
                padding: 1.5rem;
            }
        }
        
        @media (max-width: 576px) {
            .page-header {
                padding: 2rem 0;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .my-ads-container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-shop"></i> OLX Clone
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="bi bi-search"></i> Cari</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="post-ads.php"><i class="bi bi-plus-circle"></i> Pasang Iklan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="my-ads.php"><i class="bi bi-collection"></i> Iklan Saya</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="bi bi-heart"></i> Favorit</a>
                    </li>
                    <li class="nav-item">
                        <div class="dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($current_user['name']); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="my-ads.php"><i class="bi bi-collection"></i> Iklan Saya</a></li>
                                <li><a class="dropdown-item" href="#"><i class="bi bi-heart"></i> Favorit</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="bi bi-globe"></i> ID</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

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
