<?php
require_once '../config/database.php';

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    $base = defined('BASE_URL') ? BASE_URL : '/';
    header('Location: ' . $base . 'admin/login.php');
    exit;
}

// Destroy session
session_destroy();
$base = defined('BASE_URL') ? BASE_URL : '/';
header('Location: ' . $base . 'admin/login.php');
exit;
?>
