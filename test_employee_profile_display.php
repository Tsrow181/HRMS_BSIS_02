<?php
/**
 * Test Employee Profile Display
 * This script tests the employee profile page display logic
 */

require_once 'dp.php';

echo "=== Testing Employee Profile Display ===\n\n";

try {
    // Test the same query used in employee_profile.php
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
    
    echo "Employee Profile Display Test:\n";
    echo "========================================\n";
    
    foreach ($employees as $employee) {
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
        
        echo sprintf("%-3d | %-25s | %s %-10s | %s\n", 
            $employee['employee_id'],
            $employee['full_name'],
            $statusIcon,
            $currentStatus,
            $employee['employment_status']
        );
    }
    
    echo "\n✅ Employee profile display test completed!\n";
    echo "\nAll employees should show 'Active' status unless they are on approved leave.\n";
    
    // Test if any employees are actually on leave
    echo "\nChecking for employees currently on leave:\n";
    echo "========================================\n";
    
    $leaveStmt = $conn->query("
        SELECT 
            ep.employee_id,
            CONCAT(pi.first_name, ' ', pi.last_name) as employee_name,
            lr.start_date,
            lr.end_date,
            lt.leave_type_name
        FROM employee_profiles ep
        LEFT JOIN personal_information pi ON ep.personal_info_id = pi.personal_info_id
        JOIN leave_requests lr ON ep.employee_id = lr.employee_id 
            AND lr.status = 'Approved' 
            AND CURDATE() BETWEEN lr.start_date AND lr.end_date
        JOIN leave_types lt ON lr.leave_type_id = lt.leave_type_id
        ORDER BY ep.employee_id
    ");
    
    $onLeaveEmployees = $leaveStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($onLeaveEmployees)) {
        echo "✅ No employees are currently on leave.\n";
    } else {
        echo "🏖️ Employees currently on leave:\n";
        foreach ($onLeaveEmployees as $employee) {
            echo sprintf("   %s - %s (%s to %s)\n", 
                $employee['employee_name'],
                $employee['leave_type_name'],
                $employee['start_date'],
                $employee['end_date']
            );
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
