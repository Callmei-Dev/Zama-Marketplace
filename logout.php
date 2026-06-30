<?php
// api/logout.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// CSRF token
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$csrf = $input['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verify_csrf($csrf)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

logout_user();
echo json_encode(['success' => true]);
exit;
