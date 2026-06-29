<?php
// admin/logout.php
require_once __DIR__ . '/auth.php';
startAdminSession();
session_unset();
session_destroy();
header('Location: /admin/login.php');
exit;
