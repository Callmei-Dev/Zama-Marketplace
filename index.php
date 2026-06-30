<?php
// public/index.php
require_once __DIR__ . '/../includes/auth.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, minimum-scale=1" />
  <title>Zama Marketplace</title>
  <link rel="stylesheet" href="assets/css/main.css">
  <link rel="icon" type="image/png" href="assets/favicon-32x32.png">
    
  
</head>
<body>
  <header class="site-header" role="banner">
    <div class="header-inner">
      <div class="left">
        <button id="menuToggle" aria-label="Open menu">☰</button>
        <a class="brand" href="http:/../public/index.php">Zama Marketplace</a>
      </div>

      <div class="center">
        <input id="searchInput" type="search" placeholder="Search items by name" aria-label="Search items">
      </div>

      <nav class="right" role="navigation" aria-label="Main navigation">
        <a href="http:/../public/index.php/">Home</a>
        <?php if (!is_logged_in()): ?>
          <a href="http:/../public/login">Login</a>
          <a href="http:/../public/register">Register</a>
        <?php else: ?>
          <a href="http:/../puclic/profile">Profile</a>
          <a href="logout">Logout</a>
        <?php endif; ?>
      </nav>
    </div>

    <div class="category-bar" id="categoryBar" role="navigation" aria-label="Categories">
      <div class="categories" id="categoriesContainer">
        <!-- categories inserted by JS -->
      </div>
    </div>
  </header>

  <main>
    <div class="ad-banner">
      <div class="ad-placeholder">Ad banner placeholder</div>
    </div>

    <section class="product-grid" id="productGrid" aria-live="polite">
      
    </section>  

    <div id="pagination" class="pagination"></div>
  </main>

  <aside id="slideMenu" class="slide-menu" aria-hidden="true" role="dialog" aria-label="Main menu">
    <div class="menu-header">
      <button id="menuClose" class="menu-close" aria-label="Close menu">✕</button>
      <h2 class="menu-title">Menu</h2>
    </div>

    <ul class="menu-list">
      <li><a href="http:/../public/profile">Profile</a></li>
      <li><a href="http:/../public/favourites">Favourites</a></li>
      <li><a href="http:/../public/my_shop">My shop</a></li>
      <li><a href="http:/../public/add_item.php">Add an item</a></li>
    </ul>

    <div class="menu-actions">
      <button id="themeToggleBtn" class="btn theme-toggle" aria-pressed="false">Switch to Dark</button>
    </div>

    <div class="report-bottom">
      <a href="#" id="http:/../public/makeReport">Make a report</a>
    </div>
  </aside>

  <script src="assets/js/theme.js"></script>
  <script src="assets/js/listings.js"></script>
  <script src="assets/js/app.js"></script>
</body>
</html>
