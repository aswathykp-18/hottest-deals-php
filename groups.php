<?php
require_once 'config/database.php';
requireLogin();
$pageTitle = 'Contact Groups';
$conn = getDbConnection();
$base = BASE_URL;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_group'])) {
    $name = $_POST['group_name'];
    $desc = $_POST['description'] ?: null;
    $color = $_POST['color'] ?: '#3b82f6';
    $stmt = $conn->prepare("INSERT INTO contact_groups (name, description, color) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $name, $desc, $color);
    $stmt->execute();
    header('Location: ' . $base . 'groups.php?msg=created');
    exit;
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM contact_groups WHERE id = $id");
    header('Location: ' . $base . 'groups.php?msg=deleted');
    exit;
}

$groups = $conn->query("SELECT cg.*, (SELECT COUNT(*) FROM contact_group_members WHERE group_id = cg.id) as member_count FROM contact_groups cg ORDER BY cg.name");

include 'includes/header.php';
?>

<div class="page-header">
    <div><p class="page-subtitle">Organize your contacts into groups for targeted messaging</p></div>
    <div class="page-actions">
        <button class="btn btn-wa" onclick="document.getElementById('groupModal').classList.add('show')">
            <i class="fas fa-plus"></i> Create Group
        </button>
    </div>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> Group <?php echo $_GET['msg'] == 'created' ? 'created' : 'deleted'; ?> successfully!</div>
<?php endif; ?>

<div class="groups-grid">
    <?php while ($group = $groups->fetch_assoc()): ?>
    <div class="group-card">
        <div class="group-color-bar" style="background: <?php echo $group['color']; ?>"></div>
        <div class="group-body">
            <h4><?php echo sanitize($group['name']); ?></h4>
            <p class="group-desc"><?php echo sanitize($group['description'] ?? 'No description'); ?></p>
            <div class="group-stats">
                <span><i class="fas fa-users"></i> <?php echo $group['member_count']; ?> contacts</span>
            </div>
        </div>
        <div class="group-actions">
            <a href="broadcast.php" class="btn btn-sm btn-outline"><i class="fas fa-bullhorn"></i> Broadcast</a>
            <a href="groups.php?delete=<?php echo $group['id']; ?>" class="btn-icon btn-danger" onclick="return confirm('Delete group?')"><i class="fas fa-trash"></i></a>
        </div>
    </div>
    <?php endwhile; ?>
</div>

<!-- Create Group Modal -->
<div class="modal" id="groupModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-users"></i> Create Contact Group</h3>
            <button class="modal-close" onclick="this.closest('.modal').classList.remove('show')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="create_group" value="1">
            <div class="modal-body">
                <div class="form-group"><label>Group Name *</label><input type="text" name="group_name" required placeholder="e.g., VIP Clients"></div>
                <div class="form-group"><label>Description</label><textarea name="description" rows="3" placeholder="Group description..."></textarea></div>
                <div class="form-group"><label>Color</label><input type="color" name="color" value="#3b82f6"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="this.closest('.modal').classList.remove('show')">Cancel</button>
                <button type="submit" class="btn btn-wa">Create Group</button>
            </div>
        </form>
    </div>
</div>

<?php $conn->close(); include 'includes/footer.php'; ?>
