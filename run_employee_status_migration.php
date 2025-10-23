<?php
/**
 * Employee Status Migration Script
 * This script adds the status column to employee_profiles table and sets up the initial data
 */

require_once 'dp.php';

echo "Starting Employee Status Migration...\n";

try {
    // Read and execute the SQL migration
    $sql = file_get_contents('add_employee_status_column.sql');
    
    if ($sql === false) {
        throw new Exception("Could not read migration SQL file");
    }
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            echo "Executing: " . substr($statement, 0, 50) . "...\n";
            $conn->exec($statement);
        }
    }
    
    echo "✅ Database migration completed successfully!\n";
    
    // Test the new functions
    require_once 'employee_status_functions.php';
    
    echo "\nTesting employee status functions...\n";
    
    // Test updating all employees
    $results = updateAllEmployeesStatusBasedOnLeave();
    echo "✅ Status update test completed:\n";
    echo "   - Total employees checked: " . $results['total_checked'] . "\n";
    echo "   - Status changes made: " . $results['status_changed'] . "\n";
    echo "   - Errors: " . $results['errors'] . "\n";
    
    // Test individual employee status check
    $testEmployeeId = 1; // Test with first employee
    $isOnLeave = isEmployeeOnLeave($testEmployeeId);
    echo "✅ Test employee #$testEmployeeId is " . ($isOnLeave ? "on leave" : "not on leave") . "\n";
    
    echo "\n🎉 Migration and testing completed successfully!\n";
    echo "\nNext steps:\n";
    echo "1. Set up the daily cron job using setup_cron_job.md\n";
    echo "2. Test the employee profile page to see the new status column\n";
    echo "3. Test approving/rejecting leave requests to see status changes\n";
    
} catch (Exception $e) {
    echo "❌ Error during migration: " . $e->getMessage() . "\n";
    exit(1);
}
?>
