<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Include database connection and helper functions
require_once 'dp.php';

// Database connection
$host = 'localhost';
$dbname = 'hr_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                // Add new settlement
                try {
                    $stmt = $pdo->prepare("INSERT INTO settlements (exit_id, employee_id, last_working_day, final_salary, severance_pay, unused_leave_payout, deductions, final_settlement_amount, payment_date, payment_method, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['exit_id'],
                        $_POST['employee_id'],
                        $_POST['last_working_day'],
                        $_POST['final_salary'],
                        $_POST['severance_pay'],
                        $_POST['unused_leave_payout'],
                        $_POST['deductions'],
                        $_POST['final_settlement_amount'],
                        $_POST['payment_date'] ?: null,
                        $_POST['payment_method'],
                        $_POST['status'],
                        $_POST['notes']
                    ]);
                    $_SESSION['message'] = "Settlement added successfully!";
                    $_SESSION['messageType'] = "success";
                    header("Location: settlements.php");
                    exit();
                } catch (PDOException $e) {
                    $_SESSION['message'] = "Error adding settlement: " . $e->getMessage();
                    $_SESSION['messageType'] = "error";
                    header("Location: settlements.php");
                    exit();
                }
                break;
            
            case 'update_status':
                // Update settlement status only
                try {
                    $processed_date = null;
                    if ($_POST['status'] === 'Completed') {
                        $processed_date = date('Y-m-d');
                    }
                    
                    $stmt = $pdo->prepare("UPDATE settlements SET status=?, processed_date=? WHERE settlement_id=?");
                    $stmt->execute([
                        $_POST['status'],
                        $processed_date,
                        $_POST['settlement_id']
                    ]);
                    $_SESSION['message'] = "Settlement status updated successfully!";
                    $_SESSION['messageType'] = "success";
                    header("Location: settlements.php");
                    exit();
                } catch (PDOException $e) {
                    $_SESSION['message'] = "Error updating status: " . $e->getMessage();
                    $_SESSION['messageType'] = "error";
                    header("Location: settlements.php");
                    exit();
                }
                break;
            
            case 'delete':
                // Delete settlement
                try {
                    $stmt = $pdo->prepare("DELETE FROM settlements WHERE settlement_id=?");
                    $stmt->execute([$_POST['settlement_id']]);
                    $message = "Settlement record deleted successfully!";
                    $messageType = "success";
                } catch (PDOException $e) {
                    $message = "Error deleting settlement: " . $e->getMessage();
                    $messageType = "error";
                }
                break;
        }
    }
}

// Check for messages in session
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['messageType'];
    unset($_SESSION['message']);
    unset($_SESSION['messageType']);
}

// Fetch settlements with related data
$stmt = $pdo->query("
    SELECT 
        s.*,
        CONCAT(pi.first_name, ' ', pi.last_name) as employee_name,
        ep.employee_number,
        ep.work_email,
        jr.title as job_title,
        jr.department,
        e.exit_type,
        e.exit_date
    FROM settlements s
    LEFT JOIN employee_profiles ep ON s.employee_id = ep.employee_id
    LEFT JOIN personal_information pi ON ep.personal_info_id = pi.personal_info_id
    LEFT JOIN job_roles jr ON ep.job_role_id = jr.job_role_id
    LEFT JOIN exits e ON s.exit_id = e.exit_id
    ORDER BY s.settlement_id DESC
");
$settlements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch exits for dropdown
$stmt = $pdo->query("
    SELECT 
        e.exit_id,
        e.employee_id,
        CONCAT(pi.first_name, ' ', pi.last_name) as employee_name,
        ep.employee_number,
        e.exit_type,
        e.exit_date
    FROM exits e
    LEFT JOIN employee_profiles ep ON e.employee_id = ep.employee_id
    LEFT JOIN personal_information pi ON ep.personal_info_id = pi.personal_info_id
    WHERE e.exit_id NOT IN (SELECT exit_id FROM settlements)
    ORDER BY e.exit_date DESC
");
$availableExits = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all employees for dropdown
$stmt = $pdo->query("
    SELECT 
        ep.employee_id,
        CONCAT(pi.first_name, ' ', pi.last_name) as employee_name,
        ep.employee_number,
        ep.current_salary
    FROM employee_profiles ep
    LEFT JOIN personal_information pi ON ep.personal_info_id = pi.personal_info_id
    ORDER BY pi.first_name
");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settlement Management - HR System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css?v=rose">
    <style>
        /* Additional custom styles for settlements page */
        :root {
            --azure-blue: #E91E63;
            --azure-blue-light: #F06292;
            --azure-blue-dark: #C2185B;
            --azure-blue-lighter: #F8BBD0;
            --azure-blue-pale: #FCE4EC;
        }

        .section-title {
            color: var(--azure-blue);
            margin-bottom: 30px;
            font-weight: 600;
        }
        
        .container-fluid {
            padding: 0;
        }
        
        .row {
            margin-right: 0;
            margin-left: 0;
        }

        body {
            background: var(--azure-blue-pale);
        }

        .main-content {
            background: var(--azure-blue-pale);
            padding: 20px;
        }

        .controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .search-box {
            position: relative;
            flex: 1;
            max-width: 400px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            border-color: var(--azure-blue);
            outline: none;
            box-shadow: 0 0 10px rgba(233, 30, 99, 0.3);
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--azure-blue) 0%, var(--azure-blue-light) 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(233, 30, 99, 0.4);
            background: linear-gradient(135deg, var(--azure-blue-light) 0%, var(--azure-blue-dark) 100%);
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }

        .btn-info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: white;
        }

        .btn-small {
            padding: 8px 15px;
            font-size: 14px;
            margin: 0 3px;
        }

        .table-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background: linear-gradient(135deg, var(--azure-blue-lighter) 0%, #e9ecef 100%);
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: var(--azure-blue-dark);
            border-bottom: 2px solid #dee2e6;
        }

        .table td {
            padding: 15px;
            border-bottom: 1px solid #f1f1f1;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background-color: var(--azure-blue-lighter);
            transform: scale(1.01);
            transition: all 0.2s ease;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-processing {
            background: #cfe2ff;
            color: #084298;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: white;
            margin: 2% auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 800px;
            max-height: 95vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            background: linear-gradient(135deg, var(--azure-blue) 0%, var(--azure-blue-light) 100%);
            color: white;
            padding: 20px 30px;
            border-radius: 15px 15px 0 0;
        }

        .modal-header h2 {
            margin: 0;
        }

        .close {
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: white;
            opacity: 0.7;
        }

        .close:hover {
            opacity: 1;
        }

        .modal-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--azure-blue-dark);
        }

        .form-control {
            width: 100%;
            padding: 6px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--azure-blue);
            outline: none;
            box-shadow: 0 0 10px rgba(233, 30, 99, 0.3);
        }

        .form-row {
            display: flex;
            gap: 20px;
        }

        .form-col {
            flex: 1;
        }

        .calculation-summary {
            background: var(--azure-blue-pale);
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }

        .calculation-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }

        .calculation-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 1.2em;
            color: var(--azure-blue-dark);
            margin-top: 10px;
            padding-top: 15px;
            border-top: 2px solid var(--azure-blue);
        }

        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .no-results {
            text-align: center;
            padding: 50px;
            color: #666;
        }

        .no-results i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #ddd;
        }

        .amount-masked {
            background: linear-gradient(90deg, #e0e0e0 0%, #f0f0f0 50%, #e0e0e0 100%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: 600;
            color: transparent;
            cursor: pointer;
            position: relative;
            display: inline-block;
            min-width: 120px;
            user-select: none;
        }

        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        .amount-masked::after {
            content: '🔒 Hidden';
            position: absolute;
            left: 0;
            right: 0;
            color: #666;
            font-size: 12px;
            font-weight: 600;
        }

        .amount-masked:hover {
            opacity: 0.8;
            transform: scale(1.05);
        }

        .sensitive-badge {
            background: #ff6b6b;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            margin-left: 5px;
        }

        .view-details-link {
            color: var(--azure-blue);
            text-decoration: none;
            cursor: pointer;
            font-weight: 600;
        }

        .view-details-link:hover {
            text-decoration: underline;
        }

        .details-modal-content {
            background: white;
        }

        .sensitive-info-section {
            background: #fff3cd;
            padding: 15px;
            border-left: 4px solid #ffc107;
            margin: 20px 0;
            border-radius: 4px;
        }

        .sensitive-info-section strong {
            color: #856404;
            display: block;
            margin-bottom: 10px;
        }

        .detail-row {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 20px;
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: var(--azure-blue-dark);
        }

        .detail-value {
            color: #333;
        }

        .access-log-badge {
            background: #d1ecf1;
            color: #0c5460;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
        }

        @media (max-width: 768px) {
            .controls {
                flex-direction: column;
                align-items: stretch;
            }

            .search-box {
                max-width: none;
            }

            .form-row {
                flex-direction: column;
            }

            .table-container {
                overflow-x: auto;
            }

            .content {
                padding: 20px;
            }

            .modal-content {
                width: 98%;
                margin: 1% auto;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <?php include 'navigation.php'; ?>
        <div class="row">
            <?php include 'sidebar.php'; ?>
            <div class="main-content">
                <h2 class="section-title">Settlement Management</h2>
                <div class="content">
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $messageType ?>">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>

                    <div class="controls">
                        <div class="search-box">
                            <span class="search-icon">🔍</span>
                            <input type="text" id="searchInput" placeholder="Search settlements by employee name or number...">
                        </div>
                        <button class="btn btn-primary" onclick="openAddModal()">
                            ➕ Add New Settlement
                        </button>
                    </div>

                    <div style="background: #e7f3ff; padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid var(--azure-blue);">
                        <strong style="color: var(--azure-blue);">🔒 Sensitive Data Protection:</strong>
                        <p style="margin: 5px 0 0 0; font-size: 13px; color: #333;">Final settlement amounts are hidden in this view. Click "View Details" to access full information. All access is logged for security.</p>
                    </div>

                    <div class="table-container">
                        <table class="table" id="settlementTable">
                            <thead>
                                <tr>
                                    <th>Settlement ID</th>
                                    <th>Employee</th>
                                    <th>Exit Type</th>
                                    <th>Last Working Day</th>
                                    <th>Final Salary</th>
                                    <th>Settlement Amount</th>
                                    <th>Payment Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="settlementTableBody">
                                <?php foreach ($settlements as $settlement): ?>
                                <tr>
                                    <td><strong>#<?= htmlspecialchars($settlement['settlement_id']) ?></strong></td>
                                    <td>
                                        <div>
                                            <strong><?= htmlspecialchars($settlement['employee_name']) ?></strong><br>
                                            <small style="color: #666;">👤 <?= htmlspecialchars($settlement['employee_number']) ?></small><br>
                                            <small style="color: #666;">📧 <?= htmlspecialchars($settlement['work_email']) ?></small>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($settlement['exit_type']) ?></td>
                                    <td><?= date('M d, Y', strtotime($settlement['last_working_day'])) ?></td>
                                    <td><strong>₱<?= number_format($settlement['final_salary'], 2) ?></strong></td>
                                    <td>
                                        <span class="<?= $settlement['final_settlement_amount'] >= 0 ? 'amount-positive' : 'amount-negative' ?>">
                                            ₱<?= number_format($settlement['final_settlement_amount'], 2) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= $settlement['payment_date'] ? date('M d, Y', strtotime($settlement['payment_date'])) : 'Not Set' ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= strtolower($settlement['status']) ?>">
                                            <?= htmlspecialchars($settlement['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-warning btn-small" onclick="editSettlement(<?= $settlement['settlement_id'] ?>)">
                                            ✏️ Edit
                                        </button>
                                        <button class="btn btn-danger btn-small" onclick="deleteSettlement(<?= $settlement['settlement_id'] ?>)">
                                            🗑️ Delete
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <?php if (empty($settlements)): ?>
                        <div class="no-results">
                            <i>💰</i>
                            <h3>No settlements found</h3>
                            <p>Start by adding your first settlement record.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Settlement Modal -->
    <div id="addSettlementModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Settlement</h2>
                <span class="close" onclick="closeAddModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="settlementForm" method="POST">
                    <input type="hidden" name="action" value="add">

                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="exit_id">Exit Record *</label>
                                <select id="exit_id" name="exit_id" class="form-control" required onchange="loadExitDetails()">
                                    <option value="">Select exit record...</option>
                                    <?php foreach ($availableExits as $exit): ?>
                                    <option value="<?= $exit['exit_id'] ?>" data-employee-id="<?= $exit['employee_id'] ?>" data-exit-date="<?= $exit['exit_date'] ?>">
                                        <?= htmlspecialchars($exit['employee_name']) ?> - <?= htmlspecialchars($exit['exit_type']) ?> (<?= date('M d, Y', strtotime($exit['exit_date'])) ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" id="employee_id" name="employee_id">

                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="last_working_day">Last Working Day *</label>
                                <input type="date" id="last_working_day" name="last_working_day" class="form-control" required>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="payment_date">Payment Date</label>
                                <input type="date" id="payment_date" name="payment_date" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="final_salary">Final Salary (₱) *</label>
                                <input type="number" id="final_salary" name="final_salary" class="form-control" step="0.01" required onchange="calculateTotal()">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="severance_pay">Severance Pay (₱)</label>
                                <input type="number" id="severance_pay" name="severance_pay" class="form-control" step="0.01" value="0" onchange="calculateTotal()">
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="unused_leave_payout">Unused Leave Payout (₱)</label>
                                <input type="number" id="unused_leave_payout" name="unused_leave_payout" class="form-control" step="0.01" value="0" onchange="calculateTotal()">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="deductions">Deductions (₱)</label>
                                <input type="number" id="deductions" name="deductions" class="form-control" step="0.01" value="0" onchange="calculateTotal()">
                            </div>
                        </div>
                    </div>

                    <div class="calculation-summary">
                        <h4 style="color: var(--azure-blue-dark); margin-bottom: 15px;">Settlement Calculation</h4>
                        <div class="calculation-row">
                            <span>Final Salary:</span>
                            <span id="display_final_salary">₱0.00</span>
                        </div>
                        <div class="calculation-row">
                            <span>Severance Pay:</span>
                            <span id="display_severance">₱0.00</span>
                        </div>
                        <div class="calculation-row">
                            <span>Unused Leave Payout:</span>
                            <span id="display_leave">₱0.00</span>
                        </div>
                        <div class="calculation-row">
                            <span>Deductions:</span>
                            <span id="display_deductions" style="color: #dc3545;">-₱0.00</span>
                        </div>
                        <div class="calculation-row">
                            <span>FINAL SETTLEMENT AMOUNT:</span>
                            <span id="display_total">₱0.00</span>
                        </div>
                    </div>

                    <input type="hidden" id="final_settlement_amount" name="final_settlement_amount" value="0">

                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="payment_method">Payment Method</label>
                                <select id="payment_method" name="payment_method" class="form-control">
                                    <option value="">Select method...</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Check">Check</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="status">Status *</label>
                                <select id="status" name="status" class="form-control" required>
                                    <option value="Pending">Pending</option>
                                    <option value="Processing">Processing</option>
                                    <option value="Completed">Completed</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Additional notes or comments..."></textarea>
                    </div>

                    <div style="text-align: center; margin-top: 30px;">
                        <button type="button" class="btn" style="background: #6c757d; color: white; margin-right: 10px;" onclick="closeAddModal()">Cancel</button>
                        <button type="submit" class="btn btn-success">💾 Save Settlement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>Update Settlement Status</h2>
                <span class="close" onclick="closeStatusModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" id="status_settlement_id" name="settlement_id">

                    <div class="form-group">
                        <label for="new_status">Select New Status *</label>
                        <select id="new_status" name="status" class="form-control" required>
                            <option value="Pending">Pending</option>
                            <option value="Processing">Processing</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>

                    <div style="text-align: center; margin-top: 30px;">
                        <button type="button" class="btn" style="background: #6c757d; color: white; margin-right: 10px;" onclick="closeStatusModal()">Cancel</button>
                        <button type="submit" class="btn btn-success">✅ Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Settlement Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-content" style="max-width: 900px;">
            <div class="modal-header">
                <h2>🔒 Settlement Details (Secure View)</h2>
                <span class="close" onclick="closeDetailsModal()">&times;</span>
            </div>
            <div class="modal-body details-modal-content" id="detailsModalBody">
                <!-- Content will be populated dynamically -->
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let settlementsData = <?= json_encode($settlements) ?>;
        let availableExitsData = <?= json_encode($availableExits) ?>;
        let employeesData = <?= json_encode($employees) ?>;

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const tableBody = document.getElementById('settlementTableBody');
            const rows = tableBody.getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const text = row.textContent.toLowerCase();
                
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });

        // Modal functions
        function openModal(mode, settlementId = null) {
            const modal = document.getElementById('settlementModal');
            const form = document.getElementById('settlementForm');
            const title = document.getElementById('modalTitle');
            const action = document.getElementById('action');

            if (mode === 'add') {
                title.textContent = 'Add New Settlement';
                action.value = 'add';
                form.reset();
                document.getElementById('settlement_id').value = '';
                document.getElementById('severance_pay').value = '0';
                document.getElementById('unused_leave_payout').value = '0';
                document.getElementById('deductions').value = '0';
                document.getElementById('calculationBreakdown').style.display = 'none';
            } else if (mode === 'edit' && settlementId) {
                title.textContent = 'Edit Settlement';
                action.value = 'update';
                document.getElementById('settlement_id').value = settlementId;
                populateEditForm(settlementId);
            }

            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            const modal = document.getElementById('settlementModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function populateEditForm(settlementId) {
            const settlement = settlementsData.find(s => s.settlement_id == settlementId);
            if (settlement) {
                document.getElementById('exit_id').value = settlement.exit_id || '';
                document.getElementById('employee_id').value = settlement.employee_id || '';
                document.getElementById('last_working_day').value = settlement.last_working_day || '';
                document.getElementById('final_salary').value = settlement.final_salary || '';
                document.getElementById('severance_pay').value = settlement.severance_pay || '0';
                document.getElementById('unused_leave_payout').value = settlement.unused_leave_payout || '0';
                document.getElementById('deductions').value = settlement.deductions || '0';
                document.getElementById('final_settlement_amount').value = settlement.final_settlement_amount || '';
                document.getElementById('payment_date').value = settlement.payment_date || '';
                document.getElementById('payment_method').value = settlement.payment_method || '';
                document.getElementById('status').value = settlement.status || 'Pending';
                document.getElementById('notes').value = settlement.notes || '';
                
                calculateSettlement();
            }
        }

        function editSettlement(settlementId) {
            openModal('edit', settlementId);
        }

        function deleteSettlement(settlementId) {
            if (confirm('Are you sure you want to delete this settlement record? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="settlement_id" value="${settlementId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Settlement calculation functions
        function calculateSettlement() {
            const finalSalary = parseFloat(document.getElementById('final_salary').value) || 0;
            const severancePay = parseFloat(document.getElementById('severance_pay').value) || 0;
            const leavePayout = parseFloat(document.getElementById('unused_leave_payout').value) || 0;
            const deductions = parseFloat(document.getElementById('deductions').value) || 0;
            
            const totalSettlement = finalSalary + severancePay + leavePayout - deductions;
            
            document.getElementById('final_settlement_amount').value = totalSettlement.toFixed(2);
            
            // Update calculation breakdown
            document.getElementById('calc-final-salary').textContent = '₱' + finalSalary.toFixed(2);
            document.getElementById('calc-severance').textContent = '₱' + severancePay.toFixed(2);
            document.getElementById('calc-leave').textContent = '₱' + leavePayout.toFixed(2);
            document.getElementById('calc-deductions').textContent = '₱' + deductions.toFixed(2);
            document.getElementById('calc-total').textContent = '₱' + totalSettlement.toFixed(2);
            
            // Show calculation breakdown if any values are entered
            if (finalSalary > 0 || severancePay > 0 || leavePayout > 0 || deductions > 0) {
                document.getElementById('calculationBreakdown').style.display = 'block';
            }
        }

        function updateEmployeeFromExit() {
            const exitSelect = document.getElementById('exit_id');
            const employeeSelect = document.getElementById('employee_id');
            
            if (exitSelect.value) {
                const selectedOption = exitSelect.options[exitSelect.selectedIndex];
                const employeeId = selectedOption.getAttribute('data-employee-id');
                employeeSelect.value = employeeId;
                updateSalaryFromEmployee();
            }
        }

        function updateSalaryFromEmployee() {
            const employeeSelect = document.getElementById('employee_id');
            const finalSalaryInput = document.getElementById('final_salary');
            
            if (employeeSelect.value) {
                const selectedOption = employeeSelect.options[employeeSelect.selectedIndex];
                const salary = selectedOption.getAttribute('data-salary');
                finalSalaryInput.value = salary;
                calculateSettlement();
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('settlementModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Form validation
        document.getElementById('settlementForm').addEventListener('submit', function(e) {
            const finalSalary = parseFloat(document.getElementById('final_salary').value);
            if (finalSalary < 0) {
                e.preventDefault();
                alert('Final salary cannot be negative');
                return;
            }

            const total = parseFloat(document.getElementById('final_settlement_amount').value);
            if (total < 0) {
                e.preventDefault();
                alert('Final settlement amount cannot be negative. Please adjust your deductions.');
                return;
            }

            const exitId = document.getElementById('exit_id').value;
            if (!exitId) {
                e.preventDefault();
                alert('Please select an exit record');
                return;
            }
        });

        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            });
        }, 5000);

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects to table rows
            const tableRows = document.querySelectorAll('#settlementTable tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.02)';
                });
                
                row.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
            });
        });
                
    </script>
</body>
</html>
