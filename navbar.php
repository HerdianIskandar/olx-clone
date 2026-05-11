<?php
// Get current user if logged in
$current_user = getCurrentUser();
?>

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
                    <?php if ($current_user): ?>
                        <div class="dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($current_user['name']); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="post-ads.php"><i class="bi bi-plus-circle"></i> Pasang Iklan</a></li>
                                <li><a class="dropdown-item" href="my-ads.php"><i class="bi bi-collection"></i> Iklan Saya</a></li>
                                <li><a class="dropdown-item" href="#"><i class="bi bi-heart"></i> Favorit</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i> Akun Saya
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="login.php?redirect=<?php echo urlencode('post-ads.php'); ?>"><i class="bi bi-plus-circle"></i> Pasang Iklan</a></li>
                                <li><a class="dropdown-item" href="login.php?redirect=<?php echo urlencode('my-ads.php'); ?>"><i class="bi bi-collection"></i> Iklan Saya</a></li>
                                <li><a class="dropdown-item" href="login.php?redirect=<?php echo urlencode('favorites.php'); ?>"><i class="bi bi-heart"></i> Favorit</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="login.php"><i class="bi bi-box-arrow-in-right"></i> Masuk</a></li>
                                <li><a class="dropdown-item" href="register.php"><i class="bi bi-person-plus"></i> Daftar</a></li>
                            </ul>
                        </div>
                    <?php endif; ?>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#"><i class="bi bi-globe"></i> ID</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
