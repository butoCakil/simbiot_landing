<?php
// admin/edit_app.php
require_once 'auth.php';
requireLogin();
$db = getDB();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $db->prepare("SELECT * FROM apps WHERE id = ?");
$stmt->execute([$id]);
$app = $stmt->fetch();

if (!$app) {
    die("Aplikasi tidak ditemukan.");
}

$existingShots = json_decode($app['screenshots'], true) ?? [];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Aplikasi - Admin Simbiot</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --bg-card: #1e293b; --bg-input: #0f172a; --border-color: #334155;
            --text-main: #f8fafc; --text-muted: #94a3b8; --accent-color: #3b82f6;
            --accent-hover: #2563eb; --danger-bg: #7f1d1d; --danger-text: #fca5a5; --success-text: #4ade80;
        }
        .admin-container { max-width: 900px; margin: 40px auto; padding: 0 20px; font-family: 'Segoe UI', Tahoma, sans-serif; color: var(--text-main); }
        .header-title { font-size: 1.8rem; font-weight: 600; margin-bottom: 5px; }
        .back-link { color: var(--accent-color); text-decoration: none; font-size: 0.95rem; display: inline-block; margin-bottom: 25px; }
        .card { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 30px; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; color: var(--text-muted); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin-bottom: 8px; }
        .form-control { width: 100%; padding: 12px 15px; background-color: var(--bg-input); color: var(--text-main); border: 1px solid var(--border-color); border-radius: 8px; box-sizing: border-box; }
        .form-text { color: var(--text-muted); font-size: 0.85em; margin-top: 6px; display: block; }
        .section-box { background: rgba(15, 23, 42, 0.4); border: 1px solid var(--border-color); padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .btn-submit { padding: 12px 24px; background-color: var(--accent-color); color: white; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; width: 100%; }
        
        /* Modal Multi-Progress & Terminal Log */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.9); z-index: 9999; justify-content: center; align-items: center; flex-direction: column; }
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
    </style>
</head>
<body>

<!-- MODAL MULTI-PROGRESS & TERMINAL LOG -->
<div class="modal-overlay" id="loadingModal">
    <div class="modal-box">
        <h3 style="margin-top:0; margin-bottom: 5px; font-size: 1.2rem;">Memperbarui & Memproses Berkas...</h3>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 20px;">Sistem memperbarui data repositori secara modular.</p>
        
        <!-- Bar 1: File Utama -->
        <div class="progress-row">
            <div class="progress-label"><span id="lblMainFile">1. Unggah Potongan File Pengganti</span><span id="pctMainFile">0%</span></div>
            <div class="progress-container"><div class="progress-bar" id="barMainFile"></div></div>
        </div>

        <!-- Bar 2: Merge -->
        <div class="progress-row">
            <div class="progress-label"><span id="lblMerge">2. Menyatukan Berkas di Server</span><span id="pctMerge">0%</span></div>
            <div class="progress-container"><div class="progress-bar" id="barMerge"></div></div>
        </div>

        <!-- Bar 3: Thumbnail -->
        <div class="progress-row">
            <div class="progress-label"><span id="lblThumb">3. Thumbnail Utama</span><span id="pctThumb">0%</span></div>
            <div class="progress-container"><div class="progress-bar" id="barThumb"></div></div>
        </div>

        <!-- Bar 4: Screenshots -->
        <div class="progress-row">
            <div class="progress-label"><span id="lblShots">4. Galeri Tangkapan Layar Baru</span><span id="pctShots">0%</span></div>
            <div class="progress-container"><div class="progress-bar" id="barShots"></div></div>
        </div>

        <div class="terminal-box" id="terminalLog">
            <div class="terminal-line">[SYSTEM] Menyiapkan modul pembaruan...</div>
        </div>
    </div>
</div>

<div class="admin-container">
    <h2 class="header-title">Edit Aplikasi / Skrip</h2>
    <a href="apps.php" class="back-link">&laquo; Kembali ke Daftar Aplikasi</a>
    
    <div id="pesanContainer"></div>

    <div class="card">
        <form id="editForm">
            <input type="hidden" id="csrf_token" value="<?= htmlspecialchars(csrfToken()); ?>">
            <input type="hidden" id="app_id" value="<?= $app['id']; ?>">
            <input type="hidden" id="current_source" value="<?= htmlspecialchars($app['source_link']); ?>">
            <input type="hidden" id="current_thumbnail" value="<?= htmlspecialchars($app['thumbnail']); ?>">
            
            <div class="form-group">
                <label>Judul Aplikasi</label>
                <input type="text" id="title" class="form-control" required value="<?= htmlspecialchars($app['title']); ?>">
            </div>
            
            <div class="form-group">
                <label>Tipe Distribusi</label>
                <select id="appTypeSelect" class="form-control" required onchange="toggleSourceInput()">
                    <option value="exe" <?= ($app['app_type'] == 'exe') ? 'selected' : ''; ?>>File Executable (.exe / .zip / .rar)</option>
                    <option value="cmd" <?= ($app['app_type'] == 'cmd') ? 'selected' : ''; ?>>Skrip CMD / Terminal / Shell</option>
                    <option value="chrome_ext" <?= ($app['app_type'] == 'chrome_ext') ? 'selected' : ''; ?>>Ekstensi Browser</option>
                    <option value="github" <?= ($app['app_type'] == 'github') ? 'selected' : ''; ?>>Repositori Source Code (GitHub)</option>
                    <option value="other" <?= ($app['app_type'] == 'other') ? 'selected' : ''; ?>>Format Lainnya</option>
                </select>
            </div>

            <!-- SECTION 1: SUMBER PROGRAM -->
            <div class="section-box">
                <h4 style="margin-top:0; color: var(--accent-color); font-size: 0.95rem; text-transform: uppercase;">1. Sumber Program / Instalasi</h4>
                <div style="background: rgba(0,0,0,0.2); padding: 10px 15px; border-radius: 6px; margin-bottom: 15px; font-family: monospace; font-size: 0.85rem; color: var(--text-muted);">
                    Saat ini: <?= htmlspecialchars($app['source_link']); ?>
                </div>

                <div class="form-group" id="fileUploadWrapper" style="margin-bottom:0;">
                    <label>Ganti Berkas Program (Opsional, biarkan kosong jika tetap)</label>
                    <input type="file" id="app_file" class="form-control" style="background: var(--bg-input);">
                </div>
                <div class="form-group" id="urlLinkWrapper" style="display: none; margin-bottom:0;">
                    <label>Tautan Repositori / URL Eksternal</label>
                    <input type="text" id="github_link" class="form-control" value="<?= ($app['app_type'] === 'github') ? htmlspecialchars($app['source_link']) : ''; ?>">
                </div>
            </div>

            <!-- SECTION 2: MEDIA VISUAL -->
            <div class="section-box">
                <h4 style="margin-top:0; color: var(--accent-color); font-size: 0.95rem; text-transform: uppercase;">2. Media Visual & Galeri</h4>
                
                <div class="form-group">
                    <label>Thumbnail Saat Ini</label>
                    <?php if (!empty($app['thumbnail'])): ?>
                        <img src="/<?= htmlspecialchars($app['thumbnail']); ?>" style="width: 70px; height: 70px; object-fit: cover; border-radius: 6px; margin-bottom: 10px; display: block;">
                    <?php endif; ?>
                    <label>Ganti Thumbnail Utama (Opsional)</label>
                    <input type="file" id="thumbnail" accept="image/*" class="form-control" style="background: var(--bg-input);">
                </div>

                <div class="form-group" style="margin-bottom:0;">
                    <label style="margin-bottom: 10px; display: block;">Tangkapan Layar Saat Ini (Centang untuk Hapus)</label>
                    <?php if (!empty($existingShots)): ?>
                        <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 15px;">
                            <?php foreach ($existingShots as $shot): ?>
                                <div style="background: rgba(0,0,0,0.2); padding: 8px; border-radius: 6px; text-align: center; width: 120px; border: 1px solid var(--border-color);">
                                    <img src="/<?= htmlspecialchars($shot); ?>" style="width: 100%; height: 60px; object-fit: cover; border-radius: 4px; margin-bottom: 6px;">
                                    <label style="font-size: 0.75rem; color: var(--danger-text); cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px;">
                                        <input type="checkbox" class="del-screenshot" value="<?= htmlspecialchars($shot); ?>"> Hapus
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 15px;">Belum ada tangkapan layar.</p>
                    <?php endif; ?>

                    <label>Tambah Tangkapan Layar Baru (Opsional)</label>
                    <input type="file" id="screenshots" accept="image/*" multiple class="form-control" style="background: var(--bg-input);">
                </div>
            </div>
            
            <div class="form-group">
                <label>Deskripsi Singkat</label>
                <textarea id="description" class="form-control" rows="3" required><?= htmlspecialchars($app['description']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Dokumentasi & Langkah Penerapan (Mendukung HTML)</label>
                <textarea id="documentation" class="form-control" rows="5"><?= htmlspecialchars($app['documentation']); ?></textarea>
            </div>
            
            <button type="submit" class="btn-submit">Simpan Perubahan</button>
        </form>
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
document.addEventListener("DOMContentLoaded", toggleSourceInput);

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

document.getElementById('editForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    let modal = document.getElementById('loadingModal');
    let term = document.getElementById('terminalLog');
    term.innerHTML = '<div class="terminal-line">[SYSTEM] Memulai pembaruan data...</div>';
    modal.style.display = 'flex';

    let appId = document.getElementById('app_id').value;
    let sourceLink = document.getElementById('current_source').value;
    let thumbnailPath = document.getElementById('current_thumbnail').value;
    
    // Kumpulkan screenshot lama yang TIDAK dicentang
    let keptScreenshots = [];
    document.querySelectorAll('.del-screenshot').forEach(cb => {
        if (!cb.checked) keptScreenshots.push(cb.value);
    });
    let newScreenshotPaths = [];

    let appType = document.getElementById('appTypeSelect').value;
    let csrfToken = document.getElementById('csrf_token').value;

    try {
        // 1. CHUNK UPLOAD FILE UTAMA BARU (Jika Diunggah)
        if (appType === 'github') {
            sourceLink = document.getElementById('github_link').value;
            logTerminal("Menggunakan URL GitHub baru: " + sourceLink, 'success');
            document.getElementById('barMainFile').style.width = '100%';
            document.getElementById('pctMainFile').innerText = '100%';
            document.getElementById('barMerge').style.width = '100%';
            document.getElementById('pctMerge').innerText = '100%';
        } else {
            let fileInput = document.getElementById('app_file');
            if (fileInput.files.length > 0) {
                let file = fileInput.files[0];
                logTerminal(`Memecah dan mengunggah file pengganti (${file.name})...`);
                
                const chunkSize = 1024 * 1024;
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
                    if (res.status !== 'success') throw new Error(res.message || "Gagal mengunggah chunk ke-" + i);

                    let percent = Math.round(((i + 1) / totalChunks) * 100);
                    document.getElementById('barMainFile').style.width = percent + '%';
                    document.getElementById('pctMainFile').innerText = percent + '%';
                }

                // 2. MERGE CHUNKS
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
                    logTerminal("Berkas program baru berhasil disatukan utuh!", 'success');
                } else {
                    throw new Error(mergeRes.message);
                }
            } else {
                document.getElementById('barMainFile').style.width = '100%';
                document.getElementById('pctMainFile').innerText = '100% (Tetap)';
                document.getElementById('barMerge').style.width = '100%';
                document.getElementById('pctMerge').innerText = '100% (Tetap)';
            }
        }

        // 3. UPLOAD THUMBNAIL BARU (Jika Diunggah)
        let thumbInput = document.getElementById('thumbnail');
        if (thumbInput.files.length > 0) {
            logTerminal("Mengompres thumbnail baru...");
            let fd = new FormData();
            fd.append('action', 'upload_image');
            fd.append('image', thumbInput.files[0]);
            fd.append('is_thumb', '1');

            let res = await postData(fd);
            if (res.status === 'success') {
                thumbnailPath = res.path;
                document.getElementById('barThumb').style.width = '100%';
                document.getElementById('pctThumb').innerText = '100%';
                logTerminal("Thumbnail baru sukses diterapkan.", 'success');
            } else {
                throw new Error(res.message);
            }
        } else {
            document.getElementById('barThumb').style.width = '100%';
            document.getElementById('pctThumb').innerText = '100% (Tetap)';
        }

        // 4. UPLOAD SCREENSHOTS BARU (Jika Ada)
        let shotsInput = document.getElementById('screenshots');
        if (shotsInput.files.length > 0) {
            let totalShots = shotsInput.files.length;
            logTerminal(`Menambahkan ${totalShots} tangkapan layar baru...`);
            
            for (let i = 0; i < totalShots; i++) {
                let file = shotsInput.files[i];
                let fd = new FormData();
                fd.append('action', 'upload_image');
                fd.append('image', file);
                fd.append('is_thumb', '0');

                let res = await postData(fd);
                if (res.status === 'success') {
                    newScreenshotPaths.push(res.path);
                    logTerminal(`Screenshot baru [${i+1}] berhasil diproses.`, 'success');
                }
                let percent = Math.round(((i + 1) / totalShots) * 100);
                document.getElementById('barShots').style.width = percent + '%';
                document.getElementById('pctShots').innerText = percent + '%';
            }
        } else {
            document.getElementById('barShots').style.width = '100%';
            document.getElementById('pctShots').innerText = '100% (Tetap)';
        }

        // Gabungkan screenshot lama yang dipertahankan dengan yang baru
        let finalScreenshots = keptScreenshots.concat(newScreenshotPaths);

        // 5. SIMPAN PERUBAHAN KE DATABASE
        logTerminal("Memperbarui data repositori ke database...");
        let finalFd = new FormData();
        finalFd.append('action', 'update_app_data');
        finalFd.append('id', appId);
        finalFd.append('csrf_token', csrfToken);
        finalFd.append('title', document.getElementById('title').value);
        finalFd.append('description', document.getElementById('description').value);
        finalFd.append('app_type', appType);
        finalFd.append('documentation', document.getElementById('documentation').value);
        finalFd.append('source_link', sourceLink);
        finalFd.append('thumbnail', thumbnailPath);
        finalScreenshots.forEach(path => finalFd.append('screenshots[]', path));

        let dbRes = await postData(finalFd);
        if (dbRes.status === 'success') {
            logTerminal(dbRes.message, 'success');
            setTimeout(() => {
                alert("Perubahan Berhasil Disimpan!");
                window.location.href = 'apps.php';
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
            throw new Error("Server merespon teks non-JSON.");
        }
    });
}
</script>
</body>
</html>