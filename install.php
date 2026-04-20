<?php
/**
 * WhatsApp Marketing Platform - Auto Installer
 * 
 * Run this once: http://localhost/your-folder/install.php
 * It will create the database, tables, and sample data automatically.
 */

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'whatsapp_platform';

$errors = [];
$success = [];
$step = $_GET['step'] ?? 'check';

// Step 1: Check connection
$conn = @new mysqli($db_host, $db_user, $db_pass);
if ($conn->connect_error) {
    $errors[] = "Cannot connect to MySQL: " . $conn->connect_error;
    $errors[] = "Make sure MySQL is running in XAMPP Control Panel!";
}

if (empty($errors) && ($step === 'install' || $step === 'auto')) {
    // Create database
    if ($conn->query("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci")) {
        $success[] = "Database '$db_name' created/verified";
    } else {
        $errors[] = "Could not create database: " . $conn->error;
    }

    if (empty($errors)) {
        $conn->select_db($db_name);

        // Read and execute setup.sql
        $sql_file = __DIR__ . '/setup.sql';
        if (file_exists($sql_file)) {
            $sql = file_get_contents($sql_file);

            // Remove the CREATE DATABASE and USE lines (we already selected the db)
            $sql = preg_replace('/CREATE DATABASE.*?;\s*/i', '', $sql);
            $sql = preg_replace('/USE\s+.*?;\s*/i', '', $sql);

            // Execute each statement
            $conn->multi_query($sql);
            $statement_count = 0;
            do {
                $statement_count++;
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->more_results() && $conn->next_result());

            if ($conn->error) {
                // Some errors are OK (duplicate entries on re-install)
                if (strpos($conn->error, 'Duplicate') === false && strpos($conn->error, 'already exists') === false) {
                    $errors[] = "SQL Error: " . $conn->error;
                }
            }

            $success[] = "Executed setup.sql ($statement_count statements)";
        } else {
            $errors[] = "setup.sql not found!";
        }

        // Verify tables exist
        $conn2 = new mysqli($db_host, $db_user, $db_pass, $db_name);
        $tables = ['users', 'contacts', 'contact_groups', 'contact_group_members', 'message_templates',
                    'broadcast_campaigns', 'broadcast_recipients', 'conversations', 'messages',
                    'chatbot_flows', 'analytics_events', 'webhook_logs'];

        $missing = [];
        foreach ($tables as $table) {
            $result = $conn2->query("SHOW TABLES LIKE '$table'");
            if ($result && $result->num_rows > 0) {
                $success[] = "Table '$table' OK";
            } else {
                $missing[] = $table;
            }
        }

        if (!empty($missing)) {
            $errors[] = "Missing tables: " . implode(', ', $missing);
        }

        // Verify admin user
        $result = $conn2->query("SELECT id FROM users WHERE username = 'admin'");
        if ($result && $result->num_rows > 0) {
            $success[] = "Admin user exists (admin / admin123)";
        } else {
            // Create admin user
            $hash = password_hash('admin123', PASSWORD_DEFAULT);
            $conn2->query("INSERT INTO users (username, password, full_name, email, role) VALUES ('admin', '$hash', 'Administrator', 'admin@example.com', 'admin')");
            $success[] = "Admin user created (admin / admin123)";
        }

        // Check sample data
        $contact_count = $conn2->query("SELECT COUNT(*) as c FROM contacts")->fetch_assoc()['c'];
        $success[] = "$contact_count contacts in database";

        $conn2->close();
    }
}

$conn->close();

// Auto-detect app URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$app_url = $protocol . '://' . $host . $dir . '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install - WhatsApp Marketing Platform</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; background: linear-gradient(135deg, #075E54, #25D366); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
        .installer { background: #fff; border-radius: 16px; max-width: 650px; width: 100%; padding: 2.5rem; box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
        .logo { text-align: center; margin-bottom: 2rem; }
        .logo i { font-size: 3rem; color: #25D366; }
        .logo h1 { font-size: 1.5rem; margin-top: 0.5rem; }
        .logo p { color: #6b7280; font-size: 0.9rem; }
        .section { margin-bottom: 1.5rem; }
        .section h3 { font-size: 1rem; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem; }
        .check-item { padding: 0.5rem 0.75rem; border-radius: 6px; margin-bottom: 0.4rem; font-size: 0.85rem; display: flex; align-items: center; gap: 0.5rem; }
        .check-ok { background: #ecfdf5; color: #059669; }
        .check-fail { background: #fef2f2; color: #dc2626; }
        .check-info { background: #eff6ff; color: #2563eb; }
        .btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 0.95rem; font-family: inherit; text-decoration: none; }
        .btn-green { background: #25D366; color: #fff; }
        .btn-green:hover { background: #075E54; }
        .btn-blue { background: #3b82f6; color: #fff; }
        .btn-outline { background: transparent; border: 1px solid #e5e7eb; color: #374151; }
        .actions { display: flex; gap: 1rem; margin-top: 2rem; flex-wrap: wrap; }
        .config-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem; font-size: 0.85rem; margin-top: 1rem; }
        .config-box code { background: #e5e7eb; padding: 0.15rem 0.4rem; border-radius: 3px; }
        .divider { border-top: 1px solid #e5e7eb; margin: 1.5rem 0; }
    </style>
</head>
<body>
    <div class="installer">
        <div class="logo">
            <i class="fab fa-whatsapp"></i>
            <h1>WhatsApp Marketing Platform</h1>
            <p>Installation & Setup</p>
        </div>

        <!-- System Check -->
        <div class="section">
            <h3><i class="fas fa-server"></i> System Check</h3>
            <div class="check-item <?php echo version_compare(PHP_VERSION, '8.0', '>=') ? 'check-ok' : 'check-fail'; ?>">
                <i class="fas <?php echo version_compare(PHP_VERSION, '8.0', '>=') ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                PHP Version: <?php echo PHP_VERSION; ?> <?php echo version_compare(PHP_VERSION, '8.0', '>=') ? '(OK)' : '(Need 8.0+)'; ?>
            </div>
            <div class="check-item <?php echo extension_loaded('mysqli') ? 'check-ok' : 'check-fail'; ?>">
                <i class="fas <?php echo extension_loaded('mysqli') ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                MySQLi Extension: <?php echo extension_loaded('mysqli') ? 'Loaded' : 'Missing'; ?>
            </div>
            <div class="check-item <?php echo extension_loaded('json') ? 'check-ok' : 'check-fail'; ?>">
                <i class="fas <?php echo extension_loaded('json') ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                JSON Extension: <?php echo extension_loaded('json') ? 'Loaded' : 'Missing'; ?>
            </div>
            <div class="check-item <?php echo empty($errors) || $step !== 'check' ? 'check-ok' : 'check-fail'; ?>">
                <i class="fas <?php echo empty($errors) ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                MySQL Connection: <?php echo empty($errors) ? 'Connected' : 'Failed - Start MySQL in XAMPP!'; ?>
            </div>
        </div>

        <?php if ($step === 'install' || $step === 'auto'): ?>
        <div class="divider"></div>

        <!-- Installation Results -->
        <div class="section">
            <h3><i class="fas fa-database"></i> Installation Results</h3>
            <?php foreach ($success as $msg): ?>
                <div class="check-item check-ok"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($msg); ?></div>
            <?php endforeach; ?>
            <?php foreach ($errors as $err): ?>
                <div class="check-item check-fail"><i class="fas fa-times-circle"></i> <?php echo htmlspecialchars($err); ?></div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($errors)): ?>
        <div class="divider"></div>
        <div class="section">
            <h3><i class="fas fa-check-double" style="color:#25D366"></i> Installation Complete!</h3>
            <div class="config-box">
                <strong>App URL:</strong> <code><?php echo $app_url; ?></code><br><br>
                <strong>Login:</strong> <code>admin</code> / <code>admin123</code><br><br>
                <strong>Database:</strong> <code>whatsapp_platform</code>
            </div>
        </div>
        <div class="actions">
            <a href="login.php" class="btn btn-green"><i class="fas fa-sign-in-alt"></i> Open App & Login</a>
            <a href="install.php" class="btn btn-outline"><i class="fas fa-redo"></i> Re-check</a>
        </div>
        <?php else: ?>
        <div class="actions">
            <a href="install.php?step=install" class="btn btn-blue"><i class="fas fa-redo"></i> Retry Installation</a>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <!-- Not yet installed -->
        <div class="divider"></div>
        <div class="section">
            <h3><i class="fas fa-info-circle"></i> Ready to Install</h3>
            <div class="check-item check-info">
                <i class="fas fa-info-circle"></i>
                This will create database <strong>whatsapp_platform</strong> with all tables and sample data.
            </div>
        </div>
        <div class="actions">
            <?php if (empty($errors)): ?>
                <a href="install.php?step=install" class="btn btn-green"><i class="fas fa-download"></i> Install Now</a>
            <?php else: ?>
                <a href="install.php" class="btn btn-outline"><i class="fas fa-redo"></i> Re-check Connection</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="divider"></div>
        <div class="config-box">
            <strong>Troubleshooting:</strong><br>
            1. Open XAMPP Control Panel<br>
            2. Click <strong>Start</strong> on both <strong>Apache</strong> and <strong>MySQL</strong><br>
            3. Both should show green "Running"<br>
            4. Then refresh this page
        </div>
    </div>
</body>
</html>
