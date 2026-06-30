<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';

// User logged in check
if (!is_logged_in()) {
    header('Location: /login.php');
    exit;
}

// Get current user info from session or auth helper
//$user = user_id(); 
//$token = csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,minimum-scale=1" />
  <title>Profile - Zama Marketplace</title>
  <link rel="stylesheet" href="/assets/css/main.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; background:#f5f7fa; margin:0; padding:28px; color:#111827; }
    .container { max-width:760px; margin:0 auto; }
    .card { background:#fff; border-radius:10px; padding:20px; box-shadow:0 6px 18px rgba(10,42,102,0.06); }
    h1 { color:#0a2a66; margin:0 0 12px 0; }
    .field { margin:12px 0; }
    .label { font-weight:600; color:#374151; margin-bottom:6px; display:block; }
    .value { background:#f8fafc; padding:10px; border-radius:6px; border:1px solid #e6eefc; color:#0b254f; }
    .actions { margin-top:18px; display:flex; gap:12px; align-items:center; }
    .btn { padding:10px 14px; border-radius:8px; text-decoration:none; color:#fff; background:#0a2a66; border:none; cursor:pointer; font-weight:600; }
    .btn.secondary { background:#6b7280; }
    .btn.danger { background:#b91c1c; }
    .note { margin-top:12px; color:#6b7280; font-size:0.95em; }
  </style>
</head>
<body>
  <main class="container">
    <div class="card">
      <h1>Your Profile</h1>

      <div class="field">
        <span class="label">Username</span>
        <div class="value" id="username"><?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></div>
      </div>

      <div class="field">
        <span class="label">Email address</span>
        <div class="value" id="email"><?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
      </div>

      <div class="field">
        <span class="label">Member since</span>
        <div class="value"><?php echo htmlspecialchars($user['created_at'] ?? ''); ?></div>
      </div>

      <div class="actions">
        <a class="btn" href="/edit_profile.php">Edit profile</a>
        

        <form id="deleteForm" method="post" action="/api/delete_account.php" style="display:inline;">
          <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
          <button type="button" id="deleteBtn" class="btn danger">Delete account</button>
        </form>
      </div>

      <div class="note">
        Deleting your account will remove your personal information, your favourites, and any product listings you created. This action cannot be reversed.
      </div>
    </div>
  </main>

  <script>
    (function () {
      const deleteBtn = document.getElementById('deleteBtn');
      const deleteForm = document.getElementById('deleteForm');

      deleteBtn.addEventListener('click', function () {
        const confirmed = confirm('Are you sure you want to permanently delete your account and all associated data? This cannot be undone.');
        if (!confirmed) return;

        const formData = new FormData(deleteForm);
        fetch(deleteForm.action, {
          method: 'POST',
          body: formData,
          credentials: 'same-origin',
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(async res => {
          const json = await res.json().catch(()=>({ error: 'Unexpected response' }));
          if (!res.ok) {
            alert(json.error || 'Retry: Failed to delete account');
            return;
          }
          
          window.location.href = json.redirect || 'http:/../public/index.php/';
        }).catch(() => {
          alert('Network error while attempting to delete account');
        });
      });
    })();
  </script>
</body>
</html>
