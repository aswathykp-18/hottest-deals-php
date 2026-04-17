<?php
require_once 'config/database.php';
requireLogin();
$pageTitle = 'Contacts';
$conn = getDbConnection();
$base = BASE_URL;

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM contacts WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    header('Location: ' . $base . 'contacts.php?msg=deleted');
    exit;
}

// Handle add
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $stmt = $conn->prepare("INSERT INTO contacts (phone, name, email, tags, notes) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('sssss', $_POST['phone'], $_POST['name'], $_POST['email'], $_POST['tags'], $_POST['notes']);
    if ($stmt->execute()) {
        header('Location: ' . $base . 'contacts.php?msg=added');
        exit;
    }
}

// Handle CSV import
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'import') {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
        $header = fgetcsv($file);
        $imported = 0;
        while (($row = fgetcsv($file)) !== false) {
            if (count($row) >= 2) {
                $phone = trim($row[0]);
                $name = trim($row[1]);
                $email = isset($row[2]) ? trim($row[2]) : '';
                $tags = isset($row[3]) ? trim($row[3]) : '';
                $stmt = $conn->prepare("INSERT IGNORE INTO contacts (phone, name, email, tags) VALUES (?, ?, ?, ?)");
                $stmt->bind_param('ssss', $phone, $name, $email, $tags);
                if ($stmt->execute() && $stmt->affected_rows > 0) $imported++;
            }
        }
        fclose($file);
        header('Location: ' . $base . 'contacts.php?msg=imported&count=' . $imported);
        exit;
    }
}

// Search & filter
$where = "1=1";
$params = [];
$types = '';
if (!empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $where .= " AND (name LIKE ? OR phone LIKE ? OR email LIKE ?)";
    $params = array_merge($params, [$search, $search, $search]);
    $types .= 'sss';
}
if (!empty($_GET['status'])) {
    $where .= " AND status = ?";
    $params[] = $_GET['status'];
    $types .= 's';
}
if (!empty($_GET['tag'])) {
    $tag = '%' . $_GET['tag'] . '%';
    $where .= " AND tags LIKE ?";
    $params[] = $tag;
    $types .= 's';
}

$sql = "SELECT * FROM contacts WHERE $where ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$contacts = $stmt->get_result();

// Get all unique tags
$all_tags = [];
$tags_result = $conn->query("SELECT tags FROM contacts WHERE tags IS NOT NULL AND tags != ''");
while ($r = $tags_result->fetch_assoc()) {
    foreach (explode(',', $r['tags']) as $t) {
        $t = trim($t);
        if ($t && !in_array($t, $all_tags)) $all_tags[] = $t;
    }
}
sort($all_tags);

// Groups for adding to group
$groups = $conn->query("SELECT * FROM contact_groups ORDER BY name");

include 'includes/header.php';
?>

<div class="page-header">
    <div>
        <p class="page-subtitle"><?php echo $contacts->num_rows; ?> contacts found</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-outline" onclick="document.getElementById('importModal').classList.add('show')">
            <i class="fas fa-file-import"></i> Import CSV
        </button>
        <button class="btn btn-wa" onclick="document.getElementById('addModal').classList.add('show')">
            <i class="fas fa-plus"></i> Add Contact
        </button>
    </div>
</div>

<!-- Filters -->
<div class="filters-bar">
    <form method="GET" class="filter-form">
        <div class="filter-input">
            <i class="fas fa-search"></i>
            <input type="text" name="search" placeholder="Search contacts..." value="<?php echo sanitize($_GET['search'] ?? ''); ?>">
        </div>
        <select name="status" class="filter-select">
            <option value="">All Status</option>
            <option value="active" <?php echo ($_GET['status'] ?? '') == 'active' ? 'selected' : ''; ?>>Active</option>
            <option value="blocked" <?php echo ($_GET['status'] ?? '') == 'blocked' ? 'selected' : ''; ?>>Blocked</option>
            <option value="unsubscribed" <?php echo ($_GET['status'] ?? '') == 'unsubscribed' ? 'selected' : ''; ?>>Unsubscribed</option>
        </select>
        <select name="tag" class="filter-select">
            <option value="">All Tags</option>
            <?php foreach ($all_tags as $tag): ?>
                <option value="<?php echo sanitize($tag); ?>" <?php echo ($_GET['tag'] ?? '') == $tag ? 'selected' : ''; ?>><?php echo sanitize($tag); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-sm">Filter</button>
        <?php if (!empty($_GET)): ?>
            <a href="contacts.php" class="btn btn-sm btn-outline">Reset</a>
        <?php endif; ?>
    </form>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php
        switch($_GET['msg']) {
            case 'added': echo 'Contact added successfully!'; break;
            case 'deleted': echo 'Contact deleted!'; break;
            case 'imported': echo ($_GET['count'] ?? 0) . ' contacts imported!'; break;
        }
        ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-0">
        <table class="data-table">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAll"></th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Tags</th>
                    <th>Status</th>
                    <th>Added</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($contact = $contacts->fetch_assoc()): ?>
                <tr>
                    <td><input type="checkbox" class="contact-check" value="<?php echo $contact['id']; ?>"></td>
                    <td>
                        <div class="contact-name-cell">
                            <div class="avatar-sm"><?php echo strtoupper(substr($contact['name'], 0, 1)); ?></div>
                            <strong><?php echo sanitize($contact['name']); ?></strong>
                        </div>
                    </td>
                    <td><i class="fab fa-whatsapp" style="color:#25D366"></i> <?php echo sanitize($contact['phone']); ?></td>
                    <td><?php echo sanitize($contact['email'] ?? '-'); ?></td>
                    <td>
                        <?php if ($contact['tags']): ?>
                            <?php foreach (explode(',', $contact['tags']) as $tag): ?>
                                <span class="tag"><?php echo sanitize(trim($tag)); ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><span class="status-pill status-<?php echo $contact['status']; ?>"><?php echo ucfirst($contact['status']); ?></span></td>
                    <td><?php echo date('M d', strtotime($contact['created_at'])); ?></td>
                    <td class="action-buttons">
                        <a href="inbox.php?contact=<?php echo $contact['id']; ?>" class="btn-icon" title="Chat"><i class="fab fa-whatsapp"></i></a>
                        <a href="contacts.php?delete=<?php echo $contact['id']; ?>" class="btn-icon btn-danger" title="Delete" onclick="return confirm('Delete this contact?')"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Contact Modal -->
<div class="modal" id="addModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus"></i> Add New Contact</h3>
            <button class="modal-close" onclick="this.closest('.modal').classList.remove('show')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group"><label>Phone *</label><input type="text" name="phone" required placeholder="+971501234567"></div>
                    <div class="form-group"><label>Name *</label><input type="text" name="name" required placeholder="Full Name"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Email</label><input type="email" name="email" placeholder="email@example.com"></div>
                    <div class="form-group"><label>Tags</label><input type="text" name="tags" placeholder="buyer,vip (comma-separated)"></div>
                </div>
                <div class="form-group"><label>Notes</label><textarea name="notes" rows="3" placeholder="Additional notes..."></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="this.closest('.modal').classList.remove('show')">Cancel</button>
                <button type="submit" class="btn btn-wa">Add Contact</button>
            </div>
        </form>
    </div>
</div>

<!-- Import CSV Modal -->
<div class="modal" id="importModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-file-import"></i> Import Contacts from CSV</h3>
            <button class="modal-close" onclick="this.closest('.modal').classList.remove('show')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="import">
            <div class="modal-body">
                <p>Upload a CSV file with columns: <strong>Phone, Name, Email, Tags</strong></p>
                <div class="form-group">
                    <label>CSV File</label>
                    <input type="file" name="csv_file" accept=".csv" required>
                </div>
                <div class="csv-example">
                    <strong>Example CSV format:</strong><br>
                    <code>+971501234567,Ahmed Hassan,ahmed@email.com,buyer,vip</code>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="this.closest('.modal').classList.remove('show')">Cancel</button>
                <button type="submit" class="btn btn-wa">Import</button>
            </div>
        </form>
    </div>
</div>

<?php
$conn->close();
include 'includes/footer.php';
?>
