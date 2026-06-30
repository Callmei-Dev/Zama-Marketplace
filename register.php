<?php
// public/register.php
require_once __DIR__ . '/../includes/csrf.php';
$token = csrf_token();

// Optional: show server-side error passed via query string
$errorMsg = '';
if (!empty($_GET['error'])) {
    $errorMsg = htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Register - Zama Marketplace</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="assets/css/main.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="assets/favicon-32x32.png">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: #f4f4f4;
      margin: 0;
      padding: 20px;
      text-align: center;
    }
    .card {
      max-width: 520px;
      margin: 60px auto;
      background: #ffffffc9;
      border-radius: 12px;
      box-shadow: 0 10px 20px rgba(0,0,0,0.15);
      padding: 24px;
      text-align: left;
    }
    h1 {
      text-align: center;
      color: #001f3f;
      margin-bottom: 20px;
    }
    form {
      font-size: 1.3em;
    }
    input, button {
      font-family: 'Inter', sans-serif;
      font-size: 1.3em;
      padding: 10px;
      margin-top: 6px;
      margin-bottom: 16px;
      width: 100%;
      box-sizing: border-box;
      border-radius: 6px;
      border: 1px solid #ccc;
    }
    input::placeholder { color: #999; }
    input:focus::placeholder { color: transparent; }
    button {
      background: #001f3f;
      color: #fff;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
    }
    button:hover { background: #4da3ff; }
    #result {
      margin-top: 12px;
      font-size: 1.1em;
      text-align: center;
    }
    .error {
      color: #b91c1c;
      background: #fff1f2;
      padding: 10px;
      border-radius: 6px;
      margin-bottom: 12px;
      border: 1px solid #fecaca;
    }
  </style>
</head>
<body>
  <div class="card">
    <h1>Register</h1>

    <?php if ($errorMsg): ?>
      <div class="error" role="alert"><?php echo $errorMsg; ?></div>
    <?php endif; ?>

    <!-- Normal form POST to api/register.php. Server will redirect on success/failure. -->
    <form id="registerForm" method="post" action="/api/register.php" novalidate>
      <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

      <label>Username
        <!-- Disallow @ and . in username to avoid conflict with email parameters -->
        <input name="username" placeholder="Enter username" required minlength="3"
               pattern="^[^@.]{3,}$"
               title="Username must be at least 3 characters and must not contain @ or .">
      </label>

      <label>Email
        <input name="email" type="email" placeholder="Enter email" required>
      </label>

      <label>Password
        <input name="password" type="password" placeholder="7–15 letters or numbers"
               required pattern="[A-Za-z0-9]{7,15}"
               title="Password must be 7–15 letters or numbers">
      </label>

      <button type="submit">Register</button>
    </form>

    <div id="result"></div>
  </div>

  <script>
    // Keep client-side validation friendly: show native validation messages.
    // No AJAX submit — server will redirect to index.php on success.
    (function () {
      var form = document.getElementById('registerForm');
      form.addEventListener('submit', function (e) {
        // Let browser handle validation; this prevents accidental double submits.
        if (!form.checkValidity()) {
          // Allow browser to show validation UI
          return;
        }
        // Normal submit proceeds
      });
    })();
  </script>
</body>
</html>
