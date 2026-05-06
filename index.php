<?php
require_once 'config.php';

// Get categories
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_query);

// Get featured ads (latest 8 ads)
$featured_query = "SELECT a.*, u.name as user_name, c.name as category_name, 
                   (SELECT image_path FROM ad_images WHERE ad_id = a.id LIMIT 1) as image
                   FROM ads a 
                   JOIN users u ON a.user_id = u.id 
                   JOIN categories c ON a.category_id = c.id 
                   ORDER BY a.created_at DESC LIMIT 8";
$featured_result = $conn->query($featured_query);

// Get recent ads (latest 4 ads after featured)
$recent_query = "SELECT a.*, u.name as user_name, c.name as category_name, 
                 (SELECT image_path FROM ad_images WHERE ad_id = a.id LIMIT 1) as image
                 FROM ads a 
                 JOIN users u ON a.user_id = u.id 
                 JOIN categories c ON a.category_id = c.id 
                 ORDER BY a.created_at DESC LIMIT 4, 4";
$recent_result = $conn->query($recent_query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OLX Clone - Jual Beli Online Terpercaya</title>
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
        
        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            padding: 3rem 0;
            margin-bottom: 3rem;
        }
        
        .hero-title {
            color: var(--white);
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .hero-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.125rem;
            text-align: center;
            margin-bottom: 2rem;
        }
        
        /* Search Bar */
        .search-container {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            padding: 2rem;
            margin: -2rem auto 3rem;
            max-width: 900px;
            position: relative;
            z-index: 10;
        }
        
        .search-input-group {
            display: flex;
            gap: 1rem;
            align-items: stretch;
        }
        
        .search-input {
            border: 2px solid var(--gray-200);
            border-radius: var(--border-radius);
            padding: 1rem 1.25rem;
            font-size: 1rem;
            transition: var(--transition);
            background-color: var(--white);
            flex: 1;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 4px rgba(0, 168, 150, 0.1);
        }
        
        .search-btn {
            background-color: var(--secondary-color);
            color: var(--white);
            border: none;
            border-radius: var(--border-radius);
            padding: 1rem 2rem;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }
        
        .search-btn:hover {
            background-color: #008f7f;
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        /* Section Headers */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--gray-200);
        }
        
        .section-title {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 1.875rem;
            margin: 0;
        }
        
        .section-subtitle {
            color: var(--gray-600);
            font-size: 1rem;
            margin-top: 0.25rem;
        }
        
        .view-all-btn {
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
        }
        
        .view-all-btn:hover {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        /* Category Cards */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .category-card {
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: var(--border-radius-lg);
            padding: 2rem 1rem;
            text-align: center;
            transition: var(--transition);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .category-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--secondary-color), var(--accent-color));
            transform: translateY(-100%);
            transition: var(--transition);
        }
        
        .category-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
            border-color: var(--secondary-color);
        }
        
        .category-card:hover::before {
            transform: translateY(0);
        }
        
        .category-icon {
            font-size: 3rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
            transition: var(--transition);
        }
        
        .category-card:hover .category-icon {
            transform: scale(1.1);
            color: var(--primary-color);
        }
        
        .category-name {
            font-weight: 600;
            color: var(--gray-800);
            margin: 0;
            font-size: 1rem;
        }
        
        /* Ad Cards */
        .ads-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .ad-card {
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            transition: var(--transition);
            cursor: pointer;
            position: relative;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .ad-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
            border-color: var(--secondary-color);
        }
        
        .ad-image-container {
            position: relative;
            overflow: hidden;
            height: 200px;
        }
        
        .ad-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }
        
        .ad-card:hover .ad-image {
            transform: scale(1.05);
        }
        
        .ad-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--accent-color);
            color: var(--white);
            padding: 0.25rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .ad-content {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .ad-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--gray-800);
            margin: 0 0 0.75rem 0;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .ad-price {
            color: var(--accent-color);
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .ad-location {
            color: var(--gray-600);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .ad-meta {
            color: var(--gray-500);
            font-size: 0.75rem;
            margin-top: auto;
            padding-top: 0.75rem;
            border-top: 1px solid var(--gray-100);
        }
        
        /* Load More Button */
        .load-more-container {
            text-align: center;
            margin: 3rem 0;
        }
        
        .load-more-btn {
            background: transparent;
            color: var(--secondary-color);
            border: 2px solid var(--secondary-color);
            border-radius: var(--border-radius);
            padding: 1rem 2rem;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .load-more-btn:hover {
            background: var(--secondary-color);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
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
        
        /* Floating Action Button */
        .floating-btn {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: linear-gradient(135deg, var(--accent-color), #ff8f00);
            color: var(--white);
            border-radius: 50%;
            width: 64px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-lg);
            z-index: 1000;
            transition: var(--transition);
            cursor: pointer;
            border: none;
            font-size: 1.5rem;
        }
        
        .floating-btn:hover {
            transform: scale(1.1) rotate(90deg);
            box-shadow: 0 1rem 4rem rgba(255, 107, 0, 0.4);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }
            
            .search-input-group {
                flex-direction: column;
            }
            
            .search-btn {
                width: 100%;
                justify-content: center;
            }
            
            .categories-grid {
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
                gap: 1rem;
            }
            
            .ads-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 1.5rem;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
        
        @media (max-width: 576px) {
            .hero-section {
                padding: 2rem 0;
            }
            
            .hero-title {
                font-size: 1.5rem;
            }
            
            .search-container {
                padding: 1.5rem;
                margin: -1rem 1rem 2rem;
            }
            
            .ads-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-shop"></i> OLX Clone
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="bi bi-search"></i> Cari</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="bi bi-plus-circle"></i> Pasang Iklan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="bi bi-heart"></i> Favorit</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="bi bi-person-circle"></i> Akun Saya</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="bi bi-globe"></i> ID</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

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
            <input type="text" class="search-input" placeholder="Cari apa saja di OLX Clone...">
            <input type="text" class="search-input" placeholder="Lokasi">
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
            <?php if ($categories_result && $categories_result->num_rows > 0): ?>
                <?php while ($category = $categories_result->fetch_assoc()): ?>
                    <div class="category-card" data-category-id="<?php echo $category['id']; ?>">
                        <div class="category-icon">
                            <?php if (!empty($category['icon'])): ?>
                                <i class="<?php echo htmlspecialchars($category['icon']); ?>"></i>
                            <?php else: ?>
                                <i class="bi bi-grid"></i>
                            <?php endif; ?>
                        </div>
                        <h6 class="category-name"><?php echo htmlspecialchars($category['name']); ?></h6>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <!-- Default categories if no data in database -->
                <div class="category-card">
                    <div class="category-icon">
                        <i class="bi bi-car-front"></i>
                    </div>
                    <h6 class="category-name">Mobil</h6>
                </div>
                <div class="category-card">
                    <div class="category-icon">
                        <i class="bi bi-bicycle"></i>
                    </div>
                    <h6 class="category-name">Motor</h6>
                </div>
                <div class="category-card">
                    <div class="category-icon">
                        <i class="bi bi-house"></i>
                    </div>
                    <h6 class="category-name">Properti</h6>
                </div>
                <div class="category-card">
                    <div class="category-icon">
                        <i class="bi bi-phone"></i>
                    </div>
                    <h6 class="category-name">Elektronik</h6>
                </div>
                <div class="category-card">
                    <div class="category-icon">
                        <i class="bi bi-laptop"></i>
                    </div>
                    <h6 class="category-name">Komputer</h6>
                </div>
                <div class="category-card">
                    <div class="category-icon">
                        <i class="bi bi-bag"></i>
                    </div>
                    <h6 class="category-name">Fashion</h6>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Featured Ads Section -->
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title">Iklan Terpopuler</h2>
                <p class="section-subtitle">Produk paling diminati minggu ini</p>
            </div>
            <a href="#" class="view-all-btn">
                Lihat Semua <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        
        <div class="ads-grid" id="featured-ads">
            <?php if ($featured_result && $featured_result->num_rows > 0): ?>
                <?php while ($ad = $featured_result->fetch_assoc()): ?>
                    <div class="ad-card">
                        <div class="ad-image-container">
                            <?php if (!empty($ad['image'])): ?>
                                <img src="<?php echo htmlspecialchars($ad['image']); ?>" class="ad-image" alt="<?php echo htmlspecialchars($ad['title']); ?>">
                            <?php else: ?>
                                <img src="https://placehold.co/300x200" class="ad-image" alt="<?php echo htmlspecialchars($ad['title']); ?>">
                            <?php endif; ?>
                            <span class="ad-badge">Populer</span>
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
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <!-- Sample ads if no data in database -->
                <div class="ad-card">
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
                </div>
                <div class="ad-card">
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
                </div>
                <div class="ad-card">
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
                </div>
                <div class="ad-card">
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
                </div>
            <?php endif; ?>
        </div>
    </div>

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
            <?php if ($recent_result && $recent_result->num_rows > 0): ?>
                <?php while ($ad = $recent_result->fetch_assoc()): ?>
                    <div class="ad-card">
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
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <!-- Sample recent ads if no data in database -->
                <div class="ad-card">
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
                </div>
                <div class="ad-card">
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
                </div>
                <div class="ad-card">
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
                </div>
                <div class="ad-card">
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
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Load More Button -->
    <div class="load-more-container">
        <button class="load-more-btn">
            <i class="bi bi-arrow-clockwise"></i> Muat Lebih Banyak
        </button>
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

    <!-- Floating Action Button -->
    <div class="floating-btn">
        <i class="bi bi-plus-lg fs-4"></i>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Basic JavaScript functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Category card click handler
            const categoryCards = document.querySelectorAll('.category-card');
            categoryCards.forEach(card => {
                card.addEventListener('click', function() {
                    const categoryName = this.querySelector('h6').textContent;
                    console.log('Category clicked:', categoryName);
                    // TODO: Filter ads by category
                });
            });

            // Search functionality
            const searchInput = document.querySelector('.search-input');
            const searchButton = document.querySelector('.btn-primary-custom');
            
            searchButton.addEventListener('click', function() {
                const searchTerm = searchInput.value;
                const location = document.querySelector('input[placeholder="Lokasi"]').value;
                console.log('Searching for:', searchTerm, 'in', location);
                // TODO: Implement search functionality
            });

            // Floating button click handler
            const floatingBtn = document.querySelector('.floating-btn');
            floatingBtn.addEventListener('click', function() {
                console.log('Post new ad clicked');
                // TODO: Redirect to post ad page
            });

            // Load more functionality
            const loadMoreBtn = document.querySelector('.btn-outline-primary');
            loadMoreBtn.addEventListener('click', function() {
                console.log('Load more ads');
                // TODO: Load more ads from database
            });
        });
    </script>
</body>
</html>
