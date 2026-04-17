<?php
require_once 'config/database.php';
requireLogin();
$pageTitle = 'Settings';
include 'includes/header.php';
?>

<div class="settings-page">
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-plug"></i> WhatsApp API Configuration</h3></div>
        <div class="card-body">
            <div class="api-mode-notice">
                <i class="fas fa-info-circle"></i>
                <strong>Currently in Demo Mode</strong> - Messages are simulated. Connect your Meta WhatsApp Business API to send real messages.
            </div>
            <form class="admin-form">
                <div class="form-group"><label>API Mode</label><select disabled><option selected>Mock / Demo</option><option>Live (Meta Cloud API)</option></select></div>
                <div class="form-row">
                    <div class="form-group"><label>Phone Number ID</label><input type="text" value="" placeholder="Enter Phone Number ID" disabled></div>
                    <div class="form-group"><label>Access Token</label><input type="password" value="" placeholder="Enter Access Token" disabled></div>
                </div>
                <div class="form-group"><label>Webhook URL</label><input type="text" value="https://yourdomain.com/api/webhook.php" readonly></div>
                <p class="form-help">To connect real WhatsApp API, update config/database.php with your Meta credentials.</p>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3><i class="fas fa-user-shield"></i> Account Settings</h3></div>
        <div class="card-body">
            <form class="admin-form">
                <div class="form-row">
                    <div class="form-group"><label>Full Name</label><input type="text" value="<?php echo sanitize($_SESSION['full_name'] ?? ''); ?>"></div>
                    <div class="form-group"><label>Email</label><input type="email" value="admin@example.com"></div>
                </div>
                <div class="form-group"><label>Change Password</label><input type="password" placeholder="New password"></div>
                <button type="button" class="btn btn-wa"><i class="fas fa-save"></i> Save Changes</button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
