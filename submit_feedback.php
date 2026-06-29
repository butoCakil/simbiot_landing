<?php
// submit_feedback.php — AJAX handler form feedback
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$name    = trim($_POST['name']    ?? '');
$role    = trim($_POST['role']    ?? '');
$message = trim($_POST['message'] ?? '');

$valid_roles = ['siswa', 'guru', 'mahasiswa_dosen', 'pengembang'];
$errors      = [];

if (strlen($name) < 2)            $errors[] = 'Nama minimal 2 karakter.';
if (strlen($name) > 100)          $errors[] = 'Nama terlalu panjang.';
if (!in_array($role, $valid_roles, true)) $errors[] = 'Pilih peran yang valid.';
if (strlen($message) < 10)        $errors[] = 'Pesan minimal 10 karakter.';
if (strlen($message) > 2000)      $errors[] = 'Pesan maksimal 2000 karakter.';

if ($errors) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

try {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
    $db  = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
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
