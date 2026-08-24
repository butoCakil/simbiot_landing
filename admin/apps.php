<?php
// admin/apps.php
require_once 'auth.php';
requireLogin();
$db = getDB();

// Proses Hapus Repo & File Fisiknya
if (isset($_POST['delete_id']) && is_numeric($_POST['delete_id'])) {
    verifyCsrf();
    $delId = (int)$_POST['delete_id'];
    $stmt = $db->prepare("SELECT source_link, thumbnail, screenshots FROM apps WHERE id = ?");
    $stmt->execute([$delId]);
    $targetApp = $stmt->fetch();
    
    if ($targetApp) {
        if (!empty($targetApp['source_link']) && strpos($targetApp['source_link'], 'uploads/files/') === 0) {
            @unlink(dirname(__DIR__) . '/' . $targetApp['source_link']);
        }
        if (!empty($targetApp['thumbnail'])) {
            @unlink(dirname(__DIR__) . '/' . $targetApp['thumbnail']);
        }
        if (!empty($targetApp['screenshots'])) {
            $shots = json_decode($targetApp['screenshots'], true);
            if (is_array($shots)) {
                foreach ($shots as $shot) { @unlink(dirname(__DIR__) . '/' . $shot); }
            }
        }
        $db->prepare("DELETE FROM apps WHERE id = ?")->execute([$delId]);
        header("Location: apps.php");
        exit;
    }
}

$daftar_apps = [];
try {
    $stmt = $db->query("SELECT * FROM apps ORDER BY created_at DESC");
    $daftar_apps = $stmt->fetchAll();
} catch (PDOException $e) {}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Aplikasi - Admin Simbiot</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --bg-card: #1e293b; --bg-input: #0f172a; --border-color: #334155;
            --text-main: #f8fafc; --text-muted: #94a3b8; --accent-color: #3b82f6;
            --accent-hover: #2563eb; --danger-bg: #7f1d1d; --danger-text: #fca5a5; --success-text: #4ade80;
        }
        .admin-container { max-width: 1100px; margin: 40px auto; padding: 0 20px; font-family: 'Segoe UI', Tahoma, sans-serif; color: var(--text-main); }
        .header-title { font-size: 1.8rem; font-weight: 600; margin-bottom: 5px; }
        .back-link { color: var(--accent-color); text-decoration: none; font-size: 0.95rem; display: inline-block; margin-bottom: 25px; }
        .card { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 30px; margin-bottom: 30px; }
        .card-title { margin-top: 0; margin-bottom: 20px; font-size: 1.3rem; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; color: var(--text-muted); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin-bottom: 8px; }
        .form-control { width: 100%; padding: 12px 15px; background-color: var(--bg-input); color: var(--text-main); border: 1px solid var(--border-color); border-radius: 8px; box-sizing: border-box; }
        .form-text { color: var(--text-muted); font-size: 0.85em; margin-top: 6px; display: block; }
        .section-box { background: rgba(15, 23, 42, 0.4); border: 1px solid var(--border-color); padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .btn-submit { padding: 12px 24px; background-color: var(--accent-color); color: white; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; }
        
        /* Modal Multi-Progress & Terminal Log */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.9); z-index: 9999; justify-content: center; align-items: center; }
        .modal-box { background: var(--bg-card); border: 1px solid var(--border-color); padding: 25px; border-radius: 12px; width: 95%; max-width: 550px; }
        .progress-row { margin-bottom: 15px; }
        .progress-label { display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 5px; color: var(--text-muted); font-weight: 600; }
        .progress-container { width: 100%; background: var(--bg-input); border-radius: 6px; overflow: hidden; height: 12px; border: 1px solid var(--border-color); }
        .progress-bar { width: 0%; height: 100%; background: var(--accent-color); transition: width 0.1s ease; }
        
        /* Terminal Log Box */
        .terminal-box { background: #020617; border: 1px solid var(--border-color); border-radius: 6px; padding: 12px; height: 130px; overflow-y: auto; font-family: 'Courier New', Courier, monospace; font-size: 0.8rem; color: #38bdf8; margin-top: 15px; text-align: left; }
        .terminal-line { margin-bottom: 4px; }
        .terminal-error { color: var(--danger-text); }
        .terminal-success { color: var(--success-text); }

        .table-container { overflow-x: auto; background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 15px 20px; border-bottom: 1px solid var(--border-color); }
        th { background-color: rgba(0, 0, 0, 0.2); color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; }
        .badge { background: rgba(255,255,255,0.1); padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; color: var(--text-muted); text-decoration: none; display: inline-block; margin-right: 5px; }
    </style>
</head>
<body>

<!-- MODAL MULTI-PROGRESS & TERMINAL LOG -->
<div class="modal-overlay" id="loadingModal">
    <div class="modal-box">
        <h3 style="margin-top:0; margin-bottom: 5px; font-size: 1.2rem;">Mengunggah & Memproses Berkas...</h3>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 20px;">Sistem menggunakan sistem potong (chunking) agar file besar 32MB+ aman tanpa timeout.</p>
        
        <!-- Bar 1: Upload Chunk -->
        <div class="progress-row">
            <div class="progress-label"><span id="lblMainFile">1. Unggah Potongan File (.exe)</span><span id="pctMainFile">0%</span></div>
            <div class="progress-container"><div class="progress-bar" id="barMainFile"></div></div>
        </div>
        
        <!-- Bar 4: Baris Baru untuk Proses Penyatuan File -->
        <div class="progress-row">
            <div class="progress-label"><span id="lblMerge">2. Menyatukan Berkas di Server</span><span id="pctMerge">0%</span></div>
            <div class="progress-container"><div class="progress-bar" id="barMerge"></div></div>
        </div>
        
        <!-- Bar 2: Thumbnail -->
        <div class="progress-row">
            <div class="progress-label"><span id="lblThumb">3. Thumbnail Utama</span><span id="pctThumb">0%</span></div>
            <div class="progress-container"><div class="progress-bar" id="barThumb"></div></div>
        </div>
        
        <!-- Bar 3: Screenshots -->
        <div class="progress-row">
            <div class="progress-label"><span id="lblShots">4. Galeri Tangkapan Layar</span><span id="pctShots">0%</span></div>
            <div class="progress-container"><div class="progress-bar" id="barShots"></div></div>
        </div>

        <!-- Terminal Log Real-time -->
        <div class="terminal-box" id="terminalLog">
            <div class="terminal-line">[SYSTEM] Menyiapkan modul unggah...</div>
        </div>
    </div>
</div>

<div class="admin-container">
    <h2 class="header-title">Kelola Aplikasi & Skrip</h2>
    <a href="dashboard.php" class="back-link">&laquo; Kembali ke Dashboard Utama</a>
    
    <div id="pesanContainer"></div>

    <div class="card">
        <h3 class="card-title">Unggah & Tambah Aplikasi Baru</h3>
        
        <form id="appForm">
            <input type="hidden" id="csrf_token" value="<?= htmlspecialchars(csrfToken()); ?>">
            
            <div class="form-group">
                <label>Judul Aplikasi</label>
                <input type="text" id="title" class="form-control" required placeholder="Contoh: Modul Absensi RFID V2">
            </div>
            
            <div class="form-group">
                <label>Tipe Distribusi</label>
                <select id="appTypeSelect" class="form-control" required onchange="toggleSourceInput()">
                    <option value="exe">File Executable (.exe / .zip / .rar)</option>
                    <option value="cmd">Skrip CMD / Terminal / Shell</option>
                    <option value="chrome_ext">Ekstensi Browser</option>
                    <option value="github">Repositori Source Code (GitHub / URL)</option>
                    <option value="other">Format Lainnya</option>
                </select>
            </div>

            <!-- SECTION 1 -->
            <div class="section-box">
                <h4 style="margin-top:0; color: var(--accent-color); font-size: 0.95rem; text-transform: uppercase;">1. Sumber Program / Instalasi</h4>
                <div class="form-group" id="fileUploadWrapper" style="margin-bottom:0;">
                    <label>Unggah Berkas Program Utama (Aman untuk file besar)</label>
                    <input type="file" id="app_file" class="form-control" style="background: var(--bg-input);">
                </div>
                <div class="form-group" id="urlLinkWrapper" style="display: none; margin-bottom:0;">
                    <label>Tautan Repositori / URL Eksternal</label>
                    <input type="text" id="github_link" class="form-control" placeholder="https://github.com/username/repository">
                </div>
            </div>

            <!-- SECTION 2 -->
            <div class="section-box">
                <h4 style="margin-top:0; color: var(--accent-color); font-size: 0.95rem; text-transform: uppercase;">2. Media Visual & Galeri (Opsional)</h4>
                <div class="form-group">
                    <label>Gambar Thumbnail Utama (Logo / Icon Store)</label>
                    <input type="file" id="thumbnail" accept="image/*" class="form-control" style="background: var(--bg-input);">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label>Galeri Screenshot / Slide (Bisa Pilih Banyak Sekaligus)</label>
                    <input type="file" id="screenshots" accept="image/*" multiple class="form-control" style="background: var(--bg-input);">
                </div>
            </div>
            
            <div class="form-group">
                <label>Deskripsi Singkat</label>
                <textarea id="description" class="form-control" rows="3" required placeholder="Jelaskan fungsi utama..."></textarea>
            </div>
            
            <div class="form-group">
                <label>Dokumentasi & Langkah Penerapan (Mendukung HTML)</label>
                <textarea id="documentation" class="form-control" rows="5" placeholder="Tuliskan prasyarat, cara install..."></textarea>
            </div>
            
            <button type="submit" class="btn-submit">Simpan ke Repositori</button>
        </form>
    </div>

    <!-- Tabel Daftar -->
    <div class="card" style="padding: 0;">
        <h3 class="card-title" style="padding: 25px 25px 0 25px; border-bottom: none;">Daftar Repositori Saat Ini</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Thumbnail</th>
                        <th>Judul & Tipe</th>
                        <th>Statistik</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($daftar_apps) > 0): ?>
                        <?php foreach ($daftar_apps as $app): ?>
                            <tr>
                                <td style="width: 80px;">
                                    <?php if (!empty($app['thumbnail'])): ?>
                                        <img src="/<?= htmlspecialchars($app['thumbnail']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 50px; background: var(--bg-input); border-radius: 6px; display: flex; align-items: center; justify-content: center;">📦</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong style="color: var(--text-main); font-size: 1.05rem;"><?= htmlspecialchars($app['title']); ?></strong><br>
                                    <span class="badge" style="background: rgba(59,130,246,0.2); color: #60a5fa;"><?= strtoupper(htmlspecialchars($app['app_type'])); ?></span>
                                </td>
                                <td><small>👁️ <?= $app['total_views']; ?> | ⬇️ <?= $app['total_downloads']; ?></small></td>
                                <td>
                                    <a href="edit_app.php?id=<?= $app['id']; ?>" class="badge" style="background: var(--accent-color); color: #fff;">✏️ Edit</a>
                                    <form action="apps.php" method="POST" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus repositori ini beserta seluruh file fisiknya secara permanen?');">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()); ?>">
                                        <input type="hidden" name="delete_id" value="<?= $app['id']; ?>">
                                        <button type="submit" class="badge" style="background: var(--danger-bg); color: var(--danger-text); border:none; cursor:pointer; padding: 4px 8px; border-radius: 4px;">🗑️ Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align: center; padding: 40px; color: var(--text-muted);">Belum ada aplikasi.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function toggleSourceInput() {
    let type = document.getElementById('appTypeSelect').value;
    let fileWrapper = document.getElementById('fileUploadWrapper');
    let urlWrapper = document.getElementById('urlLinkWrapper');
    if (type === 'github') {
        fileWrapper.style.display = 'none';
        urlWrapper.style.display = 'block';
    } else {
        fileWrapper.style.display = 'block';
        urlWrapper.style.display = 'none';
    }
}

function logTerminal(text, type = 'normal') {
    let term = document.getElementById('terminalLog');
    let div = document.createElement('div');
    div.className = 'terminal-line';
    if(type === 'error') div.classList.add('terminal-error');
    if(type === 'success') div.classList.add('terminal-success');
    div.innerText = '> ' + text;
    term.appendChild(div);
    term.scrollTop = term.scrollHeight;
}

document.getElementById('appForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    let modal = document.getElementById('loadingModal');
    let term = document.getElementById('terminalLog');
    term.innerHTML = '<div class="terminal-line">[SYSTEM] Memulai proses chunk upload...</div>';
    modal.style.display = 'flex';

    let sourceLink = '';
    let thumbnailPath = '';
    let screenshotPaths = [];

    let appType = document.getElementById('appTypeSelect').value;
    let csrfToken = document.getElementById('csrf_token').value;

    try {
        // 1. CHUNK UPLOAD FILE UTAMA
        if (appType === 'github') {
            sourceLink = document.getElementById('github_link').value;
        } else {
            let fileInput = document.getElementById('app_file');
            if (fileInput.files.length > 0) {
                let file = fileInput.files[0];
                logTerminal(`Memecah dan mengunggah file (${file.name})...`);
                
                const chunkSize = 1024 * 1024; // 1MB per chunk
                const totalChunks = Math.ceil(file.size / chunkSize);
                
                for (let i = 0; i < totalChunks; i++) {
                    let start = i * chunkSize;
                    let end = Math.min(file.size, start + chunkSize);
                    let chunk = file.slice(start, end);

                    let fd = new FormData();
                    fd.append('action', 'upload_chunk');
                    fd.append('chunk', chunk);
                    fd.append('file_name', file.name);
                    fd.append('chunk_index', i);

                    let res = await postData(fd);
                    if (res.status !== 'success') throw new Error(res.message);

                    let percent = Math.round(((i + 1) / totalChunks) * 100);
                    document.getElementById('barMainFile').style.width = percent + '%';
                    document.getElementById('pctMainFile').innerText = percent + '%';
                }

                // Setelah semua chunk terkirim, panggil proses penyatuan file
                logTerminal("Semua potongan terkirim. Memulai penyatuan file di server...");
                document.getElementById('barMerge').style.width = '50%';
                document.getElementById('pctMerge').innerText = 'Memproses...';

                let mergeFd = new FormData();
                mergeFd.append('action', 'merge_chunks');
                mergeFd.append('file_name', file.name);
                mergeFd.append('total_chunks', totalChunks);

                let mergeRes = await postData(mergeFd);
                if (mergeRes.status === 'complete') {
                    sourceLink = mergeRes.path;
                    document.getElementById('barMerge').style.width = '100%';
                    document.getElementById('pctMerge').innerText = '100%';
                    logTerminal("Berkas program utama berhasil disatukan utuh!", 'success');
                } else {
                    throw new Error(mergeRes.message);
                }
            }
        }

        // 2. UPLOAD THUMBNAIL
        let thumbInput = document.getElementById('thumbnail');
        if (thumbInput.files.length > 0) {
            logTerminal("Mengompres thumbnail utama ke WebP...");
            let fd = new FormData();
            fd.append('action', 'upload_image');
            fd.append('image', thumbInput.files[0]);
            fd.append('is_thumb', '1');

            let res = await postData(fd);
            if (res.status === 'success') {
                thumbnailPath = res.path;
                document.getElementById('barThumb').style.width = '100%';
                document.getElementById('pctThumb').innerText = '100%';
                logTerminal("Thumbnail sukses: " + thumbnailPath, 'success');
            } else {
                throw new Error(res.message);
            }
        } else {
            document.getElementById('barThumb').style.width = '100%';
            document.getElementById('pctThumb').innerText = '100% (Lewat)';
        }

        // 3. UPLOAD SCREENSHOTS MULTIPLE
        let shotsInput = document.getElementById('screenshots');
        if (shotsInput.files.length > 0) {
            let totalShots = shotsInput.files.length;
            logTerminal(`Memproses galeri screenshot (${totalShots} file)...`);
            
            for (let i = 0; i < totalShots; i++) {
                let file = shotsInput.files[i];
                logTerminal(`Kompresi screenshot [${i+1}/${totalShots}]: ${file.name}`);

                let fd = new FormData();
                fd.append('action', 'upload_image');
                fd.append('image', file);
                fd.append('is_thumb', '0');

                let res = await postData(fd);
                if (res.status === 'success') {
                    screenshotPaths.push(res.path);
                    logTerminal(`Screenshot [${i+1}] berhasil.`, 'success');
                } else {
                    logTerminal(`Gagal screenshot [${i+1}]: ${res.message}`, 'error');
                }

                let percent = Math.round(((i + 1) / totalShots) * 100);
                document.getElementById('barShots').style.width = percent + '%';
                document.getElementById('pctShots').innerText = percent + '%';
            }
        } else {
            document.getElementById('barShots').style.width = '100%';
            document.getElementById('pctShots').innerText = '100% (Lewat)';
        }

        // 4. SIMPAN METADATA KE DATABASE
        logTerminal("Menyimpan data aplikasi ke database...");
        let finalFd = new FormData();
        finalFd.append('action', 'save_app_data');
        finalFd.append('csrf_token', csrfToken);
        finalFd.append('title', document.getElementById('title').value);
        finalFd.append('description', document.getElementById('description').value);
        finalFd.append('app_type', appType);
        finalFd.append('documentation', document.getElementById('documentation').value);
        finalFd.append('source_link', sourceLink);
        finalFd.append('thumbnail', thumbnailPath);
        screenshotPaths.forEach(path => finalFd.append('screenshots[]', path));

        let dbRes = await postData(finalFd);
        if (dbRes.status === 'success') {
            logTerminal(dbRes.message, 'success');
            setTimeout(() => {
                alert("Repositori Berhasil Ditambahkan!");
                window.location.reload();
            }, 1000);
        } else {
            throw new Error(dbRes.message);
        }

    } catch (err) {
        logTerminal("FATAL ERROR: " + err.message, 'error');
        alert("Gagal: " + err.message);
        setTimeout(() => { modal.style.display = 'none'; }, 4000);
    }
});

function postData(formData) {
    return fetch('upload_chunk.php', {
        method: 'POST',
        body: formData
    }).then(async res => {
        let text = await res.text();
        try {
            return JSON.parse(text);
        } catch(e) {
            console.error("Raw text:", text);
            throw new Error("Server merespon teks non-JSON (Cek error server).");
        }
    });
}
</script>
</body>
</html>