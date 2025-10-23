<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once 'dp.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['employee_id'])) {
    $employeeId = $_GET['employee_id'];

    $hireDate = getEmployeeHireDate($employeeId);

    if ($hireDate) {
        echo json_encode(['hire_date' => $hireDate]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Employee not found or hire date not available']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
}
?>
