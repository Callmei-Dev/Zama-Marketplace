<?php
// public/admin.php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Require admin
$user = current_user();
if (!$user || ($user['role'] ?? '') !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo 'Access denied';
    exit;
}

/**
 * Helper: format cents to Rands
 */
function format_rands(?int $cents): string {
    if ($cents === null) return 'R 0.00';
    return 'R ' . number_format($cents / 100, 2, '.', ',');
}

try {
    // Total users
    $stmt = $pdo->query('SELECT COUNT(*) FROM users');
    $totalUsers = (int)$stmt->fetchColumn();

    // Recently added users (last 3 days)
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE created_at >= (NOW() - INTERVAL 3 DAY)');
    $stmt->execute();
    $recentUsers = (int)$stmt->fetchColumn();

    // Items sold (count of order_items for orders with status = paid)
    $stmt = $pdo->prepare("SELECT COUNT(oi.id) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.status = 'paid'");
    $stmt->execute();
    $itemsSold = (int)$stmt->fetchColumn();

    // Users list with amount owing (sum of paid orders for which payout_processed is NULL or 0)
    // If your schema uses a different payout flag, adjust the WHERE clause accordingly.
    $sql = "
      SELECT u.id, u.username, u.email, u.role,
             COALESCE(SUM(o.total_cents), 0) AS owing_cents
      FROM users u
      LEFT JOIN orders o
        ON o.seller_id = u.id
        AND o.status = 'paid'
        AND (o.payout_processed = 0 OR o.payout_processed IS NULL)
      GROUP BY u.id
      ORDER BY owing_cents DESC, u.created_at DESC
      LIMIT 200
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent reports (if table exists)
    $reports = [];
    $hasReports = false;
    try {
        $check = $pdo->query("SELECT 1 FROM reports LIMIT 1");
        $hasReports = true;
    } catch (Exception $e) {
        $hasReports = false;
    }

    if ($hasReports) {
        $stmt = $pdo->prepare("SELECT r.id, r.product_id, p.name AS product_name, r.reporter_id, ru.username AS reporter_name, r.seller_id, su.username AS seller_name, r.status, r.created_at FROM reports r LEFT JOIN products p ON p.id = r.product_id LEFT JOIN users ru ON ru.id = r.reporter_id LEFT JOIN users su ON su.id = r.seller_id ORDER BY r.created_at DESC LIMIT 50");
        $stmt->execute();
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $ex) {
    error_log('admin dashboard error: ' . $ex->getMessage());
    http_response_code(500);
    echo 'Server error';
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Zama - Admin Dashboard</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: #f4f4f4;
      margin: 0;
      padding: 20px;
      color: #333;
    }
    h1 {
      text-align: center;
      color: #001f3f;
      margin-bottom: 30px;
    }
    .stats {
      display: flex;
      gap: 20px;
      margin-bottom: 40px;
      flex-wrap:wrap;
    }
    .card {
      flex: 1;
      min-width: 200px;
      background: #ffffffc9;
      border-radius: 12px;
      padding: 20px;
      text-align: center;
      box-shadow: 0 5px 10px rgba(0,0,0,0.1);
    }
    .card h2 {
      color: #001f3f;
      margin-bottom: 12px;
      font-size: 1.05em;
    }
    .card p {
      font-size: 1.6em;
      font-weight: 700;
      color: #001f3f;
      margin: 0;
    }
    .lower-section {
      display: flex;
      gap: 20px;
      margin-top: 20px;
      flex-wrap:wrap;
    }
    .lower-card {
      flex: 1;
      min-width: 320px;
      background: #ffffffc9;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 5px 10px rgba(0,0,0,0.1);
    }
    .lower-card h2 {
      color: #001f3f;
      margin-bottom: 12px;
      text-align: center;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.95em;
    }
    th, td {
      padding: 10px;
      border-bottom: 1px solid #ddd;
      text-align: left;
    }
    th {
      background: #001f3f;
      color: #fff;
      font-weight: 600;
    }
    .small { font-size:0.85em; color:#6b7280; }
    .owed { font-weight:700; color:#0a2a66; }
    .actions a { color:#0a66c2; text-decoration:none; margin-right:8px; }
  </style>
</head>
<body>
  <h1>Zama - Admin Dashboard</h1>

  <div class="stats" role="region" aria-label="Top statistics">
    <div class="card">
      <h2>Total Users</h2>
      <p><?php echo number_format($totalUsers); ?></p>
    </div>
    <div class="card">
      <h2>Recently added Users (3 days)</h2>
      <p>+<?php echo number_format($recentUsers); ?></p>
    </div>
    <div class="card">
      <h2>Items Sold</h2>
      <p><?php echo number_format($itemsSold); ?></p>
    </div>
  </div>

  <div class="lower-section">
    <div class="lower-card" aria-labelledby="usersHeading">
      <h2 id="usersHeading">User Accounts & Amounts Owing</h2>
      <table>
        <thead>
          <tr>
            <th>UID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Amount Owing</th>
            <th class="small">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($users)): ?>
            <tr><td colspan="6" class="small">No users found</td></tr>
          <?php else: ?>
            <?php foreach ($users as $u): ?>
              <tr>
                <td><?php echo (int)$u['id']; ?></td>
                <td><?php echo htmlspecialchars($u['username'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($u['email'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($u['role'] ?? 'user', ENT_QUOTES, 'UTF-8'); ?></td>
                <td class="owed"><?php echo format_rands((int)$u['owing_cents']); ?></td>
                <td class="actions small">
                  <a href="/admin/user_view.php?id=<?php echo (int)$u['id']; ?>">View</a>
                  <a href="/admin/process_payout.php?user_id=<?php echo (int)$u['id']; ?>">Process payout</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="lower-card" aria-labelledby="reportsHeading">
      <h2 id="reportsHeading">Report Details</h2>
      <?php if (!$hasReports): ?>
        <p class="small">No reports table found in the database.</p>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Report ID</th>
              <th>Product</th>
              <th>Reporter</th>
              <th>Seller</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($reports)): ?>
              <tr><td colspan="5" class="small">No reports</td></tr>
            <?php else: ?>
              <?php foreach ($reports as $r): ?>
                <tr>
                  <td><?php echo (int)$r['id']; ?></td>
                  <td><?php echo htmlspecialchars($r['product_name'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($r['reporter_name'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($r['seller_name'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($r['status'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>

