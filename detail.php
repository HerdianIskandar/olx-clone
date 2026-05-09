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

// echo "<pre>";
// print_r($images);
// echo "</pre>";
// die;
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
        
        /* Typography */
        h1, h2, h3, h4, h5, h6 {
            font-weight: 600;
            line-height: 1.2;
            margin-bottom: 1rem;
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
        
        /* Breadcrumb */
        .breadcrumb {
            background-color: transparent;
            padding: 1rem 0;
            margin-bottom: 0;
        }
        
        .breadcrumb-item {
            color: var(--gray-600);
        }
        
        .breadcrumb-item.active {
            color: var(--gray-500);
        }
        
        .breadcrumb-item a {
            color: var(--secondary-color);
            text-decoration: none;
            transition: var(--transition);
        }
        
        .breadcrumb-item a:hover {
            color: var(--primary-color);
        }
        
        /* Product Detail Container */
        .product-detail-container {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        /* Image Gallery */
        .image-gallery {
            position: relative;
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            background: var(--gray-100);
        }
        
        .main-image {
            width: 100%;
            height: 500px;
            object-fit: cover;
            border-radius: var(--border-radius-lg);
        }
        
        .image-thumbnails {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
            overflow-x: auto;
            padding: 0.5rem 0;
        }
        
        .thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: var(--border-radius);
            cursor: pointer;
            border: 2px solid transparent;
            transition: var(--transition);
        }
        
        .thumbnail:hover,
        .thumbnail.active {
            border-color: var(--secondary-color);
            transform: scale(1.05);
        }
        
        /* Product Info */
        .product-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .product-price {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--accent-color);
            margin-bottom: 1.5rem;
        }
        
        .product-meta {
            display: flex;
            gap: 2rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray-600);
        }
        
        .meta-item i {
            color: var(--secondary-color);
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .btn-action {
            padding: 1rem 2rem;
            border-radius: var(--border-radius);
            font-weight: 600;
            font-size: 1rem;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        
        .btn-primary-action {
            background: var(--secondary-color);
            color: var(--white);
        }
        
        .btn-primary-action:hover {
            background: #008f7f;
            transform: translateY(-2px);
            box-shadow: var(--shadow);
            color: var(--white);
            text-decoration: none;
        }
        
        .btn-secondary-action {
            background: transparent;
            color: var(--secondary-color);
            border: 2px solid var(--secondary-color);
        }
        
        .btn-secondary-action:hover {
            background: var(--secondary-color);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        /* Seller Info */
        .seller-card {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .seller-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: var(--white);
            font-size: 2.5rem;
            font-weight: 700;
        }
        
        .seller-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .seller-badge {
            background: var(--gray-100);
            color: var(--gray-600);
            padding: 0.25rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            display: inline-block;
        }
        
        .contact-buttons {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }
        
        .btn-contact {
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        
        .btn-whatsapp {
            background: #25D366;
            color: var(--white);
        }
        
        .btn-whatsapp:hover {
            background: #128C7E;
            transform: translateY(-2px);
            box-shadow: var(--shadow);
            color: var(--white);
            text-decoration: none;
        }
        
        .btn-phone {
            background: var(--primary-color);
            color: var(--white);
        }
        
        .btn-phone:hover {
            background: #001a1d;
            transform: translateY(-2px);
            box-shadow: var(--shadow);
            color: var(--white);
            text-decoration: none;
        }
        
        /* Product Details */
        .details-section {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--gray-200);
        }
        
        .description-text {
            color: var(--gray-700);
            line-height: 1.8;
            margin-bottom: 1.5rem;
        }
        
        .read-more-btn {
            color: var(--secondary-color);
            font-weight: 600;
            padding: 0;
            margin-top: 0.5rem;
            text-decoration: none;
            transition: var(--transition);
        }
        
        .read-more-btn:hover {
            color: var(--primary-color);
            text-decoration: underline;
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--gray-100);
        }
        
        .detail-label {
            font-weight: 600;
            color: var(--gray-600);
        }
        
        .detail-value {
            color: var(--gray-800);
            text-align: right;
        }
        
        /* Related Products */
        .related-section {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        
        .related-card {
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            transition: var(--transition);
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }
        
        .related-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow);
            border-color: var(--secondary-color);
            text-decoration: none;
            color: inherit;
        }
        
        .related-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        
        .related-content {
            padding: 1rem;
        }
        
        .related-title {
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 0.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .related-price {
            color: var(--accent-color);
            font-weight: 700;
            font-size: 1.125rem;
        }
        
        .related-location {
            color: var(--gray-600);
            font-size: 0.875rem;
            margin-top: 0.25rem;
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
        
        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .social-link {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            text-decoration: none;
            transition: var(--transition);
        }
        
        .social-link:hover {
            background: var(--secondary-color);
            color: var(--white);
            text-decoration: none;
            transform: translateY(-2px);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .product-detail-container {
                padding: 1.5rem;
            }
            
            .main-image {
                height: 300px;
            }
            
            .product-title {
                font-size: 1.5rem;
            }
            
            .product-price {
                font-size: 2rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn-action {
                width: 100%;
                justify-content: center;
            }
            
            .details-grid {
                grid-template-columns: 1fr;
            }
            
            .related-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 576px) {
            .product-detail-container {
                padding: 1rem;
            }
            
            .main-image {
                height: 250px;
            }
            
            .product-title {
                font-size: 1.25rem;
            }
            
            .product-price {
                font-size: 1.75rem;
            }
            
            .product-meta {
                flex-direction: column;
                gap: 1rem;
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
                        <a class="nav-link" href="#"><i class="bi bi-heart"></i> Favorit</a>
                    </li>
                    <li class="nav-item">
                        <?php if ($current_user): ?>
                            <a class="nav-link" href="#"><i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($current_user['name']); ?></a>
                        <?php else: ?>
                            <a class="nav-link" href="login.php"><i class="bi bi-person-circle"></i> Masuk</a>
                        <?php endif; ?>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="bi bi-globe"></i> ID</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

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
