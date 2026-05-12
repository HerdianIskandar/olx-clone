<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php?redirect=edit-profile.php');
}

// Get current user data
$currentUser = getCurrentUser();
if (!$currentUser) {
    setFlashMessage('error', 'User not found.');
    redirect('index.php');
}

// Handle profile update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
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
    
    // Check if email is being changed and if it already exists
    if ($email !== $currentUser['email']) {
        try {
            if (recordExists($pdo, 'users', 'email', $email)) {
                $errors[] = "Email sudah digunakan oleh pengguna lain!";
            }
        } catch (Exception $e) {
            $errors[] = "Terjadi kesalahan. Silakan coba lagi.";
        }
    }
    
    // Password change validation (only if user wants to change password)
    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        if (empty($current_password)) {
            $errors[] = "Password saat ini harus diisi untuk mengubah password!";
        } elseif (!verifyPassword($current_password, $currentUser['password'])) {
            $errors[] = "Password saat ini salah!";
        }
        
        if (empty($new_password)) {
            $errors[] = "Password baru harus diisi!";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "Password baru minimal 6 karakter!";
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $new_password)) {
            $errors[] = "Password baru harus mengandung huruf besar, huruf kecil, dan angka!";
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = "Konfirmasi password baru tidak cocok!";
        }
    }
    
    // Update profile if no errors
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Update user info
            $sql = "UPDATE users SET name = ?, email = ?";
            $params = [$name, $email];
            
            // Add password update if provided
            if (!empty($new_password)) {
                $sql .= ", password = ?";
                $params[] = password_hash($new_password, PASSWORD_DEFAULT);
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $currentUser['id'];
            
            $stmt = executeQuery($pdo, $sql, $params);
            
            // Update session data
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            
            $pdo->commit();
            
            setFlashMessage('success', 'Profil berhasil diperbarui!');
            
            // Refresh user data
            $currentUser = getCurrentUser();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Profile Update Error: " . $e->getMessage());
            $errors[] = "Terjadi kesalahan saat memperbarui profil. Silakan coba lagi.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil - OLX Clone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/edit-profile.css">
</head>
<body>
    <!-- Navbar -->
    <?php include 'navbar.php'; ?>

    <!-- Edit Profile Container -->
    <div class="edit-profile-container">
        <div class="edit-profile-card">
            <!-- Left Side - Profile Form -->
            <div class="edit-profile-form-side">
                <div class="edit-profile-header">
                    <h1 class="edit-profile-title">Edit Profil</h1>
                    <p class="edit-profile-subtitle">Perbarui informasi akun Anda</p>
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
                    <div class="profile-section">
                        <h5 class="section-title">Informasi Dasar</h5>
                        
                        <div class="form-group">
                            <label for="name" class="form-label">Nama Lengkap *</label>
                            <div class="input-group">
                                <i class="bi bi-person input-icon"></i>
                                <input type="text" class="form-control input-with-icon" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($currentUser['name']); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">Email *</label>
                            <div class="input-group">
                                <i class="bi bi-envelope input-icon"></i>
                                <input type="email" class="form-control input-with-icon" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tanggal Bergabung</label>
                            <div class="input-group">
                                <i class="bi bi-calendar input-icon"></i>
                                <input type="text" class="form-control input-with-icon" 
                                       value="<?php echo date('d F Y', strtotime($currentUser['created_at'])); ?>" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="profile-section">
                        <h5 class="section-title">Ubah Password</h5>
                        <p class="section-subtitle">Kosongkan jika tidak ingin mengubah password</p>
                        
                        <div class="form-group">
                            <label for="current_password" class="form-label">Password Saat Ini</label>
                            <div class="input-group">
                                <i class="bi bi-lock input-icon"></i>
                                <input type="password" class="form-control input-with-icon" id="current_password" 
                                       name="current_password" placeholder="Masukkan password saat ini">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="new_password" class="form-label">Password Baru</label>
                            <div class="input-group">
                                <i class="bi bi-lock input-icon"></i>
                                <input type="password" class="form-control input-with-icon" id="new_password" 
                                       name="new_password" placeholder="Minimal 6 karakter">
                            </div>
                            <div class="password-strength" id="passwordStrength">
                                <div class="password-strength-bar" id="passwordStrengthBar"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                            <div class="input-group">
                                <i class="bi bi-lock-fill input-icon"></i>
                                <input type="password" class="form-control input-with-icon" id="confirm_password" 
                                       name="confirm_password" placeholder="Ulangi password baru">
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-save-profile">
                            <i class="bi bi-check-circle"></i> Simpan Perubahan
                        </button>
                        <a href="index.php" class="btn-cancel">
                            <i class="bi bi-x-circle"></i> Batal
                        </a>
                    </div>
                </form>
            </div>

            <!-- Right Side - Profile Info -->
            <div class="edit-profile-info-side">
                <i class="bi bi-person-gear hero-icon"></i>
                <h2 class="hero-title">Kelola Profil Anda</h2>
                <p class="hero-description">
                    Perbarui informasi pribadi dan keamanan akun Anda
                </p>
                <ul class="hero-features">
                    <li>
                        <i class="bi bi-shield-check"></i>
                        <span>Keamanan data terjamin</span>
                    </li>
                    <li>
                        <i class="bi bi-clock-history"></i>
                        <span>Perubahan tersimpan otomatis</span>
                    </li>
                    <li>
                        <i class="bi bi-envelope-check"></i>
                        <span>Notifikasi perubahan akun</span>
                    </li>
                    <li>
                        <i class="bi bi-headset"></i>
                        <span>Support 24/7</span>
                    </li>
                </ul>
                
                <div class="profile-stats">
                    <div class="stat-item">
                        <i class="bi bi-calendar-check"></i>
                        <div>
                            <strong><?php echo date('Y', strtotime($currentUser['created_at'])) - date('Y', strtotime($currentUser['created_at'])) + 1; ?></strong>
                            <span>Tahun Bergabung</span>
                        </div>
                    </div>
                    <div class="stat-item">
                        <i class="bi bi-envelope"></i>
                        <div>
                            <strong>Terverifikasi</strong>
                            <span>Email Aktif</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

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
        document.getElementById('new_password').addEventListener('input', function() {
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
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && newPassword !== confirmPassword) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (!name || !email) {
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
            
            // Password change validation
            if (currentPassword || newPassword || confirmPassword) {
                if (!currentPassword) {
                    e.preventDefault();
                    alert('Password saat ini harus diisi untuk mengubah password');
                    return false;
                }
                
                if (!newPassword) {
                    e.preventDefault();
                    alert('Password baru harus diisi');
                    return false;
                }
                
                if (newPassword.length < 6) {
                    e.preventDefault();
                    alert('Password baru minimal 6 karakter');
                    return false;
                }
                
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('Konfirmasi password baru tidak cocok');
                    return false;
                }
            }
        });
        
        // Password visibility toggle
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInputs = document.querySelectorAll('input[type="password"]');
            
            passwordInputs.forEach(input => {
                const icon = input.previousElementSibling;
                if (icon && (icon.classList.contains('bi-lock') || icon.classList.contains('bi-lock-fill'))) {
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
