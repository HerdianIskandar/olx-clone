<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect("login.php");
}

// Get current user
$current_user = getCurrentUser();
if (!$current_user) {
    redirect("login.php");
}

// Get categories for dropdown using PDO
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
    $price = sanitize($_POST['price']);
    $location = sanitize($_POST['location']);
    $category_id = (int)$_POST['category_id'];
    $user_id = $current_user['id'];
    
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
    
    // Insert ad if no errors using PDO
    if (empty($errors)) {
        try {
            // Start transaction for data consistency
            $pdo->beginTransaction();
            
            // Create ad using PDO helper function
            $ad_id = createAd($pdo, $user_id, $category_id, $title, $description, $price, $location);
            
            if ($ad_id) {
                // Handle image uploads using PDO helper function
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
                                // Upload file using PDO helper function
                                $image_path = uploadFile($file_data, 'uploads/');
                                
                                // Add image to database using PDO helper function
                                addAdImage($pdo, $ad_id, $image_path);
                            } catch (Exception $e) {
                                error_log("Image upload error: " . $e->getMessage());
                                // Continue with other images, don't fail the entire process
                            }
                        }
                    }
                }
                
                // Commit transaction
                $pdo->commit();
                
                // Set success message
                setFlashMessage('success', 'Iklan berhasil diposting!');
                
                // Redirect to ad detail page
                redirect("detail.php?id=" . $ad_id);
            } else {
                $pdo->rollBack();
                $errors[] = "Gagal memposting iklan! Silakan coba lagi.";
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            
            error_log("Post Ad Error: " . $e->getMessage());
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
    <title>Pasang Iklan - OLX Clone</title>
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
            background: var(--light-bg);
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
        
        /* Post Ads Container */
        .post-ads-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .post-ads-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .post-ads-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .post-ads-subtitle {
            color: var(--gray-600);
            font-size: 1.125rem;
        }
        
        /* Form Card */
        .form-card {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            padding: 2.5rem;
            margin-bottom: 2rem;
        }
        
        .form-section {
            margin-bottom: 2rem;
        }
        
        .form-section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-section-title i {
            color: var(--secondary-color);
        }
        
        /* Form Styles */
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
        
        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }
        
        select.form-control {
            cursor: pointer;
        }
        
        /* Image Upload */
        .image-upload-area {
            border: 2px dashed var(--gray-300);
            border-radius: var(--border-radius);
            padding: 2rem;
            text-align: center;
            background: var(--gray-100);
            transition: var(--transition);
            cursor: pointer;
        }
        
        .image-upload-area:hover {
            border-color: var(--secondary-color);
            background: rgba(0, 168, 150, 0.05);
        }
        
        .image-upload-area.dragover {
            border-color: var(--secondary-color);
            background: rgba(0, 168, 150, 0.1);
        }
        
        .upload-icon {
            font-size: 3rem;
            color: var(--gray-400);
            margin-bottom: 1rem;
        }
        
        .upload-text {
            color: var(--gray-600);
            margin-bottom: 0.5rem;
        }
        
        .upload-hint {
            color: var(--gray-500);
            font-size: 0.875rem;
        }
        
        .image-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .image-preview-item {
            position: relative;
            border-radius: var(--border-radius);
            overflow: hidden;
            aspect-ratio: 1;
            background: var(--gray-100);
        }
        
        .image-preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .image-remove {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: var(--accent-color);
            color: var(--white);
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .image-remove:hover {
            background: #e55a00;
            transform: scale(1.1);
        }
        
        /* Buttons */
        .btn-submit {
            background: var(--secondary-color);
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
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-submit:hover {
            background: #008f7f;
            transform: translateY(-2px);
            box-shadow: var(--shadow);
            color: var(--white);
        }
        
        .btn-cancel {
            background: var(--white);
            color: var(--gray-700);
            border: 2px solid var(--gray-200);
            border-radius: var(--border-radius);
            padding: 1rem 2rem;
            font-weight: 500;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-cancel:hover {
            background: var(--gray-100);
            border-color: var(--gray-300);
            color: var(--gray-700);
            text-decoration: none;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
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
            margin-top: 4rem;
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
        @media (max-width: 768px) {
            .post-ads-container {
                margin: 1rem auto;
            }
            
            .form-card {
                padding: 1.5rem;
            }
            
            .post-ads-title {
                font-size: 2rem;
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
            .form-card {
                padding: 1rem;
            }
            
            .post-ads-title {
                font-size: 1.75rem;
            }
            
            .image-preview {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
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
                        <a class="nav-link active" href="post-ads.php"><i class="bi bi-plus-circle"></i> Pasang Iklan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="bi bi-heart"></i> Favorit</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="bi bi-globe"></i> ID</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Post Ads Container -->
    <div class="post-ads-container">
        <div class="post-ads-header">
            <h1 class="post-ads-title">Pasang Iklan Baru</h1>
            <p class="post-ads-subtitle">Jual produk Anda dengan mudah dan cepat</p>
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

        <div class="form-card">
            <form method="POST" action="" enctype="multipart/form-data" id="postAdForm">
                <!-- Basic Information -->
                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="bi bi-info-circle"></i>
                        Informasi Dasar
                    </h3>
                    
                    <div class="form-group">
                        <label for="title" class="form-label">Judul Iklan *</label>
                        <input type="text" class="form-control" id="title" name="title" 
                               placeholder="Contoh: iPhone 13 Pro Max 256GB" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id" class="form-label">Kategori *</label>
                        <select class="form-control" id="category_id" name="category_id" required>
                            <option value="">Pilih Kategori</option>
                            <?php if (!empty($categories)): ?>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <!-- Description -->
                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="bi bi-text-paragraph"></i>
                        Deskripsi Produk
                    </h3>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">Deskripsi *</label>
                        <textarea class="form-control" id="description" name="description" 
                                  placeholder="Deskripsikan kondisi, spesifikasi, dan informasi penting lainnya..." required></textarea>
                    </div>
                </div>

                <!-- Price and Location -->
                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="bi bi-tag"></i>
                        Harga dan Lokasi
                    </h3>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="price" class="form-label">Harga (Rp) *</label>
                                <input type="number" class="form-control" id="price" name="price" 
                                       placeholder="15000000" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="location" class="form-label">Lokasi</label>
                                <select class="form-control" id="location_select" name="location_select">
                                    <option value="">Pilih lokasi yang ada</option>
                                    <?php if (!empty($locations)): ?>
                                        <?php foreach ($locations as $loc): ?>
                                            <option value="<?php echo htmlspecialchars($loc['location']); ?>">
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
                                       placeholder="Masukkan lokasi baru" style="display: none;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Images -->
                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="bi bi-images"></i>
                        Foto Produk
                    </h3>
                    
                    <div class="image-upload-area" id="imageUploadArea">
                        <i class="bi bi-cloud-upload upload-icon"></i>
                        <p class="upload-text">Klik atau drag & drop foto di sini</p>
                        <p class="upload-hint">Maksimal 5 foto, format: JPG, PNG, GIF, WebP</p>
                        <input type="file" name="images[]" id="imageInput" accept="image/*" multiple style="display: none;">
                    </div>
                    
                    <div class="image-preview" id="imagePreview"></div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn-submit">
                        <i class="bi bi-check-circle"></i> Pasang Iklan
                    </button>
                    <a href="index.php" class="btn-cancel">
                        <i class="bi bi-x-circle"></i> Batal
                    </a>
                </div>
            </form>
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
        // Image upload functionality
        const imageUploadArea = document.getElementById('imageUploadArea');
        const imageInput = document.getElementById('imageInput');
        const imagePreview = document.getElementById('imagePreview');
        let uploadedImages = [];
        
        // Click to upload
        imageUploadArea.addEventListener('click', () => {
            imageInput.click();
        });
        
        // Drag and drop
        imageUploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            imageUploadArea.classList.add('dragover');
        });
        
        imageUploadArea.addEventListener('dragleave', () => {
            imageUploadArea.classList.remove('dragover');
        });
        
        imageUploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            imageUploadArea.classList.remove('dragover');
            handleFiles(e.dataTransfer.files);
        });
        
        // File input change
        imageInput.addEventListener('change', (e) => {
            handleFiles(e.target.files);
        });
        
        function handleFiles(files) {
            const maxFiles = 5;
            const currentFiles = uploadedImages.length;
            
            if (currentFiles >= maxFiles) {
                alert('Maksimal 5 foto yang diizinkan');
                return;
            }
            
            const remainingSlots = maxFiles - currentFiles;
            const filesToProcess = Array.from(files).slice(0, remainingSlots);
            
            filesToProcess.forEach(file => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const imageId = Date.now() + Math.random();
                        uploadedImages.push({
                            id: imageId,
                            file: file,
                            url: e.target.result
                        });
                        
                        addImagePreview(imageId, e.target.result);
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
        
        function addImagePreview(id, url) {
            const previewItem = document.createElement('div');
            previewItem.className = 'image-preview-item';
            previewItem.dataset.imageId = id;
            
            const img = document.createElement('img');
            img.src = url;
            img.alt = 'Preview';
            
            const removeBtn = document.createElement('button');
            removeBtn.className = 'image-remove';
            removeBtn.innerHTML = '<i class="bi bi-x"></i>';
            removeBtn.onclick = () => removeImage(id);
            
            previewItem.appendChild(img);
            previewItem.appendChild(removeBtn);
            imagePreview.appendChild(previewItem);
        }
        
        function removeImage(id) {
            uploadedImages = uploadedImages.filter(img => img.id !== id);
            const previewItem = document.querySelector(`[data-image-id="${id}"]`);
            if (previewItem) {
                previewItem.remove();
            }
        }
        
        // Form validation
        document.getElementById('postAdForm').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value;
            const description = document.getElementById('description').value;
            const price = document.getElementById('price').value;
            const categoryId = document.getElementById('category_id').value;
            
            if (!title || !description || !price || !categoryId) {
                e.preventDefault();
                alert('Mohon isi semua field yang diperlukan');
                return false;
            }
            
            if (isNaN(price) || parseFloat(price) <= 0) {
                e.preventDefault();
                alert('Harga harus berupa angka positif');
                return false;
            }
            
            if (title.length < 5) {
                e.preventDefault();
                alert('Judul iklan minimal 5 karakter');
                return false;
            }
            
            if (description.length < 20) {
                e.preventDefault();
                alert('Deskripsi minimal 20 karakter');
                return false;
            }
        });
        
        // Price formatting
        document.getElementById('price').addEventListener('input', function() {
            if (this.value < 0) {
                this.value = 0;
            }
        });
        
        // Character counter for description
        const descriptionField = document.getElementById('description');
        const maxLength = 1000;
        
        descriptionField.addEventListener('input', function() {
            const remaining = maxLength - this.value.length;
            if (remaining < 100) {
                this.style.borderColor = 'var(--accent-color)';
            } else {
                this.style.borderColor = '';
            }
        });
        
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
        
        // Form submission - ensure location value is set
        document.getElementById('postAdForm').addEventListener('submit', function(e) {
            const selectedLocation = locationSelect.value;
            const newLocation = locationInput.value;
            
            // If dropdown is visible and has selection, use it
            if (locationSelect.style.display !== 'none' && selectedLocation) {
                locationInput.value = selectedLocation;
            }
            // If text input is visible, use its value
            else if (locationInput.style.display !== 'none' && newLocation) {
                locationInput.value = newLocation;
            }
            // If neither has value, set empty
            else {
                locationInput.value = '';
            }
        });
    </script>
</body>
</html>
