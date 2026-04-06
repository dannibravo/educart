-- ============================================================
--  EduCart Database Schema
--  Import this in phpMyAdmin: database name = educart_db
-- ============================================================

CREATE DATABASE IF NOT EXISTS educart_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE educart_db;

-- ── USERS ──────────────────────────────────────────────────
CREATE TABLE users (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  username   VARCHAR(50)  NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,          -- bcrypt hash
  name       VARCHAR(100) NOT NULL,
  email      VARCHAR(100) NOT NULL UNIQUE,
  role       ENUM('customer','admin') DEFAULT 'customer',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── PRODUCTS ───────────────────────────────────────────────
CREATE TABLE products (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(150) NOT NULL,
  category   VARCHAR(80)  NOT NULL,
  price      DECIMAL(10,2) NOT NULL,
  stock      INT NOT NULL DEFAULT 0,
  icon       VARCHAR(10)  DEFAULT '📦',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── ORDERS ─────────────────────────────────────────────────
CREATE TABLE orders (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  order_code   VARCHAR(20) NOT NULL UNIQUE,   -- e.g. EC-1001
  user_id      INT,
  customer     VARCHAR(100) NOT NULL,
  email        VARCHAR(100) NOT NULL,
  phone        VARCHAR(30)  NOT NULL,
  address      TEXT         NOT NULL,
  notes        TEXT,
  payment      VARCHAR(50)  NOT NULL,
  total        DECIMAL(10,2) NOT NULL,
  status       ENUM('Pending','Processing','Completed','Cancelled') DEFAULT 'Pending',
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ── ORDER ITEMS ────────────────────────────────────────────
CREATE TABLE order_items (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  order_id   INT NOT NULL,
  product_id INT,
  name       VARCHAR(150) NOT NULL,   -- snapshot at time of order
  price      DECIMAL(10,2) NOT NULL,
  qty        INT NOT NULL,
  icon       VARCHAR(10),
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- ── SEED DATA ──────────────────────────────────────────────
-- Default admin (password: admin)
INSERT INTO users (username, password, name, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 'admin@educart.com', 'admin');

-- Default student (password: 1234)
INSERT INTO users (username, password, name, email, role) VALUES
('student', '$2y$10$TKh8H1.PkD/VAu9VSKkMuuV8OiPpCo/5r2MiRsygt2/MeRrqtCVmy', 'Juan Dela Cruz', 'juan@email.com', 'customer');

-- NOTE: The hashes above use the "password" bcrypt cost=10 defaults.
-- admin  → password: admin
-- student → password: 1234

INSERT INTO products (name, category, price, stock, icon) VALUES
('Composition Notebook', 'Notebooks',      49.00,  50, '📓'),
('Spiral Notebook',      'Notebooks',      35.00,  80, '📔'),
('Ballpen (Black)',       'Pens & Pencils', 12.00, 200, '🖊️'),
('Mongol Pencil #2',     'Pens & Pencils',  8.00, 150, '✏️'),
('Ruler (30cm)',          'Organizers',     25.00,  60, '📏'),
('Scotch Tape',           'Paper Products', 18.00,  90, '🗂️'),
('Watercolor Set',        'Art Supplies',   95.00,  30, '🎨'),
('Colored Pencils (12s)', 'Art Supplies',   65.00,  40, '🖍️'),
('Bond Paper (Short)',    'Paper Products', 140.00,  25, '📄'),
('Scientific Calculator', 'Calculators',   450.00,  15, '🧮'),
('Highlighter Set',       'Pens & Pencils', 55.00,  70, '🖌️'),
('Binder Clip Set',       'Organizers',     30.00, 100, '📎');
