<?php
require_once 'config/database.php';
$pageTitle = 'Hottest Property Deals';

$conn = getDbConnection();

// Build query with filters
$where = ["is_active = 1"];
$params = [];
$types = '';

if (!empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $where[] = "(agent_name LIKE ? OR area LIKE ? OR project_name LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $types .= 'sss';
}

if (!empty($_GET['property_type'])) {
    $where[] = "property_type = ?";
    $params[] = $_GET['property_type'];
    $types .= 's';
}

if (!empty($_GET['min_price'])) {
    $where[] = "sp_amount >= ?";
    $params[] = $_GET['min_price'];
    $types .= 'd';
}

if (!empty($_GET['max_price'])) {
    $where[] = "sp_amount <= ?";
    $params[] = $_GET['max_price'];
    $types .= 'd';
}

$whereClause = implode(' AND ', $where);

// Sorting
$orderBy = 'display_order ASC';
if (!empty($_GET['sort'])) {
    switch ($_GET['sort']) {
        case 'price_asc':
            $orderBy = 'sp_amount ASC';
            break;
        case 'price_desc':
            $orderBy = 'sp_amount DESC';
            break;
        case 'status':
            $orderBy = 'status_text ASC';
            break;
    }
}

$sql = "SELECT * FROM hottest_deals WHERE $whereClause ORDER BY $orderBy";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Get unique property types for filter
$propertyTypesResult = $conn->query("SELECT DISTINCT property_type FROM hottest_deals WHERE is_active = 1 AND property_type IS NOT NULL ORDER BY property_type");
$propertyTypes = [];
while ($row = $propertyTypesResult->fetch_assoc()) {
    $propertyTypes[] = $row['property_type'];
}

include 'includes/header.php';
?>

<div class="hero-section">
    <div class="container">
        <h1 class="hero-title">Discover Your Dream Property</h1>
        <p class="hero-subtitle">Exclusive real estate deals from top agents across Dubai</p>
    </div>
</div>

<div class="container">
    <div class="filters-section">
        <form method="GET" action="" class="filters-form">
            <div class="filter-group">
                <label><i class="fas fa-search"></i></label>
                <input type="text" name="search" placeholder="Search by agent, area, or project..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            </div>
            
            <div class="filter-group">
                <label><i class="fas fa-building"></i></label>
                <select name="property_type">
                    <option value="">All Property Types</option>
                    <?php foreach ($propertyTypes as $type): ?>
                        <option value="<?php echo htmlspecialchars($type); ?>" <?php echo (isset($_GET['property_type']) && $_GET['property_type'] == $type) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label><i class="fas fa-dollar-sign"></i></label>
                <input type="number" name="min_price" placeholder="Min Price" step="100000" value="<?php echo htmlspecialchars($_GET['min_price'] ?? ''); ?>">
            </div>
            
            <div class="filter-group">
                <label><i class="fas fa-dollar-sign"></i></label>
                <input type="number" name="max_price" placeholder="Max Price" step="100000" value="<?php echo htmlspecialchars($_GET['max_price'] ?? ''); ?>">
            </div>
            
            <div class="filter-group">
                <label><i class="fas fa-sort"></i></label>
                <select name="sort">
                    <option value="">Default Order</option>
                    <option value="price_asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'price_asc') ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price_desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'price_desc') ? 'selected' : ''; ?>>Price: High to Low</option>
                    <option value="status" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'status') ? 'selected' : ''; ?>>By Status</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Apply Filters</button>
            <?php if (!empty($_GET)): ?>
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-redo"></i> Reset</a>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="deals-count">
        <p><i class="fas fa-home"></i> Showing <strong><?php echo $result->num_rows; ?></strong> property deals</p>
    </div>
    
    <div class="deals-grid">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($deal = $result->fetch_assoc()): ?>
                <div class="deal-card">
                    <div class="deal-header">
                        <div class="agent-info">
                            <i class="fas fa-user-tie"></i>
                            <span class="agent-name"><?php echo htmlspecialchars($deal['agent_name']); ?></span>
                        </div>
                        <?php if ($deal['status_text']): ?>
                            <span class="status-badge"><?php echo htmlspecialchars($deal['status_text']); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="deal-content">
                        <h3 class="project-name"><?php echo htmlspecialchars($deal['project_name']); ?></h3>
                        <p class="area-info"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($deal['area']); ?></p>
                        
                        <div class="property-details">
                            <span class="detail-item"><i class="fas fa-bed"></i> <?php echo htmlspecialchars($deal['unit'] ?? 'N/A'); ?></span>
                            <span class="detail-item"><i class="fas fa-building"></i> <?php echo htmlspecialchars($deal['property_type'] ?? 'N/A'); ?></span>
                        </div>
                        
                        <div class="price-section">
                            <?php if ($deal['op_amount']): ?>
                                <div class="price-item">
                                    <span class="price-label">Original Price</span>
                                    <span class="price-value original"><?php echo $deal['op_text'] ?: number_format($deal['op_amount']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($deal['sp_amount']): ?>
                                <div class="price-item">
                                    <span class="price-label">Selling Price</span>
                                    <span class="price-value selling"><?php echo $deal['sp_text'] ?: number_format($deal['sp_amount']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($deal['payout']): ?>
                            <div class="payout-info">
                                <i class="fas fa-calendar-check"></i>
                                <span><?php echo htmlspecialchars($deal['payout']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-results">
                <i class="fas fa-search"></i>
                <h3>No deals found</h3>
                <p>Try adjusting your search criteria</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$conn->close();
include 'includes/footer.php';
?>