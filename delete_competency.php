<?php
include 'db_connect.php';
$id = $_POST['id'];

$stmt = $conn->prepare("DELETE FROM competencies WHERE competency_id=?");
$stmt->bind_param("i", $id);

echo json_encode(["success" => $stmt->execute()]);
