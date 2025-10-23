<?php
include 'dp.php';

echo "Assigning default shifts to employees without shifts...\n";

// Get all employees without shifts
$allShifts = getEmployeeShifts();
$employeesWithoutShifts = array_filter($allShifts, function($shift) {
    return empty($shift['shift_name']);
});

// Get available shifts
$availableShifts = getShifts();

if (empty($availableShifts)) {
    echo "No shifts available to assign!\n";
    exit;
}

// Use the first available shift as default
$defaultShiftId = $availableShifts[0]['shift_id'];
$defaultShiftName = $availableShifts[0]['shift_name'];

echo "Using default shift: $defaultShiftName (ID: $defaultShiftId)\n";
echo "Employees to assign: " . count($employeesWithoutShifts) . "\n\n";

$assignedCount = 0;
foreach ($employeesWithoutShifts as $employee) {
    $employeeId = $employee['employee_id'];
    $hireDate = getEmployeeHireDate($employeeId);

    if ($hireDate) {
        $assignedDate = $hireDate;
    } else {
        $assignedDate = date('Y-m-d'); // Use today if no hire date
    }

    $isOvertime = 0; // Default to not overtime

    if (addEmployeeShift($employeeId, $defaultShiftId, $assignedDate, $isOvertime)) {
        echo "✓ Assigned shift to " . $employee['first_name'] . " " . $employee['last_name'] . " (ID: $employeeId) on $assignedDate\n";
        $assignedCount++;
    } else {
        echo "✗ Failed to assign shift to " . $employee['first_name'] . " " . $employee['last_name'] . " (ID: $employeeId)\n";
    }
}

echo "\nAssignment complete! $assignedCount employees assigned default shifts.\n";
?>
