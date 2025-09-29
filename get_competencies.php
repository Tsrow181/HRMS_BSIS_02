<?php
header('Content-Type: application/json');
require_once 'config.php'; // provides $conn (PDO)

try {
    $job_role_id = isset($_GET['job_role_id']) ? (int)$_GET['job_role_id'] : 0;

    if ($job_role_id > 0) {
        // Fetch competencies for this job role or global ones
        $sql = "
            SELECT competency_id, name, description
            FROM competencies
            WHERE job_role_id = :job_role_id OR job_role_id IS NULL
            ORDER BY name
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':job_role_id' => $job_role_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($rows);
    } else {
        // Fetch all competencies if no job role is specified
        $sql = "SELECT competency_id, name, description FROM competencies ORDER BY name";
        $stmt = $conn->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($rows);
    }

} catch (PDOException $e) {
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}
