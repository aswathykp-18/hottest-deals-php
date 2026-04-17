<?php
require_once 'config/database.php';
requireLogin();
$pageTitle = 'Analytics';
$conn = getDbConnection();

$total_sent = $conn->query("SELECT SUM(JSON_EXTRACT(data, '$.count')) as t FROM analytics_events WHERE event_type='message_sent'")->fetch_assoc()['t'] ?? 0;
$total_delivered = $conn->query("SELECT SUM(JSON_EXTRACT(data, '$.count')) as t FROM analytics_events WHERE event_type='message_delivered'")->fetch_assoc()['t'] ?? 0;
$total_read = $conn->query("SELECT SUM(JSON_EXTRACT(data, '$.count')) as t FROM analytics_events WHERE event_type='message_read'")->fetch_assoc()['t'] ?? 0;

$delivery_rate = $total_sent > 0 ? round(($total_delivered / $total_sent) * 100, 1) : 0;
$read_rate = $total_sent > 0 ? round(($total_read / $total_sent) * 100, 1) : 0;

// Campaign performance
$campaign_stats = $conn->query("SELECT name, total_recipients, sent_count, delivered_count, read_count, failed_count FROM broadcast_campaigns WHERE status='completed' ORDER BY sent_at DESC LIMIT 10");

include 'includes/header.php';
?>

<div class="analytics-page">
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-icon" style="background:#25D366"><i class="fas fa-paper-plane"></i></div><div class="stat-info"><h3><?php echo number_format($total_sent); ?></h3><p>Messages Sent</p></div></div>
        <div class="stat-card"><div class="stat-icon" style="background:#3b82f6"><i class="fas fa-check-double"></i></div><div class="stat-info"><h3><?php echo number_format($total_delivered); ?></h3><p>Delivered</p></div></div>
        <div class="stat-card"><div class="stat-icon" style="background:#8b5cf6"><i class="fas fa-eye"></i></div><div class="stat-info"><h3><?php echo number_format($total_read); ?></h3><p>Read</p></div></div>
        <div class="stat-card"><div class="stat-icon" style="background:#f59e0b"><i class="fas fa-percentage"></i></div><div class="stat-info"><h3><?php echo $delivery_rate; ?>%</h3><p>Delivery Rate</p></div></div>
    </div>

    <div class="card">
        <div class="card-header"><h3><i class="fas fa-chart-bar"></i> Campaign Performance</h3></div>
        <div class="card-body p-0">
            <table class="data-table">
                <thead><tr><th>Campaign</th><th>Recipients</th><th>Sent</th><th>Delivered</th><th>Read</th><th>Failed</th><th>Delivery %</th><th>Read %</th></tr></thead>
                <tbody>
                    <?php while ($c = $campaign_stats->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo sanitize($c['name']); ?></strong></td>
                        <td><?php echo $c['total_recipients']; ?></td>
                        <td><?php echo $c['sent_count']; ?></td>
                        <td><?php echo $c['delivered_count']; ?></td>
                        <td><?php echo $c['read_count']; ?></td>
                        <td><?php echo $c['failed_count']; ?></td>
                        <td><?php echo $c['total_recipients'] > 0 ? round(($c['delivered_count']/$c['total_recipients'])*100) : 0; ?>%</td>
                        <td><?php echo $c['total_recipients'] > 0 ? round(($c['read_count']/$c['total_recipients'])*100) : 0; ?>%</td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php $conn->close(); include 'includes/footer.php'; ?>
