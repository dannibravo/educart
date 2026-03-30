<?php
// ============================================================
//  api/auth.php
//  POST /api/auth.php?action=login
//  POST /api/auth.php?action=register
// ============================================================
require_once __DIR__ . '/config.php';

$action = $_GET['action'] ?? '';
$data   = body();

// ── LOGIN ─────────────────────────────────────────────────
if ($action === 'login') {
    require_fields($data, ['username', 'password']);

    $stmt = db()->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$data['username']]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($data['password'], $user['password'])) {
        respond(['error' => 'Invalid username or password'], 401);
    }

    respond([
        'success' => true,
        'user' => [
            'id'       => $user['id'],
            'username' => $user['username'],
            'name'     => $user['name'],
            'email'    => $user['email'],
            'role'     => $user['role'],
        ]
    ]);
}

// ── REGISTER ──────────────────────────────────────────────
if ($action === 'register') {
    require_fields($data, ['username', 'password', 'name', 'email']);

    // Check for duplicate username
    $chk = db()->prepare('SELECT id FROM users WHERE username = ?');
    $chk->execute([$data['username']]);
    if ($chk->fetch()) {
        respond(['error' => 'Username already taken'], 409);
    }

    // Check for duplicate email
    $chk2 = db()->prepare('SELECT id FROM users WHERE email = ?');
    $chk2->execute([$data['email']]);
    if ($chk2->fetch()) {
        respond(['error' => 'Email already registered'], 409);
    }

    $hash = password_hash($data['password'], PASSWORD_BCRYPT);

    $ins = db()->prepare(
        'INSERT INTO users (username, password, name, email, role)
         VALUES (?, ?, ?, ?, "customer")'
    );
    $ins->execute([
        $data['username'],
        $hash,
        $data['name'],
        $data['email'],
    ]);

    respond(['success' => true, 'message' => 'Account created. Please login.'], 201);
}

respond(['error' => 'Invalid action'], 400);
