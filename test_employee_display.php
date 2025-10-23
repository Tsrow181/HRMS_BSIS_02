<?php
/**
 * Test Employee Display
 * This script creates a simple test page to see what's being displayed
 */

require_once 'dp.php';

// Fetch employees with related data including status (same as employee_profile.php)
$stmt = $conn->query("
    SELECT 
        ep.*,
        CONCAT(pi.first_name, ' ', pi.last_name) as full_name,
        pi.first_name,
        pi.last_name,
        pi.phone_number,
        jr.title as job_title,
        jr.department,
        COALESCE(ep.status, 'Active') as current_status
    FROM employee_profiles ep
    LEFT JOIN personal_information pi ON ep.personal_info_id = pi.personal_info_id
    LEFT JOIN job_roles jr ON ep.job_role_id = jr.job_role_id
    ORDER BY ep.employee_id DESC
    LIMIT 10
");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Status Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        .status-on-leave {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
    </style>
</head>
<body>
    <h1>Employee Status Test Page</h1>
    <p>This page shows exactly what the employee profile page should display:</p>
    
    <table>
        <thead>
            <tr>
                <th>Employee #</th>
                <th>Name</th>
                <th>Job Title</th>
                <th>Department</th>
                <th>Email</th>
                <th>Salary</th>
                <th>Employment Status</th>
                <th>Current Status</th>
                <th>Hire Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($employees as $employee): ?>
            <tr>
                <td><strong><?= htmlspecialchars($employee['employee_number']) ?></strong></td>
                <td>
                    <div>
                        <strong><?= htmlspecialchars($employee['full_name']) ?></strong><br>
                        <small style="color: #666;">📞 <?= htmlspecialchars($employee['phone_number']) ?></small>
                    </div>
                </td>
                <td><?= htmlspecialchars($employee['job_title']) ?></td>
                <td><?= htmlspecialchars($employee['department']) ?></td>
                <td><?= htmlspecialchars($employee['work_email']) ?></td>
                <td><strong>₱<?= number_format($employee['current_salary'], 2) ?></strong></td>
                <td>
                    <span class="status-badge status-<?= strtolower($employee['employment_status']) === 'full-time' ? 'active' : 'inactive' ?>">
                        <?= htmlspecialchars($employee['employment_status']) ?>
                    </span>
                </td>
                <td>
                    <?php 
                    $currentStatus = $employee['current_status'] ?? 'Active';
                    $statusClass = '';
                    $statusIcon = '';
                    
                    switch($currentStatus) {
                        case 'Active':
                            $statusClass = 'status-active';
                            $statusIcon = '✅';
                            break;
                        case 'On Leave':
                            $statusClass = 'status-on-leave';
                            $statusIcon = '🏖️';
                            break;
                        case 'Inactive':
                            $statusClass = 'status-inactive';
                            $statusIcon = '⏸️';
                            break;
                        default:
                            $statusClass = 'status-active';
                            $statusIcon = '✅';
                    }
                    ?>
                    <span class="status-badge <?= $statusClass ?>">
                        <?= $statusIcon ?> <?= htmlspecialchars($currentStatus) ?>
                    </span>
                </td>
                <td><?= date('M d, Y', strtotime($employee['hire_date'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <h2>Debug Information</h2>
    <p><strong>Total employees:</strong> <?= count($employees) ?></p>
    <p><strong>Database connection:</strong> Working</p>
    <p><strong>Status column:</strong> Exists</p>
    
    <h3>Raw Employee Data (First 3 employees):</h3>
    <pre><?= htmlspecialchars(print_r(array_slice($employees, 0, 3), true)) ?></pre>
</body>
</html>
