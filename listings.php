<?php
// api/listings.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/db_script.php';

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 24;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;
$category = $_GET['category'] ?? null;
$q = $_GET['q'] ?? null;

$params = [];
$where = "p.status = 'available'";

if ($category) {
    $where .= " AND c.slug = :category";
    $params[':category'] = $category;
}
if ($q) {
    $where .= " AND p.name LIKE :q";
    $params[':q'] = '%' . $q . '%';
}

$sql = "
SELECT p.id, p.name, p.price, p.`condition`, p.days_ago, p.created_at,
       COALESCE(pi.file_path, '') AS main_image,
       u.username AS seller_username
FROM products p
LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_main = 1
JOIN users u ON u.id = p.user_id
JOIN categories c ON c.id = p.category_id
WHERE {$where}
ORDER BY p.created_at DESC
LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

echo json_encode(['page'=>$page,'limit'=>$limit,'items'=>$rows]);
