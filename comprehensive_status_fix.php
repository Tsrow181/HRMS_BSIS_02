<?php
/**
 * Comprehensive Employee Status Fix
 * This script ensures all employee statuses are correctly set
 */

require_once 'dp.php';
require_once 'employee_status_functions.php';

echo "=== Comprehensive Employee Status Fix ===\n\n";

try {
    // Step 1: Check for any employees with incorrect statuses
    echo "1. Checking for employees with incorrect statuses:\n";
    echo "----------------------------------------\n";
    
    $stmt = $conn->query("
        SELECT 
            ep.employee_id,
            CONCAT(pi.first_name, ' ', pi.last_name) as employee_name,
            ep.status,
            ep.employment_status,
            CASE 
                WHEN lr.leave_id IS NOT NULL THEN 'On Leave'
                WHEN ep.employment_status IN ('Full-time', 'Part-time', 'Contract', 'Intern') THEN 'Active'
                WHEN ep.employment_status = 'Terminated' THEN 'Inactive'
                ELSE 'Active'
            END as correct_status
        FROM employee_profiles ep
        LEFT JOIN personal_information pi ON ep.personal_info_id = pi.personal_info_id
        LEFT JOIN leave_requests lr ON ep.employee_id = lr.employee_id 
            AND lr.status = 'Approved' 
            AND CURDATE() BETWEEN lr.start_date AND lr.end_date
        ORDER BY ep.employee_id
    ");
    
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $incorrectStatuses = [];
    
    foreach ($employees as $employee) {
        $currentStatus = $employee['status'] ?? 'NULL';
        $correctStatus = $employee['correct_status'];
        
        if ($currentStatus !== $correctStatus) {
            $incorrectStatuses[] = $employee;
            echo sprintf("❌ Employee %d (%s): Current='%s', Should be='%s'\n", 
                $employee['employee_id'],
                $employee['employee_name'],
                $currentStatus,
                $correctStatus
            );
        }
    }
    
    if (empty($incorrectStatuses)) {
        echo "✅ All employee statuses are correct!\n";
    } else {
        echo sprintf("Found %d employees with incorrect statuses.\n", count($incorrectStatuses));
    }
    
    echo "\n";
    
    // Step 2: Fix all incorrect statuses
    echo "2. Fixing incorrect statuses:\n";
    echo "----------------------------------------\n";
    
    $fixedCount = 0;
    foreach ($incorrectStatuses as $employee) {
        $correctStatus = $employee['correct_status'];
        
        $updateStmt = $conn->prepare("UPDATE employee_profiles SET status = ? WHERE employee_id = ?");
        $updateStmt->execute([$correctStatus, $employee['employee_id']]);
        
        echo sprintf("✅ Fixed employee %d (%s): %s → %s\n", 
            $employee['employee_id'],
            $employee['employee_name'],
            $employee['status'] ?? 'NULL',
            $correctStatus
        );
        $fixedCount++;
    }
    
    if ($fixedCount > 0) {
        echo sprintf("\n✅ Fixed %d employee statuses.\n", $fixedCount);
    } else {
        echo "✅ No statuses needed fixing.\n";
    }
    
    echo "\n";
    
    // Step 3: Ensure all employees have a status (no NULL values)
    echo "3. Ensuring no NULL statuses:\n";
    echo "----------------------------------------\n";
    
    $nullStmt = $conn->prepare("UPDATE employee_profiles SET status = 'Active' WHERE status IS NULL");
    $nullStmt->execute();
    $nullCount = $nullStmt->rowCount();
    
    if ($nullCount > 0) {
        echo sprintf("✅ Fixed %d employees with NULL status.\n", $nullCount);
    } else {
        echo "✅ No NULL statuses found.\n";
    }
    
    echo "\n";
    
    // Step 4: Final status check
    echo "4. Final employee statuses:\n";
    echo "----------------------------------------\n";
    
    $finalStmt = $conn->query("
        SELECT 
            ep.employee_id,
            CONCAT(pi.first_name, ' ', pi.last_name) as employee_name,
            ep.status,
            ep.employment_status,
            CASE 
                WHEN lr.leave_id IS NOT NULL THEN '🏖️ On Leave'
                ELSE '✅ Active'
            END as leave_status
        FROM employee_profiles ep
        LEFT JOIN personal_information pi ON ep.personal_info_id = pi.personal_info_id
        LEFT JOIN leave_requests lr ON ep.employee_id = lr.employee_id 
            AND lr.status = 'Approved' 
            AND CURDATE() BETWEEN lr.start_date AND lr.end_date
        ORDER BY ep.employee_id
    ");
    
    $finalEmployees = $finalStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($finalEmployees as $employee) {
        $statusIcon = '';
        switch($employee['status']) {
            case 'Active':
                $statusIcon = '✅';
                break;
            case 'On Leave':
                $statusIcon = '🏖️';
                break;
            case 'Inactive':
                $statusIcon = '⏸️';
                break;
            default:
                $statusIcon = '❓';
        }
        
        echo sprintf("%-3d | %-25s | %s %-10s | %s\n", 
            $employee['employee_id'],
            $employee['employee_name'],
            $statusIcon,
            $employee['status'],
            $employee['leave_status']
        );
    }
    
    echo "\n✅ Employee status fix completed!\n";
    echo "\nAll employees should now have correct statuses:\n";
    echo "- Active employees: ✅ Active\n";
    echo "- Employees on leave: 🏖️ On Leave\n";
    echo "- Terminated employees: ⏸️ Inactive\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
