<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireLogin();

$role = $_SESSION['role'];
$redirect = '';

switch ($role) {
    case 'admin':
        $redirect = 'modules/admin/dashboard.php';
        break;
    case 'lecturer':
        $redirect = 'modules/lecturer/dashboard.php';
        break;
    case 'student':
        $redirect = 'modules/student/dashboard.php';
        break;
    default:
        $redirect = 'index.php';
}
header("Location: $redirect");
exit;
?>