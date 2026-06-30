<?php
// public/login.php
require_once __DIR__ . '/../includes/csrf.php';
$token = csrf_token();
$errorMsg = '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Login - Zama Marketplace</title>
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
      box-shadow: 0 10px 20px rgba(0,0,0,0.15); /* 10pt shadow */
      padding: 24px;
      text-align: left;
    }
    h1 {
      text-align: center;
      color: #001f3f;
      margin-bottom: 20px;
    }
    form {
      font-size: 1.3em; /* slightly larger text */
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
  </style>
</head>
<body>
  <div class="card">
    <h1>Login</h1>

    <?php if ($errorMsg): ?>
      <div class="error" role="alert"><?php echo $errorMsg; ?></div>
    <?php endif; ?>

    <!-- Normal form POST to api/login.php. Server will redirect on success/failure. -->
    <form id="loginForm" method="post" action="/api/login.php">
      <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

      <label>Username or Email
        <input name="username_or_email" placeholder="Enter username or email" required>
      </label>

      <label>Password
        <input name="password" type="password" placeholder="Enter password" required>
      </label>

      <button type="submit">Login</button>
    </form>

    <div id="result"></div>
  </div>

  <script>
  document.getElementById('loginForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const form = e.target;
    const data = new FormData(form);
    const res = await fetch(form.action, { method: 'POST', body: data });
    const json = await res.json();
    const out = document.getElementById('result');
    if (!res.ok) {
      out.style.color = 'red';
      out.textContent = json.error || 'Login failed';
      return;
    }
    out.style.color = 'green';
    out.textContent = 'Login successful';
    window.location.href = '/';
  });
  </script>
</body>
</html>

