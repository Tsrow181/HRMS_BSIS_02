<?php
require_once 'config.php';

function getTotalEmployees() {
    global $conn;
    try {
        $sql = "SELECT COUNT(*) as total_employees FROM employee_profiles";
        $stmt = $conn->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total_employees'] ?? 0;
    } catch (PDOException $e) {
        return 0;
    }
}

function getTotalDepartments() {
    global $conn;
    try {
        $sql = "SELECT COUNT(*) as total_departments FROM departments";
        $stmt = $conn->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total_departments'] ?? 0;
    } catch (PDOException $e) {
        return 0;
    }
}

function getActiveJobOpenings() {
    global $conn;
    try {
        $sql = "SELECT COUNT(*) as active_job_openings FROM job_openings WHERE status = 'Open'";
        $stmt = $conn->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['active_job_openings'] ?? 0;
    } catch (PDOException $e) {
        return 0;
    }
}

function getUpcomingTrainingSessions() {
    global $conn;
    try {
        $sql = "SELECT COUNT(*) as upcoming_training_sessions FROM training_sessions WHERE status = 'Scheduled' AND start_date >= CURDATE()";
        $stmt = $conn->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['upcoming_training_sessions'] ?? 0;
    } catch (PDOException $e) {
        return 0;
    }
}

function getRecentActivities() {
    global $conn;
    try {
        $sql = "SELECT a.action, u.username, a.created_at FROM audit_logs a JOIN users u ON a.user_id = u.user_id ORDER BY a.created_at DESC LIMIT 5";
        $stmt = $conn->query($sql);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("getRecentActivities: fetched " . count($result) . " activities");
        return $result;
    } catch (PDOException $e) {
        error_log("getRecentActivities: error - " . $e->getMessage());
        return [];
    }
}

// Leave Balances Functions
function getLeaveBalances() {
    global $conn;
    try {
        // Get all employees with their leave balances grouped by employee, including gender info
        $sql = "SELECT 
                    ep.employee_id,
                    pi.first_name, 
                    pi.last_name,
                    pi.gender,
                    ep.employee_number,
                    d.department_name,
                    COALESCE(SUM(CASE WHEN lt.leave_type_name = 'Vacation Leave' THEN lb.leaves_remaining ELSE 0 END), 0) as vacation_leave,
                    COALESCE(SUM(CASE WHEN lt.leave_type_name = 'Sick Leave' THEN lb.leaves_remaining ELSE 0 END), 0) as sick_leave,
                    COALESCE(SUM(CASE WHEN lt.leave_type_name = 'Maternity Leave' AND pi.gender = 'Female' THEN lb.leaves_remaining ELSE 0 END), 0) as maternity_leave,
                    COALESCE(SUM(CASE WHEN lt.leave_type_name = 'Paternity Leave' AND pi.gender = 'Male' THEN lb.leaves_remaining ELSE 0 END), 0) as paternity_leave,
                    COALESCE(SUM(lb.leaves_remaining), 0) as total_balance
                FROM employee_profiles ep
                JOIN personal_information pi ON ep.personal_info_id = pi.personal_info_id
                LEFT JOIN departments d ON ep.job_role_id IN (
                    SELECT job_role_id FROM job_roles WHERE department = d.department_name
                )
                LEFT JOIN leave_balances lb ON ep.employee_id = lb.employee_id AND lb.year = YEAR(CURDATE())
                LEFT JOIN leave_types lt ON lb.leave_type_id = lt.leave_type_id
                GROUP BY ep.employee_id, pi.first_name, pi.last_name, pi.gender, ep.employee_number, d.department_name
                ORDER BY pi.first_name, pi.last_name";
        $stmt = $conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("getLeaveBalances error: " . $e->getMessage());
        return [];
    }
}

function getLeaveTypeTotals() {
    global $conn;
    try {
        $sql = "SELECT lt.leave_type_name,
                       SUM(lb.total_leaves) as total_allocated,
                       SUM(lb.leaves_taken) as total_taken,
                       SUM(lb.leaves_remaining) as total_remaining,
                       ROUND((SUM(lb.leaves_taken) / SUM(lb.total_leaves)) * 100, 2) as utilization_percentage
                FROM leave_types lt
                LEFT JOIN leave_balances lb ON lt.leave_type_id = lb.leave_type_id AND lb.year = YEAR(CURDATE())
                GROUP BY lt.leave_type_id, lt.leave_type_name
                ORDER BY lt.leave_type_name";
        $stmt = $conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function getLeaveUtilizationTrend() {
    global $conn;
    try {
        // This is a simplified trend - in a real system, you'd have historical data
        $sql = "SELECT MONTH(CURDATE()) as current_month,
                       YEAR(CURDATE()) as current_year,
                       SUM(leaves_taken) as current_taken,
                       (SELECT SUM(leaves_taken) FROM leave_balances WHERE year = YEAR(CURDATE()) - 1 AND MONTH(created_at) = MONTH(CURDATE())) as previous_year_taken
                FROM leave_balances
                WHERE year = YEAR(CURDATE())";
        $stmt = $conn->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: ['current_month' => date('m'), 'current_year' => date('Y'), 'current_taken' => 0, 'previous_year_taken' => 0];
    } catch (PDOException $e) {
        return ['current_month' => date('m'), 'current_year' => date('Y'), 'current_taken' => 0, 'previous_year_taken' => 0];
    }
}

function getLowBalanceAlerts() {
    global $conn;
    try {
        $sql = "SELECT 
                    pi.first_name, 
                    pi.last_name,
                    pi.gender,
                    COALESCE(SUM(CASE WHEN lt.leave_type_name = 'Vacation Leave' THEN lb.leaves_remaining ELSE 0 END), 0) as vacation_leave,
                    COALESCE(SUM(CASE WHEN lt.leave_type_name = 'Sick Leave' THEN lb.leaves_remaining ELSE 0 END), 0) as sick_leave,
                    COALESCE(SUM(lb.leaves_remaining), 0) as total_balance
                FROM employee_profiles ep
                JOIN personal_information pi ON ep.personal_info_id = pi.personal_info_id
                LEFT JOIN leave_balances lb ON ep.employee_id = lb.employee_id AND lb.year = YEAR(CURDATE())
                LEFT JOIN leave_types lt ON lb.leave_type_id = lt.leave_type_id
                GROUP BY ep.employee_id, pi.first_name, pi.last_name, pi.gender
                HAVING (vacation_leave < 5 OR sick_leave < 5 OR total_balance < 10)
                ORDER BY total_balance ASC";
        $stmt = $conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("getLowBalanceAlerts error: " . $e->getMessage());
        return [];
    }
}

// Shifts Functions
function getShifts() {
    global $conn;
    try {
        $sql = "SELECT DISTINCT * FROM shifts ORDER BY shift_name";
        $stmt = $conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Employee Shifts Functions
function getEmployeeShifts() {
    global $conn;
    try {
        $sql = "SELECT ep.employee_id, pi.first_name, pi.last_name, d.department_name,
                       es.employee_shift_id, es.shift_id, es.assigned_date, es.is_overtime, es.status,
                       s.shift_name
                FROM employee_profiles ep
                JOIN personal_information pi ON ep.personal_info_id = pi.personal_info_id
                LEFT JOIN employee_shifts es ON ep.employee_id = es.employee_id
                LEFT JOIN shifts s ON es.shift_id = s.shift_id
                LEFT JOIN departments d ON ep.job_role_id IN (
                    SELECT job_role_id FROM job_roles WHERE department = d.department_name
                )
                WHERE ep.employee_id IN (
                    SELECT employee_id FROM employment_history
                    WHERE history_id IN (
                        SELECT MAX(history_id) FROM employment_history GROUP BY employee_id
                    ) AND employment_status = 'Active'
                )
                ORDER BY pi.first_name, pi.last_name";
        $stmt = $conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function addEmployeeShift($employeeId, $shiftId, $assignedDate, $isOvertime) {
    global $conn;
    try {
        $sql = "INSERT INTO employee_shifts (employee_id, shift_id, assigned_date, is_overtime, status) VALUES (?, ?, ?, ?, 'Active')";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([$employeeId, $shiftId, $assignedDate, $isOvertime]);
    } catch (PDOException $e) {
        return false;
    }
}

function updateEmployeeShift($employeeShiftId, $shiftId, $assignedDate, $isOvertime) {
    global $conn;
    try {
        $sql = "UPDATE employee_shifts SET shift_id = ?, assigned_date = ?, is_overtime = ?, status = 'Active' WHERE employee_shift_id = ?";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([$shiftId, $assignedDate, $isOvertime, $employeeShiftId]);
    } catch (PDOException $e) {
        return false;
    }
}

function deleteEmployeeShift($employeeShiftId) {
    global $conn;
    try {
        $sql = "DELETE FROM employee_shifts WHERE employee_shift_id = ?";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([$employeeShiftId]);
    } catch (PDOException $e) {
        return false;
    }
}

// Additional helper functions
function getEmployees() {
    global $conn;
    try {
        $sql = "SELECT ep.employee_id, pi.first_name, pi.last_name, ep.employee_number
                FROM employee_profiles ep
                JOIN personal_information pi ON ep.personal_info_id = pi.personal_info_id
                ORDER BY pi.first_name, pi.last_name";
        $stmt = $conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function getLeaveTypes() {
    global $conn;
    try {
        $sql = "SELECT * FROM leave_types ORDER BY leave_type_name";
        $stmt = $conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Function to log user activity
function logActivity($action, $table_name, $record_id = null, $details = '') {
    global $conn;

    error_log("logActivity called: $action for table $table_name, record $record_id");
    error_log("logActivity: conn is " . (isset($conn) ? "set" : "not set"));
    error_log("logActivity: user_id in session is " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : "not set"));

    if (isset($conn) && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $sql = "INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        try {
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([
                $user_id,
                $action,
                $table_name,
                $record_id,
                null,
                json_encode($details),
                $ip_address,
                $user_agent
            ]);
            error_log("logActivity: Successfully executed INSERT, result: " . ($result ? "true" : "false"));
            error_log("logActivity: Last insert ID: " . $conn->lastInsertId());
        } catch (PDOException $e) {
            // Log error silently
            error_log("Error logging activity: " . $e->getMessage());
        }
    } else {
        error_log("logActivity: conn or user_id not set");
    }
}

function getRecentAuditLogs($limit = 5) {
    global $conn;
    $sql = "SELECT a.action, u.username, a.created_at
            FROM audit_logs a
            LEFT JOIN users u ON a.user_id = u.user_id
            ORDER BY a.created_at DESC
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$limit]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("getRecentAuditLogs: fetched " . count($result) . " activities");
    return $result;
}

// Function to ensure all employees have leave balance records with gender-based restrictions
function ensureEmployeeLeaveBalances() {
    global $conn;
    try {
        // Get all employees with their gender information who don't have leave balance records for current year
        $sql = "SELECT ep.employee_id, pi.gender, lt.leave_type_id, lt.leave_type_name, lt.default_days
                FROM employee_profiles ep
                JOIN personal_information pi ON ep.personal_info_id = pi.personal_info_id
                CROSS JOIN leave_types lt
                LEFT JOIN leave_balances lb ON ep.employee_id = lb.employee_id 
                    AND lt.leave_type_id = lb.leave_type_id 
                    AND lb.year = YEAR(CURDATE())
                WHERE lb.balance_id IS NULL";
        
        $stmt = $conn->query($sql);
        $missingBalances = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $createdCount = 0;
        
        // Insert missing leave balance records with gender restrictions
        foreach ($missingBalances as $balance) {
            $employeeGender = $balance['gender'];
            $leaveTypeName = $balance['leave_type_name'];
            
            // Apply gender-based restrictions
            $shouldCreate = true;
            
            if ($leaveTypeName === 'Maternity Leave' && $employeeGender !== 'Female') {
                $shouldCreate = false;
                error_log("Skipping Maternity Leave for non-female employee: {$balance['employee_id']} (Gender: $employeeGender)");
            }
            
            if ($leaveTypeName === 'Paternity Leave' && $employeeGender !== 'Male') {
                $shouldCreate = false;
                error_log("Skipping Paternity Leave for non-male employee: {$balance['employee_id']} (Gender: $employeeGender)");
            }
            
            if ($shouldCreate) {
                $insertSql = "INSERT INTO leave_balances (employee_id, leave_type_id, year, total_leaves, leaves_taken, leaves_pending, leaves_remaining) 
                             VALUES (?, ?, ?, ?, 0, 0, ?)";
                $insertStmt = $conn->prepare($insertSql);
                $insertStmt->execute([
                    $balance['employee_id'],
                    $balance['leave_type_id'],
                    date('Y'),
                    $balance['default_days'],
                    $balance['default_days']
                ]);
                $createdCount++;
            }
        }
        
        return $createdCount;
    } catch (PDOException $e) {
        error_log("ensureEmployeeLeaveBalances error: " . $e->getMessage());
        return 0;
    }
}

// Function to clean up leave balance records that violate gender restrictions
function cleanupGenderViolations() {
    global $conn;
    try {
        // Remove maternity leave records for non-female employees
        $sql = "DELETE lb FROM leave_balances lb
                JOIN employee_profiles ep ON lb.employee_id = ep.employee_id
                JOIN personal_information pi ON ep.personal_info_id = pi.personal_info_id
                JOIN leave_types lt ON lb.leave_type_id = lt.leave_type_id
                WHERE lt.leave_type_name = 'Maternity Leave' AND pi.gender != 'Female'";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $maternityRemoved = $stmt->rowCount();
        
        // Remove paternity leave records for non-male employees
        $sql = "DELETE lb FROM leave_balances lb
                JOIN employee_profiles ep ON lb.employee_id = ep.employee_id
                JOIN personal_information pi ON ep.personal_info_id = pi.personal_info_id
                JOIN leave_types lt ON lb.leave_type_id = lt.leave_type_id
                WHERE lt.leave_type_name = 'Paternity Leave' AND pi.gender != 'Male'";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $paternityRemoved = $stmt->rowCount();
        
        $totalRemoved = $maternityRemoved + $paternityRemoved;
        
        if ($totalRemoved > 0) {
            error_log("Cleaned up $totalRemoved gender-violating leave balance records ($maternityRemoved maternity, $paternityRemoved paternity)");
        }
        
        return $totalRemoved;
    } catch (PDOException $e) {
        error_log("cleanupGenderViolations error: " . $e->getMessage());
        return 0;
    }
}

// Function to validate leave requests based on gender
function validateLeaveRequestByGender($employee_id, $leave_type_id) {
    global $conn;
    try {
        // Get employee gender and leave type name
        $sql = "SELECT pi.gender, lt.leave_type_name
                FROM employee_profiles ep
                JOIN personal_information pi ON ep.personal_info_id = pi.personal_info_id
                JOIN leave_types lt ON lt.leave_type_id = ?
                WHERE ep.employee_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$leave_type_id, $employee_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            return ['valid' => false, 'message' => 'Employee or leave type not found.'];
        }
        
        $employeeGender = $result['gender'];
        $leaveTypeName = $result['leave_type_name'];
        
        // Check gender restrictions
        if ($leaveTypeName === 'Maternity Leave' && $employeeGender !== 'Female') {
            return [
                'valid' => false, 
                'message' => 'Maternity leave is only available for female employees. Your gender is recorded as: ' . $employeeGender
            ];
        }
        
        if ($leaveTypeName === 'Paternity Leave' && $employeeGender !== 'Male') {
            return [
                'valid' => false, 
                'message' => 'Paternity leave is only available for male employees. Your gender is recorded as: ' . $employeeGender
            ];
        }
        
        return ['valid' => true, 'message' => 'Leave request is valid.'];
        
    } catch (PDOException $e) {
        error_log("validateLeaveRequestByGender error: " . $e->getMessage());
        return ['valid' => false, 'message' => 'Error validating leave request. Please contact administrator.'];
    }
}

// Function to get leave types appropriate for an employee's gender
function getLeaveTypesForEmployee($employee_id) {
    global $conn;
    try {
        // Get employee gender
        $sql = "SELECT pi.gender
                FROM employee_profiles ep
                JOIN personal_information pi ON ep.personal_info_id = pi.personal_info_id
                WHERE ep.employee_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$employee_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return [];
        }

        $employeeGender = $result['gender'];

        // Get all leave types with gender restrictions
        $sql = "SELECT * FROM leave_types ORDER BY leave_type_name";
        $stmt = $conn->query($sql);
        $allLeaveTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $appropriateLeaveTypes = [];

        foreach ($allLeaveTypes as $leaveType) {
            $leaveTypeName = $leaveType['leave_type_name'];
            $shouldInclude = true;

            // Apply gender restrictions
            if ($leaveTypeName === 'Maternity Leave' && $employeeGender !== 'Female') {
                $shouldInclude = false;
            }

            if ($leaveTypeName === 'Paternity Leave' && $employeeGender !== 'Male') {
                $shouldInclude = false;
            }

            if ($shouldInclude) {
                $appropriateLeaveTypes[] = $leaveType;
            }
        }

        return $appropriateLeaveTypes;

    } catch (PDOException $e) {
        error_log("getLeaveTypesForEmployee error: " . $e->getMessage());
        return [];
    }
}

// Function to get employee hire date
function getEmployeeHireDate($employeeId) {
    global $conn;
    try {
        $sql = "SELECT hire_date FROM employee_profiles WHERE employee_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$employeeId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['hire_date'] : null;
    } catch (PDOException $e) {
        return null;
    }
}
?>
