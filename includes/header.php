<?php
require_once __DIR__ . '/../config/database.php';
$base = defined('BASE_URL') ? BASE_URL : '/';

// Get unread counts for sidebar
$conn_sidebar = getDbConnection();
$unread_convos = $conn_sidebar->query("SELECT COUNT(*) as cnt FROM conversations WHERE unread_count > 0")->fetch_assoc()['cnt'];
$total_contacts = $conn_sidebar->query("SELECT COUNT(*) as cnt FROM contacts WHERE status='active'")->fetch_assoc()['cnt'];
$active_campaigns = $conn_sidebar->query("SELECT COUNT(*) as cnt FROM broadcast_campaigns WHERE status IN ('draft','scheduled','sending')")->fetch_assoc()['cnt'];
$conn_sidebar->close();

$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="app-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fab fa-whatsapp"></i>
                    <span>WA Platform</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="<?php echo $base; ?>index.php" class="nav-item <?php echo $current_page == 'index' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
                <a href="<?php echo $base; ?>inbox.php" class="nav-item <?php echo $current_page == 'inbox' ? 'active' : ''; ?>">
                    <i class="fas fa-inbox"></i>
                    <span>Inbox</span>
                    <?php if ($unread_convos > 0): ?>
                        <span class="badge"><?php echo $unread_convos; ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?php echo $base; ?>contacts.php" class="nav-item <?php echo $current_page == 'contacts' ? 'active' : ''; ?>">
                    <i class="fas fa-address-book"></i>
                    <span>Contacts</span>
                    <span class="badge badge-muted"><?php echo $total_contacts; ?></span>
                </a>
                <a href="<?php echo $base; ?>groups.php" class="nav-item <?php echo $current_page == 'groups' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Groups</span>
                </a>

                <div class="nav-section">Messaging</div>
                <a href="<?php echo $base; ?>templates.php" class="nav-item <?php echo $current_page == 'templates' ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i>
                    <span>Templates</span>
                </a>
                <a href="<?php echo $base; ?>broadcast.php" class="nav-item <?php echo $current_page == 'broadcast' ? 'active' : ''; ?>">
                    <i class="fas fa-bullhorn"></i>
                    <span>Broadcast</span>
                </a>

                <div class="nav-section">Automation</div>
                <a href="<?php echo $base; ?>chatbot-flows.php" class="nav-item <?php echo $current_page == 'chatbot-flows' ? 'active' : ''; ?>">
                    <i class="fas fa-robot"></i>
                    <span>Chatbot Flows</span>
                </a>
                <a href="<?php echo $base; ?>flow-builder.php" class="nav-item <?php echo $current_page == 'flow-builder' ? 'active' : ''; ?>">
                    <i class="fas fa-project-diagram"></i>
                    <span>Flow Builder</span>
                </a>

                <div class="nav-section">Reports</div>
                <a href="<?php echo $base; ?>analytics.php" class="nav-item <?php echo $current_page == 'analytics' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Analytics</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="api-status">
                    <span class="status-dot <?php echo WA_API_MODE == 'mock' ? 'mock' : 'live'; ?>"></span>
                    <span><?php echo WA_API_MODE == 'mock' ? 'Demo Mode' : 'Live API'; ?></span>
                </div>
                <a href="<?php echo $base; ?>settings.php" class="nav-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a href="<?php echo $base; ?>logout.php" class="nav-item logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="main-wrapper">
            <header class="top-bar">
                <div class="top-bar-left">
                    <button class="sidebar-toggle" onclick="document.querySelector('.app-layout').classList.toggle('sidebar-collapsed')">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h2 class="page-title"><?php echo isset($pageTitle) ? $pageTitle : 'Dashboard'; ?></h2>
                </div>
                <div class="top-bar-right">
                    <div class="user-menu">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo $_SESSION['full_name'] ?? 'Admin'; ?></span>
                    </div>
                </div>
            </header>
            <main class="main-content">
