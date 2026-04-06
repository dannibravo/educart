<?php
// ============================================================
//  api/users.php
//  GET /api/users.php        – list all users (admin only)
//  GET /api/users.php?id=3   – single user
// ============================================================
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($method === 'GET') {
    if ($id) {
        $stmt = db()->prepare(
            'SELECT id, username, name, email, role, created_at FROM users WHERE id = ?'
        );
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        $user ? respond($user) : respond(['error' => 'User not found'], 404);
    }

    // List all with order count
    $stmt = db()->query(
        'SELECT u.id, u.username, u.name, u.email, u.role, u.created_at,
                COUNT(o.id) AS order_count
         FROM users u
         LEFT JOIN orders o ON o.email = u.email
         GROUP BY u.id
         ORDER BY u.id ASC'
    );
    respond($stmt->fetchAll());
}

respond(['error' => 'Method not allowed'], 405);
