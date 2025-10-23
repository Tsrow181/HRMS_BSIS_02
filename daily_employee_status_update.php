<?php
/**
 * Daily Employee Status Update Script
 * This script should be run daily via cron job to update employee statuses
 * based on their current leave status
 */

// Set timezone
date_default_timezone_set('Asia/Manila');

// Include required files
require_once 'dp.php';
require_once 'employee_status_functions.php';

// Log the start of the process
error_log("Daily employee status update started at " . date('Y-m-d H:i:s'));

try {
    // Update all employees' status based on their current leave status
    $results = updateAllEmployeesStatusBasedOnLeave();
    
    // Log the results
    error_log("Daily employee status update completed:");
    error_log("- Total employees checked: " . $results['total_checked']);
    error_log("- Status changes made: " . $results['status_changed']);
    error_log("- Errors encountered: " . $results['errors']);
    
    // If running from command line, output results
    if (php_sapi_name() === 'cli') {
        echo "Daily Employee Status Update Results:\n";
        echo "- Total employees checked: " . $results['total_checked'] . "\n";
        echo "- Status changes made: " . $results['status_changed'] . "\n";
        echo "- Errors encountered: " . $results['errors'] . "\n";
        echo "Update completed at " . date('Y-m-d H:i:s') . "\n";
    }
    
} catch (Exception $e) {
    error_log("Error in daily employee status update: " . $e->getMessage());
    
    if (php_sapi_name() === 'cli') {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

error_log("Daily employee status update finished at " . date('Y-m-d H:i:s'));
?>
