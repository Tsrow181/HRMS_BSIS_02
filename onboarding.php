<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'assign_tasks':
                $onboarding_id = $_POST['onboarding_id'];
                $selected_tasks = $_POST['tasks'] ?? [];
                
                foreach ($selected_tasks as $task_id) {
                    $due_date = date('Y-m-d', strtotime('+7 days'));
                    $stmt = $conn->prepare("INSERT INTO employee_onboarding_tasks (onboarding_id, task_id, due_date, status) VALUES (?, ?, ?, 'Not Started')");
                    $stmt->execute([$onboarding_id, $task_id, $due_date]);
                }
                break;
                
            case 'update_task_status':
                $employee_task_id = $_POST['employee_task_id'];
                $status = $_POST['status'];
                $completion_date = ($status === 'Completed') ? date('Y-m-d') : null;
                
                $stmt = $conn->prepare("UPDATE employee_onboarding_tasks SET status = ?, completion_date = ? WHERE employee_task_id = ?");
                $stmt->execute([$status, $completion_date, $employee_task_id]);
                break;
        }
        header('Location: onboarding_modern.php');
        exit;
    }
}

// Get onboarding records with employee details
try {
    $onboarding_query = "SELECT eo.*, 
                         CASE 
                            WHEN ep.employee_id IS NOT NULL THEN CONCAT('EMP-', ep.employee_number)
                            WHEN c.candidate_id IS NOT NULL THEN CONCAT(c.first_name, ' ', c.last_name)
                         END as person_name,
                         CASE 
                            WHEN ep.employee_id IS NOT NULL THEN ep.work_email
                            WHEN c.candidate_id IS NOT NULL THEN c.email
                         END as person_email,
                         CASE 
                            WHEN ep.employee_id IS NOT NULL THEN 'Employee Promotion'
                            WHEN c.candidate_id IS NOT NULL THEN 'New Hire'
                         END as onboarding_type
                         FROM employee_onboarding eo
                         LEFT JOIN employee_profiles ep ON eo.employee_id = ep.employee_id
                         LEFT JOIN candidates c ON eo.employee_id IN (
                             SELECT ep2.employee_id FROM employee_profiles ep2 
                             WHERE ep2.work_email IN (SELECT c2.email FROM candidates c2 WHERE c2.source = 'Hired')
                         )
                         ORDER BY eo.start_date DESC";
    
    $onboarding_records = $conn->query($onboarding_query)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $onboarding_records = [];
}

// Get available onboarding tasks
try {
    $tasks_query = "SELECT * FROM onboarding_tasks ORDER BY task_type, task_name";
    $available_tasks = $conn->query($tasks_query)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $available_tasks = [];
}

// Get statistics
$stats = ['pending' => 0, 'in_progress' => 0, 'completed' => 0, 'new_hires' => 0];
try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM employee_onboarding WHERE status = 'Pending'");
    $stats['pending'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    $stmt = $conn->query("SELECT COUNT(*) as count FROM employee_onboarding WHERE status = 'In Progress'");
    $stats['in_progress'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    $stmt = $conn->query("SELECT COUNT(*) as count FROM employee_onboarding WHERE status = 'Completed'");
    $stats['completed'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    $stmt = $conn->query("SELECT COUNT(*) as count FROM employee_onboarding WHERE start_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stats['new_hires'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
} catch (Exception $e) {
    // Keep default values
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Onboarding Management - HR Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css?v=rose">
    <style>
        .onboarding-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(233, 30, 99, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .onboarding-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(233, 30, 99, 0.15);
        }
        
        .onboarding-header {
            background: linear-gradient(135deg, #F8BBD0 0%, #FCE4EC 100%);
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .type-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .type-new-hire {
            background: #28a745;
            color: white;
        }
        
        .type-promotion {
            background: #007bff;
            color: white;
        }
        
        .status-pending { background: #ffc107; color: #000; }
        .status-in-progress { background: #17a2b8; color: white; }
        .status-completed { background: #28a745; color: white; }
        
        .progress-bar-custom {
            height: 8px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <?php include 'navigation.php'; ?>
        <div class="row">
            <?php include 'sidebar.php'; ?>
            <div class="main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-user-plus text-rose"></i> Onboarding Management</h2>
                </div>

                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <h3><?php echo $stats['pending']; ?></h3>
                                <p>Pending Onboarding</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h3><?php echo $stats['in_progress']; ?></h3>
                                <p>In Progress</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h3><?php echo $stats['completed']; ?></h3>
                                <p>Completed</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h3><?php echo $stats['new_hires']; ?></h3>
                                <p>Recent Hires</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Onboarding Records -->
                <div class="row">
                    <?php foreach ($onboarding_records as $record): ?>
                    <div class="col-md-6">
                        <div class="onboarding-card">
                            <div class="onboarding-header">
                                <div>
                                    <h5 class="mb-1"><?php echo htmlspecialchars($record['person_name']); ?></h5>
                                    <p class="mb-0 text-muted"><?php echo htmlspecialchars($record['person_email']); ?></p>
                                    <small class="text-muted">Start: <?php echo date('M d, Y', strtotime($record['start_date'])); ?></small>
                                </div>
                                <div class="text-right">
                                    <span class="type-badge <?php echo strpos($record['onboarding_type'], 'New') !== false ? 'type-new-hire' : 'type-promotion'; ?>">
                                        <?php echo $record['onboarding_type']; ?>
                                    </span>
                                    <br>
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $record['status'])); ?>">
                                        <?php echo $record['status']; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6">
                                        <strong>Expected Completion:</strong><br>
                                        <?php echo date('M d, Y', strtotime($record['expected_completion_date'])); ?>
                                    </div>
                                    <div class="col-6">
                                        <strong>Days Remaining:</strong><br>
                                        <?php 
                                        $days_remaining = ceil((strtotime($record['expected_completion_date']) - time()) / (60 * 60 * 24));
                                        echo $days_remaining > 0 ? $days_remaining . ' days' : 'Overdue';
                                        ?>
                                    </div>
                                </div>
                                
                                <?php if ($record['status'] === 'Pending'): ?>
                                <div class="mt-3">
                                    <button class="btn btn-rose btn-sm" data-toggle="modal" data-target="#assignTasksModal<?php echo $record['onboarding_id']; ?>">
                                        <i class="fas fa-tasks"></i> Assign Tasks
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Assign Tasks Modal -->
                    <div class="modal fade" id="assignTasksModal<?php echo $record['onboarding_id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header bg-rose text-white">
                                    <h5 class="modal-title">Assign Onboarding Tasks</h5>
                                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                </div>
                                <form method="POST">
                                    <div class="modal-body">
                                        <input type="hidden" name="action" value="assign_tasks">
                                        <input type="hidden" name="onboarding_id" value="<?php echo $record['onboarding_id']; ?>">
                                        
                                        <p><strong>Assigning tasks for:</strong> <?php echo htmlspecialchars($record['person_name']); ?></p>
                                        
                                        <div class="row">
                                            <?php foreach ($available_tasks as $task): ?>
                                            <div class="col-md-6 mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="tasks[]" value="<?php echo $task['task_id']; ?>" id="task<?php echo $task['task_id']; ?>">
                                                    <label class="form-check-label" for="task<?php echo $task['task_id']; ?>">
                                                        <strong><?php echo htmlspecialchars($task['task_name']); ?></strong>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($task['task_type']); ?></small>
                                                        <?php if ($task['description']): ?>
                                                        <br><small><?php echo htmlspecialchars($task['description']); ?></small>
                                                        <?php endif; ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-rose">Assign Tasks</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>