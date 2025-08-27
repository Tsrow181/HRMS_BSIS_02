<?php
header('Content-Type: application/json');
include __DIR__ . '/db_connect.php';

if (!isset($_GET['job_role_id'])) {
    echo json_encode(['error' => 'Job Role ID is required']);
    exit;
}

$job_role_id = intval($_GET['job_role_id']);

$sql = "SELECT competency_id, name 
        FROM competencies 
        WHERE job_role_id = ? 
        ORDER BY name";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $job_role_id);
$stmt->execute();
$result = $stmt->get_result();

$competencies = [];
while ($row = $result->fetch_assoc()) {
    $competencies[] = $row;
}

echo json_encode($competencies);

$stmt->close();
$conn->close();
