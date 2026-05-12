<?php
require_once 'config.php';

// Check if user is already logged in
if (isLoggedIn()) {
    // If there's a redirect parameter, go there instead of index.php
    $redirect_url = isset($_GET['redirect']) ? sanitize($_GET['redirect']) : 'index.php';
    redirect($redirect_url);
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    // Validation
    $errors = [];
    
    if (empty($email)) {
        $errors[] = "Email harus diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid!";
    }
    
    if (empty($password)) {
        $errors[] = "Password harus diisi!";
    }
    
    // Authenticate user if no validation errors
    if (empty($errors)) {
        try {
            // Get user by email using PDO
            $user = getUserByEmail($pdo, $email);
            
            if ($user) {
                // Verify password using PDO helper function
                if (verifyPassword($password, $user['password'])) {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    
                    // Set success message
                    setFlashMessage('success', 'Login berhasil! Selamat datang kembali.');
                    
                    // Redirect to original page or home
                    $redirect_url = isset($_POST['redirect']) ? sanitize($_POST['redirect']) : 'index.php';
                    redirect($redirect_url);
                } else {
                    $errors[] = "Password salah!";
                }
            } else {
                $errors[] = "Email tidak ditemukan!";
            }
        } catch (Exception $e) {
            error_log("Login Error: " . $e->getMessage());
            $errors[] = "Terjadi kesalahan. Silakan coba lagi.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - OLX Clone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <!-- Navbar -->
    <?php include 'navbar.php'; ?>

    <!-- Login Container -->
    <div class="login-container">
        <div class="login-card">
            <!-- Left Side - Login Form -->
            <div class="login-form-side">
                <div class="login-header">
                    <h1 class="login-title">Selamat Datang Kembali</h1>
                    <p class="login-subtitle">Masuk ke akun OLX Clone Anda</p>
                </div>

                <?php 
                $successMessage = getFlashMessage('success');
                if ($successMessage): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i>
                        <?php echo $successMessage; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($errors) && !empty($errors)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <?php if (isset($_GET['redirect'])): ?>
                        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_GET['redirect']); ?>">
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-group">
                            <i class="bi bi-envelope input-icon"></i>
                            <input type="email" class="form-control input-with-icon" id="email" name="email" 
                                   placeholder="nama@email.com" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <i class="bi bi-lock input-icon"></i>
                            <input type="password" class="form-control input-with-icon" id="password" name="password" 
                                   placeholder="Masukkan password" required>
                        </div>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                        <label class="form-check-label" for="remember">
                            Ingat saya
                        </label>
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="bi bi-box-arrow-in-right"></i> Masuk
                    </button>
                </form>

                <div class="divider">
                    <span>atau masuk dengan</span>
                </div>

                <div class="social-login">
                    <a href="#" class="btn-social btn-google mb-3">
                        <i class="bi bi-google"></i> Google
                    </a>
                    <a href="#" class="btn-social btn-facebook">
                        <i class="bi bi-facebook"></i> Facebook
                    </a>
                </div>

                <div class="auth-links">
                    <p>Belum punya akun? <a href="register.php" class="auth-link">Daftar sekarang</a></p>
                    <p><a href="#" class="auth-link">Lupa password?</a></p>
                </div>
            </div>

            <!-- Right Side - Hero Section -->
            <div class="login-hero-side">
                <i class="bi bi-shop hero-icon"></i>
                <h2 class="hero-title">OLX Clone</h2>
                <p class="hero-description">
                    Platform jual beli online terpercaya di Indonesia
                </p>
                <ul class="hero-features">
                    <li>
                        <i class="bi bi-check-circle"></i>
                        <span>Jutaan produk tersedia</span>
                    </li>
                    <li>
                        <i class="bi bi-check-circle"></i>
                        <span>Transaksi aman dan terpercaya</span>
                    </li>
                    <li>
                        <i class="bi bi-check-circle"></i>
                        <span>Mudah dan cepat</span>
                    </li>
                    <li>
                        <i class="bi bi-check-circle"></i>
                        <span>Support 24/7</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Mohon isi semua field yang diperlukan');
                return false;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Format email tidak valid');
                return false;
            }
        });
        
        // Password visibility toggle (optional enhancement)
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const lockIcon = document.querySelector('.input-group .bi-lock');
            
            if (passwordInput && lockIcon) {
                lockIcon.style.cursor = 'pointer';
                lockIcon.addEventListener('click', function() {
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        this.classList.remove('bi-lock');
                        this.classList.add('bi-eye');
                    } else {
                        passwordInput.type = 'password';
                        this.classList.remove('bi-eye');
                        this.classList.add('bi-lock');
                    }
                });
            }
        });
    </script>
</body>
</html>
