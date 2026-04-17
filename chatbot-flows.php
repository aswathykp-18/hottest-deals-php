<?php
require_once 'config/database.php';
requireLogin();
$pageTitle = 'Chatbot Flows';
$conn = getDbConnection();
$base = BASE_URL;

if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $conn->query("UPDATE chatbot_flows SET is_active = NOT is_active WHERE id = $id");
    header('Location: ' . $base . 'chatbot-flows.php');
    exit;
}
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM chatbot_flows WHERE id = $id");
    header('Location: ' . $base . 'chatbot-flows.php?msg=deleted');
    exit;
}

$flows = $conn->query("SELECT * FROM chatbot_flows ORDER BY created_at DESC");

include 'includes/header.php';
?>

<div class="page-header">
    <div><p class="page-subtitle">Build automated conversation flows for your WhatsApp chatbot</p></div>
    <div class="page-actions">
        <a href="flow-builder.php" class="btn btn-wa"><i class="fas fa-plus"></i> Create New Flow</a>
    </div>
</div>

<div class="flows-grid">
    <?php while ($flow = $flows->fetch_assoc()): ?>
    <div class="flow-card">
        <div class="flow-header">
            <div>
                <h4><i class="fas fa-project-diagram"></i> <?php echo sanitize($flow['name']); ?></h4>
                <p><?php echo sanitize($flow['description'] ?? 'No description'); ?></p>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" <?php echo $flow['is_active'] ? 'checked' : ''; ?> onchange="window.location='chatbot-flows.php?toggle=<?php echo $flow['id']; ?>'">
                <span class="toggle-slider"></span>
            </label>
        </div>
        <div class="flow-meta">
            <span><i class="fas fa-keyboard"></i> Trigger: <strong><?php echo sanitize($flow['trigger_keyword']); ?></strong></span>
            <span><i class="fas fa-clock"></i> <?php echo date('M d, Y', strtotime($flow['created_at'])); ?></span>
        </div>
        <?php
        $flow_data = json_decode($flow['flow_data'], true);
        $node_count = isset($flow_data['nodes']) ? count($flow_data['nodes']) : 0;
        ?>
        <div class="flow-stats">
            <span class="flow-stat"><i class="fas fa-shapes"></i> <?php echo $node_count; ?> nodes</span>
            <span class="flow-stat status-pill <?php echo $flow['is_active'] ? 'status-active' : 'status-draft'; ?>">
                <?php echo $flow['is_active'] ? 'Active' : 'Inactive'; ?>
            </span>
        </div>
        <div class="flow-actions">
            <a href="flow-builder.php?id=<?php echo $flow['id']; ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i> Edit Flow</a>
            <a href="chatbot-flows.php?delete=<?php echo $flow['id']; ?>" class="btn-icon btn-danger" onclick="return confirm('Delete flow?')"><i class="fas fa-trash"></i></a>
        </div>
    </div>
    <?php endwhile; ?>
</div>

<?php $conn->close(); include 'includes/footer.php'; ?>
