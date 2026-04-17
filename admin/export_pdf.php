<?php
require_once '../config/database.php';

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    $base = defined('BASE_URL') ? BASE_URL : '/';
    header('Location: ' . $base . 'admin/login.php');
    exit;
}

$conn = getDbConnection();
$result = $conn->query("SELECT * FROM hottest_deals WHERE is_active = 1 ORDER BY display_order ASC");

// Set headers for PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename=hottest_deals_' . date('Y-m-d') . '.pdf');

// Simple PDF generation using FPDF-like approach (basic HTML to PDF)
// For production, use libraries like TCPDF or FPDF
// Here we'll generate a simple HTML page that can be printed as PDF

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Hottest Property Deals Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h1 {
            text-align: center;
            color: #1e40af;
        }
        .report-info {
            text-align: center;
            margin-bottom: 30px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 12px;
        }
        th {
            background-color: #1e40af;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #999;
            font-size: 11px;
        }
        @media print {
            button { display: none; }
        }
    </style>
</head>
<body>
    <h1>Hottest Property Deals Report</h1>
    <div class="report-info">
        <p>Generated on: <?php echo date('F d, Y H:i:s'); ?></p>
        <button onclick="window.print()" style="padding: 10px 20px; background: #1e40af; color: white; border: none; border-radius: 5px; cursor: pointer;">Print / Save as PDF</button>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Agent</th>
                <th>Area</th>
                <th>Project</th>
                <th>Unit</th>
                <th>Type</th>
                <th>OP</th>
                <th>SP</th>
                <th>Payout</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($deal = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($deal['agent_name']); ?></td>
                    <td><?php echo htmlspecialchars($deal['area']); ?></td>
                    <td><?php echo htmlspecialchars($deal['project_name']); ?></td>
                    <td><?php echo htmlspecialchars($deal['unit'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($deal['property_type'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($deal['op_text'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($deal['sp_text'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($deal['payout'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($deal['status_text'] ?? '-'); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <div class="footer">
        <p>&copy; <?php echo date('Y'); ?> Hottest Property Deals - Confidential Report</p>
    </div>
</body>
</html>
<?php
$conn->close();
exit;
?>
