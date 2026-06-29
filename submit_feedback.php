<?php
// submit_feedback.php — AJAX handler + rate limiting
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// ----------------------------------------------------------------
// Rate limiting
// ----------------------------------------------------------------

function getClientIP(): string {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return filter_var($_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP) ?: '0.0.0.0';
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        return filter_var($ip, FILTER_VALIDATE_IP) ?: '0.0.0.0';
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function checkRateLimit(PDO $db, string $ip, int $max = 5, int $windowMinutes = 60): bool {
    // Returns true = boleh lanjut, false = terlalu banyak
    $db->exec("CREATE TABLE IF NOT EXISTS `rate_limit` (
        `ip`           VARCHAR(45) NOT NULL,
        `endpoint`     VARCHAR(50) NOT NULL,
        `count`        INT         NOT NULL DEFAULT 1,
        `window_start` DATETIME    NOT NULL,
        PRIMARY KEY (`ip`, `endpoint`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $endpoint = 'feedback';

    $stmt = $db->prepare("SELECT count, window_start FROM rate_limit WHERE ip = ? AND endpoint = ?");
    $stmt->execute([$ip, $endpoint]);
    $row  = $stmt->fetch();

    $windowStart = new DateTime("-{$windowMinutes} minutes");

    if (!$row) {
        $db->prepare("INSERT INTO rate_limit (ip, endpoint, count, window_start) VALUES (?, ?, 1, NOW())")
           ->execute([$ip, $endpoint]);
        return true;
    }

    // Window sudah lewat — reset
    if (new DateTime($row['window_start']) < $windowStart) {
        $db->prepare("UPDATE rate_limit SET count = 1, window_start = NOW() WHERE ip = ? AND endpoint = ?")
           ->execute([$ip, $endpoint]);
        return true;
    }

    if ((int)$row['count'] >= $max) {
        return false;
    }

    $db->prepare("UPDATE rate_limit SET count = count + 1 WHERE ip = ? AND endpoint = ?")
       ->execute([$ip, $endpoint]);
    return true;
}

// ----------------------------------------------------------------
// Validasi input
// ----------------------------------------------------------------

$name    = trim($_POST['name']    ?? '');
$role    = trim($_POST['role']    ?? '');
$message = trim($_POST['message'] ?? '');

$valid_roles = ['siswa', 'guru', 'mahasiswa', 'dosen', 'pengembang', 'hobi', 'lainnya'];
$errors      = [];

if (strlen($name) < 2)                      $errors[] = 'Nama minimal 2 karakter.';
if (strlen($name) > 100)                    $errors[] = 'Nama terlalu panjang.';
if (!in_array($role, $valid_roles, true))   $errors[] = 'Pilih peran yang valid.';
if (strlen($message) < 10)                  $errors[] = 'Pesan minimal 10 karakter.';
if (strlen($message) > 2000)               $errors[] = 'Pesan maksimal 2000 karakter.';

if ($errors) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// ----------------------------------------------------------------
// Simpan ke DB
// ----------------------------------------------------------------

try {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
    $db  = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Cek rate limit
    $ip = getClientIP();
    if (!checkRateLimit($db, $ip)) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => 'Terlalu banyak pengiriman. Coba lagi dalam 1 jam.',
        ]);
        exit;
    }

    $stmt = $db->prepare("INSERT INTO feedback (name, role, message) VALUES (?, ?, ?)");
    $stmt->execute([$name, $role, $message]);

    echo json_encode([
        'success' => true,
        'message' => 'Terima kasih! Masukan kamu sudah diterima dan akan segera ditinjau.',
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan. Silakan coba lagi.']);
}