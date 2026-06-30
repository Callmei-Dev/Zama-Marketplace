<?php
// api/delete_account.php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Ensure user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Read input (form POST)
$csrf = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verify_csrf($csrf)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

$user = current_user();
$userId = (int)$user['id'];

try {
    // Begin transaction
    $pdo->beginTransaction();

    // 1) Delete product images for user's products
    $stmt = $pdo->prepare('SELECT id FROM products WHERE user_id = :uid');
    $stmt->execute([':uid' => $userId]);
    $productIds = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

    if (!empty($productIds)) {
        // Delete product_images rows
        $in = implode(',', array_fill(0, count($productIds), '?'));
        $delImg = $pdo->prepare("DELETE FROM product_images WHERE product_id IN ($in)");
        $delImg->execute($productIds);

        // Delete products
        $delProducts = $pdo->prepare("DELETE FROM products WHERE id IN ($in)");
        $delProducts->execute($productIds);
    }

    // 2) Delete favourites (or wishlist) entries for this user
    $delFav = $pdo->prepare('DELETE FROM favourites WHERE user_id = :uid');
    $delFav->execute([':uid' => $userId]);

    // 3) Delete other user-related tables if present (e.g., orders, messages)
    // Example: delete user messages
    if (tableExists($pdo, 'messages')) {
        $delMsg = $pdo->prepare('DELETE FROM messages WHERE user_id = :uid OR recipient_id = :uid');
        $delMsg->execute([':uid' => $userId]);
    }

    // 4) Remove personal identifiers from users table to anonymize
    // Option A: delete user row entirely. Option B: anonymize. We'll anonymize to preserve referential integrity.
    $anonymizedUsername = 'deleted_user_' . $userId;
    $anonymizedEmail = null;

    $upd = $pdo->prepare('UPDATE users SET username = :uname, email = :email, deleted_at = NOW() WHERE id = :uid');
    $upd->execute([':uname' => $anonymizedUsername, ':email' => $anonymizedEmail, ':uid' => $userId]);

    // Commit transaction
    $pdo->commit();

    // Log the user out and destroy session
    logout_current_user();

    // Return JSON success and redirect to homepage
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true, 'redirect' => '/goodbye.php']);
    exit;
} catch (Exception $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Server error while deleting account']);
    exit;
}

/**
 * Helper: check if table exists in current DB
 */
function tableExists(PDO $pdo, string $table): bool {
    try {
        $stmt = $pdo->prepare("SELECT 1 FROM {$table} LIMIT 1");
        $stmt->execute();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
