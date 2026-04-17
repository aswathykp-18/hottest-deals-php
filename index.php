<?php
require_once 'config/database.php';
requireLogin();
$pageTitle = 'Dashboard';
$conn = getDbConnection();

// Stats
$stats = [];
$stats['contacts'] = $conn->query("SELECT COUNT(*) as cnt FROM contacts WHERE status='active'")->fetch_assoc()['cnt'];
$stats['templates'] = $conn->query("SELECT COUNT(*) as cnt FROM message_templates WHERE status='approved'")->fetch_assoc()['cnt'];
$stats['campaigns'] = $conn->query("SELECT COUNT(*) as cnt FROM broadcast_campaigns")->fetch_assoc()['cnt'];
$stats['conversations'] = $conn->query("SELECT COUNT(*) as cnt FROM conversations WHERE status='open'")->fetch_assoc()['cnt'];
$stats['messages_today'] = $conn->query("SELECT COUNT(*) as cnt FROM messages WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['cnt'];
$stats['unread'] = $conn->query("SELECT SUM(unread_count) as cnt FROM conversations")->fetch_assoc()['cnt'] ?? 0;

// Recent campaigns
$recent_campaigns = $conn->query("SELECT * FROM broadcast_campaigns ORDER BY created_at DESC LIMIT 5");

// Recent conversations
$recent_convos = $conn->query("SELECT c.*, ct.name as contact_name, ct.phone FROM conversations c JOIN contacts ct ON c.contact_id = ct.id ORDER BY c.last_message_at DESC LIMIT 5");

// Analytics data (last 7 days)
$analytics = $conn->query("SELECT event_type, JSON_EXTRACT(data, '$.count') as count_val, DATE(created_at) as event_date FROM analytics_events WHERE created_at >= NOW() - INTERVAL 7 DAY ORDER BY created_at ASC");
$chart_data = ['labels' => [], 'sent' => [], 'delivered' => [], 'read' => []];
$dates_seen = [];
while ($row = $analytics->fetch_assoc()) {
    $date = $row['event_date'];
    if (!in_array($date, $dates_seen) && $row['event_type'] == 'message_sent') {
        $dates_seen[] = $date;
        $chart_data['labels'][] = date('M d', strtotime($date));
    }
    $chart_data[$row['event_type'] == 'message_sent' ? 'sent' : ($row['event_type'] == 'message_delivered' ? 'delivered' : 'read')][] = intval($row['count_val']);
}

include 'includes/header.php';
?>

<div class="dashboard">
    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: #25D366"><i class="fas fa-address-book"></i></div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['contacts']); ?></h3>
                <p>Total Contacts</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #3b82f6"><i class="fas fa-inbox"></i></div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['conversations']); ?></h3>
                <p>Open Chats</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #f59e0b"><i class="fas fa-bullhorn"></i></div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['campaigns']); ?></h3>
                <p>Campaigns</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #8b5cf6"><i class="fas fa-paper-plane"></i></div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['messages_today']); ?></h3>
                <p>Messages Today</p>
            </div>
        </div>
    </div>

    <div class="dashboard-grid">
        <!-- Chart -->
        <div class="card chart-card">
            <div class="card-header">
                <h3><i class="fas fa-chart-area"></i> Message Analytics (7 Days)</h3>
            </div>
            <div class="card-body">
                <canvas id="analyticsChart" height="250"></canvas>
            </div>
        </div>

        <!-- Recent Conversations -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-comments"></i> Recent Conversations</h3>
                <a href="inbox.php" class="btn btn-sm">View All</a>
            </div>
            <div class="card-body p-0">
                <?php while ($convo = $recent_convos->fetch_assoc()): ?>
                <div class="convo-item">
                    <div class="convo-avatar"><?php echo strtoupper(substr($convo['contact_name'], 0, 1)); ?></div>
                    <div class="convo-info">
                        <div class="convo-name"><?php echo sanitize($convo['contact_name']); ?></div>
                        <div class="convo-preview"><?php echo sanitize(substr($convo['last_message'] ?? '', 0, 50)); ?></div>
                    </div>
                    <div class="convo-meta">
                        <span class="convo-time"><?php echo timeAgo($convo['last_message_at']); ?></span>
                        <?php if ($convo['unread_count'] > 0): ?>
                            <span class="unread-badge"><?php echo $convo['unread_count']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- Recent Campaigns -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-bullhorn"></i> Recent Campaigns</h3>
            <a href="broadcast.php" class="btn btn-sm">View All</a>
        </div>
        <div class="card-body p-0">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Campaign</th>
                        <th>Status</th>
                        <th>Recipients</th>
                        <th>Delivered</th>
                        <th>Read</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($camp = $recent_campaigns->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo sanitize($camp['name']); ?></strong></td>
                        <td><span class="status-pill status-<?php echo $camp['status']; ?>"><?php echo ucfirst($camp['status']); ?></span></td>
                        <td><?php echo $camp['total_recipients']; ?></td>
                        <td><?php echo $camp['delivered_count']; ?></td>
                        <td><?php echo $camp['read_count']; ?></td>
                        <td><?php echo date('M d, Y', strtotime($camp['created_at'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('analyticsChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($chart_data['labels']); ?>,
        datasets: [
            { label: 'Sent', data: <?php echo json_encode($chart_data['sent']); ?>, borderColor: '#25D366', backgroundColor: 'rgba(37,211,102,0.1)', fill: true, tension: 0.4 },
            { label: 'Delivered', data: <?php echo json_encode($chart_data['delivered']); ?>, borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.1)', fill: true, tension: 0.4 },
            { label: 'Read', data: <?php echo json_encode($chart_data['read']); ?>, borderColor: '#8b5cf6', backgroundColor: 'rgba(139,92,246,0.1)', fill: true, tension: 0.4 }
        ]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } }
});
</script>

<?php
$conn->close();
include 'includes/footer.php';
?>
