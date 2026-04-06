<?php
// ============================================================
//  api/orders.php
//  GET  /api/orders.php              – list all orders (admin)
//  GET  /api/orders.php?user_id=3    – orders by user
//  GET  /api/orders.php?id=5         – single order with items
//  POST /api/orders.php              – place new order
//  PUT  /api/orders.php?id=5         – update status (admin)
// test if git is working
// ============================================================
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id'])      ? (int)$_GET['id']      : null;
$uid    = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

// ── GET ───────────────────────────────────────────────────
if ($method === 'GET') {

    // Single order with its items
    if ($id) {
        $stmt = db()->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$id]);
        $order = $stmt->fetch();
        if (!$order) respond(['error' => 'Order not found'], 404);

        $items = db()->prepare('SELECT * FROM order_items WHERE order_id = ?');
        $items->execute([$id]);
        $order['items'] = $items->fetchAll();
        respond($order);
    }

    // All orders for a specific user
    if ($uid) {
        $stmt = db()->prepare(
            'SELECT o.*, GROUP_CONCAT(oi.name SEPARATOR ", ") AS item_names
             FROM orders o
             LEFT JOIN order_items oi ON oi.order_id = o.id
             WHERE o.user_id = ?
             GROUP BY o.id
             ORDER BY o.created_at DESC'
        );
        $stmt->execute([$uid]);
        respond($stmt->fetchAll());
    }

    // All orders (admin)
    $stmt = db()->query(
        'SELECT o.*,
                SUM(oi.qty) AS total_items
         FROM orders o
         LEFT JOIN order_items oi ON oi.order_id = o.id
         GROUP BY o.id
         ORDER BY o.created_at DESC'
    );
    respond($stmt->fetchAll());
}

// ── POST (place order) ────────────────────────────────────
if ($method === 'POST') {
    $data = body();
    require_fields($data, ['customer', 'email', 'phone', 'address', 'payment', 'items']);

    if (empty($data['items'])) {
        respond(['error' => 'Cart is empty'], 400);
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        // Verify stock & calculate total
        $total = 0;
        $itemDetails = [];

        foreach ($data['items'] as $item) {
            $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? FOR UPDATE');
            $stmt->execute([$item['id']]);
            $prod = $stmt->fetch();

            if (!$prod) {
                throw new Exception("Product ID {$item['id']} not found");
            }
            if ($prod['stock'] < $item['qty']) {
                throw new Exception("Not enough stock for: {$prod['name']}");
            }

            $total += $prod['price'] * $item['qty'];
            $itemDetails[] = [
                'product_id' => $prod['id'],
                'name'       => $prod['name'],
                'price'      => $prod['price'],
                'qty'        => $item['qty'],
                'icon'       => $prod['icon'],
            ];
        }

        // Generate order code
        $countRow  = $pdo->query('SELECT COUNT(*) AS c FROM orders')->fetch();
        $orderCode = 'EC-' . (1001 + (int)$countRow['c']);

        // Insert order
        $ins = $pdo->prepare(
            'INSERT INTO orders (order_code, user_id, customer, email, phone, address, notes, payment, total)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $ins->execute([
            $orderCode,
            $data['user_id'] ?? null,
            $data['customer'],
            $data['email'],
            $data['phone'],
            $data['address'],
            $data['notes'] ?? '',
            $data['payment'],
            $total,
        ]);
        $orderId = (int)$pdo->lastInsertId();

        // Insert order items & deduct stock
        foreach ($itemDetails as $item) {
            $insItem = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, name, price, qty, icon)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $insItem->execute([
                $orderId,
                $item['product_id'],
                $item['name'],
                $item['price'],
                $item['qty'],
                $item['icon'],
            ]);

            // Deduct stock
            $deduct = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
            $deduct->execute([$item['qty'], $item['product_id']]);
        }

        $pdo->commit();
        respond(['success' => true, 'order_code' => $orderCode, 'order_id' => $orderId, 'total' => $total], 201);

    } catch (Exception $e) {
        $pdo->rollBack();
        respond(['error' => $e->getMessage()], 400);
    }
}

// ── PUT (update order status) ─────────────────────────────
if ($method === 'PUT') {
    if (!$id) respond(['error' => 'id required'], 400);
    $data = body();
    require_fields($data, ['status']);

    $valid = ['Pending', 'Processing', 'Completed', 'Cancelled'];
    if (!in_array($data['status'], $valid)) {
        respond(['error' => 'Invalid status'], 400);
    }

    $stmt = db()->prepare('UPDATE orders SET status = ? WHERE id = ?');
    $stmt->execute([$data['status'], $id]);
    respond(['success' => true, 'id' => $id, 'status' => $data['status']]);
}

respond(['error' => 'Method not allowed'], 405);
