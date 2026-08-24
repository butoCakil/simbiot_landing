<?php
// admin/upload_chunk.php
@ini_set('memory_limit', '512M');
@ini_set('max_execution_time', '300');

require_once 'auth.php';
requireLogin();

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$db = getDB();

if ($action === 'upload_chunk') {
    $fileData = $_FILES['chunk'] ?? null;
    $fileName = preg_replace("/[^a-zA-Z0-9\._-]/", "", $_POST['file_name'] ?? 'app.exe');
    $chunkIndex = (int)($_POST['chunk_index'] ?? 0);

    if (!$fileData || $fileData['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menerima potongan file.']);
        exit;
    }

    $uploadDir = dirname(__DIR__) . '/uploads/files/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    // Simpan tiap chunk dengan penanda unik indeksnya
    $chunkPath = $uploadDir . $fileName . '.part_' . $chunkIndex;
    if (move_uploaded_file($fileData['tmp_name'], $chunkPath)) {
        echo json_encode(['status' => 'success', 'chunk' => $chunkIndex]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan chunk ke server.']);
    }
    exit;
}

if ($action === 'merge_chunks') {
    $fileName = preg_replace("/[^a-zA-Z0-9\._-]/", "", $_POST['file_name'] ?? 'app.exe');
    $totalChunks = (int)($_POST['total_chunks'] ?? 0);

    $uploadDir = dirname(__DIR__) . '/uploads/files/';
    $finalFileName = time() . '_' . $fileName;
    $finalPath = $uploadDir . $finalFileName;

    $out = fopen($finalPath, 'wb');
    if (!$out) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal membuat file gabungan di server.']);
        exit;
    }

    for ($i = 0; $i < $totalChunks; $i++) {
        $chunkPath = $uploadDir . $fileName . '.part_' . $i;
        if (file_exists($chunkPath)) {
            $in = fopen($chunkPath, 'rb');
            while ($buff = fread($in, 4096)) {
                fwrite($out, $buff);
            }
            fclose($in);
            @unlink($chunkPath); // Hapus chunk setelah digabung
        } else {
            fclose($out);
            echo json_encode(['status' => 'error', 'message' => 'Potongan file ke-' . $i . ' hilang.']);
            exit;
        }
    }
    fclose($out);

    echo json_encode([
        'status' => 'complete',
        'path' => 'uploads/files/' . $finalFileName
    ]);
    exit;
}

if ($action === 'upload_image') {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengunggah gambar.']);
        exit;
    }
    $targetDir = dirname(__DIR__) . '/uploads/images';
    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

    $fileTmpPath = $_FILES['image']['tmp_name'];
    $isThumb = isset($_POST['is_thumb']) && $_POST['is_thumb'] === '1';
    $maxWidth = $isThumb ? 600 : 1200;

    $newFileName = time() . '_' . mt_rand(1000, 9999) . '.webp';
    $destPath = $targetDir . '/' . $newFileName;

    list($width, $height, $type) = getimagesize($fileTmpPath);
    switch ($type) {
        case IMAGETYPE_JPEG: $img = imagecreatefromjpeg($fileTmpPath); break;
        case IMAGETYPE_PNG:  $img = imagecreatefrompng($fileTmpPath); break;
        case IMAGETYPE_WEBP: $img = imagecreatefromwebp($fileTmpPath); break;
        default: echo json_encode(['status' => 'error', 'message' => 'Format gambar tidak didukung.']); exit;
    }

    if ($width > $maxWidth) {
        $newW = $maxWidth;
        $newH = floor($height * ($maxWidth / $width));
        $tmp = imagecreatetruecolor($newW, $newH);
        imagealphablending($tmp, false); imagesavealpha($tmp, true);
        imagecopyresampled($tmp, $img, 0, 0, 0, 0, $newW, $newH, $width, $height);
        imagedestroy($img); $img = $tmp;
    }

    imagewebp($img, $destPath, 80);
    imagedestroy($img);

    echo json_encode(['status' => 'success', 'path' => 'uploads/images/' . $newFileName]);
    exit;
}

if ($action === 'save_app_data') {
    verifyCsrf();
    $title         = trim($_POST['title'] ?? '');
    $description   = trim($_POST['description'] ?? '');
    $app_type      = $_POST['app_type'] ?? 'other';
    $documentation = trim($_POST['documentation'] ?? '');
    $source_link   = trim($_POST['source_link'] ?? '');
    $thumbnail     = trim($_POST['thumbnail'] ?? '');
    $screenshots   = $_POST['screenshots'] ?? [];

    $screenshotsJson = !empty($screenshots) ? json_encode($screenshots) : null;

    $stmt = $db->prepare("INSERT INTO apps (title, description, app_type, source_link, documentation, thumbnail, screenshots) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$title, $description, $app_type, $source_link, $documentation, $thumbnail, $screenshotsJson])) {
        echo json_encode(['status' => 'success', 'message' => 'Repositori berhasil ditambahkan!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan ke database.']);
    }
    exit;
}

if ($action === 'update_app_data') {
    verifyCsrf();
    $id            = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $title         = trim($_POST['title'] ?? '');
    $description   = trim($_POST['description'] ?? '');
    $app_type      = $_POST['app_type'] ?? 'other';
    $documentation = trim($_POST['documentation'] ?? '');
    $source_link   = trim($_POST['source_link'] ?? '');
    $thumbnail     = trim($_POST['thumbnail'] ?? '');
    $screenshots   = $_POST['screenshots'] ?? [];

    $screenshotsJson = !empty($screenshots) ? json_encode($screenshots) : null;

    $stmt = $db->prepare("UPDATE apps SET title=?, description=?, app_type=?, source_link=?, documentation=?, thumbnail=?, screenshots=? WHERE id=?");
    if ($stmt->execute([$title, $description, $app_type, $source_link, $documentation, $thumbnail, $screenshotsJson, $id])) {
        echo json_encode(['status' => 'success', 'message' => 'Perubahan aplikasi berhasil disimpan!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui database.']);
    }
    exit;
}