<?php
include 'db_connect.php';

$role_id = $_GET['role_id'] ?? '';

if ($role_id) {
    $stmt = $conn->prepare("SELECT c.competency_id, c.name, c.description, r.title AS role
                            FROM competencies c
                            LEFT JOIN job_roles r ON c.job_role_id = r.job_role_id
                            WHERE c.job_role_id = ?
                            ORDER BY c.created_at DESC");
    $stmt->bind_param("i", $role_id);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT c.competency_id, c.name, c.description, r.title AS role
                            FROM competencies c
                            LEFT JOIN job_roles r ON c.job_role_id = r.job_role_id
                            ORDER BY c.created_at DESC");
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
echo json_encode($data);
?>
