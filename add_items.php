<?php
// api/add_item.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

// FTP credentials 
const FTP_HOST = 'ftpupload.net';
const FTP_PORT = 21;
const FTP_USER = 'if0_42072524';
const FTP_PASS = 'nz3AMOoQ0xqwder';

// POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Upload not allowed']);
    exit;
}

// Authicate
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Re-Authentication required']);
    exit;
}

$csrf = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verify_csrf($csrf)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

$name = trim((string)($_POST['name'] ?? ''));
$category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
$price_raw = trim((string)($_POST['price'] ?? ''));
$condition = trim((string)($_POST['condition'] ?? ''));
$description = trim((string)($_POST['Description'] ?? '')); 

$errors = [];

// Validation
if ($name === '' || mb_strlen($name) < 2) $errors[] = 'Name must be at least 2 characters';
if ($category_id <= 0) $errors[] = 'Invalid category';
if ($price_raw === '') {
    $errors[] = 'Price is required';
} else {
    // Accept 199.99 
    $price_raw = str_replace(',', '.', $price_raw);
    if (!is_numeric($price_raw)) {
        $errors[] = 'Price must be a number';
    }
}
if ($description === '') $description = null;

// Images validation
$files = $_FILES['images'] ?? null;
$imageCount = 0;
if ($files && isset($files['tmp_name']) && is_array($files['tmp_name'])) {
    $imageCount = count(array_filter($files['tmp_name']));
}
if ($imageCount < 1 || $imageCount > 3) {
    $errors[] = 'Please upload between 1 and 3 images';
}

if ($errors) {
    http_response_code(422);
    echo json_encode(['errors' => $errors]);
    exit;
}


// Begin DB transaction
try {
    $pdo->beginTransaction();

    // Insert product row
    $stmt = $pdo->prepare('INSERT INTO products (user_id, name, price, category_id, `condition`, description, status, created_at) VALUES (:uid, :name, :price, :cat, :cond, :desc, :status, NOW())');
    $user = current_user();
    $userId = (int)($user['id'] ?? 0);
    $stmt->execute([
        ':uid' => $userId,
        ':name' => $name,
        ':price' => $priceCents,
        ':cat' => $category_id,
        ':cond' => $condition,
        ':desc' => $description,
        ':status' => 'available'
    ]);
    $productId = (int)$pdo->lastInsertId();

    // Prepare to upload images to FTP
    $ftp = ftp_connect(FTP_HOST, FTP_PORT, 30);
    if ($ftp === false) {
        throw new RuntimeException('Could not connect to FTP server');
    }
    $login = ftp_login($ftp, FTP_USER, FTP_PASS);
    if ($login === false) {
        ftp_close($ftp);
        throw new RuntimeException('FTP login failed');
    }
    ftp_pasv($ftp, true);

    $remoteBase = '/public/assets/img/products/' . $productId . '/';

    // Create directory
    $parts = explode('/', trim($remoteBase, '/'));
    $path = '/';
    foreach ($parts as $part) {
        if ($part === '') continue;
        $path .= $part . '/';
        // Try to change into directory; if fails, create it
        if (!@ftp_chdir($ftp, $path)) {
            if (!@ftp_mkdir($ftp, $path)) {
                // cleanup and throw
                ftp_close($ftp);
                throw new RuntimeException('Failed to create remote directory: ' . $path);
            }
        }
    }

    // Process each uploaded file and upload to FTP
    $uploadedPaths = [];
    $mainSet = false;
    for ($i = 0; $i < count($files['tmp_name']); $i++) {
        $tmp = $files['tmp_name'][$i];
        if (empty($tmp) || !is_uploaded_file($tmp)) continue;

        $error = $files['error'][$i] ?? UPLOAD_ERR_OK;
        if ($error !== UPLOAD_ERR_OK) continue;

        $size = $files['size'][$i] ?? 0;
        if ($size > 8 * 1024 * 1024) { // 8MB limit
            continue;
        }

        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmp);
        finfo_close($finfo);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'])) {
            continue;
        }

        // extension
        $ext = 'jpg';
        if ($mime === 'image/png') $ext = 'png';
        if ($mime === 'image/webp') $ext = 'webp';

        // Remote filename
        $remoteFilename = ($i === 0 ? 'main' : 'extra-' . $i) . '.' . $ext;
        $remoteFilepath = $remoteBase . $remoteFilename; // remote path

        
        // ftp_put to upload in binary mode
        $uploadOk = ftp_put($ftp, $remoteFilepath, $tmp, FTP_BINARY);
        if ($uploadOk) {
            // Optionally set permissions
            @ftp_chmod($ftp, 0644, $remoteFilepath);

            // Store relative web-accessible path in DB.
            $webPath = 'assets/img/products/' . $productId . '/' . $remoteFilename;

            $stmtImg = $pdo->prepare('INSERT INTO product_images (product_id, file_path, is_main, created_at) VALUES (:pid, :path, :is_main, NOW())');
            $isMain = 0;
            if (!$mainSet) {
                $isMain = 1;
                $mainSet = true;
            }
            $stmtImg->execute([':pid' => $productId, ':path' => $webPath, ':is_main' => $isMain]);

            $uploadedPaths[] = $webPath;
        }
    }

    // Close FTP connection
    ftp_close($ftp);

    // Commit DB transaction
    $pdo->commit();

    // Return success JSON
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'product_id' => $productId,
        'images' => $uploadedPaths
    ]);
    exit;
} catch (Exception $ex) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    // If FTP connection exists, try to close
    if (isset($ftp) && is_resource($ftp)) {
        @ftp_close($ftp);
    }
    http_response_code(500);
    // Return generic message and log details server-side
    error_log('add_item error: Item cannot be addeed');
    echo json_encode(['error' => 'Server error while adding item']);
    exit;
}

