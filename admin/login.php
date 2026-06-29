<?php
// admin/login.php

require_once __DIR__ . '/auth.php';
startAdminSession();

if (!empty($_SESSION['admin_id'])) {
    header('Location: /admin/dashboard.php');
    exit;
}

$error   = '';
$timeout = isset($_GET['timeout']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                session_regenerate_id(true); // cegah session fixation
                $_SESSION['admin_id']       = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['last_activity']  = time();
                $_SESSION['csrf_token']     = bin2hex(random_bytes(32));

                $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

                header('Location: /admin/dashboard.php');
                exit;
            } else {
                $error = 'Username atau password salah.';
            }
        } catch (PDOException $e) {
            $error = 'Koneksi database bermasalah. Coba beberapa saat lagi.';
        }
    }
}
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
  button {
    width: 100%; background: #0D9488; color: #fff; border: none;
    padding: 0.75rem; border-radius: 8px; font-size: 1rem;
    cursor: pointer; font-weight: 600; transition: background 0.2s;
  }
  button:hover { background: #0f766e; }
  .alert { padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.875rem; }
  .alert-error { background: #451a1a; border: 1px solid #dc2626; color: #fca5a5; }
  .alert-warn  { background: #431407; border: 1px solid #ea580c; color: #fdba74; }
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

  <?php if ($error): ?>
    <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <label for="l-username">Username</label>
    <input type="text" id="l-username" name="username"
           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
           autocomplete="username" required>
    <label for="l-password">Password</label>
    <input type="password" id="l-password" name="password"
           autocomplete="current-password" required>
    <button type="submit">Masuk</button>
  </form>
  <a href="/" class="back">← Kembali ke SimbIoT</a>
</div>
</body>
</html>
