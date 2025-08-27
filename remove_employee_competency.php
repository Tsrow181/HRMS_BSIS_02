<?php
header('Content-Type: application/json');
require_once 'config.php'; // Contains $conn (PDO)

// Get POST parameters
$employee_id = isset($_POST['employee_id']) ? (int) $_POST['employee_id'] : 0;
$competency_id = isset($_POST['competency_id']) ? (int) $_POST['competency_id'] : 0;

if ($employee_id <= 0 || $competency_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid employee or competency ID.']);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM employee_competencies WHERE employee_id = :employee_id AND competency_id = :competency_id");
    $stmt->execute([
        ':employee_id' => $employee_id,
        ':competency_id' => $competency_id
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No matching record found.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
