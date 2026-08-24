<?php
// admin/upload_processor.php

// Longgarkan batas server khusus untuk proses upload file besar
@ini_set('memory_limit', '512M');
@ini_set('max_execution_time', '300');
@ini_set('post_max_size', '100M');
@ini_set('upload_max_filesize', '100M');

require_once 'auth.php';
requireLogin();

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$db = getDB();

if ($action === 'upload_main_file') {
    // Tangkap semua output/warning tersembunyi agar tidak merusak JSON
    ob_start();

    if (!isset($_FILES['app_file']) || $_FILES['app_file']['error'] !== UPLOAD_ERR_OK) {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Gagal menerima file. Error code: ' . ($_FILES['app_file']['error'] ?? 'unknown')]);
        exit;
    }
    
    $uploadDir = dirname(__DIR__) . '/uploads/files/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9\._-]/", "", basename($_FILES['app_file']['name']));
    $destPath = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['app_file']['tmp_name'], $destPath)) {
        // Bersihkan semua buffer output/warning PHP yang mungkin ikut tercetak
        $debugOutput = ob_get_clean();
        
        echo json_encode([
            'status' => 'success', 
            'path' => 'uploads/files/' . $fileName,
            'debug' => trim($debugOutput) // Mengirim pesan debug jika ada warning
        ]);
    } else {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Gagal memindahkan file ke direktori server.']);
    }
    exit;
}

if ($action === 'upload_thumbnail') {
    if (!isset($_FILES['thumbnail']) || $_FILES['thumbnail']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'message' => 'Thumbnail tidak valid.']);
        exit;
    }
    $targetDir = dirname(__DIR__) . '/uploads/images';
    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

    $fileTmpPath = $_FILES['thumbnail']['tmp_name'];
    $newFileName = time() . '_thumb_' . mt_rand(1000, 9999) . '.webp';
    $destPath = $targetDir . '/' . $newFileName;

    list($width, $height, $type) = getimagesize($fileTmpPath);
    switch ($type) {
        case IMAGETYPE_JPEG: $img = imagecreatefromjpeg($fileTmpPath); break;
        case IMAGETYPE_PNG:  $img = imagecreatefrompng($fileTmpPath); break;
        case IMAGETYPE_WEBP: $img = imagecreatefromwebp($fileTmpPath); break;
        default: echo json_encode(['status' => 'error', 'message' => 'Format thumbnail tidak didukung.']); exit;
    }

    if ($width > 600) {
        $newW = 600;
        $newH = floor($height * (600 / $width));
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

if ($action === 'upload_single_screenshot') {
    if (!isset($_FILES['screenshot']) || $_FILES['screenshot']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'message' => 'Screenshot gagal diterima.']);
        exit;
    }
    $targetDir = dirname(__DIR__) . '/uploads/images';
    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

    $fileTmpPath = $_FILES['screenshot']['tmp_name'];
    $newFileName = time() . '_shot_' . mt_rand(1000, 9999) . '.webp';
    $destPath = $targetDir . '/' . $newFileName;

    list($width, $height, $type) = getimagesize($fileTmpPath);
    switch ($type) {
        case IMAGETYPE_JPEG: $img = imagecreatefromjpeg($fileTmpPath); break;
        case IMAGETYPE_PNG:  $img = imagecreatefrompng($fileTmpPath); break;
        case IMAGETYPE_WEBP: $img = imagecreatefromwebp($fileTmpPath); break;
        default: echo json_encode(['status' => 'error', 'message' => 'Format screenshot tidak didukung.']); exit;
    }

    if ($width > 1200) {
        $newW = 1200;
        $newH = floor($height * (1200 / $width));
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
    $id            = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $title         = trim($_POST['title'] ?? '');
    $description   = trim($_POST['description'] ?? '');
    $app_type      = $_POST['app_type'] ?? 'other';
    $documentation = trim($_POST['documentation'] ?? '');
    $source_link   = trim($_POST['source_link'] ?? '');
    $thumbnail     = trim($_POST['thumbnail'] ?? '');
    $screenshots   = $_POST['screenshots'] ?? []; // Array path

    $screenshotsJson = !empty($screenshots) ? json_encode($screenshots) : null;

    if ($id > 0) {
        // Mode Edit
        $stmt = $db->prepare("UPDATE apps SET title=?, description=?, app_type=?, source_link=?, documentation=?, thumbnail=?, screenshots=? WHERE id=?");
        $success = $stmt->execute([$title, $description, $app_type, $source_link, $documentation, $thumbnail, $screenshotsJson, $id]);
    } else {
        // Mode Tambah Baru
        $stmt = $db->prepare("INSERT INTO apps (title, description, app_type, source_link, documentation, thumbnail, screenshots) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $success = $stmt->execute([$title, $description, $app_type, $source_link, $documentation, $thumbnail, $screenshotsJson]);
    }

    if ($success) {
        echo json_encode(['status' => 'success', 'message' => 'Data aplikasi berhasil disimpan ke database!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan ke database.']);
    }
    exit;
}