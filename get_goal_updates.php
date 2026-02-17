<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and has HR role
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check user role - only HR and admin can access
$user_role = $_SESSION['role'] ?? '';
if (!in_array($user_role, ['admin', 'hr'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

// Include database connection
require_once 'dp.php';

// Database connection
$host = 'localhost';
$dbname = 'hr_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$goal_id = $_GET['goal_id'] ?? null;

if (!$goal_id || !is_numeric($goal_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid goal ID']);
    exit;
}

// Get goal details
$stmt = $pdo->prepare("
    SELECT g.*, CONCAT(pi.first_name, ' ', pi.last_name) as employee_name
    FROM goals g
    LEFT JOIN employee_profiles ep ON g.employee_id = ep.employee_id
    LEFT JOIN personal_information pi ON ep.personal_info_id = pi.personal_info_id
    WHERE g.goal_id = ?
");
$stmt->execute([$goal_id]);
$goal = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$goal) {
    http_response_code(404);
    echo json_encode(['error' => 'Goal not found']);
    exit;
}

// Get goal updates
$stmt = $pdo->prepare("
    SELECT gu.*, u.username as updated_by_name
    FROM goal_updates gu
    LEFT JOIN users u ON gu.updated_by = u.user_id
    WHERE gu.goal_id = ?
    ORDER BY gu.update_date DESC, gu.created_at DESC
");
$stmt->execute([$goal_id]);
$updates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Return HTML content for the modal
?>
<div class="goal-details mb-4">
    <h5 class="mb-3"><?= htmlspecialchars($goal['title']) ?></h5>
    <div class="row">
        <div class="col-md-6">
            <p><strong>Employee:</strong> <?= htmlspecialchars($goal['employee_name']) ?></p>
            <p><strong>Status:</strong>
                <span class="badge badge-<?= strtolower(str_replace(' ', '-', $goal['status'])) === 'completed' ? 'success' : (strtolower(str_replace(' ', '-', $goal['status'])) === 'in-progress' ? 'primary' : 'secondary') ?>">
                    <?= htmlspecialchars($goal['status']) ?>
                </span>
            </p>
        </div>
        <div class="col-md-6">
            <p><strong>Progress:</strong> <?= $goal['progress'] ?>%</p>
            <p><strong>Period:</strong> <?= date('M d, Y', strtotime($goal['start_date'])) ?> - <?= date('M d, Y', strtotime($goal['end_date'])) ?></p>
        </div>
    </div>
    <p><strong>Description:</strong> <?= htmlspecialchars($goal['description']) ?></p>
</div>

<?php if (!empty($updates)): ?>
<div class="timeline">
    <?php foreach ($updates as $update): ?>
    <div class="timeline-item">
        <div class="bg-light p-3 rounded">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h6 class="mb-1">Progress Update: <?= $update['progress'] ?>%</h6>
                    <?php if ($update['comments']): ?>
                    <p class="mb-1 text-muted small">
                        <?= htmlspecialchars($update['comments']) ?>
                    </p>
                    <?php endif; ?>
                </div>
                <small class="text-muted">
                    <?= date('M d, Y', strtotime($update['update_date'])) ?>
                    <?php if ($update['updated_by_name']): ?>
                        <br>by <?= htmlspecialchars($update['updated_by_name']) ?>
                    <?php endif; ?>
                </small>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="text-center py-4">
    <i class="fas fa-history fa-3x text-muted mb-3"></i>
    <p class="text-muted">No updates recorded for this goal yet.</p>
</div>
<?php endif; ?>
