<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect("login.php?redirect=" . urlencode('edit-ads.php?id=' . $_GET['id']));
}

// Get current user
$current_user = getCurrentUser();

// Get ad ID from URL
$ad_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get ad details and verify ownership
try {
    $ad = getAdById($pdo, $ad_id);
    
    if (!$ad || $ad['user_id'] != $current_user['id']) {
        setFlashMessage('error', 'Iklan tidak ditemukan atau Anda tidak memiliki izin untuk mengeditnya.');
        redirect('my-ads.php');
    }
} catch (Exception $e) {
    error_log("Error fetching ad details: " . $e->getMessage());
    setFlashMessage('error', 'Terjadi kesalahan. Silakan coba lagi.');
    redirect('my-ads.php');
}

// Get ad images
try {
    $images = getAdImages($pdo, $ad_id);
} catch (Exception $e) {
    error_log("Error fetching ad images: " . $e->getMessage());
    $images = [];
}

// Get categories for dropdown
try {
    $categories = getAllCategories($pdo);
} catch (Exception $e) {
    error_log("Error fetching categories: " . $e->getMessage());
    $categories = [];
}

// Get existing locations from ads table (unique locations)
try {
    $locations_query = "SELECT DISTINCT location FROM ads WHERE location IS NOT NULL AND location != '' ORDER BY location ASC";
    $locations = fetchAll($pdo, $locations_query);
} catch (Exception $e) {
    error_log("Error fetching locations: " . $e->getMessage());
    $locations = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    // Handle price - use clean value from hidden input or format the visible input
    if (isset($_POST['price_clean'])) {
        $price = sanitize($_POST['price_clean']);
    } else {
        // Fallback: remove formatting from visible input
        $price = sanitize($_POST['price']);
        $price = preg_replace('/\D/', '', $price); // Remove all non-digit characters
    }
    $location = sanitize($_POST['location']);
    $category_id = (int)$_POST['category_id'];
    
    // Validation
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Judul iklan harus diisi!";
    }
    
    if (empty($description)) {
        $errors[] = "Deskripsi harus diisi!";
    }
    
    if (empty($price) || !is_numeric($price)) {
        $errors[] = "Harga harus berupa angka!";
    }
    
    if (empty($category_id)) {
        $errors[] = "Kategori harus dipilih!";
    }
    
    // Update ad if no errors
    if (empty($errors)) {
        try {
            // Start transaction for data consistency
            $pdo->beginTransaction();
            
            // Update ad
            $success = updateAd($pdo, $ad_id, $current_user['id'], $category_id, $title, $description, $price, $location);
            
            if ($success) {
                // Handle image uploads
                if (!empty($_FILES['images']['name'][0])) {
                    foreach ($_FILES['images']['name'] as $key => $name) {
                        if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                            $file_data = [
                                'name' => $_FILES['images']['name'][$key],
                                'tmp_name' => $_FILES['images']['tmp_name'][$key],
                                'error' => $_FILES['images']['error'][$key],
                                'size' => $_FILES['images']['size'][$key]
                            ];
                            
                            try {
                                // Upload file
                                $image_path = uploadFile($file_data, 'uploads/');
                                
                                // Add image to database
                                addAdImage($pdo, $ad_id, $image_path);
                            } catch (Exception $e) {
                                error_log("Image upload error: " . $e->getMessage());
                                // Continue with other images, don't fail the entire process
                            }
                        }
                    }
                }
                
                // Handle image deletion
                if (isset($_POST['delete_images']) && is_array($_POST['delete_images'])) {
                    foreach ($_POST['delete_images'] as $image_id) {
                        try {
                            // Get image path before deletion
                            $image_sql = "SELECT image_path FROM ad_images WHERE id = ? AND ad_id = ?";
                            $image = fetchOne($pdo, $image_sql, [$image_id, $ad_id]);
                            
                            if ($image) {
                                // Delete from database
                                $delete_sql = "DELETE FROM ad_images WHERE id = ? AND ad_id = ?";
                                executeQuery($pdo, $delete_sql, [$image_id, $ad_id]);
                                
                                // Delete file from server
                                if (file_exists($image['image_path'])) {
                                    unlink($image['image_path']);
                                }
                            }
                        } catch (Exception $e) {
                            error_log("Error deleting image: " . $e->getMessage());
                            // Continue with other images
                        }
                    }
                }
                
                $pdo->commit();
                setFlashMessage('success', 'Iklan berhasil diperbarui!');
                redirect('my-ads.php');
            } else {
                $errors[] = "Gagal memperbarui iklan. Silakan coba lagi.";
                $pdo->rollBack();
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Update Error: " . $e->getMessage());
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
    <title>Edit Iklan - OLX Clone</title>
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
        
        /* Edit Ads Container */
        .edit-ads-container {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        /* Form Styles */
        .form-section {
            margin-bottom: 2rem;
        }
        
        .form-section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--gray-200);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
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
        
        .form-select {
            border: 2px solid var(--gray-200);
            border-radius: var(--border-radius);
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: var(--transition);
            background-color: var(--white);
        }
        
        .form-select:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 4px rgba(0, 168, 150, 0.1);
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
        
        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }
        
        /* Image Management */
        .current-images {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .current-image-item {
            position: relative;
            border-radius: var(--border-radius);
            overflow: hidden;
            border: 2px solid var(--gray-200);
            transition: var(--transition);
        }
        
        .current-image-item:hover {
            border-color: var(--accent-color);
        }
        
        .current-image-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        
        .delete-image-checkbox {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .delete-image-checkbox:hover {
            background: var(--accent-color);
            color: var(--white);
        }
        
        .delete-image-checkbox input {
            display: none;
        }
        
        .delete-image-checkbox.checked {
            background: var(--accent-color);
            color: var(--white);
        }
        
        .file-upload {
            border: 2px dashed var(--gray-300);
            border-radius: var(--border-radius);
            padding: 2rem;
            text-align: center;
            transition: var(--transition);
            cursor: pointer;
        }
        
        .file-upload:hover {
            border-color: var(--secondary-color);
            background: var(--gray-50);
        }
        
        .file-upload.dragover {
            border-color: var(--secondary-color);
            background: rgba(0, 168, 150, 0.1);
        }
        
        .file-upload-icon {
            font-size: 3rem;
            color: var(--gray-400);
            margin-bottom: 1rem;
        }
        
        .file-upload-text {
            color: var(--gray-600);
            margin-bottom: 0.5rem;
        }
        
        .file-upload-hint {
            color: var(--gray-500);
            font-size: 0.875rem;
        }
        
        /* Buttons */
        .btn-submit {
            background: var(--secondary-color);
            color: var(--white);
            padding: 1rem 2rem;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-submit:hover {
            background: #008f7f;
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .btn-cancel {
            background: transparent;
            color: var(--gray-600);
            padding: 1rem 2rem;
            border: 2px solid var(--gray-300);
            border-radius: var(--border-radius);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        
        .btn-cancel:hover {
            background: var(--gray-100);
            border-color: var(--gray-400);
            color: var(--gray-700);
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid var(--gray-200);
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
        
        .alert ul {
            margin-bottom: 0;
            padding-left: 1.5rem;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .current-images {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .edit-ads-container {
                padding: 1.5rem;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn-submit,
            .btn-cancel {
                width: 100%;
                justify-content: center;
            }
        }
        
        @media (max-width: 576px) {
            .page-header {
                padding: 2rem 0;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .current-images {
                grid-template-columns: 1fr;
            }
            
            .edit-ads-container {
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
                        <a class="nav-link" href="my-ads.php"><i class="bi bi-collection"></i> Iklan Saya</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="bi bi-heart"></i> Favorit</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($current_user['name']); ?></a>
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
            <h1 class="page-title">Edit Iklan</h1>
            <p class="page-subtitle">Perbarui informasi iklan Anda</p>
        </div>
    </div>

    <!-- Edit Ads Content -->
    <div class="container">
        <div class="edit-ads-container">
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

            <form method="POST" action="" enctype="multipart/form-data">
                <!-- Basic Information -->
                <div class="form-section">
                    <h2 class="form-section-title">
                        <i class="bi bi-info-circle"></i>
                        Informasi Dasar
                    </h2>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="title" class="form-label">Judul Iklan *</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?php echo htmlspecialchars($ad['title']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="category_id" class="form-label">Kategori *</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Pilih kategori</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo ($ad['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">Deskripsi *</label>
                        <textarea class="form-control" id="description" name="description" required><?php echo htmlspecialchars($ad['description']); ?></textarea>
                    </div>
                </div>

                <!-- Price and Location -->
                <div class="form-section">
                    <h2 class="form-section-title">
                        <i class="bi bi-tag"></i>
                        Harga dan Lokasi
                    </h2>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="price" class="form-label">Harga *</label>
                            <div class="input-group">
                                <i class="bi bi-currency-dollar input-icon"></i>
                                <input type="text" class="form-control input-with-icon" id="price" name="price" 
                                       value="<?php echo formatPrice($ad['price']); ?>" required>
                                <input type="hidden" id="price_clean" name="price_clean" value="<?php echo $ad['price']; ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="location_select" class="form-label">Lokasi</label>
                            <select class="form-select" id="location_select" name="location_select">
                                <option value="">Pilih lokasi yang ada</option>
                                <?php if (!empty($locations)): ?>
                                    <?php foreach ($locations as $loc): ?>
                                        <option value="<?php echo htmlspecialchars($loc['location']); ?>"
                                                <?php echo ($ad['location'] === $loc['location']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($loc['location']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <div class="mt-2">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="add_new_location_btn">
                                    <i class="bi bi-plus"></i> Tambah Lokasi Baru
                                </button>
                            </div>
                            <input type="text" class="form-control mt-2" id="location" name="location" 
                                   placeholder="Masukkan lokasi baru" style="display: none;"
                                   value="<?php echo htmlspecialchars($ad['location']); ?>">
                        </div>
                    </div>
                </div>

                <!-- Images -->
                <div class="form-section">
                    <h2 class="form-section-title">
                        <i class="bi bi-image"></i>
                        Foto Produk
                    </h2>
                    
                    <?php if (!empty($images)): ?>
                        <div class="mb-3">
                            <label class="form-label">Foto Saat Ini (Centang untuk hapus)</label>
                            <div class="current-images">
                                <?php foreach ($images as $image): ?>
                                    <div class="current-image-item">
                                        <img src="<?php echo htmlspecialchars($image['image_path']); ?>" 
                                             alt="Current image">
                                        <label class="delete-image-checkbox">
                                            <input type="checkbox" name="delete_images[]" value="<?php echo $image['id']; ?>">
                                            <i class="bi bi-trash"></i>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label class="form-label">Tambah Foto Baru (Opsional)</label>
                        <div class="file-upload" id="fileUpload">
                            <i class="bi bi-cloud-upload file-upload-icon"></i>
                            <div class="file-upload-text">Klik atau seret foto ke sini</div>
                            <div class="file-upload-hint">Maksimal 5 foto, format JPG/PNG, maksimal 5MB per foto</div>
                            <input type="file" name="images[]" multiple accept="image/*" style="display: none;">
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <a href="my-ads.php" class="btn-cancel">
                        <i class="bi bi-x-circle"></i> Batal
                    </a>
                    <button type="submit" class="btn-submit">
                        <i class="bi bi-check-circle"></i> Perbarui Iklan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Price formatting
        const priceInput = document.getElementById('price');
        const priceCleanInput = document.getElementById('price_clean');
        
        priceInput.addEventListener('input', function() {
            let value = this.value.replace(/[^\d]/g, '');
            if (value) {
                priceCleanInput.value = value;
                this.value = formatPrice(value);
            } else {
                priceCleanInput.value = '';
                this.value = '';
            }
        });
        
        function formatPrice(amount) {
            return new Intl.NumberFormat('id-ID').format(amount);
        }
        
        // Location Management
        const locationSelect = document.getElementById('location_select');
        const locationInput = document.getElementById('location');
        const addNewLocationBtn = document.getElementById('add_new_location_btn');
        
        // Toggle between dropdown and text input
        addNewLocationBtn.addEventListener('click', function() {
            if (locationInput.style.display === 'none') {
                // Show text input, hide dropdown
                locationSelect.style.display = 'none';
                locationInput.style.display = 'block';
                locationInput.focus();
                addNewLocationBtn.innerHTML = '<i class="bi bi-list"></i> Pilih Lokasi Ada';
                addNewLocationBtn.classList.remove('btn-outline-primary');
                addNewLocationBtn.classList.add('btn-outline-secondary');
            } else {
                // Show dropdown, hide text input
                locationSelect.style.display = 'block';
                locationInput.style.display = 'none';
                locationInput.value = '';
                addNewLocationBtn.innerHTML = '<i class="bi bi-plus"></i> Tambah Lokasi Baru';
                addNewLocationBtn.classList.remove('btn-outline-secondary');
                addNewLocationBtn.classList.add('btn-outline-primary');
            }
        });
        
        // Handle dropdown selection
        locationSelect.addEventListener('change', function() {
            if (this.value) {
                locationInput.value = this.value;
            }
        });
        
        // File upload
        const fileUpload = document.getElementById('fileUpload');
        const fileInput = fileUpload.querySelector('input[type="file"]');
        
        fileUpload.addEventListener('click', function() {
            fileInput.click();
        });
        
        // Drag and drop
        fileUpload.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        
        fileUpload.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });
        
        fileUpload.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                updateFileDisplay();
            }
        });
        
        fileInput.addEventListener('change', updateFileDisplay);
        
        function updateFileDisplay() {
            const files = fileInput.files;
            const fileUploadText = fileUpload.querySelector('.file-upload-text');
            
            if (files.length > 0) {
                const fileNames = Array.from(files).map(f => f.name).join(', ');
                fileUploadText.textContent = `${files.length} file dipilih: ${fileNames}`;
            } else {
                fileUploadText.textContent = 'Klik atau seret foto ke sini';
            }
        }
        
        // Delete image checkbox
        document.querySelectorAll('.delete-image-checkbox').forEach(checkbox => {
            checkbox.addEventListener('click', function(e) {
                e.preventDefault();
                const input = this.querySelector('input');
                
                if (input.checked) {
                    input.checked = false;
                    this.classList.remove('checked');
                } else {
                    input.checked = true;
                    this.classList.add('checked');
                }
            });
        });
        
        // Form submission - ensure location value is set and handle price formatting
        document.querySelector('form').addEventListener('submit', function(e) {
            const selectedLocation = locationSelect.value;
            const newLocation = locationInput.value;
            
            // Handle location value
            if (locationSelect.style.display !== 'none' && selectedLocation) {
                locationInput.value = selectedLocation;
            }
            else if (locationInput.style.display !== 'none' && newLocation) {
                locationInput.value = newLocation;
            }
            else {
                locationInput.value = '';
            }
            
            // Handle price formatting - convert formatted price to clean number
            const priceValue = priceInput.value.replace(/[^\d]/g, '');
            priceCleanInput.value = priceValue;
        });
    </script>
</body>
</html>
