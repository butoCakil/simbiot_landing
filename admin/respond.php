<?php
// admin/respond.php — POST handler: approve / reject / pending

require_once __DIR__ . '/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/dashboard.php');
    exit;
}

verifyCsrf();

$id       = (int) ($_POST['id']          ?? 0);
$action   = trim($_POST['action']        ?? '');
$response = trim($_POST['response']      ?? '');
$tab      = trim($_POST['redirect_tab']  ?? 'pending');

$valid_actions = ['approve', 'reject', 'pending'];
$valid_tabs    = ['pending', 'approved', 'rejected'];

if (!$id || !in_array($action, $valid_actions, true)) {
    header('Location: /admin/dashboard.php');
    exit;
}
if (!in_array($tab, $valid_tabs, true)) {
    $tab = 'pending';
}

try {
    $db = getDB();

    $status_map = ['approve' => 'approved', 'reject' => 'rejected', 'pending' => 'pending'];
    $db_status  = $status_map[$action];

    if ($response !== '') {
        $stmt = $db->prepare("UPDATE feedback SET status = ?, response = ?, responded_at = NOW() WHERE id = ?");
        $stmt->execute([$db_status, $response, $id]);
    } else {
        $stmt = $db->prepare("UPDATE feedback SET status = ? WHERE id = ?");
        $stmt->execute([$db_status, $id]);
    }
} catch (PDOException $e) {
    // Fail silently, tetap redirect
}

// Arahkan ke tab yang sesuai dengan aksi yang dilakukan
$redirect_tab = match ($action) {
    'approve' => 'approved',
    'reject'  => 'rejected',
    default   => 'pending',
};

header("Location: /admin/dashboard.php?tab={$redirect_tab}");
exit;
