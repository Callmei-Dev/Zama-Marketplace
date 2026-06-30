CREATE DATABASE IF NOT EXISTS zama_marketplace CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE zama_marketplace;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user','seller','admin','suspended') DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) UNIQUE NOT NULL
);

INSERT INTO categories (name, slug) VALUES
('Tops','tops'),('Hats','hats'),('Jewellery','jewellery'),
('Beauty','beauty'),('Jackets','jackets'),('Books','books'),
('Electronics','electronics'),('Games','games'),('Fitness','fitness');

CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(255) NOT NULL,
  category_id INT NOT NULL,
  price INT NOT NULL, -- price in cents (ZAR cents)
  `condition` VARCHAR(50),
  days_ago INT DEFAULT 0,
  status ENUM('available','sold','removed') DEFAULT 'available',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
  INDEX (created_at)
);

CREATE TABLE product_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  file_path VARCHAR(500) NOT NULL,
  is_main TINYINT(1) DEFAULT 0,
  file_order INT DEFAULT 0,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE favourites (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  product_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY (user_id, product_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  buyer_id INT NOT NULL,
  seller_id INT NOT NULL,
  amount INT NOT NULL, -- product price in cents
  service_fee INT NOT NULL, -- 8% in cents
  total_amount INT NOT NULL, -- amount + service_fee
  status ENUM('pending','paid','failed','cancelled') DEFAULT 'pending',
  payment_provider VARCHAR(100),
  payment_reference VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id),
  FOREIGN KEY (buyer_id) REFERENCES users(id),
  FOREIGN KEY (seller_id) REFERENCES users(id)
);

CREATE TABLE reports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT,
  reporter_id INT NOT NULL,
  reported_user_id INT,
  reason VARCHAR(255) NOT NULL,
  details TEXT,
  status ENUM('open','reviewing','resolved','dismissed') DEFAULT 'open',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id),
  FOREIGN KEY (reporter_id) REFERENCES users(id),
  FOREIGN KEY (reported_user_id) REFERENCES users(id)
);
