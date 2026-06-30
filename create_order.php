<?php
// api/create_order.php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

$csrf = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verify_csrf($csrf)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$delivery = $_POST['delivery'] ?? 'pudu';

if ($product_id <= 0) {
    http_response_code(422);
    echo json_encode(['error' => 'Invalid product']);
    exit;
}

// Fetch product
$stmt = $pdo->prepare('SELECT id, name, price, user_id FROM products WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) {
    http_response_code(404);
    echo json_encode(['error' => 'Product not found']);
    exit;
}

// Delivery prices (same as product page)
$deliveryOptions = [
    'pudu' => 7500,
    'door' => 14500,
];
$delivery_cents = $deliveryOptions[$delivery] ?? $deliveryOptions['pudu'];

// Service fee
const SERVICE_FEE_RATE = 0.025;
$product_cents = (int)$product['price'];
$service_fee = (int)round($product_cents * SERVICE_FEE_RATE);
$total_cents = $product_cents + $delivery_cents + $service_fee;

try {
    $pdo->beginTransaction();

    // Insert order
    $user = current_user();
    $buyer_id = (int)$user['id'];

    $stmt = $pdo->prepare('INSERT INTO orders (buyer_id, seller_id, total_cents, delivery_cents, service_fee_cents, status, created_at) VALUES (:buyer, :seller, :total, :delivery, :service, :status, NOW())');
    $stmt->execute([
        ':buyer' => $buyer_id,
        ':seller' => (int)$product['user_id'],
        ':total' => $total_cents,
        ':delivery' => $delivery_cents,
        ':service' => $service_fee,
        ':status' => 'pending'
    ]);
    $order_id = (int)$pdo->lastInsertId();

    // Insert order item
    $stmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, unit_price_cents, quantity) VALUES (:oid, :pid, :price, :qty)');
    $stmt->execute([':oid' => $order_id, ':pid' => $product_id, ':price' => $product_cents, ':qty' => 1]);

    $pdo->commit();

    // PayFast redirect URL 
    // Simulates PayFast flow.
    $payfast_url = '/api/payfast_redirect.php?order_id=' . urlencode((string)$order_id);

    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'payfast_url' => $payfast_url,
        'total_cents' => $total_cents
    ]);
    exit;
} catch (Exception $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('create_order error: ' . $ex->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error creating order']);
    exit;
}
