<?php
// ============================================================
//  api/products.php
//  GET    /api/products.php              – list all products
//  GET    /api/products.php?id=5         – single product
//  POST   /api/products.php              – add product (admin)
//  PUT    /api/products.php?id=5         – edit product (admin)
//  DELETE /api/products.php?id=5         – delete product (admin)
// ============================================================
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

// ── GET (list or single) ──────────────────────────────────
if ($method === 'GET') {
    if ($id) {
        $stmt = db()->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([$id]);
        $p = $stmt->fetch();
        $p ? respond($p) : respond(['error' => 'Product not found'], 404);
    }

    // Optional: filter by category or search query
    $cat    = $_GET['category'] ?? '';
    $search = $_GET['q'] ?? '';

    $sql    = 'SELECT * FROM products WHERE 1=1';
    $params = [];

    if ($cat) {
        $sql .= ' AND category = ?';
        $params[] = $cat;
    }
    if ($search) {
        $sql .= ' AND (name LIKE ? OR category LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    $sql .= ' ORDER BY id ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    respond($stmt->fetchAll());
}

// ── POST (add) ────────────────────────────────────────────
if ($method === 'POST') {
    $data = body();
    require_fields($data, ['name', 'category', 'price', 'stock']);

    $icons = [
        'Notebooks'      => '📓',
        'Pens & Pencils' => '✏️',
        'Art Supplies'   => '🎨',
        'Paper Products' => '📄',
        'Organizers'     => '📁',
        'Calculators'    => '🧮',
    ];
    $icon = $data['icon'] ?? ($icons[$data['category']] ?? '📦');

    $stmt = db()->prepare(
        'INSERT INTO products (name, category, price, stock, icon)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $data['name'],
        $data['category'],
        (float)$data['price'],
        (int)$data['stock'],
        $icon,
    ]);

    $newId = (int)db()->lastInsertId();
    $row   = db()->prepare('SELECT * FROM products WHERE id = ?');
    $row->execute([$newId]);
    respond($row->fetch(), 201);
}

// ── PUT (update) ──────────────────────────────────────────
if ($method === 'PUT') {
    if (!$id) respond(['error' => 'id required'], 400);
    $data = body();

    // Build dynamic SET clause from provided fields
    $allowed = ['name', 'category', 'price', 'stock', 'icon'];
    $sets    = [];
    $params  = [];

    foreach ($allowed as $field) {
        if (array_key_exists($field, $data)) {
            $sets[]   = "$field = ?";
            $params[] = $data[$field];
        }
    }

    if (empty($sets)) respond(['error' => 'Nothing to update'], 400);

    $params[] = $id;
    $stmt = db()->prepare('UPDATE products SET ' . implode(', ', $sets) . ' WHERE id = ?');
    $stmt->execute($params);

    $row = db()->prepare('SELECT * FROM products WHERE id = ?');
    $row->execute([$id]);
    respond($row->fetch());
}

// ── DELETE ────────────────────────────────────────────────
if ($method === 'DELETE') {
    if (!$id) respond(['error' => 'id required'], 400);
    $stmt = db()->prepare('DELETE FROM products WHERE id = ?');
    $stmt->execute([$id]);
    respond(['success' => true, 'deleted_id' => $id]);
}

respond(['error' => 'Method not allowed'], 405);
