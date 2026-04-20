<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'whatsapp_platform');

// Application Configuration
define('APP_NAME', 'WhatsApp Marketing Platform');
define('APP_VERSION', '1.0.0');

// Auto-detect BASE_URL from folder path
$script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
// Remove /config, /includes, /api, /admin subfolders from path
$base_dir = preg_replace('#/(config|includes|api|admin)$#', '', $script_dir);
if ($base_dir === '') $base_dir = '/';
if (substr($base_dir, -1) !== '/') $base_dir .= '/';
define('BASE_URL', $base_dir);

// WhatsApp API Configuration (Mock Mode)
define('WA_API_MODE', 'mock'); // 'mock' or 'live'
define('WA_API_URL', 'https://graph.facebook.com/v17.0/');
define('WA_PHONE_NUMBER_ID', '');
define('WA_ACCESS_TOKEN', '');

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getDbConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function sanitize($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    if ($diff->d > 0) return $diff->d . 'd ago';
    if ($diff->h > 0) return $diff->h . 'h ago';
    if ($diff->i > 0) return $diff->i . 'm ago';
    return 'Just now';
}
?>
