<?php
// admin/dashboard.php

require_once __DIR__ . '/auth.php';
requireLogin();

$db = getDB();

$tab        = $_GET['tab'] ?? 'pending';
$valid_tabs = ['pending', 'approved', 'rejected'];
if (!in_array($tab, $valid_tabs, true)) $tab = 'pending';

// Jumlah per status
$counts = [];
foreach ($valid_tabs as $s) {
    $counts[$s] = (int) $db->query(
        "SELECT COUNT(*) FROM feedback WHERE status = " . $db->quote($s)
    )->fetchColumn();
}

// Feedback untuk tab aktif
$stmt = $db->prepare("SELECT * FROM feedback WHERE status = ? ORDER BY created_at DESC");
$stmt->execute([$tab]);
$feedbacks = $stmt->fetchAll();

$role_labels = [
    'siswa'           => 'Siswa',
    'guru'            => 'Guru / Pengajar',
    'mahasiswa_dosen' => 'Mahasiswa / Dosen',
    'pengembang'      => 'Pengembang IoT',
];

$csrf = csrfToken();

$tab_labels = [
    'pending'  => 'Menunggu Review',
    'approved' => 'Ditampilkan',
    'rejected' => 'Ditolak',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin — SimbIoT</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: system-ui, -apple-system, sans-serif; background: #0A1628; color: #F0F9FF; min-height: 100vh; }

  /* Navbar */
  .navbar {
    background: #1E293B; border-bottom: 1px solid #2D3D52;
    padding: 0.875rem 1.5rem;
    display: flex; justify-content: space-between; align-items: center;
    position: sticky; top: 0; z-index: 100;
  }
  .nav-brand { font-size: 1.2rem; font-weight: 700; }
  .nav-brand span { color: #0D9488; }
  .nav-brand small { color: #64748B; font-size: 0.75rem; font-weight: 400; margin-left: 0.4rem; }
  .nav-right { display: flex; gap: 0.875rem; align-items: center; }
  .nav-user { color: #94A3B8; font-size: 0.85rem; }
  .btn-sm {
    padding: 0.4rem 0.875rem; border-radius: 6px; font-size: 0.83rem;
    cursor: pointer; text-decoration: none; transition: all 0.2s;
  }
  .btn-logout { background: transparent; border: 1px solid #2D3D52; color: #94A3B8; }
  .btn-logout:hover { border-color: #dc2626; color: #fca5a5; }

  /* Main */
  .main { max-width: 1000px; margin: 0 auto; padding: 2rem 1.5rem; }
  .page-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.75rem; flex-wrap: wrap; gap: 1rem; }
  .page-head h1 { font-size: 1.4rem; }
  .back-link { font-size: 0.83rem; color: #38BDF8; text-decoration: none; }
  .back-link:hover { text-decoration: underline; }

  /* Stats / Tab nav */
  .stat-row { display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap; }
  .stat-tab {
    background: #1E293B; border: 1px solid #2D3D52; border-radius: 10px;
    padding: 1rem 1.5rem; flex: 1; min-width: 140px;
    text-decoration: none; color: inherit; transition: border-color 0.2s;
    cursor: pointer;
  }
  .stat-tab:hover { border-color: #0D9488; }
  .stat-tab.active { border-color: #0D9488; }
  .stat-tab.active .stat-num { color: #0D9488; }
  .stat-num { font-size: 2rem; font-weight: 700; line-height: 1; margin-bottom: 0.2rem; }
  .stat-label { font-size: 0.8rem; color: #64748B; }

  /* Feedback cards */
  .fb-list { display: flex; flex-direction: column; gap: 1.25rem; }
  .fb-card { background: #1E293B; border: 1px solid #2D3D52; border-radius: 10px; padding: 1.25rem; }
  .fb-meta { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 0.875rem; flex-wrap: wrap; }
  .fb-name { font-weight: 600; font-size: 0.95rem; }
  .fb-role-badge {
    font-size: 0.72rem; font-weight: 600; padding: 0.2rem 0.6rem;
    border-radius: 4px; background: rgba(56,189,248,0.08);
    color: #38BDF8; border: 1px solid rgba(56,189,248,0.15);
    white-space: nowrap;
  }
  .fb-date { font-size: 0.78rem; color: #64748B; }
  .fb-message { color: #CBD5E1; line-height: 1.65; font-size: 0.9rem; margin-bottom: 1rem; }
  .fb-existing-response {
    background: #0A1628; border-left: 3px solid #0D9488;
    padding: 0.75rem 1rem; border-radius: 0 8px 8px 0; margin-bottom: 1rem;
  }
  .fb-resp-label { font-size: 0.7rem; font-weight: 700; color: #0D9488; letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 0.3rem; }
  .fb-resp-text { color: #CBD5E1; font-size: 0.875rem; line-height: 1.55; }

  /* Action form */
  .fb-action-form { width: 100%; }
  textarea {
    width: 100%; background: #0A1628; border: 1px solid #2D3D52; color: #F0F9FF;
    padding: 0.6rem 0.875rem; border-radius: 8px; font-size: 0.875rem;
    font-family: inherit; resize: vertical; outline: none; min-height: 72px;
    margin-bottom: 0.625rem; transition: border-color 0.2s; line-height: 1.55;
  }
  textarea:focus { border-color: #0D9488; }
  .fb-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
  .btn-action {
    padding: 0.45rem 1rem; border-radius: 6px; border: 1px solid transparent;
    cursor: pointer; font-size: 0.83rem; font-weight: 600; transition: all 0.2s;
    font-family: inherit;
  }
  .btn-approve  { background: #0D9488; color: #fff; border-color: #0D9488; }
  .btn-approve:hover  { background: #0f766e; }
  .btn-reject   { background: transparent; border-color: #dc2626; color: #fca5a5; }
  .btn-reject:hover   { background: #451a1a; }
  .btn-pending  { background: transparent; border-color: #475569; color: #94A3B8; }
  .btn-pending:hover  { background: #1E293B; }

  .empty { text-align: center; padding: 3rem; color: #64748B; font-size: 0.9rem; }

  @media (max-width: 600px) {
    .stat-row { flex-direction: column; }
    .stat-tab { min-width: unset; }
    .page-head { flex-direction: column; align-items: flex-start; }
  }
</style>
</head>
<body>

<nav class="navbar">
  <div class="nav-brand">Simb<span>IoT</span><small>Admin</small></div>
  <div class="nav-right">
    <span class="nav-user">👤 <?= htmlspecialchars($_SESSION['admin_username']) ?></span>
    <a href="/admin/logout.php" class="btn-sm btn-logout">Keluar</a>
  </div>
</nav>

<main class="main">
  <div class="page-head" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
    <h1 style="margin: 0;">Kelola Feedback</h1>
    
    <div style="display: flex; align-items: center; gap: 20px;">
        <a href="/" class="back-link" style="margin: 0;">← Lihat Landing Page</a>
        
        <a href="apps.php" style="text-decoration: none; padding: 8px 16px; background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 8px; font-size: 0.9rem; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: all 0.2s ease;">
            📦 Kelola Aplikasi Store
        </a>
    </div>
  </div>

  <!-- Tab navigator -->
  <div class="stat-row">
    <?php foreach ($valid_tabs as $s): ?>
      <a href="?tab=<?= $s ?>" class="stat-tab <?= $tab === $s ? 'active' : '' ?>">
        <div class="stat-num"><?= $counts[$s] ?></div>
        <div class="stat-label"><?= $tab_labels[$s] ?></div>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Feedback list -->
  <div class="fb-list">
    <?php if (empty($feedbacks)): ?>
      <div class="empty">Tidak ada feedback dengan status "<strong><?= $tab_labels[$tab] ?></strong>".</div>
    <?php else: ?>
      <?php foreach ($feedbacks as $fb): ?>
        <div class="fb-card">
          <div class="fb-meta">
            <div>
              <div class="fb-name"><?= htmlspecialchars($fb['name']) ?></div>
              <span class="fb-role-badge"><?= htmlspecialchars($role_labels[$fb['role']] ?? $fb['role']) ?></span>
            </div>
            <div class="fb-date"><?= date('d M Y, H:i', strtotime($fb['created_at'])) ?> WIB</div>
          </div>

          <div class="fb-message"><?= nl2br(htmlspecialchars($fb['message'])) ?></div>

          <?php if ($fb['response']): ?>
            <div class="fb-existing-response">
              <div class="fb-resp-label">Tanggapan saya</div>
              <div class="fb-resp-text"><?= nl2br(htmlspecialchars($fb['response'])) ?></div>
            </div>
          <?php endif; ?>

          <form method="POST" action="/admin/respond.php" class="fb-action-form">
            <input type="hidden" name="csrf_token"   value="<?= $csrf ?>">
            <input type="hidden" name="id"           value="<?= (int)$fb['id'] ?>">
            <input type="hidden" name="redirect_tab" value="<?= $tab ?>">
            <textarea name="response"
              placeholder="Tulis tanggapan opsional — ditampilkan bersama komentar saat diapprove..."
            ><?= htmlspecialchars($fb['response'] ?? '') ?></textarea>
            <div class="fb-actions">
              <?php if ($tab !== 'approved'): ?>
                <button type="submit" name="action" value="approve" class="btn-action btn-approve">✓ Tampilkan</button>
              <?php else: ?>
                <button type="submit" name="action" value="approve" class="btn-action btn-approve">↺ Perbarui Tanggapan</button>
              <?php endif; ?>
              <?php if ($tab !== 'rejected'): ?>
                <button type="submit" name="action" value="reject"  class="btn-action btn-reject">✕ Tolak</button>
              <?php endif; ?>
              <?php if ($tab === 'approved'): ?>
                <button type="submit" name="action" value="pending" class="btn-action btn-pending">↩ Sembunyikan</button>
              <?php endif; ?>
              <?php if ($tab === 'rejected'): ?>
                <button type="submit" name="action" value="pending" class="btn-action btn-pending">↩ Kembalikan ke Pending</button>
              <?php endif; ?>
            </div>
          </form>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</main>

</body>
</html>
