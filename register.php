<?php
require_once 'config.php';

// Check if user is already logged in
if (isLoggedIn()) {
    redirect("index.php");
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Nama harus diisi!";
    } elseif (strlen($name) < 3) {
        $errors[] = "Nama minimal 3 karakter!";
    }
    
    if (empty($email)) {
        $errors[] = "Email harus diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid!";
    }
    
    if (empty($password)) {
        $errors[] = "Password harus diisi!";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password minimal 6 karakter!";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
        $errors[] = "Password harus mengandung huruf besar, huruf kecil, dan angka!";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Password tidak cocok!";
    }
    
    // Check if email already exists using PDO
    if (empty($errors)) {
        try {
            if (recordExists($pdo, 'users', 'email', $email)) {
                $errors[] = "Email sudah terdaftar!";
            }
        } catch (Exception $e) {
            $errors[] = "Terjadi kesalahan. Silakan coba lagi.";
        }
    }
    
    // Register user if no errors using PDO
    if (empty($errors)) {
        try {
            $userId = createUser($pdo, $name, $email, $password);
            
            if ($userId) {
                // Auto login after registration
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                
                // Set success message
                setFlashMessage('success', 'Registrasi berhasil! Selamat datang di OLX Clone.');
                
                redirect("index.php");
            } else {
                $errors[] = "Registrasi gagal! Silakan coba lagi.";
            }
        } catch (Exception $e) {
            error_log("Registration Error: " . $e->getMessage());
            $errors[] = "Terjadi kesalahan sistem. Silakan coba lagi nanti.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - OLX Clone</title>
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
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
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
        
        /* Register Container */
        .register-container {
            min-height: calc(100vh - 76px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        
        .register-card {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            max-width: 1200px;
            width: 100%;
            display: flex;
            min-height: 700px;
        }
        
        /* Register Left Side - Form */
        .register-form-side {
            flex: 1.2;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: auto;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .register-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .register-subtitle {
            color: var(--gray-600);
            font-size: 1rem;
        }
        
        /* Form Styles */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .form-control {
            border: 2px solid var(--gray-200);
            border-radius: var(--border-radius);
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: var(--transition);
            background-color: var(--white);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 4px rgba(0, 168, 150, 0.1);
        }
        
        .form-control.is-invalid {
            border-color: var(--accent-color);
        }
        
        .input-group {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            z-index: 5;
        }
        
        .input-with-icon {
            padding-left: 2.5rem;
        }
        
        /* Password Strength Indicator */
        .password-strength {
            margin-top: 0.5rem;
            height: 4px;
            background: var(--gray-200);
            border-radius: 2px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            transition: var(--transition);
            border-radius: 2px;
        }
        
        .strength-weak {
            background: var(--accent-color);
            width: 33%;
        }
        
        .strength-medium {
            background: #ffc107;
            width: 66%;
        }
        
        .strength-strong {
            background: #28a745;
            width: 100%;
        }
        
        /* Checkbox */
        .form-check {
            margin-bottom: 1.5rem;
        }
        
        .form-check-input {
            border-color: var(--gray-300);
        }
        
        .form-check-input:checked {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .form-check-label {
            color: var(--gray-700);
            font-weight: 500;
        }
        
        /* Buttons */
        .btn-register {
            background: var(--secondary-color);
            color: var(--white);
            border: none;
            border-radius: var(--border-radius);
            padding: 0.875rem 2rem;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-register:hover {
            background: #008f7f;
            transform: translateY(-2px);
            box-shadow: var(--shadow);
            color: var(--white);
        }
        
        .btn-social {
            background: var(--white);
            color: var(--gray-700);
            border: 2px solid var(--gray-200);
            border-radius: var(--border-radius);
            padding: 0.75rem 1rem;
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        
        .btn-social:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
            color: var(--gray-700);
            text-decoration: none;
        }
        
        .btn-google:hover {
            border-color: #ea4335;
            color: #ea4335;
        }
        
        .btn-facebook:hover {
            border-color: #1877f2;
            color: #1877f2;
        }
        
        /* Divider */
        .divider {
            text-align: center;
            margin: 1.5rem 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: var(--gray-200);
        }
        
        .divider span {
            background: var(--white);
            padding: 0 1rem;
            color: var(--gray-500);
            font-size: 0.875rem;
            position: relative;
        }
        
        /* Links */
        .auth-links {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .auth-link {
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .auth-link:hover {
            color: var(--primary-color);
            text-decoration: underline;
        }
        
        /* Register Right Side - Hero */
        .register-hero-side {
            flex: 0.8;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 3rem;
            color: var(--white);
            text-align: center;
        }
        
        .hero-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.9;
        }
        
        .hero-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .hero-description {
            font-size: 1.125rem;
            opacity: 0.9;
            margin-bottom: 2rem;
            max-width: 300px;
        }
        
        .hero-features {
            list-style: none;
            padding: 0;
            text-align: left;
            max-width: 300px;
        }
        
        .hero-features li {
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .hero-features i {
            color: rgba(255, 255, 255, 0.9);
        }
        
        /* Alert */
        .alert {
            border-radius: var(--border-radius);
            border: none;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .alert-danger {
            background: rgba(255, 107, 0, 0.1);
            color: var(--accent-color);
            border-left: 4px solid var(--accent-color);
        }
        
        .alert-success {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border-left: 4px solid #28a745;
        }
        
        .alert-danger ul {
            margin: 0;
            padding-left: 1.2rem;
        }
        
        /* Footer */
        .footer {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 2rem 0 1rem;
            text-align: center;
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        
        .footer-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: var(--transition);
        }
        
        .footer-links a:hover {
            color: var(--white);
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .register-card {
                flex-direction: column;
                max-width: 600px;
            }
            
            .register-hero-side {
                padding: 2rem;
                min-height: 300px;
            }
            
            .hero-title {
                font-size: 2rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 576px) {
            .register-form-side {
                padding: 2rem 1.5rem;
            }
            
            .register-hero-side {
                padding: 1.5rem;
            }
            
            .hero-title {
                font-size: 1.75rem;
            }
            
            .hero-description {
                font-size: 1rem;
            }
            
            .register-title {
                font-size: 1.5rem;
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
                        <a class="nav-link" href="#"><i class="bi bi-plus-circle"></i> Pasang Iklan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="bi bi-heart"></i> Favorit</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php"><i class="bi bi-person-circle"></i> Masuk</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="bi bi-globe"></i> ID</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Register Container -->
    <div class="register-container">
        <div class="register-card">
            <!-- Left Side - Register Form -->
            <div class="register-form-side">
                <div class="register-header">
                    <h1 class="register-title">Buat Akun Baru</h1>
                    <p class="register-subtitle">Bergabung dengan jutaan pengguna OLX Clone</p>
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
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name" class="form-label">Nama Lengkap *</label>
                            <div class="input-group">
                                <i class="bi bi-person input-icon"></i>
                                <input type="text" class="form-control input-with-icon" id="name" name="name" 
                                       placeholder="John Doe" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="email" class="form-label">Email *</label>
                            <div class="input-group">
                                <i class="bi bi-envelope input-icon"></i>
                                <input type="email" class="form-control input-with-icon" id="email" name="email" 
                                       placeholder="nama@email.com" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="password" class="form-label">Password *</label>
                            <div class="input-group">
                                <i class="bi bi-lock input-icon"></i>
                                <input type="password" class="form-control input-with-icon" id="password" name="password" 
                                       placeholder="Minimal 6 karakter" required>
                            </div>
                            <div class="password-strength" id="passwordStrength">
                                <div class="password-strength-bar" id="passwordStrengthBar"></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Konfirmasi Password *</label>
                            <div class="input-group">
                                <i class="bi bi-lock-fill input-icon"></i>
                                <input type="password" class="form-control input-with-icon" id="confirm_password" name="confirm_password" 
                                       placeholder="Ulangi password" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                        <label class="form-check-label" for="terms">
                            Saya setuju dengan <a href="#" class="auth-link">Syarat & Ketentuan</a> dan <a href="#" class="auth-link">Kebijakan Privasi</a>
                        </label>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="newsletter" name="newsletter">
                        <label class="form-check-label" for="newsletter">
                            Saya ingin menerima newsletter dan promosi dari OLX Clone
                        </label>
                    </div>

                    <button type="submit" class="btn-register">
                        <i class="bi bi-person-plus"></i> Daftar Sekarang
                    </button>
                </form>

                <div class="divider">
                    <span>atau daftar dengan</span>
                </div>

                <div class="social-register">
                    <a href="#" class="btn-social btn-google mb-3">
                        <i class="bi bi-google"></i> Google
                    </a>
                    <a href="#" class="btn-social btn-facebook">
                        <i class="bi bi-facebook"></i> Facebook
                    </a>
                </div>

                <div class="auth-links">
                    <p>Sudah punya akun? <a href="login.php" class="auth-link">Masuk di sini</a></p>
                </div>
            </div>

            <!-- Right Side - Hero Section -->
            <div class="register-hero-side">
                <i class="bi bi-person-plus hero-icon"></i>
                <h2 class="hero-title">Bergabung Sekarang</h2>
                <p class="hero-description">
                    Nikmati kemudahan jual beli online dengan ribuan produk tersedia
                </p>
                <ul class="hero-features">
                    <li>
                        <i class="bi bi-check-circle"></i>
                        <span>Gratis mendaftar</span>
                    </li>
                    <li>
                        <i class="bi bi-check-circle"></i>
                        <span>Posting iklan tanpa batas</span>
                    </li>
                    <li>
                        <i class="bi bi-check-circle"></i>
                        <span>Chat langsung dengan penjual</span>
                    </li>
                    <li>
                        <i class="bi bi-check-circle"></i>
                        <span>Keamanan transaksi terjamin</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-links">
                <a href="#">Tentang Kami</a>
                <a href="#">Bantuan</a>
                <a href="#">Kebijakan Privasi</a>
                <a href="#">Syarat & Ketentuan</a>
                <a href="#">Kontak</a>
            </div>
            <p class="mb-0">&copy; 2024 OLX Clone. Hak Cipta Dilindungi.</p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength checker
        function checkPasswordStrength(password) {
            let strength = 0;
            
            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            return strength;
        }
        
        // Update password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrengthBar');
            
            if (password.length === 0) {
                strengthBar.className = 'password-strength-bar';
                return;
            }
            
            const strength = checkPasswordStrength(password);
            
            strengthBar.className = 'password-strength-bar';
            
            if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
            } else if (strength <= 4) {
                strengthBar.classList.add('strength-medium');
            } else {
                strengthBar.classList.add('strength-strong');
            }
        });
        
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const terms = document.getElementById('terms').checked;
            
            if (!name || !email || !password || !confirmPassword) {
                e.preventDefault();
                alert('Mohon isi semua field yang diperlukan');
                return false;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Password tidak cocok!');
                return false;
            }
            
            if (!terms) {
                e.preventDefault();
                alert('Anda harus menyetujui syarat dan ketentuan');
                return false;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Format email tidak valid');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password minimal 6 karakter');
                return false;
            }
        });
        
        // Password visibility toggle (optional enhancement)
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInputs = document.querySelectorAll('input[type="password"]');
            
            passwordInputs.forEach(input => {
                const icon = input.previousElementSibling;
                if (icon && icon.classList.contains('bi-lock') || icon.classList.contains('bi-lock-fill')) {
                    icon.style.cursor = 'pointer';
                    icon.addEventListener('click', function() {
                        if (input.type === 'password') {
                            input.type = 'text';
                            if (this.classList.contains('bi-lock')) {
                                this.classList.remove('bi-lock');
                                this.classList.add('bi-eye');
                            } else {
                                this.classList.remove('bi-lock-fill');
                                this.classList.add('bi-eye-fill');
                            }
                        } else {
                            input.type = 'password';
                            if (this.classList.contains('bi-eye')) {
                                this.classList.remove('bi-eye');
                                this.classList.add('bi-lock');
                            } else {
                                this.classList.remove('bi-eye-fill');
                                this.classList.add('bi-lock-fill');
                            }
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>
