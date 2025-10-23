<?php
/**
 * Test Web Interface Connection
 * This script tests the same database connection used by the web interface
 */

require_once 'dp.php';

echo "=== Testing Web Interface Database Connection ===\n\n";

try {
    // Test the exact same query used in employee_profile.php
    echo "1. Testing employee profile query:\n";
    echo "----------------------------------------\n";
    
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
    
    echo "Employee data from web interface connection:\n";
    foreach ($employees as $employee) {
        echo sprintf("%-3d | %-25s | %-10s | %s\n", 
            $employee['employee_id'],
            $employee['full_name'],
            $employee['current_status'],
            $employee['employment_status']
        );
    }
    
    echo "\n";
    
    // Check if status column exists in this connection
    echo "2. Checking status column in web interface connection:\n";
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
        echo "❌ Status column does not exist in web interface connection!\n";
        echo "This is the problem - the web interface is using a different database!\n";
    }
    
    // Check current status values
    echo "\n3. Current status values in web interface connection:\n";
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
    
    // Check for any NULL or problematic values
    echo "\n4. Problematic status values in web interface connection:\n";
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
    
    echo "\n✅ Web interface connection test completed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
