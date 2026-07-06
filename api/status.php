<?php
// api/status.php — Cek status semua subdomain secara paralel
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

// ----------------------------------------------------------------
// Konfigurasi target
// ----------------------------------------------------------------
$targets = [
    'adlean'    => ['url' => 'https://adlean.simbiot.id',    'type' => 'http'],
    'kitacatat' => ['url' => 'https://kitacatat.simbiot.id', 'type' => 'http'],
    'broker'    => ['url' => 'https://broker.simbiot.id/api/public/status', 'type' => 'json', 'key' => 'broker.active'],
    'panel'     => ['url' => 'https://broker.simbiot.id/api/public/status', 'type' => 'json', 'key' => 'broker.active'],
    'ben'       => ['url' => 'https://ben.simbiot.id',       'type' => 'http'],
];

// ----------------------------------------------------------------
// Inisialisasi curl_multi
// ----------------------------------------------------------------
$mh      = curl_multi_init();
$handles = [];

foreach ($targets as $key => $cfg) {
    $ch = curl_init($cfg['url']);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 6,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'SimbIoT-StatusChecker/1.0',
    ]);
    curl_multi_add_handle($mh, $ch);
    $handles[$key] = $ch;
}

// Jalankan semua request paralel
$running = null;
do {
    curl_multi_exec($mh, $running);
    curl_multi_select($mh);
} while ($running > 0);

// ----------------------------------------------------------------
// Proses hasil
// ----------------------------------------------------------------
$result = [];

foreach ($handles as $key => $ch) {
    $cfg      = $targets[$key];
    $body     = curl_multi_getcontent($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errno    = curl_errno($ch);
    curl_multi_remove_handle($mh, $ch);
    curl_close($ch);

    if ($errno || $httpCode === 0) {
        $result[$key] = false;
        continue;
    }

    if ($cfg['type'] === 'http') {
        $result[$key] = ($httpCode >= 200 && $httpCode < 400);
    } elseif ($cfg['type'] === 'json') {
        // Sudah punya hasil dari broker — tidak perlu fetch ulang untuk panel
        if ($key === 'panel' && isset($result['broker'])) {
            $result[$key] = $result['broker'];
            continue;
        }
        $data = json_decode($body, true);
        if (!$data) {
            $result[$key] = false;
            continue;
        }
        // Ambil nilai nested key (misal: "broker.active")
        $keys  = explode('.', $cfg['key']);
        $value = $data;
        foreach ($keys as $k) {
            $value = $value[$k] ?? null;
        }
        $result[$key] = ($value === true);
    }
}

curl_multi_close($mh);

echo json_encode(['success' => true, 'status' => $result]);