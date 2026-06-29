<?php
// api/feedback.php — Mengembalikan feedback approved sebagai JSON
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once dirname(__DIR__) . '/config.php';

$role_labels = [
    'siswa'           => 'Siswa',
    'guru'            => 'Guru / Pengajar',
    'mahasiswa'       => 'Mahasiswa',
    'dosen'           => 'Dosen',
    'pengembang'      => 'Pengembang IoT',
    'hobi'            => 'Hobi',
    'lainnya'         => 'Lainnya',
];

try {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
    $db  = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $stmt = $db->query("
        SELECT name, role, message, response, created_at
        FROM feedback
        WHERE status = 'approved'
        ORDER BY created_at DESC
        LIMIT 30
    ");

    $data = array_map(function ($row) use ($role_labels) {
        return [
            'name'     => $row['name'],
            'role'     => $role_labels[$row['role']] ?? $row['role'],
            'message'  => $row['message'],
            'response' => $row['response'],
            'date'     => date('d M Y', strtotime($row['created_at'])),
        ];
    }, $stmt->fetchAll());

    echo json_encode(['success' => true, 'data' => $data]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'data' => []]);
}
