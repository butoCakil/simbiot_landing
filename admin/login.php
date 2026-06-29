<?php
require_once __DIR__ . '/auth.php';
startAdminSession();

if (!empty($_SESSION['admin_id'])) {
    header('Location: /admin/dashboard.php');
    exit;
}

$error   = '';
$timeout = isset($_GET['timeout']);

// ----------------------------------------------------------------
// Brute force protection
// ----------------------------------------------------------------

function getClientIP(): string {
    // Cloudflare kirim IP asli via header ini
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return filter_var($_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP) ?: '0.0.0.0';
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        return filter_var($ip, FILTER_VALIDATE_IP) ?: '0.0.0.0';
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function ensureAttemptsTable(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS `login_attempts` (
        `ip`           VARCHAR(45) NOT NULL,
        `attempts`     INT         NOT NULL DEFAULT 1,
        `last_attempt` DATETIME    NOT NULL,
        `locked_until` DATETIME    NULL,
        PRIMARY KEY (`ip`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function getBruteForceStatus(PDO $db, string $ip): array {
    ensureAttemptsTable($db);
    $stmt = $db->prepare("SELECT attempts, locked_until FROM login_attempts WHERE ip = ?");
    $stmt->execute([$ip]);
    $row  = $stmt->fetch();

    if (!$row) return ['locked' => false, 'remaining_minutes' => 0, 'attempts' => 0];

    if ($row['locked_until'] && new DateTime() < new DateTime($row['locked_until'])) {
        $diff = (new DateTime())->diff(new DateTime($row['locked_until']));
        $mins = $diff->i + ($diff->h * 60) + 1;
        return ['locked' => true, 'remaining_minutes' => $mins, 'attempts' => (int)$row['attempts']];
    }

    return ['locked' => false, 'remaining_minutes' => 0, 'attempts' => (int)$row['attempts']];
}

function recordFailedAttempt(PDO $db, string $ip): void {
    ensureAttemptsTable($db);

    $stmt = $db->prepare("SELECT attempts, locked_until FROM login_attempts WHERE ip = ?");
    $stmt->execute([$ip]);
    $row  = $stmt->fetch();

    if (!$row) {
        $db->prepare("INSERT INTO login_attempts (ip, attempts, last_attempt) VALUES (?, 1, NOW())")
           ->execute([$ip]);
        return;
    }

    // Lockout sudah lewat — reset
    if ($row['locked_until'] && new DateTime() >= new DateTime($row['locked_until'])) {
        $db->prepare("UPDATE login_attempts SET attempts = 1, last_attempt = NOW(), locked_until = NULL WHERE ip = ?")
           ->execute([$ip]);
        return;
    }

    // Masih terkunci — jangan tambah counter
    if ($row['locked_until'] && new DateTime() < new DateTime($row['locked_until'])) {
        return;
    }

    // Tambah counter
    $newAttempts    = $row['attempts'] + 1;
    $lockSQL        = $newAttempts >= 5 ? 'DATE_ADD(NOW(), INTERVAL 15 MINUTE)' : 'NULL';
    $db->prepare("UPDATE login_attempts SET attempts = ?, last_attempt = NOW(), locked_until = $lockSQL WHERE ip = ?")
       ->execute([$newAttempts, $ip]);
}

function clearLoginAttempts(PDO $db, string $ip): void {
    $db->prepare("DELETE FROM login_attempts WHERE ip = ?")->execute([$ip]);
}

// ----------------------------------------------------------------
// Cek status brute force sebelum proses form
// ----------------------------------------------------------------

$ip        = getClientIP();
$bf        = ['locked' => false, 'remaining_minutes' => 0, 'attempts' => 0];
$locked    = false;

try {
    $db  = getDB();
    $bf  = getBruteForceStatus($db, $ip);
    $locked = $bf['locked'];
} catch (PDOException $e) {
    // Skip jika DB belum siap
}

// ----------------------------------------------------------------
// Proses form
// ----------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$locked) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password']      ?? '';

    if (!$username || !$password) {
        $error = 'Username dan password wajib diisi.';
    } else {
        try {
            $db   = getDB();
            $stmt = $db->prepare("SELECT id, username, password_hash FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                clearLoginAttempts($db, $ip);
                session_regenerate_id(true);
                $_SESSION['admin_id']       = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['last_activity']  = time();
                $_SESSION['csrf_token']     = bin2hex(random_bytes(32));

                $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

                header('Location: /admin/dashboard.php');
                exit;
            } else {
                recordFailedAttempt($db, $ip);
                $bf     = getBruteForceStatus($db, $ip);
                $locked = $bf['locked'];
                $error  = $locked
                    ? "Terlalu banyak percobaan gagal. Akses dikunci selama {$bf['remaining_minutes']} menit."
                    : 'Username atau password salah.';
            }
        } catch (PDOException $e) {
            $error = 'Koneksi database bermasalah.';
        }
    }
}

$attempts_left = max(0, 5 - ($bf['attempts'] ?? 0));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Admin — SimbIoT</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: system-ui, -apple-system, sans-serif;
    background: #0A1628; color: #F0F9FF;
    min-height: 100vh; display: flex; align-items: center;
    justify-content: center; padding: 1.5rem;
  }
  .card {
    background: #1E293B; border: 1px solid #2D3D52;
    border-radius: 12px; padding: 2rem; width: 100%; max-width: 400px;
  }
  .logo { font-size: 1.4rem; font-weight: 700; margin-bottom: 0.2rem; }
  .logo span { color: #0D9488; }
  .sub { color: #64748B; font-size: 0.83rem; margin-bottom: 1.75rem; }
  label { display: block; font-size: 0.83rem; color: #94A3B8; margin-bottom: 0.35rem; font-weight: 500; }
  input {
    width: 100%; background: #0A1628; border: 1px solid #2D3D52;
    color: #F0F9FF; padding: 0.625rem 0.875rem; border-radius: 8px;
    font-size: 0.93rem; margin-bottom: 1rem; outline: none; transition: border-color 0.2s;
  }
  input:focus { border-color: #0D9488; }
  input:disabled { opacity: 0.5; cursor: not-allowed; }
  button {
    width: 100%; background: #0D9488; color: #fff; border: none;
    padding: 0.75rem; border-radius: 8px; font-size: 1rem;
    cursor: pointer; font-weight: 600; transition: background 0.2s;
  }
  button:hover:not(:disabled) { background: #0f766e; }
  button:disabled { opacity: 0.5; cursor: not-allowed; }
  .alert { padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.875rem; line-height: 1.5; }
  .alert-error  { background: #451a1a; border: 1px solid #dc2626; color: #fca5a5; }
  .alert-warn   { background: #431407; border: 1px solid #ea580c; color: #fdba74; }
  .alert-locked { background: #1e1a45; border: 1px solid #6d28d9; color: #c4b5fd; }
  .attempts-hint { font-size: 0.78rem; color: #64748B; text-align: center; margin-top: 0.75rem; }
  .attempts-hint span { color: #fbbf24; }
  .back { display: inline-block; margin-top: 1.25rem; font-size: 0.83rem; color: #64748B; text-decoration: none; }
  .back:hover { color: #38BDF8; }
</style>
</head>
<body>
<div class="card">
  <div class="logo">Simb<span>IoT</span></div>
  <p class="sub">Panel Admin — kelola feedback platform</p>

  <?php if ($timeout): ?>
    <div class="alert alert-warn">⏱ Sesi berakhir. Silakan login kembali.</div>
  <?php endif; ?>

  <?php if ($locked): ?>
    <div class="alert alert-locked">
      🔒 Akses dikunci karena terlalu banyak percobaan gagal.<br>
      Coba lagi dalam <strong><?= $bf['remaining_minutes'] ?> menit</strong>.
    </div>
  <?php elseif ($error): ?>
    <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <label for="l-username">Username</label>
    <input type="text" id="l-username" name="username"
           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
           autocomplete="username" <?= $locked ? 'disabled' : '' ?> required>
    <label for="l-password">Password</label>
    <input type="password" id="l-password" name="password"
           autocomplete="current-password" <?= $locked ? 'disabled' : '' ?> required>
    <button type="submit" <?= $locked ? 'disabled' : '' ?>>Masuk</button>
  </form>

  <?php if (!$locked && $bf['attempts'] >= 2): ?>
    <p class="attempts-hint">
      Percobaan tersisa: <span><?= $attempts_left ?></span> sebelum akses dikunci 15 menit
    </p>
  <?php endif; ?>

  <a href="/" class="back">← Kembali ke SimbIoT</a>
</div>
</body>
</html>