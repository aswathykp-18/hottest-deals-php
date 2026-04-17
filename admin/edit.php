<?php
require_once '../config/database.php';

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    $base = defined('BASE_URL') ? BASE_URL : '/';
    header('Location: ' . $base . 'admin/login.php');
    exit;
}

$conn = getDbConnection();
$base = defined('BASE_URL') ? BASE_URL : '/';
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: ' . $base . 'admin/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $conn->prepare("UPDATE hottest_deals SET agent_name=?, area=?, project_name=?, unit=?, property_type=?, op_text=?, sp_text=?, op_amount=?, sp_amount=?, payout=?, status_text=?, display_order=?, is_active=? WHERE id=?");
    
    $agent_name = $_POST['agent_name'];
    $area = $_POST['area'];
    $project_name = $_POST['project_name'];
    $unit = $_POST['unit'] ?: null;
    $property_type = $_POST['property_type'] ?: null;
    $op_text = $_POST['op_text'] ?: null;
    $sp_text = $_POST['sp_text'] ?: null;
    $op_amount = $_POST['op_amount'] ?: null;
    $sp_amount = $_POST['sp_amount'] ?: null;
    $payout = $_POST['payout'] ?: null;
    $status_text = $_POST['status_text'] ?: null;
    $display_order = intval($_POST['display_order']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $stmt->bind_param('sssssssddssiii', $agent_name, $area, $project_name, $unit, $property_type, $op_text, $sp_text, $op_amount, $sp_amount, $payout, $status_text, $display_order, $is_active, $id);
    
    if ($stmt->execute()) {
        header('Location: ' . $base . 'admin/dashboard.php?msg=updated');
        exit;
    } else {
        $error = 'Error updating deal: ' . $conn->error;
    }
}

// Get deal data
$stmt = $conn->prepare("SELECT * FROM hottest_deals WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$deal = $result->fetch_assoc();

if (!$deal) {
    header('Location: ' . $base . 'admin/dashboard.php');
    exit;
}

$pageTitle = 'Edit Deal';
include '../includes/header.php';
?>

<div class="container">
    <div class="form-header">
        <h1><i class="fas fa-edit"></i> Edit Property Deal</h1>
        <a href="<?php echo $base; ?>admin/dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" class="admin-form">
        <div class="form-grid">
            <div class="form-group">
                <label>Agent Name *</label>
                <input type="text" name="agent_name" value="<?php echo htmlspecialchars($deal['agent_name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Area *</label>
                <input type="text" name="area" value="<?php echo htmlspecialchars($deal['area']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Project Name *</label>
                <input type="text" name="project_name" value="<?php echo htmlspecialchars($deal['project_name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Unit</label>
                <input type="text" name="unit" value="<?php echo htmlspecialchars($deal['unit'] ?? ''); ?>" placeholder="e.g., 3 Bed">
            </div>
            
            <div class="form-group">
                <label>Property Type</label>
                <select name="property_type">
                    <option value="">Select Type</option>
                    <option value="Apartment" <?php echo $deal['property_type'] == 'Apartment' ? 'selected' : ''; ?>>Apartment</option>
                    <option value="Villa" <?php echo $deal['property_type'] == 'Villa' ? 'selected' : ''; ?>>Villa</option>
                    <option value="Townhouse" <?php echo $deal['property_type'] == 'Townhouse' ? 'selected' : ''; ?>>Townhouse</option>
                    <option value="SA Villa" <?php echo $deal['property_type'] == 'SA Villa' ? 'selected' : ''; ?>>SA Villa</option>
                    <option value="SD Villa" <?php echo $deal['property_type'] == 'SD Villa' ? 'selected' : ''; ?>>SD Villa</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Original Price (Text)</label>
                <input type="text" name="op_text" value="<?php echo htmlspecialchars($deal['op_text'] ?? ''); ?>" placeholder="e.g., 1.5M">
            </div>
            
            <div class="form-group">
                <label>Original Price (Amount)</label>
                <input type="number" name="op_amount" value="<?php echo $deal['op_amount']; ?>" step="0.01" placeholder="1500000.00">
            </div>
            
            <div class="form-group">
                <label>Selling Price (Text)</label>
                <input type="text" name="sp_text" value="<?php echo htmlspecialchars($deal['sp_text'] ?? ''); ?>" placeholder="e.g., 1.4M">
            </div>
            
            <div class="form-group">
                <label>Selling Price (Amount)</label>
                <input type="number" name="sp_amount" value="<?php echo $deal['sp_amount']; ?>" step="0.01" placeholder="1400000.00">
            </div>
            
            <div class="form-group">
                <label>Payout Terms</label>
                <input type="text" name="payout" value="<?php echo htmlspecialchars($deal['payout'] ?? ''); ?>" placeholder="e.g., 60% Paid">
            </div>
            
            <div class="form-group">
                <label>Status</label>
                <input type="text" name="status_text" value="<?php echo htmlspecialchars($deal['status_text'] ?? ''); ?>" placeholder="e.g., 2027, Ready">
            </div>
            
            <div class="form-group">
                <label>Display Order</label>
                <input type="number" name="display_order" value="<?php echo $deal['display_order']; ?>">
            </div>
        </div>
        
        <div class="form-group checkbox-group">
            <label>
                <input type="checkbox" name="is_active" <?php echo $deal['is_active'] ? 'checked' : ''; ?>>
                <span>Active (Show on public page)</span>
            </label>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Update Deal</button>
            <a href="<?php echo $base; ?>admin/dashboard.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php 
$conn->close();
include '../includes/footer.php'; 
?>
