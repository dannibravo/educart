<?php
// ============================================================
//  api/stats.php
//  GET /api/stats.php   – dashboard stats for admin overview
// test if git is working
// ============================================================
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(['error' => 'Method not allowed'], 405);
}

$pdo = db();

$totalProducts = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$totalOrders   = (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$totalUsers    = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$revenue       = (float)$pdo->query(
    "SELECT COALESCE(SUM(total),0) FROM orders WHERE status != 'Cancelled'"
)->fetchColumn();

// Recent 5 orders
$recent = $pdo->query(
    'SELECT o.order_code, o.customer, o.total, o.status, o.created_at,
            SUM(oi.qty) AS total_items
     FROM orders o
     LEFT JOIN order_items oi ON oi.order_id = o.id
     GROUP BY o.id
     ORDER BY o.created_at DESC
     LIMIT 5'
)->fetchAll();

respond([
    'total_products' => $totalProducts,
    'total_orders'   => $totalOrders,
    'total_users'    => $totalUsers,
    'revenue'        => $revenue,
    'recent_orders'  => $recent,
]);
