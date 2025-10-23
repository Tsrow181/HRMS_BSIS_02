<?php
/**
 * Debug Employee Status Issues
 * This script helps debug any employee status display issues
 */

require_once 'dp.php';

echo "=== Employee Status Debug Information ===\n\n";

try {
    // 1. Check database connection and table structure
    echo "1. Database Connection Test:\n";
    echo "----------------------------------------\n";
    echo "✅ Database connection successful\n";
    
    // 2. Check if status column exists
    echo "\n2. Table Structure Check:\n";
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
        echo "❌ Status column does not exist!\n";
        echo "Run: php run_employee_status_migration.php\n";
        exit(1);
    }
    
    // 3. Check current status values
    echo "\n3. Current Status Values:\n";
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
    
    // 4. Check for any NULL or problematic values
    echo "\n4. Problematic Status Values:\n";
    echo "----------------------------------------\n";
    
    $stmt = $conn->query("
        SELECT 
            ep.employee_id,
            CONCAT(pi.first_name, ' ', pi.last_name) as employee_name,
            ep.status,
            ep.employment_status
        FROM employee_profiles ep
        LEFT JOIN personal_information pi ON ep.personal_info_id = pi.personal_info_id
        WHERE ep.status IS NULL 
           OR ep.status = '' 
           OR ep.status NOT IN ('Active', 'Inactive', 'On Leave')
        ORDER BY ep.employee_id
    ");
    
    $problematicEmployees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($problematicEmployees)) {
        echo "✅ No problematic status values found\n";
    } else {
        echo "❌ Found problematic status values:\n";
        foreach ($problematicEmployees as $employee) {
            echo sprintf("   Employee %d (%s): '%s' (Employment: %s)\n", 
                $employee['employee_id'],
                $employee['employee_name'],
                $employee['status'] ?? 'NULL',
                $employee['employment_status']
            );
        }
    }
    
    // 5. Test the exact query used in employee_profile.php
    echo "\n5. Employee Profile Query Test:\n";
    echo "----------------------------------------\n";
    
    $stmt = $conn->query("
        SELECT 
            ep.employee_id,
            CONCAT(pi.first_name, ' ', pi.last_name) as full_name,
            ep.status,
            COALESCE(ep.status, 'Active') as current_status,
            ep.employment_status
        FROM employee_profiles ep
        LEFT JOIN personal_information pi ON ep.personal_info_id = pi.personal_info_id
        ORDER BY ep.employee_id
        LIMIT 5
    ");
    
    $testEmployees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "First 5 employees from employee profile query:\n";
    foreach ($testEmployees as $employee) {
        echo sprintf("   %d | %s | DB Status: '%s' | Display Status: '%s' | Employment: %s\n", 
            $employee['employee_id'],
            $employee['full_name'],
            $employee['status'] ?? 'NULL',
            $employee['current_status'],
            $employee['employment_status']
        );
    }
    
    // 6. Check for any employees on leave
    echo "\n6. Leave Status Check:\n";
    echo "----------------------------------------\n";
    
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
        echo "✅ No employees are currently on approved leave\n";
    } else {
        echo "🏖️ Employees currently on approved leave:\n";
        foreach ($onLeaveEmployees as $employee) {
            echo sprintf("   %s - %s (%s to %s)\n", 
                $employee['employee_name'],
                $employee['leave_type_name'],
                $employee['start_date'],
                $employee['end_date']
            );
        }
    }
    
    echo "\n✅ Debug information complete!\n";
    echo "\nIf you're still seeing issues:\n";
    echo "1. Clear browser cache (Ctrl+F5)\n";
    echo "2. Check if you're looking at the correct page\n";
    echo "3. Verify the database connection in the web interface\n";
    echo "4. Check for any JavaScript errors in the browser console\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
