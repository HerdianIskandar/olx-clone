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
    <link rel="stylesheet" href="css/post-ads.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

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
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control" id="price" name="price" 
                                           placeholder="15.000.000" required>
                                </div>
                                <small class="form-text text-muted">Masukkan harga tanpa format, contoh: 15000000</small>
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

    <?php include 'footer.php'; ?>

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
            const priceInput = document.getElementById('price');
            const categoryId = document.getElementById('category_id').value;
            
            // Get clean price value from data attribute
            const price = priceInput.getAttribute('data-raw-value') || 
                         priceInput.value.replace(/\D/g, '');
            
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
        
        // Price formatting with currency format
        const priceInput = document.getElementById('price');
        
        function formatCurrency(value) {
            // Remove all non-digit characters
            let cleanValue = value.replace(/\D/g, '');
            
            // Convert to number and format with thousand separator
            if (cleanValue === '') {
                return '';
            }
            
            // Add thousand separator (dot for Indonesian format)
            let formatted = cleanValue.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            
            return formatted;
        }
        
        function unformatCurrency(formattedValue) {
            // Remove all non-digit characters to get clean number
            return formattedValue.replace(/\D/g, '');
        }
        
        priceInput.addEventListener('input', function(e) {
            let value = e.target.value;
            
            // Format the value while typing
            let formatted = formatCurrency(value);
            
            // Update the input value
            e.target.value = formatted;
            
            // Store the clean value in a data attribute for form submission
            e.target.setAttribute('data-raw-value', unformatCurrency(formatted));
        });
        
        priceInput.addEventListener('blur', function(e) {
            // Ensure proper formatting when leaving the field
            let value = e.target.value;
            let formatted = formatCurrency(value);
            e.target.value = formatted;
            e.target.setAttribute('data-raw-value', unformatCurrency(formatted));
        });
        
        // Prevent negative values
        priceInput.addEventListener('keydown', function(e) {
            // Allow backspace, delete, tab, escape, enter
            if ([8, 9, 27, 13].indexOf(e.keyCode) !== -1 ||
                // Allow: Ctrl+A, Command+A
                (e.keyCode === 65 && (e.ctrlKey === true || e.metaKey === true)) ||
                // Allow: home, end, left, right, down, up
                (e.keyCode >= 35 && e.keyCode <= 40)) {
                // let it happen, don't do anything
                return;
            }
            
            // Ensure that it is a number and stop the keypress
            if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                e.preventDefault();
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
        
        // Form submission - ensure location value is set and handle price formatting
        document.getElementById('postAdForm').addEventListener('submit', function(e) {
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
            const priceRawValue = priceInput.getAttribute('data-raw-value');
            if (priceRawValue) {
                // Create a hidden input with the clean price value
                let hiddenPriceInput = document.createElement('input');
                hiddenPriceInput.type = 'hidden';
                hiddenPriceInput.name = 'price_clean';
                hiddenPriceInput.value = priceRawValue;
                this.appendChild(hiddenPriceInput);
                
                // Update the visible input to show formatted value
                priceInput.value = formatCurrency(priceRawValue);
            }
        });
    </script>
</body>
</html>
