# EduCart – Backend Setup Guide

## Folder Structure
Place everything inside your XAMPP/WAMP htdocs folder:

```
htdocs/
└── educart/
    ├── index.html          ← your frontend
    └── api/
        ├── config.php      ← DB connection
        ├── auth.php        ← login / register
        ├── products.php    ← product CRUD
        ├── orders.php      ← place & manage orders
        ├── users.php       ← user list
        └── stats.php       ← admin dashboard stats
```

---

## Step 1 – Import the Database

1. Open **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Click **Import** (top menu)
3. Choose the file: `educart.sql`
4. Click **Go**

This creates the `educart_db` database with all tables and seed data.

---

## Step 2 – Configure the Connection

Open `api/config.php` and edit if needed:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'educart_db');
define('DB_USER', 'root');   // your phpMyAdmin username
define('DB_PASS', '');       // your phpMyAdmin password (blank by default)
```

---

## Step 3 – Set the API URL in index.html

Near the top of the `<script>` block in `index.html`, find:

```js
const API = 'http://localhost/educart/api';
```

Change `educart` if your folder has a different name.

---

## Step 4 – Start XAMPP

Make sure both **Apache** and **MySQL** are running in the XAMPP Control Panel.

---

## Step 5 – Open the App

Visit: `http://localhost/educart/index.html`

Login credentials (from seed data):
| Username | Password | Role     |
|----------|----------|----------|
| student  | 1234     | customer |
| admin    | admin    | admin    |

---

## API Endpoints Reference

| Method | Endpoint                         | Description              |
|--------|----------------------------------|--------------------------|
| POST   | api/auth.php?action=login        | Login                    |
| POST   | api/auth.php?action=register     | Register                 |
| GET    | api/products.php                 | List all products        |
| GET    | api/products.php?id=5            | Get single product       |
| GET    | api/products.php?q=pen           | Search products          |
| POST   | api/products.php                 | Add product (admin)      |
| PUT    | api/products.php?id=5            | Edit product (admin)     |
| DELETE | api/products.php?id=5            | Delete product (admin)   |
| GET    | api/orders.php                   | List all orders (admin)  |
| GET    | api/orders.php?user_id=3         | Orders by user           |
| POST   | api/orders.php                   | Place new order          |
| PUT    | api/orders.php?id=5              | Update order status      |
| GET    | api/users.php                    | List all users (admin)   |
| GET    | api/stats.php                    | Admin dashboard stats    |

---

## Notes

- Passwords are hashed with **bcrypt** (PHP `password_hash`)
- Stock is automatically deducted when an order is placed
- Orders use a **database transaction** to prevent race conditions
- The `educart.sql` seed file includes the correct bcrypt hashes for the demo accounts
