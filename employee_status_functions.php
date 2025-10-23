<?php
/**
 * Employee Status Management Functions
 * Functions to handle employee status based on leave status
 */

require_once 'dp.php';

/**
 * Check if an employee is currently on approved leave
 * @param int $employee_id The employee ID to check
 * @param string $date Optional date to check (defaults to current date)
 * @return bool True if employee is on leave, false otherwise
 */
function isEmployeeOnLeave($employee_id, $date = null) {
    global $conn;
    
    if ($date === null) {
        $date = date('Y-m-d');
    }
    
    try {
        $sql = "SELECT COUNT(*) as leave_count 
                FROM leave_requests 
                WHERE employee_id = ? 
                AND status = 'Approved' 
                AND start_date <= ? 
                AND end_date >= ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$employee_id, $date, $date]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['leave_count'] > 0;
    } catch (PDOException $e) {
        error_log("Error checking leave status for employee $employee_id: " . $e->getMessage());
        return false;
    }
}

/**
 * Update employee status based on their current leave status
 * @param int $employee_id The employee ID to update
 * @param string $date Optional date to check (defaults to current date)
 * @return bool True if status was updated, false otherwise
 */
function updateEmployeeStatusBasedOnLeave($employee_id, $date = null) {
    global $conn;
    
    if ($date === null) {
        $date = date('Y-m-d');
    }
    
    try {
        $isOnLeave = isEmployeeOnLeave($employee_id, $date);
        $newStatus = $isOnLeave ? 'On Leave' : 'Active';
        
        // Get current status
        $stmt = $conn->prepare("SELECT status FROM employee_profiles WHERE employee_id = ?");
        $stmt->execute([$employee_id]);
        $currentStatus = $stmt->fetchColumn();
        
        // Only update if status has changed
        if ($currentStatus !== $newStatus) {
            $updateStmt = $conn->prepare("UPDATE employee_profiles SET status = ? WHERE employee_id = ?");
            $updateStmt->execute([$newStatus, $employee_id]);
            
            // Log the status change
            logActivity("Employee status changed from '$currentStatus' to '$newStatus'", "employee_profiles", $employee_id);
            
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Error updating employee status for employee $employee_id: " . $e->getMessage());
        return false;
    }
}

/**
 * Update all employees' status based on their current leave status
 * @param string $date Optional date to check (defaults to current date)
 * @return array Array with counts of updated employees
 */
function updateAllEmployeesStatusBasedOnLeave($date = null) {
    global $conn;
    
    if ($date === null) {
        $date = date('Y-m-d');
    }
    
    $results = [
        'total_checked' => 0,
        'status_changed' => 0,
        'errors' => 0
    ];
    
    try {
        // Get all active employees (not terminated)
        $stmt = $conn->query("SELECT employee_id FROM employee_profiles WHERE employment_status != 'Terminated'");
        $employees = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($employees as $employee_id) {
            $results['total_checked']++;
            
            if (updateEmployeeStatusBasedOnLeave($employee_id, $date)) {
                $results['status_changed']++;
            }
        }
        
    } catch (PDOException $e) {
        error_log("Error updating all employees status: " . $e->getMessage());
        $results['errors']++;
    }
    
    return $results;
}

/**
 * Get employee status with leave information
 * @param int $employee_id The employee ID
 * @return array Employee status information
 */
function getEmployeeStatusInfo($employee_id) {
    global $conn;
    
    try {
        $sql = "SELECT 
                    ep.employee_id,
                    ep.status,
                    ep.employment_status,
                    CONCAT(pi.first_name, ' ', pi.last_name) as employee_name,
                    lr.leave_id,
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
                WHERE ep.employee_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$employee_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error getting employee status info for employee $employee_id: " . $e->getMessage());
        return null;
    }
}

/**
 * Automatically update employee status when leave is approved/rejected
 * This function should be called when leave status changes
 * @param int $employee_id The employee ID
 * @param string $leave_status The new leave status
 */
function handleLeaveStatusChange($employee_id, $leave_status) {
    // Only update status if leave was approved or rejected
    if (in_array($leave_status, ['Approved', 'Rejected'])) {
        updateEmployeeStatusBasedOnLeave($employee_id);
    }
}
?>
