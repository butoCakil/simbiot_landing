<?php
// store.php - Halaman Etalase Aplikasi Publik

require_once 'config.php';

// Inisiasi koneksi database khusus halaman publik
try {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
    $db  = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("<div style='color:red; text-align:center; padding:50px;'>Koneksi database gagal. Silakan coba beberapa saat lagi.</div>");
}

// Ambil semua data aplikasi
$apps = [];
try {
    $stmt = $db->query("SELECT * FROM apps ORDER BY created_at DESC");
    $apps = $stmt->fetchAll();
} catch (PDOException $e) {
    // Abaikan error jika tabel belum ada agar halaman tidak crash
}

// Fungsi pembantu untuk label tipe aplikasi
function getAppLabel($type) {
    $labels = [
        'exe' => ['text' => 'Executable', 'color' => '#10b981'], // Emerald
        'chrome_ext' => ['text' => 'Extension', 'color' => '#f59e0b'], // Amber
        'cmd' => ['text' => 'Terminal / Shell', 'color' => '#8b5cf6'], // Violet
        'github' => ['text' => 'Source Code', 'color' => '#64748b'], // Slate
        'other' => ['text' => 'Lainnya', 'color' => '#3b82f6'] // Blue
    ];
    return $labels[$type] ?? $labels['other'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SimbIoT Store - Repositori Aplikasi & Skrip</title>
    <!-- Asumsi Anda menggunakan file style.css bawaan untuk background utama -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root {
            --card-bg: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: #334155;
            --hover-bg: #273549;
        }
        
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            color: var(--text-main);
            margin: 0;
            padding: 0;
            /* Latar belakang gelap mengikuti tema Simbiot */
            background-color: #0f172a; 
        }

        .store-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .store-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .store-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: #38bdf8;
        }

        .store-header p {
            color: var(--text-muted);
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Grid System untuk Card Aplikasi */
        .app-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
        }

        .app-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 25px;
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .app-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
            border-color: #475569;
        }

        .app-badge {
            position: absolute;
            top: 25px;
            right: 25px;
            font-size: 0.75rem;
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .app-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin: 0 0 15px 0;
            padding-right: 90px; /* Space for badge */
            color: var(--text-main);
        }

        .app-desc {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.5;
            flex-grow: 1;
            margin-bottom: 20px;
        }

        .app-stats {
            display: flex;
            gap: 15px;
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 20px;
            padding-top: 15px;
            border-top: 1px dashed var(--border-color);
        }

        .btn-action {
            display: block;
            width: 100%;
            text-align: center;
            padding: 12px;
            background: #38bdf8;
            color: #0f172a;
            text-decoration: none;
            font-weight: 600;
            border-radius: 8px;
            transition: background 0.2s;
            box-sizing: border-box;
        }

        .btn-action:hover {
            background: #0ea5e9;
        }

        .back-link { 
            color: var(--accent-color); 
            text-decoration: none; 
            font-size: 0.95rem; 
            display: inline-block; 
            margin-bottom: 25px; 
        }
    </style>
</head>
<body>

<div class="store-container">
    <a href="/" class="back-link">&laquo; Kembali ke Beranda Utama</a>
    <div class="store-header">
        <h1>SimbIoT App Store</h1>
        <p>Kumpulan berkas instalasi, skrip sistem tertanam (embedded), dan pustaka kode sumber terbuka untuk kebutuhan praktik dan pengembangan.</p>
    </div>

    <div class="app-grid">
        <?php if (count($apps) > 0): ?>
            <?php foreach ($apps as $app): ?>
                <?php $label = getAppLabel($app['app_type']); ?>
                <div class="app-card">
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                        <?php if (!empty($app['thumbnail'])): ?>
                            <img src="/<?= htmlspecialchars($app['thumbnail']); ?>" alt="Thumbnail" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px; flex-shrink: 0;">
                        <?php else: ?>
                            <div style="width: 50px; height: 50px; background: rgba(255,255,255,0.05); border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">📦</div>
                        <?php endif; ?>
                        
                        <div>
                            <h3 style="margin: 0 0 5px 0; font-size: 1.1rem;"><?= htmlspecialchars($app['title']); ?></h3>
                            <span class="badge" style="background: rgba(59,130,246,0.2); color: #60a5fa; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem;"><?= strtoupper(htmlspecialchars($app['app_type'])); ?></span>
                        </div>
                    </div>
                    
                    <p class="app-desc"><?= nl2br(htmlspecialchars($app['description'])); ?></p>
                    
                    <div class="app-stats">
                        <span>👁️ <?= number_format($app['total_views']); ?>x dilihat</span>
                        <span>⬇️ <?= number_format($app['total_downloads']); ?>x diunduh</span>
                    </div>
                    
                    <a href="/app.php?id=<?= $app['id']; ?>" class="btn-action">Lihat Detail & Panduan</a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1 / -1; text-align: center; color: var(--text-muted); padding: 50px; background: var(--card-bg); border: 1px dashed var(--border-color); border-radius: 12px;">
                <p>Belum ada aplikasi atau skrip yang dipublikasikan saat ini.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>