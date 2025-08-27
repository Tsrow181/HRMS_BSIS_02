<?php
header('Content-Type: application/json');
include 'db_connect.php';

try {
    // Validate required fields
    if (empty($_POST['competency_id']) || empty($_POST['competency_name']) || empty($_POST['job_role_id'])) {
        echo json_encode(["success" => false, "message" => "Missing required fields."]);
        exit;
    }

    $id   = (int) $_POST['competency_id'];
    $name = trim($_POST['competency_name']);
    $desc = isset($_POST['description']) ? trim($_POST['description']) : null;
    $role = (int) $_POST['job_role_id'];

    $stmt = $conn->prepare("
        UPDATE competencies 
        SET name = ?, description = ?, job_role_id = ?, updated_at = NOW() 
        WHERE competency_id = ?
    ");

    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("ssii", $name, $desc, $role, $id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => $stmt->error]);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
