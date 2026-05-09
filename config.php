<?php
// Database configuration using PDO
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'db_olx_clone';
$charset = 'utf8mb4';

// Data Source Name (DSN)
$dsn = "mysql:host=$host;dbname=$database;charset=$charset";

// PDO options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_PERSISTENT         => false,
];

try {
    // Create PDO connection
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Set error reporting for development
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    // Log error and display user-friendly message
    error_log("Database Connection Error: " . $e->getMessage());
    
    // For development - show error
    die("Connection failed: " . $e->getMessage());
    
    // For production - use generic message
    // die("Database connection failed. Please try again later.");
}

// Function to sanitize input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Function to format price
function formatPrice($price) {
    return 'Rp ' . number_format($price, 0, ',', '.');
}

// Function to get time ago
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Baru saja';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' menit yang lalu';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' jam yang lalu';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . ' hari yang lalu';
    } elseif ($diff < 2629744) {
        return floor($diff / 604800) . ' minggu yang lalu';
    } elseif ($diff < 31556926) {
        return floor($diff / 2629744) . ' bulan yang lalu';
    } else {
        return floor($diff / 31556926) . ' tahun yang lalu';
    }
}

// Function to execute prepared statement with error handling
function executeQuery($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query Error: " . $e->getMessage() . " SQL: " . $sql);
        throw new Exception("Database query failed. Please try again later.");
    }
}

// Function to fetch single record
function fetchOne($pdo, $sql, $params = []) {
    $stmt = executeQuery($pdo, $sql, $params);
    return $stmt->fetch();
}

// Function to fetch multiple records
function fetchAll($pdo, $sql, $params = []) {
    $stmt = executeQuery($pdo, $sql, $params);
    return $stmt->fetchAll();
}

// Function to insert record and return last insert ID
function insertRecord($pdo, $sql, $params = []) {
    $stmt = executeQuery($pdo, $sql, $params);
    return $pdo->lastInsertId();
}

// Function to update record
function updateRecord($pdo, $sql, $params = []) {
    $stmt = executeQuery($pdo, $sql, $params);
    return $stmt->rowCount();
}

// Function to delete record
function deleteRecord($pdo, $sql, $params = []) {
    $stmt = executeQuery($pdo, $sql, $params);
    return $stmt->rowCount();
}

// Function to check if record exists
function recordExists($pdo, $table, $column, $value) {
    $sql = "SELECT COUNT(*) as count FROM $table WHERE $column = ?";
    $result = fetchOne($pdo, $sql, [$value]);
    return $result['count'] > 0;
}

// Function to get user by ID
function getUserById($pdo, $userId) {
    $sql = "SELECT id, name, email, whatsapp, created_at FROM users WHERE id = ?";
    return fetchOne($pdo, $sql, [$userId]);
}

// Function to get user by email
function getUserByEmail($pdo, $email) {
    $sql = "SELECT * FROM users WHERE email = ?";
    return fetchOne($pdo, $sql, [$email]);
}

// Function to get all categories
function getAllCategories($pdo) {
    $sql = "SELECT * FROM categories ORDER BY name ASC";
    return fetchAll($pdo, $sql);
}

// Function to get all locations
function getAllLocations($pdo) {
    $sql = "SELECT DISTINCT location FROM ads WHERE location IS NOT NULL AND location != '' ORDER BY location ASC";
    return fetchAll($pdo, $sql);
}

// Function to get category by ID
function getCategoryById($pdo, $categoryId) {
    $sql = "SELECT * FROM categories WHERE id = ?";
    return fetchOne($pdo, $sql, [$categoryId]);
}

// Function to get ads by category
function getAdsByCategory($pdo, $categoryId, $limit = 8) {
    $sql = "SELECT a.*, u.name as user_name, c.name as category_name,
                   (SELECT image_path FROM ad_images WHERE ad_id = a.id LIMIT 1) as image
            FROM ads a 
            JOIN users u ON a.user_id = u.id 
            JOIN categories c ON a.category_id = c.id 
            WHERE a.category_id = ? 
            ORDER BY a.created_at DESC 
            LIMIT ?";
    return fetchAll($pdo, $sql, [$categoryId, $limit]);
}

// Function to search ads
function searchAds($pdo, $searchTerm = '', $location = '', $limit = 12) {
    $sql = "SELECT a.*, u.name as user_name, c.name as category_name,
                   (SELECT image_path FROM ad_images WHERE ad_id = a.id LIMIT 1) as image
            FROM ads a 
            JOIN users u ON a.user_id = u.id 
            JOIN categories c ON a.category_id = c.id 
            WHERE 1=1";
    
    $params = [];
    
    if (!empty($searchTerm)) {
        $sql .= " AND (a.title LIKE ? OR a.description LIKE ?)";
        $searchParam = '%' . $searchTerm . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    if (!empty($location)) {
        $sql .= " AND a.location LIKE ?";
        $locationParam = '%' . $location . '%';
        $params[] = $locationParam;
    }
    
    $sql .= " ORDER BY a.created_at DESC LIMIT ?";
    $params[] = $limit;
    
    return fetchAll($pdo, $sql, $params);
}

// Function to get featured ads
function getFeaturedAds($pdo, $limit = 8) {
    $sql = "SELECT a.*, u.name as user_name, c.name as category_name,
                   (SELECT image_path FROM ad_images WHERE ad_id = a.id LIMIT 1) as image
            FROM ads a 
            JOIN users u ON a.user_id = u.id 
            JOIN categories c ON a.category_id = c.id 
            ORDER BY a.created_at DESC 
            LIMIT ?";
    return fetchAll($pdo, $sql, [$limit]);
}

// Function to get recent ads
function getRecentAds($pdo, $limit = 8) {
    $sql = "SELECT a.*, u.name as user_name, c.name as category_name,
                   (SELECT image_path FROM ad_images WHERE ad_id = a.id LIMIT 1) as image
            FROM ads a 
            JOIN users u ON a.user_id = u.id 
            JOIN categories c ON a.category_id = c.id 
            ORDER BY a.created_at DESC 
            LIMIT ?";
    return fetchAll($pdo, $sql, [$limit]);
}

// Function to get ad by ID
function getAdById($pdo, $adId) {
    $sql = "SELECT a.*, u.name as user_name, u.email as user_email, c.name as category_name 
            FROM ads a 
            JOIN users u ON a.user_id = u.id 
            JOIN categories c ON a.category_id = c.id 
            WHERE a.id = ?";
    return fetchOne($pdo, $sql, [$adId]);
}

// Function to get ad images
function getAdImages($pdo, $adId) {
    $sql = "SELECT * FROM ad_images WHERE ad_id = ? ORDER BY id ASC";
    return fetchAll($pdo, $sql, [$adId]);
}

// Function to get related ads
function getRelatedAds($pdo, $categoryId, $currentAdId, $limit = 4) {
    $sql = "SELECT a.*, (SELECT image_path FROM ad_images WHERE ad_id = a.id LIMIT 1) as image 
            FROM ads a 
            WHERE a.category_id = ? AND a.id != ? 
            ORDER BY a.created_at DESC 
            LIMIT ?";
    return fetchAll($pdo, $sql, [$categoryId, $currentAdId, $limit]);
}

// Function to get user's ads
function getUserAds($pdo, $userId, $limit = 50) {
    $sql = "SELECT a.*, c.name as category_name,
                   (SELECT image_path FROM ad_images WHERE ad_id = a.id LIMIT 1) as image
            FROM ads a 
            JOIN categories c ON a.category_id = c.id 
            WHERE a.user_id = ? 
            ORDER BY a.created_at DESC 
            LIMIT ?";
    return fetchAll($pdo, $sql, [$userId, $limit]);
}

// Function to create user
function createUser($pdo, $name, $email, $password, $whatsapp = null) {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (name, email, whatsapp, password) VALUES (?, ?, ?, ?)";
    return insertRecord($pdo, $sql, [$name, $email, $whatsapp, $hashedPassword]);
}

// Function to create ad
function createAd($pdo, $userId, $categoryId, $title, $description, $price, $location) {
    $sql = "INSERT INTO ads (user_id, category_id, title, description, price, location) 
            VALUES (?, ?, ?, ?, ?, ?)";
    return insertRecord($pdo, $sql, [$userId, $categoryId, $title, $description, $price, $location]);
}

// Function to update ad
function updateAd($pdo, $adId, $userId, $categoryId, $title, $description, $price, $location) {
    $sql = "UPDATE ads SET category_id = ?, title = ?, description = ?, price = ?, location = ? 
            WHERE id = ? AND user_id = ?";
    return executeQuery($pdo, $sql, [$categoryId, $title, $description, $price, $location, $adId, $userId]);
}

// Function to delete ad
function deleteAd($pdo, $adId, $userId) {
    try {
        $pdo->beginTransaction();
        
        // Delete ad images first
        $sql = "DELETE FROM ad_images WHERE ad_id = ?";
        executeQuery($pdo, $sql, [$adId]);
        
        // Delete the ad
        $sql = "DELETE FROM ads WHERE id = ? AND user_id = ?";
        executeQuery($pdo, $sql, [$adId, $userId]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error deleting ad: " . $e->getMessage());
        return false;
    }
}

// Function to add ad image
function addAdImage($pdo, $adId, $imagePath) {
    $sql = "INSERT INTO ad_images (ad_id, image_path) VALUES (?, ?)";
    return insertRecord($pdo, $sql, [$adId, $imagePath]);
}

// Function to validate password
function verifyPassword($password, $hashedPassword) {
    return password_verify($password, $hashedPassword);
}

// Function to generate random string
function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}

// Function to upload file
function uploadFile($file, $uploadDir = 'uploads/') {
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($fileExtension, $allowedExtensions)) {
        throw new Exception('Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.');
    }
    
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
        throw new Exception('File size too large. Maximum size is 5MB.');
    }
    
    $newFilename = uniqid() . '.' . $fileExtension;
    $uploadPath = $uploadDir . $newFilename;
    
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('Failed to upload file.');
    }
    
    return $uploadPath;
}

// Function to start session securely
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Set secure session parameters
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
        
        session_start();
    }
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to get current user
function getCurrentUser() {
    if (isLoggedIn()) {
        global $pdo;
        return getUserById($pdo, $_SESSION['user_id']);
    }
    return null;
}

// Function to redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Function to set flash message
function setFlashMessage($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

// Function to get flash message
function getFlashMessage($type) {
    if (isset($_SESSION['flash'][$type])) {
        $message = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $message;
    }
    return null;
}

// Initialize session
startSecureSession();
?>
