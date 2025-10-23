<?php
/**
 * Fix Employee Statuses Script
 * This script checks and fixes employee statuses that are incorrectly set
 */

require_once 'dp.php';
require_once 'employee_status_functions.php';

echo "=== Fixing Employee Statuses ===\n\n";

try {
    // First, let's check current statuses
    echo "1. Checking current employee statuses:\n";
    echo "----------------------------------------\n";
    
    $stmt = $conn->query("
        SELECT 
            ep.employee_id,
            CONCAT(pi.first_name, ' ', pi.last_name) as employee_name,
            ep.status,
            ep.employment_status
        FROM employee_profiles ep
        LEFT JOIN personal_information pi ON ep.personal_info_id = pi.personal_info_id
        ORDER BY ep.employee_id
    ");
    
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($employees as $employee) {
        $status = $employee['status'] ?? 'NULL';
        echo sprintf("%-3d | %-25s | %-10s | %s\n", 
            $employee['employee_id'],
            $employee['employee_name'],
            $status,
            $employee['employment_status']
        );
    }
    
    echo "\n";
    
    // Fix NULL or incorrect statuses
    echo "2. Fixing employee statuses:\n";
    echo "----------------------------------------\n";
    
    // Update NULL statuses to 'Active'
    $updateStmt = $conn->prepare("UPDATE employee_profiles SET status = 'Active' WHERE status IS NULL");
    $updateStmt->execute();
    $nullUpdates = $updateStmt->rowCount();
    echo "✅ Updated $nullUpdates employees with NULL status to 'Active'\n";
    
    // Update any 'Inactive' statuses that shouldn't be inactive
    $updateStmt2 = $conn->prepare("
        UPDATE employee_profiles 
        SET status = 'Active' 
        WHERE status = 'Inactive' 
        AND employment_status IN ('Full-time', 'Part-time', 'Contract', 'Intern')
    ");
    $updateStmt2->execute();
    $inactiveUpdates = $updateStmt2->rowCount();
    echo "✅ Updated $inactiveUpdates employees from 'Inactive' to 'Active'\n";
    
    // Now run the proper status update based on leave status
    echo "\n3. Running proper status update based on leave status:\n";
    echo "----------------------------------------\n";
    
    $results = updateAllEmployeesStatusBasedOnLeave();
    echo "✅ Status update completed:\n";
    echo sprintf("   - Total employees checked: %d\n", $results['total_checked']);
    echo sprintf("   - Status changes made: %d\n", $results['status_changed']);
    echo sprintf("   - Errors: %d\n", $results['errors']);
    
    echo "\n4. Final employee statuses:\n";
    echo "----------------------------------------\n";
    
    $stmt = $conn->query("
        SELECT 
            ep.employee_id,
            CONCAT(pi.first_name, ' ', pi.last_name) as employee_name,
            ep.status,
            ep.employment_status
        FROM employee_profiles ep
        LEFT JOIN personal_information pi ON ep.personal_info_id = pi.personal_info_id
        ORDER BY ep.employee_id
    ");
    
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($employees as $employee) {
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
            $employee['employment_status']
        );
    }
    
    echo "\n✅ Employee statuses have been fixed!\n";
    echo "\nAll employees should now show 'Active' status unless they are actually on approved leave.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
