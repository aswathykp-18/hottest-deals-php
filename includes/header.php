<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
$base = defined('BASE_URL') ? BASE_URL : '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Hottest Property Deals'; ?></title>
    <link rel="stylesheet" href="<?php echo $base; ?>assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <h1 class="logo"><i class="fas fa-home"></i> Hottest Deals</h1>
                <nav class="main-nav">
                    <a href="<?php echo $base; ?>index.php" <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-th-large"></i> All Deals
                    </a>
                    <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']): ?>
                        <a href="<?php echo $base; ?>admin/dashboard.php" <?php echo strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? 'class="active"' : ''; ?>>
                            <i class="fas fa-cog"></i> Admin Panel
                        </a>
                        <a href="<?php echo $base; ?>admin/logout.php" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    <?php else: ?>
                        <a href="<?php echo $base; ?>admin/login.php">
                            <i class="fas fa-lock"></i> Admin Login
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>
    <main class="main-content">
