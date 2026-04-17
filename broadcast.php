<?php
require_once 'config/database.php';
requireLogin();
$pageTitle = 'Broadcast Campaigns';
$conn = getDbConnection();
$base = BASE_URL;

// Handle create campaign
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_campaign'])) {
    $name = $_POST['campaign_name'];
    $template_id = intval($_POST['template_id']);
    $target_type = $_POST['target_type'];
    $target_group_id = ($target_type == 'group') ? intval($_POST['target_group_id']) : null;

    $stmt = $conn->prepare("INSERT INTO broadcast_campaigns (name, template_id, target_type, target_group_id, status, created_by) VALUES (?, ?, ?, ?, 'draft', ?)");
    $user_id = $_SESSION['user_id'];
    $stmt->bind_param('sisii', $name, $template_id, $target_type, $target_group_id, $user_id);
    $stmt->execute();
    header('Location: ' . $base . 'broadcast.php?msg=created');
    exit;
}

// Handle send campaign (mock)
if (isset($_GET['send'])) {
    $camp_id = intval($_GET['send']);
    $campaign = $conn->query("SELECT * FROM broadcast_campaigns WHERE id = $camp_id")->fetch_assoc();

    if ($campaign && $campaign['status'] == 'draft') {
        // Get target contacts
        if ($campaign['target_type'] == 'all') {
            $contacts_result = $conn->query("SELECT id FROM contacts WHERE status = 'active'");
        } else {
            $gid = $campaign['target_group_id'];
            $contacts_result = $conn->query("SELECT c.id FROM contacts c JOIN contact_group_members cgm ON c.id = cgm.contact_id WHERE cgm.group_id = $gid AND c.status = 'active'");
        }

        $total = 0;
        $sent = 0;
        $delivered = 0;
        $read = 0;

        while ($c = $contacts_result->fetch_assoc()) {
            $total++;
            // Mock: randomly assign statuses
            $rand = rand(1, 10);
            if ($rand <= 9) { $sent++; $status = 'sent'; }
            if ($rand <= 8) { $delivered++; $status = 'delivered'; }
            if ($rand <= 5) { $read++; $status = 'read'; }
            if ($rand > 9) { $status = 'failed'; }

            $stmt = $conn->prepare("INSERT INTO broadcast_recipients (campaign_id, contact_id, status, sent_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param('iis', $camp_id, $c['id'], $status);
            $stmt->execute();
        }

        $conn->query("UPDATE broadcast_campaigns SET status='completed', total_recipients=$total, sent_count=$sent, delivered_count=$delivered, read_count=$read, failed_count=" . ($total - $sent) . ", sent_at=NOW() WHERE id=$camp_id");
        header('Location: ' . $base . 'broadcast.php?msg=sent');
        exit;
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM broadcast_campaigns WHERE id = $id");
    header('Location: ' . $base . 'broadcast.php?msg=deleted');
    exit;
}

$campaigns = $conn->query("SELECT bc.*, mt.name as template_name FROM broadcast_campaigns bc LEFT JOIN message_templates mt ON bc.template_id = mt.id ORDER BY bc.created_at DESC");
$templates_list = $conn->query("SELECT id, name FROM message_templates WHERE status = 'approved' ORDER BY name");
$groups_list = $conn->query("SELECT id, name, contact_count FROM contact_groups ORDER BY name");

include 'includes/header.php';
?>

<div class="page-header">
    <div><p class="page-subtitle">Send targeted messages to your contacts</p></div>
    <div class="page-actions">
        <button class="btn btn-wa" onclick="document.getElementById('createCampaignModal').classList.add('show')">
            <i class="fas fa-plus"></i> New Campaign
        </button>
    </div>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i>
        <?php echo $_GET['msg'] == 'created' ? 'Campaign created!' : ($_GET['msg'] == 'sent' ? 'Campaign sent successfully!' : 'Campaign deleted!'); ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Campaign</th>
                    <th>Template</th>
                    <th>Status</th>
                    <th>Recipients</th>
                    <th>Sent</th>
                    <th>Delivered</th>
                    <th>Read</th>
                    <th>Rate</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($camp = $campaigns->fetch_assoc()): ?>
                <tr>
                    <td><strong><?php echo sanitize($camp['name']); ?></strong><br><small><?php echo date('M d, Y', strtotime($camp['created_at'])); ?></small></td>
                    <td><?php echo sanitize($camp['template_name'] ?? 'N/A'); ?></td>
                    <td><span class="status-pill status-<?php echo $camp['status']; ?>"><?php echo ucfirst($camp['status']); ?></span></td>
                    <td><?php echo $camp['total_recipients']; ?></td>
                    <td><?php echo $camp['sent_count']; ?></td>
                    <td><?php echo $camp['delivered_count']; ?></td>
                    <td><?php echo $camp['read_count']; ?></td>
                    <td>
                        <?php
                        $rate = $camp['total_recipients'] > 0 ? round(($camp['delivered_count'] / $camp['total_recipients']) * 100) : 0;
                        ?>
                        <div class="progress-bar-sm">
                            <div class="progress-fill" style="width: <?php echo $rate; ?>%"></div>
                        </div>
                        <small><?php echo $rate; ?>%</small>
                    </td>
                    <td class="action-buttons">
                        <?php if ($camp['status'] == 'draft'): ?>
                            <a href="broadcast.php?send=<?php echo $camp['id']; ?>" class="btn-icon btn-send" title="Send Now" onclick="return confirm('Send this campaign now?')"><i class="fas fa-paper-plane"></i></a>
                        <?php endif; ?>
                        <a href="broadcast.php?delete=<?php echo $camp['id']; ?>" class="btn-icon btn-danger" title="Delete" onclick="return confirm('Delete campaign?')"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create Campaign Modal -->
<div class="modal" id="createCampaignModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-bullhorn"></i> Create Broadcast Campaign</h3>
            <button class="modal-close" onclick="this.closest('.modal').classList.remove('show')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="create_campaign" value="1">
            <div class="modal-body">
                <div class="form-group">
                    <label>Campaign Name *</label>
                    <input type="text" name="campaign_name" required placeholder="e.g., April Property Launch">
                </div>
                <div class="form-group">
                    <label>Message Template *</label>
                    <select name="template_id" required>
                        <option value="">Select Template</option>
                        <?php while ($t = $templates_list->fetch_assoc()): ?>
                            <option value="<?php echo $t['id']; ?>"><?php echo sanitize($t['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Target Audience</label>
                    <select name="target_type" id="targetType" onchange="document.getElementById('groupSelect').style.display = this.value == 'group' ? 'block' : 'none'">
                        <option value="all">All Contacts</option>
                        <option value="group">Specific Group</option>
                    </select>
                </div>
                <div class="form-group" id="groupSelect" style="display:none">
                    <label>Select Group</label>
                    <select name="target_group_id">
                        <?php while ($g = $groups_list->fetch_assoc()): ?>
                            <option value="<?php echo $g['id']; ?>"><?php echo sanitize($g['name']); ?> (<?php echo $g['contact_count']; ?> contacts)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="this.closest('.modal').classList.remove('show')">Cancel</button>
                <button type="submit" class="btn btn-wa">Create Campaign</button>
            </div>
        </form>
    </div>
</div>

<?php
$conn->close();
include 'includes/footer.php';
?>
