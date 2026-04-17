<?php
require_once '../config/database.php';

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

// Destroy session
session_destroy();
header('Location: login.php');
exit;
?>