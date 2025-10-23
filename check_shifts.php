<?php
include 'dp.php';

$shifts = getEmployeeShifts();
$employeesWithoutShifts = 0;
$employeesWithShifts = 0;

foreach($shifts as $shift) {
    if(empty($shift['shift_name'])) {
        $employeesWithoutShifts++;
    } else {
        $employeesWithShifts++;
    }
}

echo "Total employees: " . count($shifts) . "\n";
echo "Employees with shifts: $employeesWithShifts\n";
echo "Employees without shifts: $employeesWithoutShifts\n";

// Show first few employees without shifts
echo "\nEmployees without shifts:\n";
$shown = 0;
foreach($shifts as $shift) {
    if(empty($shift['shift_name']) && $shown < 5) {
        echo "- " . $shift['first_name'] . " " . $shift['last_name'] . " (ID: " . $shift['employee_id'] . ")\n";
        $shown++;
    }
}
?>
