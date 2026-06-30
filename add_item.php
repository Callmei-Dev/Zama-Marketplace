<?php
// public/add_item.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

// Ensure user is logged in
if (!is_logged_in()) {
    header('Location: /public/login.php');
    exit;
}

$token = csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Add Item - Zama Marketplace</title>
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
      max-width: 640px;
      margin: 40px auto;
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
      font-size: 1.1em;
    }
    input, select, textarea, button {
      font-family: 'Inter', sans-serif;
      font-size: 1em;
      padding: 10px;
      margin-top: 6px;
      margin-bottom: 16px;
      width: 100%;
      box-sizing: border-box;
      border-radius: 6px;
      border: 1px solid #ccc;
    }
    input::placeholder, textarea::placeholder { color: #999; }
    input:focus::placeholder, textarea:focus::placeholder { color: transparent; }
    button {
      background: #001f3f;
      color: #fff;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
    }
    button:hover { background: #4da3ff; }
    .preview-container {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      margin-top: 20px;
    }
    .preview-container img {
      width: 180px;
      height: 180px;
      object-fit: cover;
      border-radius: 6px;
      border: 1px solid #ccc;
    }
    .progress-bar {
      width: 100%;
      background: #eee;
      border-radius: 6px;
      overflow: hidden;
      margin-top: 12px;
    }
    .progress-bar div {
      height: 18px;
      background: #0a66c2;
      width: 0%;
      transition: width .2s;
    }
    .result {
      margin-top: 12px;
      font-size: 1.05em;
    }
    label { display: block; margin-bottom: 8px; }
    .images-section { margin-top: 20px; }
    .note { font-size: 0.95em; color: #6b7280; margin-top: 8px; }
  </style>
</head>
<body>
  <div class="card">
    <h1>Add Item</h1>

    <form id="addItemForm" method="post" action="/api/add_item.php" enctype="multipart/form-data" novalidate>
      <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

      <label>Name
        <input name="name" placeholder="Enter item name" required minlength="2">
      </label>

      <label>Category
        <select name="category_id" required>
          <option value="">-- Select --</option>
          <option value="1">Tops</option>
          <option value="2">Hats</option>
          <option value="3">Jewellery</option>
          <option value="4">Beauty</option>
          <option value="5">Jackets</option>
          <option value="6">Books</option>
          <option value="7">Electronics</option>
          <option value="8">Games</option>
          <option value="9">Fitness</option>
        </select>
      </label>

      <label>Price (ZAR)
        <input name="price" type="text" placeholder="e.g. 199.99" required pattern="^\d+([.,]\d{1,2})?$" title="Enter a valid price, e.g. 199.99">
      </label>

      <label>Condition
        <input name="condition" type="text" placeholder="New / Used">
      </label>

      <label>Description
        <!-- NOTE: API expects the field named "Description" (capital D) in current implementation -->
        <textarea name="Description" rows="4" placeholder="Extra item details..."></textarea>
      </label>

      <div class="images-section">
        <label>Images (1–3)
          <input type="file" name="images[]" id="imagesInput" accept="image/*" multiple required>
        </label>
        <div class="note">Allowed types: JPG, PNG, WEBP. Max 3 images, max 8MB each.</div>
      </div>

      <div class="preview-container" id="previewContainer" aria-live="polite"></div>

      <div class="progress-bar" aria-hidden="true"><div id="progressFill"></div></div>

      <button type="submit">Add Item</button>
    </form>

    <div id="result" class="result" role="status" aria-live="polite"></div>
  </div>

  <script>
  (function () {
    const imagesInput = document.getElementById('imagesInput');
    const previewContainer = document.getElementById('previewContainer');
    const form = document.getElementById('addItemForm');
    const progressFill = document.getElementById('progressFill');
    const resultDiv = document.getElementById('result');

    // Preview selected images (limit to 3)
    imagesInput.addEventListener('change', () => {
      previewContainer.innerHTML = '';
      const files = imagesInput.files;
      if (!files) return;
      Array.from(files).slice(0,3).forEach(file => {
        if (!file.type.startsWith('image/')) return;
        const reader = new FileReader();
        reader.onload = e => {
          const img = document.createElement('img');
          img.src = e.target.result;
          img.alt = file.name;
          previewContainer.appendChild(img);
        };
        reader.readAsDataURL(file);
      });
    });

    // AJAX submit with progress
    form.addEventListener('submit', e => {
      e.preventDefault();

      // Basic client-side validation
      const files = imagesInput.files;
      if (!files || files.length < 1) {
        resultDiv.style.color = 'red';
        resultDiv.textContent = 'Please select at least one image.';
        return;
      }
      if (files.length > 3) {
        resultDiv.style.color = 'red';
        resultDiv.textContent = 'You can upload up to 3 images only.';
        return;
      }

      // Validate price pattern
      const priceInput = form.querySelector('input[name="price"]');
      if (priceInput && !priceInput.checkValidity()) {
        resultDiv.style.color = 'red';
        resultDiv.textContent = priceInput.title || 'Please enter a valid price.';
        return;
      }

      const data = new FormData(form);
      const xhr = new XMLHttpRequest();
      xhr.open('POST', form.action, true);

      xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

      xhr.upload.onprogress = e => {
        if (e.lengthComputable) {
          const percent = (e.loaded / e.total) * 100;
          progressFill.style.width = percent + '%';
        }
      };

      xhr.onload = () => {
        progressFill.style.width = '0%';
        if (xhr.status === 200) {
          try {
            const json = JSON.parse(xhr.responseText);
            if (json && json.success) {
              resultDiv.style.color = 'green';
              resultDiv.textContent = 'Item added successfully (ID ' + json.product_id + ')';
              form.reset();
              previewContainer.innerHTML = '';
            } else {
              resultDiv.style.color = 'red';
              resultDiv.textContent = json.error || 'Upload failed';
            }
          } catch (err) {
            resultDiv.style.color = 'red';
            resultDiv.textContent = 'Unexpected server response';
          }
        } else {
          try {
            const json = JSON.parse(xhr.responseText);
            resultDiv.style.color = 'red';
            resultDiv.textContent = json.error || (json.errors ? json.errors.join('; ') : 'Upload failed');
          } catch {
            resultDiv.style.color = 'red';
            resultDiv.textContent = 'Upload failed';
          }
        }
      };

      xhr.onerror = () => {
        progressFill.style.width = '0%';
        resultDiv.style.color = 'red';
        resultDiv.textContent = 'Network error';
      };

      // Remove previous messages
      resultDiv.textContent = '';
      xhr.send(data);
    });
  })();
  </script>
</body>
</html>
