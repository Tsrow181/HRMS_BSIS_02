<?php
include 'db_connect.php';
header('Content-Type: application/json');

$result = $conn->query("SELECT job_role_id, title FROM job_roles ORDER BY title ASC");

$roles = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $roles[] = $row;
    }
}

echo json_encode($roles);
?>