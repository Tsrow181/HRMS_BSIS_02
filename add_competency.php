<?php
include 'db_connect.php';
header('Content-Type: application/json');

$name = $_POST['competency_name'] ?? '';
$description = $_POST['description'] ?? '';
$job_role_id = $_POST['job_role_id'] ?? '';

try {
    $stmt = $conn->prepare("INSERT INTO competencies (name, description, job_role_id) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $name, $description, $job_role_id);
    $stmt->execute();

    echo json_encode(["success" => true]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
