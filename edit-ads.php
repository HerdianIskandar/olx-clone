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
    <link rel="stylesheet" href="css/edit-ads.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

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
