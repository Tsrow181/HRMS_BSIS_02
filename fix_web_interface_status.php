<?php
/**
 * Fix Web Interface Employee Status
 * This script ensures the status column exists and all employees are set to Active
 */

require_once 'dp.php';

echo "=== Fixing Web Interface Employee Status ===\n\n";

try {
    // Step 1: Check if status column exists
    echo "1. Checking if status column exists:\n";
    echo "----------------------------------------\n";
    
    $stmt = $conn->query("DESCRIBE employee_profiles");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasStatusColumn = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'status') {
            $hasStatusColumn = true;
            echo "✅ Status column exists: " . $column['Type'] . " (Default: " . $column['Default'] . ")\n";
            break;
        }
    }
    
    if (!$hasStatusColumn) {
        echo "❌ Status column does not exist! Adding it now...\n";
        
        // Add the status column
        $conn->exec("ALTER TABLE employee_profiles ADD COLUMN status ENUM('Active', 'Inactive', 'On Leave') DEFAULT 'Active' AFTER employment_status");
        echo "✅ Status column added successfully!\n";
    }
    
    echo "\n";
    
    // Step 2: Check current status values
    echo "2. Checking current status values:\n";
    echo "----------------------------------------\n";
    
    $stmt = $conn->query("
        SELECT 
            status,
            COUNT(*) as count
        FROM employee_profiles 
        GROUP BY status
        ORDER BY status
    ");
    
    $statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($statusCounts as $status) {
        echo sprintf("   %s: %d employees\n", 
            $status['status'] ?? 'NULL', 
            $status['count']
        );
    }
    
    echo "\n";
    
    // Step 3: Fix any NULL or incorrect statuses
    echo "3. Fixing employee statuses:\n";
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
    
    // Update any empty string statuses
    $updateStmt3 = $conn->prepare("UPDATE employee_profiles SET status = 'Active' WHERE status = ''");
    $updateStmt3->execute();
    $emptyUpdates = $updateStmt3->rowCount();
    echo "✅ Updated $emptyUpdates employees with empty status to 'Active'\n";
    
    echo "\n";
    
    // Step 4: Final status check
    echo "4. Final employee statuses:\n";
    echo "----------------------------------------\n";
    
    $finalStmt = $conn->query("
        SELECT 
            ep.employee_id,
            CONCAT(pi.first_name, ' ', pi.last_name) as employee_name,
            ep.status,
            ep.employment_status
        FROM employee_profiles ep
        LEFT JOIN personal_information pi ON ep.personal_info_id = pi.personal_info_id
        ORDER BY ep.employee_id
        LIMIT 10
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
            $employee['employment_status']
        );
    }
    
    echo "\n✅ Web interface employee status fix completed!\n";
    echo "\nAll employees should now show 'Active' status in the web interface.\n";
    echo "Try refreshing the employee profile page now.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
