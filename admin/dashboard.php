<?php
require_once '../config/database.php';

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    $base = defined('BASE_URL') ? BASE_URL : '/';
    header('Location: ' . $base . 'admin/login.php');
    exit;
}

$conn = getDbConnection();
$base = defined('BASE_URL') ? BASE_URL : '/';

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM hottest_deals WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    header('Location: ' . $base . 'admin/dashboard.php?msg=deleted');
    exit;
}

// Handle toggle active status
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $stmt = $conn->prepare("UPDATE hottest_deals SET is_active = NOT is_active WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    header('Location: ' . $base . 'admin/dashboard.php?msg=updated');
    exit;
}

// Get all deals
$result = $conn->query("SELECT * FROM hottest_deals ORDER BY display_order ASC, id DESC");

$pageTitle = 'Admin Dashboard';
include '../includes/header.php';
?>

<div class="container">
    <div class="admin-header">
        <div>
            <h1><i class="fas fa-cog"></i> Property Management Dashboard</h1>
            <p class="subtitle">Manage all property deals</p>
        </div>
        <div class="admin-actions">
            <a href="<?php echo $base; ?>admin/add.php" class="btn btn-success"><i class="fas fa-plus"></i> Add New Deal</a>
            <a href="<?php echo $base; ?>admin/export_excel.php" class="btn btn-info"><i class="fas fa-file-excel"></i> Export Excel</a>
            <a href="<?php echo $base; ?>admin/export_pdf.php" class="btn btn-warning"><i class="fas fa-file-pdf"></i> Export PDF</a>
        </div>
    </div>
    
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php 
                switch($_GET['msg']) {
                    case 'added': echo 'Deal added successfully!'; break;
                    case 'updated': echo 'Deal updated successfully!'; break;
                    case 'deleted': echo 'Deal deleted successfully!'; break;
                }
            ?>
        </div>
    <?php endif; ?>
    
    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Agent</th>
                    <th>Area</th>
                    <th>Project</th>
                    <th>Unit</th>
                    <th>Type</th>
                    <th>OP</th>
                    <th>SP</th>
                    <th>Status</th>
                    <th>Order</th>
                    <th>Active</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($deal = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $deal['id']; ?></td>
                        <td><?php echo htmlspecialchars($deal['agent_name']); ?></td>
                        <td><?php echo htmlspecialchars($deal['area']); ?></td>
                        <td><?php echo htmlspecialchars($deal['project_name']); ?></td>
                        <td><?php echo htmlspecialchars($deal['unit'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($deal['property_type'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($deal['op_text'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($deal['sp_text'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($deal['status_text'] ?? '-'); ?></td>
                        <td><?php echo $deal['display_order']; ?></td>
                        <td>
                            <span class="status-indicator <?php echo $deal['is_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $deal['is_active'] ? 'Yes' : 'No'; ?>
                            </span>
                        </td>
                        <td class="action-buttons">
                            <a href="<?php echo $base; ?>admin/edit.php?id=<?php echo $deal['id']; ?>" class="btn-icon btn-edit" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="<?php echo $base; ?>admin/dashboard.php?toggle=<?php echo $deal['id']; ?>" class="btn-icon btn-toggle" title="Toggle Active" onclick="return confirm('Toggle active status?')">
                                <i class="fas fa-toggle-on"></i>
                            </a>
                            <a href="<?php echo $base; ?>admin/dashboard.php?delete=<?php echo $deal['id']; ?>" class="btn-icon btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this deal?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
$conn->close();
include '../includes/footer.php'; 
?>
