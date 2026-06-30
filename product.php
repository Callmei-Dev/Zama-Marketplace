<?php
// public/product.php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
//require_once __DIR__ . '/../includes/auth.php';
//require_once __DIR__ . '/../includes/csrf.php';

// Get product id
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(404);
    echo "Product not found.";
    exit;
}

// Fetch product and main image
$stmt = $pdo->prepare("
  SELECT p.id, p.name, p.price, p.description, p.user_id AS seller_id, p.created_at,
         COALESCE(pi.file_path, '') AS main_image, u.username AS seller_username, u.email AS seller_email
  FROM products p
  LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_main = 1
  JOIN users u ON u.id = p.user_id
  WHERE p.id = :id
  LIMIT 1
");
$stmt->execute([':id' => $id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    http_response_code(404);
    echo "Product not found.";
    exit;
}

// Convert stored cents to rands for display
function cents_to_rands($cents) {
    return number_format($cents / 100, 2, '.', '');
}

$mainImage = $product['main_image'] ? '/' . ltrim($product['main_image'], '/') : '/assets/img/placeholder.png';

// Delivery options (BobGo labels; prices fixed per your instruction)
$deliveryOptions = [
    'pudu' => ['label' => 'Pudu (collection point)', 'price_cents' => 7500], // R75.00
    'door' => ['label' => 'Door-to-door', 'price_cents' => 14500], // R145.00
];

// Service fee rate (2.5%)
const SERVICE_FEE_RATE = 0.025;

// CSRF token for order creation
$csrf = csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,minimum-scale=1" />
  <title><?php echo htmlspecialchars($product['name']); ?> — Zama Marketplace</title>
  <link rel="stylesheet" href="/assets/css/main.css">
  <style>
    body{font-family:Inter,system-ui,Arial;margin:20px;background:#f7fafc;color:#0b254f}
    .container{max-width:980px;margin:0 auto}
    .grid{display:grid;grid-template-columns:360px 1fr;gap:20px}
    .card{background:#fff;padding:18px;border-radius:10px;box-shadow:0 6px 18px rgba(10,42,102,0.06)}
    img.product{width:100%;height:320px;object-fit:cover;border-radius:8px}
    h1{margin:0 0 8px;color:#0a2a66}
    .price{font-size:20px;font-weight:700;color:#0a2a66;margin:8px 0}
    .meta{color:#6b7280;font-size:14px;margin-bottom:12px}
    .delivery-options label{display:block;margin-bottom:8px}
    .totals{margin-top:12px;padding:12px;background:#f1f5f9;border-radius:8px}
    .totals div{display:flex;justify-content:space-between;padding:6px 0}
    .btn-pay{display:inline-flex;align-items:center;gap:10px;padding:10px 14px;border-radius:8px;background:#007bff;color:#fff;border:none;cursor:pointer;font-weight:700}
    .btn-pay[disabled]{opacity:0.6;cursor:not-allowed}
    .spinner{width:18px;height:18px;border:3px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;animation:spin 1s linear infinite;display:inline-block}
    @keyframes spin{to{transform:rotate(360deg)}}
    .note{font-size:13px;color:#6b7280;margin-top:10px}
    .seller-notify{margin-top:12px;font-size:14px}
    .link-sim{display:inline-block;margin-top:12px;color:#0a66c2}
  </style>
</head>
<body>
  <main class="container">
    <div class="grid">
      <div class="card">
        <img class="product" src="<?php echo htmlspecialchars($mainImage, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>">
        <div style="margin-top:12px">
          <strong>Seller:</strong> <?php echo htmlspecialchars($product['seller_username'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <div class="seller-notify">
          Seller email: <?php echo htmlspecialchars($product['seller_email'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
        </div>
      </div>

      <div class="card">
        <h1><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></h1>
        <div class="meta">Added: <?php echo htmlspecialchars($product['created_at']); ?></div>

        <div class="price">R <?php echo cents_to_rands((int)$product['price']); ?></div>

        <p><?php echo nl2br(htmlspecialchars($product['description'] ?? '')); ?></p>

        <hr>

        <form id="checkoutForm" method="post" action="/api/create_order.php">
          <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">

          <div class="delivery-options">
            <strong>Delivery option (BobGo)</strong>
            <?php foreach ($deliveryOptions as $key => $opt): ?>
              <label>
                <input type="radio" name="delivery" value="<?php echo $key; ?>" <?php echo $key === 'pudu' ? 'checked' : ''; ?>>
                <?php echo htmlspecialchars($opt['label']); ?> — R <?php echo number_format($opt['price_cents'] / 100, 2); ?>
              </label>
            <?php endforeach; ?>
          </div>

          <div class="totals" id="totalsBox" aria-live="polite">
            <?php
              $productCents = (int)$product['price'];
              $deliveryCents = $deliveryOptions['pudu']['price_cents'];
              $serviceFee = (int)round($productCents * SERVICE_FEE_RATE);
              $totalCents = $productCents + $deliveryCents + $serviceFee;
            ?>
            <div><span>Product</span><span>R <?php echo cents_to_rands($productCents); ?></span></div>
            <div id="deliveryRow"><span>Delivery</span><span>R <?php echo cents_to_rands($deliveryCents); ?></span></div>
            <div><span>Service fee (<?php echo (SERVICE_FEE_RATE * 100); ?>%)</span><span>R <?php echo cents_to_rands($serviceFee); ?></span></div>
            <hr>
            <div style="font-weight:700"><span>Total</span><span id="totalAmount">R <?php echo cents_to_rands($totalCents); ?></span></div>
          </div>

          <div style="margin-top:14px">
            <button id="payBtn" type="button" class="btn-pay">
              <span id="payBtnText">Pay with PayFast</span>
              <span id="paySpinner" style="display:none" class="spinner" aria-hidden="true"></span>
            </button>
            <div class="note">PayFast will handle secure payment. You will be redirected to PayFast to complete payment.</div>
          </div>

          <div class="note">
            Example PayFast response link (simulate IPN): 
            <a id="simLink" class="link-sim" href="/api/payfast_notify.php?order_id=EXAMPLE&status=COMPLETE">Simulate PayFast response</a>
          </div>
        </form>
      </div>
    </div>
  </main>

  <script>
  (function () {
    const deliveryOptions = {
      <?php foreach ($deliveryOptions as $k => $v) {
        echo "'" . $k . "': " . ((int)$v['price_cents']) . ",";
      } ?>
    };
    const SERVICE_FEE_RATE = <?php echo SERVICE_FEE_RATE; ?>;
    const productCents = <?php echo (int)$product['price']; ?>;

    const radios = document.querySelectorAll('input[name="delivery"]');
    const deliveryRow = document.getElementById('deliveryRow');
    const totalAmountEl = document.getElementById('totalAmount');
    const payBtn = document.getElementById('payBtn');
    const paySpinner = document.getElementById('paySpinner');
    const payBtnText = document.getElementById('payBtnText');
    const simLink = document.getElementById('simLink');

    function updateTotals() {
      let selected = document.querySelector('input[name="delivery"]:checked').value;
      let deliveryCents = deliveryOptions[selected] || 0;
      let serviceFee = Math.round(productCents * SERVICE_FEE_RATE);
      let total = productCents + deliveryCents + serviceFee;
      deliveryRow.innerHTML = '<span>Delivery</span><span>R ' + centsToRands(deliveryCents) + '</span>';
      totalAmountEl.textContent = 'R ' + centsToRands(total);
    }

    radios.forEach(r => r.addEventListener('change', updateTotals));
    updateTotals();

    // Create order and redirect to PayFast (via create_order API)
    payBtn.addEventListener('click', async function () {
      // disable button and show spinner for 30s to prevent double-buying
      payBtn.disabled = true;
      paySpinner.style.display = 'inline-block';
      payBtnText.textContent = 'Processing...';

      // Re-enable after 30 seconds
      setTimeout(() => {
        payBtn.disabled = false;
        paySpinner.style.display = 'none';
        payBtnText.textContent = 'Pay with PayFast';
      }, 30000);

      // Build form data
      const form = document.getElementById('checkoutForm');
      const fd = new FormData(form);
      // include selected delivery
      const selected = document.querySelector('input[name="delivery"]:checked').value;
      fd.set('delivery', selected);

      try {
        const res = await fetch('/api/create_order.php', {
          method: 'POST',
          body: fd,
          credentials: 'same-origin',
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        const json = await res.json();
        if (!res.ok) {
          alert(json.error || 'Failed to create order');
          return;
        }

        // Update simulate link to point to the real order notify URL for testing
        if (simLink) {
          simLink.href = '/api/payfast_notify.php?order_id=' + encodeURIComponent(json.order_id) + '&status=COMPLETE';
          simLink.textContent = 'Simulate PayFast response for order ' + json.order_id;
        }

        // Redirect user to PayFast (or to the returned payfast_url)
        // In production you'd redirect to PayFast's payment page. Here we redirect to the provided URL.
        if (json.payfast_url) {
          window.location.href = json.payfast_url;
        } else {
          // fallback: show message
          alert('Order created. Please complete payment via PayFast.');
        }
      } catch (err) {
        console.error(err);
        alert('Network error while creating order');
      }
    });
  })();
  </script>
</body>
</html>
