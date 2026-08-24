<?php
// api/download.php

require_once dirname(__DIR__) . '/config.php';

// Validasi keberadaan ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID Aplikasi tidak valid.");
}

$app_id = (int)$_GET['id'];

// Koneksi PDO Public
try {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
    $db  = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Sistem sedang sibuk. Silakan coba lagi nanti.");
}

try {
    // 1. Cek apakah aplikasi exist
    $stmt = $db->prepare("SELECT source_link FROM apps WHERE id = ?");
    $stmt->execute([$app_id]);
    $app = $stmt->fetch();

    if (!$app) {
        die("Aplikasi tidak ditemukan di repositori.");
    }

    // 2. Ambil IP Address (Menangani Reverse Proxy seperti Cloudflare)
    $ip_address = $_SERVER['HTTP_CF_CONNECTING_IP'] 
                ?? $_SERVER['HTTP_X_FORWARDED_FOR'] 
                ?? $_SERVER['REMOTE_ADDR'] 
                ?? 'UNKNOWN';
    
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';

    // 3. Catat ke tabel app_stats
    $stat_stmt = $db->prepare("INSERT INTO app_stats (app_id, ip_address, user_agent) VALUES (?, ?, ?)");
    $stat_stmt->execute([$app_id, $ip_address, $user_agent]);

    // 4. Update akumulasi total_downloads di tabel apps
    $update_stmt = $db->prepare("UPDATE apps SET total_downloads = total_downloads + 1 WHERE id = ?");
    $update_stmt->execute([$app_id]);

    // 5. Eksekusi Redirect atau Download Berdasarkan Jenis Tautan
    $target_url = $app['source_link'];
    $isExternalLink = (strpos($target_url, 'http://') === 0 || strpos($target_url, 'https://') === 0);

    if ($isExternalLink) {
        // Jika tautan eksternal (GitHub, GDrive, dll), langsung redirect ke URL tersebut
        header("Location: " . $target_url);
        exit;
    } else {
        // Jika berupa berkas lokal di server (contoh: "uploads/files/...")
        $filePath = dirname(__DIR__) . '/' . ltrim($target_url, '/');
        if (!file_exists($filePath)) {
            $filePath = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($target_url, '/');
        }

        if (file_exists($filePath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            flush();
            readfile($filePath);
            exit;
        } else {
            die("Berkas fisik tidak ditemukan di server.");
        }
    }

} catch (PDOException $e) {
    die("Terjadi kesalahan saat memproses permintaan.");
}