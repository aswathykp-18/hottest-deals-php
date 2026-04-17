<?php
require_once 'config/database.php';
requireLogin();
$pageTitle = 'Message Templates';
$conn = getDbConnection();
$base = BASE_URL;

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM message_templates WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    header('Location: ' . $base . 'templates.php?msg=deleted');
    exit;
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $body_text = $_POST['body_text'];
    $footer_text = $_POST['footer_text'] ?: null;
    $header_type = $_POST['header_type'];
    $status = $_POST['status'] ?? 'draft';

    if (isset($_POST['edit_id']) && $_POST['edit_id'] > 0) {
        $stmt = $conn->prepare("UPDATE message_templates SET name=?, category=?, header_type=?, body_text=?, footer_text=?, status=? WHERE id=?");
        $id = intval($_POST['edit_id']);
        $stmt->bind_param('ssssssi', $name, $category, $header_type, $body_text, $footer_text, $status, $id);
        $stmt->execute();
        header('Location: ' . $base . 'templates.php?msg=updated');
    } else {
        $stmt = $conn->prepare("INSERT INTO message_templates (name, category, header_type, body_text, footer_text, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssss', $name, $category, $header_type, $body_text, $footer_text, $status);
        $stmt->execute();
        header('Location: ' . $base . 'templates.php?msg=added');
    }
    exit;
}

$templates = $conn->query("SELECT * FROM message_templates ORDER BY created_at DESC");

// If editing, get the template
$edit_template = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM message_templates WHERE id = ?");
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $edit_template = $stmt->get_result()->fetch_assoc();
}

include 'includes/header.php';
?>

<div class="page-header">
    <div><p class="page-subtitle">Create and manage WhatsApp message templates</p></div>
    <div class="page-actions">
        <button class="btn btn-wa" onclick="document.getElementById('templateModal').classList.add('show')">
            <i class="fas fa-plus"></i> Create Template
        </button>
    </div>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i>
        <?php echo $_GET['msg'] == 'added' ? 'Template created!' : ($_GET['msg'] == 'updated' ? 'Template updated!' : 'Template deleted!'); ?>
    </div>
<?php endif; ?>

<div class="templates-grid">
    <?php while ($tpl = $templates->fetch_assoc()): ?>
    <div class="template-card">
        <div class="template-header">
            <div>
                <h4><?php echo sanitize($tpl['name']); ?></h4>
                <span class="template-category"><?php echo ucfirst($tpl['category']); ?></span>
            </div>
            <span class="status-pill status-<?php echo $tpl['status']; ?>"><?php echo ucfirst($tpl['status']); ?></span>
        </div>
        <div class="template-preview">
            <div class="wa-message-bubble">
                <?php echo nl2br(sanitize($tpl['body_text'])); ?>
                <?php if ($tpl['footer_text']): ?>
                    <div class="wa-footer"><?php echo sanitize($tpl['footer_text']); ?></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="template-footer">
            <span class="template-date"><?php echo date('M d, Y', strtotime($tpl['created_at'])); ?></span>
            <div class="template-actions">
                <a href="templates.php?edit=<?php echo $tpl['id']; ?>" class="btn-icon" title="Edit"><i class="fas fa-edit"></i></a>
                <a href="templates.php?delete=<?php echo $tpl['id']; ?>" class="btn-icon btn-danger" title="Delete" onclick="return confirm('Delete template?')"><i class="fas fa-trash"></i></a>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
</div>

<!-- Template Modal -->
<div class="modal <?php echo $edit_template ? 'show' : ''; ?>" id="templateModal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3><i class="fas fa-file-alt"></i> <?php echo $edit_template ? 'Edit' : 'Create'; ?> Message Template</h3>
            <button class="modal-close" onclick="this.closest('.modal').classList.remove('show');window.history.replaceState({}, '', 'templates.php')">&times;</button>
        </div>
        <form method="POST">
            <?php if ($edit_template): ?>
                <input type="hidden" name="edit_id" value="<?php echo $edit_template['id']; ?>">
            <?php endif; ?>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Template Name *</label>
                        <input type="text" name="name" required value="<?php echo sanitize($edit_template['name'] ?? ''); ?>" placeholder="e.g., Welcome Message">
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category">
                            <option value="marketing" <?php echo ($edit_template['category'] ?? '') == 'marketing' ? 'selected' : ''; ?>>Marketing</option>
                            <option value="utility" <?php echo ($edit_template['category'] ?? '') == 'utility' ? 'selected' : ''; ?>>Utility</option>
                            <option value="authentication" <?php echo ($edit_template['category'] ?? '') == 'authentication' ? 'selected' : ''; ?>>Authentication</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Header Type</label>
                        <select name="header_type">
                            <option value="none" <?php echo ($edit_template['header_type'] ?? '') == 'none' ? 'selected' : ''; ?>>None</option>
                            <option value="text" <?php echo ($edit_template['header_type'] ?? '') == 'text' ? 'selected' : ''; ?>>Text</option>
                            <option value="image" <?php echo ($edit_template['header_type'] ?? '') == 'image' ? 'selected' : ''; ?>>Image</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="draft" <?php echo ($edit_template['status'] ?? '') == 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="approved" <?php echo ($edit_template['status'] ?? '') == 'approved' ? 'selected' : ''; ?>>Approved</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Message Body * <small>(Use {{1}}, {{2}} for variables)</small></label>
                    <textarea name="body_text" rows="6" required placeholder="Hello {{1}}, your order is confirmed..."><?php echo sanitize($edit_template['body_text'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Footer Text</label>
                    <input type="text" name="footer_text" value="<?php echo sanitize($edit_template['footer_text'] ?? ''); ?>" placeholder="e.g., Reply STOP to unsubscribe">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="this.closest('.modal').classList.remove('show');window.history.replaceState({}, '', 'templates.php')">Cancel</button>
                <button type="submit" class="btn btn-wa"><?php echo $edit_template ? 'Update' : 'Create'; ?> Template</button>
            </div>
        </form>
    </div>
</div>

<?php
$conn->close();
include 'includes/footer.php';
?>
