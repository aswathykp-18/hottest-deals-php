<?php
require_once 'config/database.php';
requireLogin();
$pageTitle = 'Webhook Logs';
$conn = getDbConnection();
$base = BASE_URL;

// Clear logs
if (isset($_GET['clear']) && $_GET['clear'] == 'all') {
    $conn->query("DELETE FROM webhook_logs");
    header('Location: ' . $base . 'webhook-logs.php?msg=cleared');
    exit;
}

// Filters
$where = "1=1";
$params = [];
$types = '';

if (!empty($_GET['event_type'])) {
    $where .= " AND event_type = ?";
    $params[] = $_GET['event_type'];
    $types .= 's';
}
if (!empty($_GET['status'])) {
    $where .= " AND status = ?";
    $params[] = $_GET['status'];
    $types .= 's';
}

$sql = "SELECT * FROM webhook_logs WHERE $where ORDER BY created_at DESC LIMIT 100";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$logs = $stmt->get_result();

// Get unique event types for filter
$event_types = $conn->query("SELECT DISTINCT event_type FROM webhook_logs ORDER BY event_type");

// Stats
$total_logs = $conn->query("SELECT COUNT(*) as cnt FROM webhook_logs")->fetch_assoc()['cnt'];
$success_logs = $conn->query("SELECT COUNT(*) as cnt FROM webhook_logs WHERE status='success'")->fetch_assoc()['cnt'];
$failed_logs = $conn->query("SELECT COUNT(*) as cnt FROM webhook_logs WHERE status='failed'")->fetch_assoc()['cnt'];
$today_logs = $conn->query("SELECT COUNT(*) as cnt FROM webhook_logs WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['cnt'];

include 'includes/header.php';
?>

<div class="page-header">
    <div><p class="page-subtitle">Monitor incoming webhook events and API calls</p></div>
    <div class="page-actions">
        <a href="webhook-logs.php?clear=all" class="btn btn-outline btn-sm" onclick="return confirm('Clear all logs?')"><i class="fas fa-trash"></i> Clear All</a>
        <button class="btn btn-wa btn-sm" onclick="document.getElementById('testModal').classList.add('show')"><i class="fas fa-flask"></i> Test Webhook</button>
    </div>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> Logs cleared!</div>
<?php endif; ?>

<!-- Stats -->
<div class="stats-grid" style="margin-bottom:1.5rem">
    <div class="stat-card"><div class="stat-icon" style="background:#3b82f6"><i class="fas fa-list"></i></div><div class="stat-info"><h3><?php echo $total_logs; ?></h3><p>Total Events</p></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#25D366"><i class="fas fa-check"></i></div><div class="stat-info"><h3><?php echo $success_logs; ?></h3><p>Successful</p></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#ef4444"><i class="fas fa-times"></i></div><div class="stat-info"><h3><?php echo $failed_logs; ?></h3><p>Failed</p></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#f59e0b"><i class="fas fa-calendar-day"></i></div><div class="stat-info"><h3><?php echo $today_logs; ?></h3><p>Today</p></div></div>
</div>

<!-- Webhook URL Info -->
<div class="card" style="margin-bottom:1.5rem">
    <div class="card-body" style="display:flex;align-items:center;gap:1rem;padding:1rem 1.5rem">
        <i class="fas fa-link" style="color:var(--wa-green);font-size:1.2rem"></i>
        <div style="flex:1">
            <strong>Webhook URL:</strong>
            <code id="webhookUrl" style="background:#f3f4f6;padding:0.3rem 0.6rem;border-radius:4px;margin-left:0.5rem;font-size:0.85rem">
                https://yourdomain.com/wa-platform/api/webhook.php
            </code>
        </div>
        <div>
            <strong>Verify Token:</strong>
            <code style="background:#f3f4f6;padding:0.3rem 0.6rem;border-radius:4px;margin-left:0.5rem;font-size:0.85rem">billionaire_homes_wa_verify_2026</code>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="filters-bar">
    <form method="GET" class="filter-form">
        <select name="event_type" class="filter-select">
            <option value="">All Event Types</option>
            <?php while ($et = $event_types->fetch_assoc()): ?>
                <option value="<?php echo sanitize($et['event_type']); ?>" <?php echo ($_GET['event_type'] ?? '') == $et['event_type'] ? 'selected' : ''; ?>>
                    <?php echo sanitize($et['event_type']); ?>
                </option>
            <?php endwhile; ?>
        </select>
        <select name="status" class="filter-select">
            <option value="">All Statuses</option>
            <option value="success" <?php echo ($_GET['status'] ?? '') == 'success' ? 'selected' : ''; ?>>Success</option>
            <option value="failed" <?php echo ($_GET['status'] ?? '') == 'failed' ? 'selected' : ''; ?>>Failed</option>
        </select>
        <button type="submit" class="btn btn-sm">Filter</button>
        <?php if (!empty($_GET)): ?><a href="webhook-logs.php" class="btn btn-sm btn-outline">Reset</a><?php endif; ?>
    </form>
</div>

<!-- Logs Table -->
<div class="card">
    <div class="card-body p-0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Event Type</th>
                    <th>Status</th>
                    <th>Payload</th>
                    <th>Response</th>
                    <th>IP</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($log = $logs->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $log['id']; ?></td>
                    <td>
                        <span class="tag" style="background:<?php
                            echo match($log['event_type']) {
                                'message_received' => '#ecfdf5',
                                'status_update' => '#eff6ff',
                                'verification' => '#f0fdf4',
                                'mock_send' => '#fef3c7',
                                'chatbot_flow' => '#fdf4ff',
                                default => '#f3f4f6'
                            };
                        ?>;color:<?php
                            echo match($log['event_type']) {
                                'message_received' => '#059669',
                                'status_update' => '#2563eb',
                                'verification' => '#16a34a',
                                'mock_send' => '#d97706',
                                'chatbot_flow' => '#9333ea',
                                default => '#4b5563'
                            };
                        ?>">
                            <?php echo sanitize($log['event_type']); ?>
                        </span>
                    </td>
                    <td><span class="status-pill status-<?php echo $log['status'] == 'success' ? 'active' : 'failed'; ?>"><?php echo ucfirst($log['status']); ?></span></td>
                    <td class="log-payload">
                        <button class="btn btn-sm btn-outline" onclick="this.nextElementSibling.classList.toggle('show')">View</button>
                        <pre class="log-json"><?php echo sanitize(json_encode(json_decode($log['payload']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                    </td>
                    <td><small><?php echo sanitize(substr($log['response'] ?? '-', 0, 80)); ?></small></td>
                    <td><small><?php echo sanitize($log['ip_address'] ?? '-'); ?></small></td>
                    <td><small><?php echo date('M d H:i:s', strtotime($log['created_at'])); ?></small></td>
                </tr>
                <?php endwhile; ?>
                <?php if ($logs->num_rows == 0): ?>
                <tr><td colspan="7" style="text-align:center;padding:3rem;color:var(--text-muted)">
                    <i class="fas fa-inbox" style="font-size:2rem;display:block;margin-bottom:1rem;opacity:0.3"></i>
                    No webhook logs yet. Send a test message to get started.
                </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Test Webhook Modal -->
<div class="modal" id="testModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-flask"></i> Test Webhook (Simulate Incoming Message)</h3>
            <button class="modal-close" onclick="this.closest('.modal').classList.remove('show')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>From Phone</label>
                <input type="text" id="testFrom" value="+971509999999" placeholder="+971501234567">
            </div>
            <div class="form-group">
                <label>Sender Name</label>
                <input type="text" id="testName" value="Test User" placeholder="Name">
            </div>
            <div class="form-group">
                <label>Message Type</label>
                <select id="testType">
                    <option value="text">Text</option>
                    <option value="image">Image</option>
                    <option value="document">Document</option>
                    <option value="button_reply">Button Reply</option>
                    <option value="location">Location</option>
                </select>
            </div>
            <div class="form-group">
                <label>Message Content</label>
                <textarea id="testMessage" rows="3" placeholder="Type a test message...">hello</textarea>
            </div>
            <div id="testResult" style="display:none" class="alert"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="this.closest('.modal').classList.remove('show')">Close</button>
            <button class="btn btn-wa" onclick="sendTestWebhook()"><i class="fas fa-paper-plane"></i> Send Test</button>
        </div>
    </div>
</div>

<style>
.log-json { display:none; background:#1e293b; color:#e2e8f0; padding:1rem; border-radius:8px; font-size:0.75rem; max-height:300px; overflow:auto; margin-top:0.5rem; white-space:pre-wrap; word-break:break-all; }
.log-json.show { display:block; }
.log-payload { max-width:300px; }
</style>

<script>
async function sendTestWebhook() {
    const result = document.getElementById('testResult');
    result.style.display = 'block';
    result.className = 'alert';
    result.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending test webhook...';

    try {
        const response = await fetch('<?php echo $base; ?>api/webhook-test.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                from: document.getElementById('testFrom').value,
                name: document.getElementById('testName').value,
                message: document.getElementById('testMessage').value,
                type: document.getElementById('testType').value
            })
        });
        const data = await response.json();
        if (data.success) {
            result.className = 'alert alert-success';
            result.innerHTML = '<i class="fas fa-check-circle"></i> Test webhook sent! Message from <strong>' + data.simulated.name + '</strong>: "' + data.simulated.message + '"<br><small>Refresh page to see the log entry.</small>';
        } else {
            result.className = 'alert alert-error';
            result.innerHTML = '<i class="fas fa-times-circle"></i> Error: ' + (data.error || 'Unknown error');
        }
    } catch (e) {
        result.className = 'alert alert-error';
        result.innerHTML = '<i class="fas fa-times-circle"></i> Request failed: ' + e.message;
    }
}
</script>

<?php $conn->close(); include 'includes/footer.php'; ?>
