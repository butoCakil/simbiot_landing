<?php
// admin/auth.php — Session, CSRF, dan koneksi DB untuk area admin

require_once dirname(__DIR__) . '/config.php';

// ----------------------------------------------------------------
// Session
// ----------------------------------------------------------------

function startAdminSession(): void {
    if (session_status() !== PHP_SESSION_NONE) return;
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,                         // hilang saat browser tutup
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),  // HTTPS only jika tersedia
        'httponly' => true,                      // tidak bisa diakses JS
        'samesite' => 'Strict',
    ]);
    session_start();
}

function requireLogin(): void {
    startAdminSession();

    if (empty($_SESSION['admin_id'])) {
        header('Location: /admin/login.php');
        exit;
    }
    // Session timeout
    if (isset($_SESSION['last_activity'])
        && (time() - $_SESSION['last_activity']) > SESSION_LIFETIME) {
        session_unset();
        session_destroy();
        header('Location: /admin/login.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

// ----------------------------------------------------------------
// CSRF
// ----------------------------------------------------------------

function csrfToken(): string {
    startAdminSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    startAdminSession();
    $token     = $_POST['csrf_token'] ?? '';
    $stored    = $_SESSION['csrf_token'] ?? '';
    if (!$stored || !hash_equals($stored, $token)) {
        http_response_code(403);
        exit('403 — CSRF token tidak valid.');
    }
}

// ----------------------------------------------------------------
// Database (singleton)
// ----------------------------------------------------------------

function getDB(): PDO {
    static $db = null;
    if ($db === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        $db  = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $db;
}
