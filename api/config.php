<?php
// ============================================================
//  config.php  –  Database connection + shared helpers
//  Edit DB_HOST / DB_NAME / DB_USER / DB_PASS to match yours
// test if git is working
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'educart_db');
define('DB_USER', 'root');       // default phpMyAdmin user
define('DB_PASS', '');           // default phpMyAdmin has no password

// ── PDO connection (singleton) ────────────────────────────
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

// ── CORS headers (allow frontend JS to call these APIs) ──
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');               // tighten in production
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── Helper: send JSON response ────────────────────────────
function respond(mixed $data, int $code = 200): never {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// ── Helper: read JSON request body ───────────────────────
function body(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}

// ── Helper: require a field from array ───────────────────
function require_fields(array $data, array $fields): void {
    foreach ($fields as $f) {
        if (!isset($data[$f]) || $data[$f] === '') {
            respond(['error' => "Missing required field: $f"], 400);
        }
    }
}
