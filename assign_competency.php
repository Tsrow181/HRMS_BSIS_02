<?php
// assign_competency.php
header('Content-Type: application/json');
require_once 'config.php'; // uses $conn (PDO)

try {
    $employee_id    = filter_input(INPUT_POST, 'employee_id', FILTER_VALIDATE_INT);
    $competency_id  = filter_input(INPUT_POST, 'competency_id', FILTER_VALIDATE_INT);
    $rating         = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
    // Map "notes" -> "comments"
    $comments       = trim($_POST['notes'] ?? $_POST['comments'] ?? '');
    // Use today if no date sent
    $assessment_date = $_POST['assessment_date'] ?? date('Y-m-d');

    if (!$employee_id || !$competency_id || !$rating) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
        exit;
    }
    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Rating must be 1–5.']);
        exit;
    }

    // Insert, or update if same (employee_id, competency_id, assessment_date) already exists
    $sql = "INSERT INTO employee_competencies
            (employee_id, competency_id, rating, assessment_date, comments)
            VALUES (:employee_id, :competency_id, :rating, :assessment_date, :comments)
            ON DUPLICATE KEY UPDATE
                rating = VALUES(rating),
                comments = VALUES(comments),
                updated_at = CURRENT_TIMESTAMP";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':employee_id'     => $employee_id,
        ':competency_id'   => $competency_id,
        ':rating'          => $rating,
        ':assessment_date' => $assessment_date,
        ':comments'        => $comments
    ]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    $msg = $e->getCode() === '23000'
        ? 'Invalid employee/competency or duplicate key.'
        : 'Database error.';
    echo json_encode(['success' => false, 'message' => $msg]);
}
