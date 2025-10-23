<?php
/**
 * Test Script for Employee Status Functionality
 * This script demonstrates the employee status management features
 */

require_once 'dp.php';
require_once 'employee_status_functions.php';

echo "=== Employee Status Management Test ===\n\n";

// Test 1: Check current employee statuses
echo "1. Current Employee Statuses:\n";
echo "----------------------------------------\n";

try {
    $stmt = $conn->query("
        SELECT 
            ep.employee_id,
            CONCAT(pi.first_name, ' ', pi.last_name) as employee_name,
            ep.status,
            ep.employment_status
        FROM employee_profiles ep
        LEFT JOIN personal_information pi ON ep.personal_info_id = pi.personal_info_id
        ORDER BY ep.employee_id
        LIMIT 5
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
                $statusIcon = '✅';
        }
        
        echo sprintf("%-3d | %-25s | %s %-10s | %s\n", 
            $employee['employee_id'],
            $employee['employee_name'],
            $statusIcon,
            $employee['status'],
            $employee['employment_status']
        );
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Check if any employees are on leave
echo "2. Leave Status Check:\n";
echo "----------------------------------------\n";

try {
    $stmt = $conn->query("
        SELECT 
            ep.employee_id,
            CONCAT(pi.first_name, ' ', pi.last_name) as employee_name,
            lr.start_date,
            lr.end_date,
            lt.leave_type_name,
            lr.status as leave_status
        FROM employee_profiles ep
        LEFT JOIN personal_information pi ON ep.personal_info_id = pi.personal_info_id
        LEFT JOIN leave_requests lr ON ep.employee_id = lr.employee_id 
            AND lr.status = 'Approved' 
            AND CURDATE() BETWEEN lr.start_date AND lr.end_date
        LEFT JOIN leave_types lt ON lr.leave_type_id = lt.leave_type_id
        WHERE lr.leave_id IS NOT NULL
        ORDER BY ep.employee_id
    ");
    
    $onLeaveEmployees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Test status update functionality
echo "3. Status Update Test:\n";
echo "----------------------------------------\n";

try {
    $results = updateAllEmployeesStatusBasedOnLeave();
    echo "✅ Status update completed:\n";
    echo sprintf("   - Total employees checked: %d\n", $results['total_checked']);
    echo sprintf("   - Status changes made: %d\n", $results['status_changed']);
    echo sprintf("   - Errors: %d\n", $results['errors']);
    
} catch (Exception $e) {
    echo "❌ Error during status update: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Show recent leave requests
echo "4. Recent Leave Requests:\n";
echo "----------------------------------------\n";

try {
    $stmt = $conn->query("
        SELECT 
            lr.leave_id,
            CONCAT(pi.first_name, ' ', pi.last_name) as employee_name,
            lt.leave_type_name,
            lr.start_date,
            lr.end_date,
            lr.status,
            lr.applied_on
        FROM leave_requests lr
        JOIN employee_profiles ep ON lr.employee_id = ep.employee_id
        JOIN personal_information pi ON ep.personal_info_id = pi.personal_info_id
        JOIN leave_types lt ON lr.leave_type_id = lt.leave_type_id
        ORDER BY lr.applied_on DESC
        LIMIT 5
    ");
    
    $recentRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($recentRequests)) {
        echo "No recent leave requests found.\n";
    } else {
        foreach ($recentRequests as $request) {
            $statusIcon = '';
            switch($request['status']) {
                case 'Approved':
                    $statusIcon = '✅';
                    break;
                case 'Rejected':
                    $statusIcon = '❌';
                    break;
                case 'Pending':
                    $statusIcon = '⏳';
                    break;
                default:
                    $statusIcon = '❓';
            }
            
            echo sprintf("%s %s - %s (%s to %s) - %s\n", 
                $statusIcon,
                $request['employee_name'],
                $request['leave_type_name'],
                $request['start_date'],
                $request['end_date'],
                $request['status']
            );
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
echo "\nTo test the full functionality:\n";
echo "1. Go to the Employee Profile page to see the new status column\n";
echo "2. Submit a leave request and approve it to see status change\n";
echo "3. Set up the daily cron job for automatic updates\n";
?>
