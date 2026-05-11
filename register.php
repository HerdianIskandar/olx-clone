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
    $whatsapp = sanitize($_POST['whatsapp']);
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
    
    if (!empty($whatsapp)) {
        // Remove any non-numeric characters except +
        $whatsapp = preg_replace('/[^0-9+]/', '', $whatsapp);
        // Validate WhatsApp number (basic validation)
        if (!preg_match('/^\+?[0-9]{10,15}$/', $whatsapp)) {
            $errors[] = "Format nomor WhatsApp tidak valid!";
        }
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
            $userId = createUser($pdo, $name, $email, $password, $whatsapp);
            
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
    <link rel="stylesheet" href="css/register.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

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
                            <label for="whatsapp" class="form-label">Nomor WhatsApp</label>
                            <div class="input-group">
                                <i class="bi bi-whatsapp input-icon"></i>
                                <input type="tel" class="form-control input-with-icon" id="whatsapp" name="whatsapp" 
                                       placeholder="+628123456789 (opsional)">
                            </div>
                            <small class="text-muted">Opsional: Untuk komunikasi lebih lanjut</small>
                        </div>
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
        
        // WhatsApp validation
        document.getElementById('whatsapp').addEventListener('input', function() {
            let value = this.value;
            // Remove any non-numeric characters except +
            value = value.replace(/[^0-9+]/g, '');
            this.value = value;
            
            // Basic validation feedback
            if (value && !/^\+?[0-9]{10,15}$/.test(value)) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const whatsapp = document.getElementById('whatsapp').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const terms = document.getElementById('terms').checked;
            
            if (!name || !email || !password || !confirmPassword) {
                e.preventDefault();
                alert('Mohon isi semua field yang diperlukan');
                return false;
            }
            
            if (whatsapp && !/^\+?[0-9]{10,15}$/.test(whatsapp)) {
                e.preventDefault();
                alert('Format nomor WhatsApp tidak valid!');
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
