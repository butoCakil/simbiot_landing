<?php
// app.php - Halaman Detail Aplikasi / Skrip

require_once 'config.php';

$id = $_GET['id'] ?? 0;
if (!is_numeric($id)) {
    header("Location: /store.php");
    exit;
}

try {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
    $db  = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Koneksi database gagal.");
}

$stmt = $db->prepare("SELECT * FROM apps WHERE id = ?");
$stmt->execute([$id]);
$app = $stmt->fetch();

if (!$app) {
    header("Location: /store.php");
    exit;
}

$db->prepare("UPDATE apps SET total_views = total_views + 1 WHERE id = ?")->execute([$id]);

// Fungsi pembantu untuk memotong nama file di tengah (Contoh: nama_panjang_banget.zip -> nama_pan...anget.zip)
function truncateFilename($filename, $maxLength = 28) {
    if (strlen($filename) <= $maxLength) return $filename;
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
    
    $keepChars = floor(($maxLength - strlen($ext) - 5) / 2);
    $start = substr($nameWithoutExt, 0, max(4, $keepChars));
    $end = substr($nameWithoutExt, -max(4, $keepChars));
    
    return $start . '...' . $end . '.' . $ext;
}

// Fungsi pembantu untuk format ukuran file
function formatBytes($bytes, $precision = 2) {
    if ($bytes <= 0) return '0 KB';
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), $precision) . ' ' . $units[$i];
}

// Perbaikan Mutlak Logika Tombol Berdasarkan Tipe Distribusi dan Link
$sourceLink = $app['source_link'];
$isExternalLink = (strpos($sourceLink, 'http://') === 0 || strpos($sourceLink, 'https://') === 0);
$isGithubType = ($app['app_type'] === 'github');

// SEMUA jenis unduhan/tautan kini wajib melewati pintu API agar counter statistik selalu bertambah
$actionUrl = "/api/download.php?id=" . $app['id'];
$actionTarget = $isExternalLink ? "_blank" : "_self";

if ($isGithubType || $isExternalLink) {
    // 1. Deteksi Nama Platform dari URL secara cerdas
    $host = strtolower(parse_url($sourceLink, PHP_URL_HOST));
    $platform = "Situs Luar";
    
    if (strpos($host, 'github.com') !== false || strpos($host, 'githubusercontent.com') !== false) {
        $platform = "GitHub";
    } elseif (strpos($host, 'drive.google.com') !== false) {
        $platform = "GDrive";
    } elseif (strpos($host, 'gitlab.com') !== false) {
        $platform = "GitLab";
    } elseif (strpos($host, 'bitbucket.org') !== false) {
        $platform = "Bitbucket";
    } elseif (strpos($host, 'mediafire.com') !== false) {
        $platform = "MediaFire";
    } else {
        $parts = explode('.', $host);
        if (count($parts) >= 2) {
            $platform = ucfirst($parts[count($parts)-2]); 
        }
    }

    // 2. Deteksi Apakah Ini File Unduhan Langsung atau Halaman Repositori
    $path = parse_url($sourceLink, PHP_URL_PATH);
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $isDirectFile = false;
    
    $fileExts = ['exe', 'zip', 'rar', '7z', 'tar', 'gz', 'apk', 'msi', 'dmg', 'iso'];
    
    if (in_array($ext, $fileExts) || strpos(strtolower($sourceLink), '/download/') !== false || strpos(strtolower($sourceLink), 'uc?id=') !== false) {
        $isDirectFile = true;
    }

    // 3. Tentukan Teks dan Ikon Tombol
    $iconExternal = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display: inline-block; vertical-align: middle; margin-left: 5px;"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>';
    
    if ($isDirectFile) {
        $actionLabel = "⬇️ Unduh di {$platform}";
    } else {
        $actionLabel = "Lihat di {$platform} {$iconExternal}";
    }

} else {
    // Jika murni berkas lokal (.exe, .zip, dll) yang di-upload langsung ke server kita
    $rawFilename = basename($app['source_link']);
    $cleanFilename = preg_replace('/^\d+_/', '', $rawFilename);
    $displayName = truncateFilename($cleanFilename, 30);
    
    $fullServerPath = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($app['source_link'], '/');
    if (!file_exists($fullServerPath)) {
        $fullServerPath = dirname(__DIR__) . '/' . $app['source_link'];
    }
    
    $fileSizeStr = file_exists($fullServerPath) ? formatBytes(filesize($fullServerPath)) : '';
    $sizeBadge = !empty($fileSizeStr) ? " <span style='opacity: 0.7; font-size: 0.85em; font-weight: normal;'>($fileSizeStr)</span>" : "";
    $actionLabel = "⬇️ Unduh: <strong>{$displayName}</strong>{$sizeBadge}";
}

$screenshots = !empty($app['screenshots']) ? json_decode($app['screenshots'], true) : [];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($app['title']); ?> - SimbIoT Store</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    
    <!-- CSS TEMA GITHUB MARKDOWN (Versi Gelap agar sesuai dengan Store Anda) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/github-markdown-css/5.2.0/github-markdown-dark.min.css">
    
    <style>
        :root {
            --card-bg: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: #334155;
            --accent-color: #38bdf8;
        }
        body { font-family: 'Segoe UI', system-ui, sans-serif; color: var(--text-main); background-color: #0f172a; margin: 0; padding: 0; }
        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        .back-link { color: var(--accent-color); text-decoration: none; display: inline-block; margin-bottom: 20px; }
        .app-header { display: flex; gap: 30px; background: var(--card-bg); border: 1px solid var(--border-color); padding: 30px; border-radius: 12px; align-items: center; }
        .app-thumb { width: 120px; height: 120px; border-radius: 8px; object-fit: cover; background: #0f172a; border: 1px solid var(--border-color); flex-shrink: 0; }
        .app-info h1 { margin: 0 0 10px 0; font-size: 2rem; color: var(--text-main); }
        .app-meta { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 15px; display: flex; gap: 20px; }
        .btn-download { display: inline-block; padding: 12px 20px; background: var(--accent-color); color: #0f172a; font-weight: 500; text-decoration: none; border-radius: 8px; transition: background 0.2s; font-size: 0.95rem; }
        .btn-download:hover { background: #0ea5e9; }
        
        .section-card { background: var(--card-bg); border: 1px solid var(--border-color); padding: 30px; border-radius: 12px; margin-top: 25px; }
        .section-card h3 { margin-top: 0; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; color: var(--accent-color); }
        
        /* Penyesuaian agar tema Markdown membaur sempurna dengan background card Anda */
        .markdown-body { background-color: transparent !important; font-family: inherit !important; color: #e2e8f0 !important; }
        
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-top: 15px; }
        .gallery-img { width: 100%; height: 130px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-color); }
    </style>
</head>
<body>

<div class="container">
    <a href="/store/" class="back-link">&laquo; Kembali ke Etalase Store</a>

    <div class="app-header">
        <?php if (!empty($app['thumbnail'])): ?>
            <img src="/<?= htmlspecialchars($app['thumbnail']); ?>" alt="Thumbnail" class="app-thumb">
        <?php else: ?>
            <div class="app-thumb" style="display: flex; align-items: center; justify-content: center; font-size: 2.5rem;">📦</div>
        <?php endif; ?>
        
        <div class="app-info">
            <h1><?= htmlspecialchars($app['title']); ?></h1>
            <div class="app-meta">
                <span>👁️ <?= number_format($app['total_views']); ?> Dilihat</span>
                <span>⬇️ <?= number_format($app['total_downloads']); ?> Diunduh</span>
                <span>🏷️ Tipe: <strong><?= strtoupper($app['app_type']); ?></strong></span>
            </div>
            <a href="<?= $actionUrl; ?>" target="<?= $actionTarget; ?>" class="btn-download"><?= $actionLabel; ?></a>
        </div>
    </div>

    <div class="section-card">
        <h3>Ringkasan</h3>
        <p style="font-size: 1.05rem; line-height: 1.6; color: #cbd5e1;"><?= nl2br(htmlspecialchars($app['description'])); ?></p>
    </div>

    <?php if (!empty($screenshots)): ?>
    <div class="section-card">
        <h3>Galeri Tangkapan Layar</h3>
        <div class="gallery-grid">
            <?php 
            if (!empty($screenshots) && is_array($screenshots)):
                foreach ($screenshots as $index => $shot): 
            ?>
                <img src="/<?= htmlspecialchars($shot); ?>" alt="Screenshot" onclick="bukaGaleri(<?= $index; ?>)" style="cursor: pointer; transition: transform 0.2s; border-radius: 6px; object-fit: cover; width: 100%; height: 100%;" onmouseover="this.style.transform='scale(1.03)'" onmouseout="this.style.transform='scale(1)'">
            <?php 
                endforeach; 
            endif; 
            ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($app['documentation'])): ?>
    <div class="section-card">
        <h3>Dokumentasi & Langkah Penerapan</h3>
        
        <!-- Sembunyikan teks mentah markdown -->
        <textarea id="raw-markdown" style="display: none;"><?= htmlspecialchars($app['documentation']); ?></textarea>
        
        <!-- Wadah hasil render (menggunakan class markdown-body dari CSS GitHub) -->
        <div id="rendered-markdown" class="markdown-body">
            Memuat dokumentasi...
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Elemen Lightbox Galeri -->
<div id="lightboxModal" class="lightbox-overlay" onclick="tutupGaleri(event)">
    <span class="lightbox-close" onclick="tutupGaleri(event)">&times;</span>
    <span class="lightbox-prev" onclick="geserGaleri(-1, event)">&#10094;</span>
    <img id="lightboxImg" class="lightbox-content" src="">
    <span class="lightbox-next" onclick="geserGaleri(1, event)">&#10095;</span>
</div>

<!-- CSS Lightbox -->
<style>
.lightbox-overlay {
    display: none; position: fixed; z-index: 99999; top: 0; left: 0;
    width: 100%; height: 100%; background-color: rgba(15, 23, 42, 0.95);
    backdrop-filter: blur(5px); align-items: center; justify-content: center;
}
.lightbox-content {
    max-width: 90%; max-height: 85vh; border-radius: 8px;
    object-fit: contain; box-shadow: 0 10px 25px rgba(0,0,0,0.5);
    animation: zoomIn 0.3s ease;
}
.lightbox-close {
    position: absolute; top: 20px; right: 30px; color: #94a3b8;
    font-size: 40px; font-weight: bold; cursor: pointer; transition: 0.2s;
}
.lightbox-close:hover { color: #f8fafc; }
.lightbox-prev, .lightbox-next {
    cursor: pointer; position: absolute; top: 50%; transform: translateY(-50%);
    width: auto; padding: 15px 20px; color: #f8fafc; font-weight: bold;
    font-size: 25px; transition: 0.2s; user-select: none;
    background-color: rgba(0,0,0,0.4); border-radius: 8px;
}
.lightbox-prev { left: 20px; }
.lightbox-next { right: 20px; }
.lightbox-prev:hover, .lightbox-next:hover { background-color: rgba(56, 189, 248, 0.8); }
@keyframes zoomIn { from {transform: scale(0.95); opacity: 0;} to {transform: scale(1); opacity: 1;} }
</style>

<!-- SCRIPT PENERJEMAH MARKDOWN (MARKED.JS) -->
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const rawMarkdown = document.getElementById('raw-markdown');
    const renderedMarkdown = document.getElementById('rendered-markdown');
    
    if (rawMarkdown && renderedMarkdown) {
        // Parse teks mentah menjadi elemen HTML GitHub Style
        renderedMarkdown.innerHTML = marked.parse(rawMarkdown.value);
    }
});

// === Script Galeri (Tidak Diubah) ===
const dataScreenshot = <?= !empty($app['screenshots']) ? $app['screenshots'] : '[]' ?>;
let indexSaatIni = 0;

function bukaGaleri(index) {
    indexSaatIni = index;
    document.getElementById('lightboxModal').style.display = 'flex';
    perbaruiGambar();
}

function tutupGaleri(event) {
    if (event === undefined || event.target.id === 'lightboxModal' || event.target.className === 'lightbox-close') {
        document.getElementById('lightboxModal').style.display = 'none';
    }
}

function geserGaleri(arah, event) {
    if(event) event.stopPropagation();
    indexSaatIni += arah;
    
    if (indexSaatIni >= dataScreenshot.length) {
        indexSaatIni = 0;
    } else if (indexSaatIni < 0) {
        indexSaatIni = dataScreenshot.length - 1;
    }
    perbaruiGambar();
}

function perbaruiGambar() {
    const imgElemen = document.getElementById('lightboxImg');
    imgElemen.src = '/' + dataScreenshot[indexSaatIni]; 
}

document.addEventListener('keydown', function(e) {
    if (document.getElementById('lightboxModal').style.display === 'flex') {
        if (e.key === 'ArrowLeft') geserGaleri(-1);
        if (e.key === 'ArrowRight') geserGaleri(1);
        if (e.key === 'Escape') tutupGaleri();
    }
});
</script>
</body>
</html>