<?php
require_once '../config/database.php';

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

$conn = getDbConnection();
$result = $conn->query("SELECT * FROM hottest_deals WHERE is_active = 1 ORDER BY display_order ASC");

// Set headers for CSV download (Excel compatible)
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=hottest_deals_' . date('Y-m-d') . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for Excel UTF-8 support
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add column headers
fputcsv($output, ['ID', 'Agent Name', 'Area', 'Project Name', 'Unit', 'Property Type', 'Original Price', 'Selling Price', 'Payout', 'Status', 'Display Order']);

// Add data rows
while ($deal = $result->fetch_assoc()) {
    fputcsv($output, [
        $deal['id'],
        $deal['agent_name'],
        $deal['area'],
        $deal['project_name'],
        $deal['unit'] ?? '',
        $deal['property_type'] ?? '',
        $deal['op_text'] ?? '',
        $deal['sp_text'] ?? '',
        $deal['payout'] ?? '',
        $deal['status_text'] ?? '',
        $deal['display_order']
    ]);
}

fclose($output);
$conn->close();
exit;
?>
