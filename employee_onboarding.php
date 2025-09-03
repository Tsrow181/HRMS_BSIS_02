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
            case 'update_task_status':
                $employee_task_id = $_POST['employee_task_id'];
                $status = $_POST['status'];
                $notes = $_POST['notes'] ?? '';
                $completion_date = ($status === 'Completed') ? date('Y-m-d') : null;
                
                $stmt = $conn->prepare("UPDATE employee_onboarding_tasks SET status = ?, completion_date = ?, notes = ? WHERE employee_task_id = ?");
                $stmt->execute([$status, $completion_date, $notes, $employee_task_id]);
                break;
                
            case 'update_onboarding_status':
                $onboarding_id = $_POST['onboarding_id'];
                $status = $_POST['status'];
                
                $stmt = $conn->prepare("UPDATE employee_onboarding SET status = ? WHERE onboarding_id = ?");
                $stmt->execute([$status, $onboarding_id]);
                break;
        }
        header('Location: employee_onboarding_modern.php');
        exit;
    }
}

// Get employee onboarding with tasks
try {
    $onboarding_query = "SELECT eo.*, 
                         CONCAT('EMP-', ep.employee_number) as employee_name,
                         ep.work_email,
                         COUNT(eot.employee_task_id) as total_tasks,
                         SUM(CASE WHEN eot.status = 'Completed' THEN 1 ELSE 0 END) as completed_tasks
                         FROM employee_onboarding eo
                         LEFT JOIN employee_profiles ep ON eo.employee_id = ep.employee_id
                         LEFT JOIN employee_onboarding_tasks eot ON eo.onboarding_id = eot.onboarding_id
                         GROUP BY eo.onboarding_id
                         ORDER BY eo.start_date DESC";
    
    $onboarding_records = $conn->query($onboarding_query)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $onboarding_records = [];
}

// Get detailed tasks for each onboarding
$onboarding_tasks = [];
foreach ($onboarding_records as $record) {
    try {
        $tasks_query = "SELECT eot.*, ot.task_name, ot.task_type, ot.description
                        FROM employee_onboarding_tasks eot
                        JOIN onboarding_tasks ot ON eot.task_id = ot.task_id
                        WHERE eot.onboarding_id = ?
                        ORDER BY ot.task_type, ot.task_name";
        $stmt = $conn->prepare($tasks_query);
        $stmt->execute([$record['onboarding_id']]);
        $onboarding_tasks[$record['onboarding_id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $onboarding_tasks[$record['onboarding_id']] = [];
    }
}

// Get statistics
$stats = ['active' => 0, 'completed_tasks' => 0, 'overdue_tasks' => 0, 'completion_rate' => 0];
try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM employee_onboarding WHERE status IN ('Pending', 'In Progress')");
    $stats['active'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    $stmt = $conn->query("SELECT COUNT(*) as count FROM employee_onboarding_tasks WHERE status = 'Completed'");
    $stats['completed_tasks'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    $stmt = $conn->query("SELECT COUNT(*) as count FROM employee_onboarding_tasks WHERE due_date < CURDATE() AND status != 'Completed'");
    $stats['overdue_tasks'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $total_tasks = $conn->query("SELECT COUNT(*) as count FROM employee_onboarding_tasks")->fetch(PDO::FETCH_ASSOC)['count'];
    $stats['completion_rate'] = $total_tasks > 0 ? round(($stats['completed_tasks'] / $total_tasks) * 100) : 0;
} catch (Exception $e) {
    // Keep default values
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Onboarding - HR Management System</title>
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
        }
        
        .task-item {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }
        
        .task-item:hover {
            box-shadow: 0 2px 8px rgba(233, 30, 99, 0.1);
        }
        
        .task-completed {
            background-color: #f8f9fa;
            border-color: #28a745;
        }
        
        .task-overdue {
            border-color: #dc3545;
            background-color: #fff5f5;
        }
        
        .progress-custom {
            height: 20px;
            border-radius: 10px;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
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
                    <h2><i class="fas fa-tasks text-rose"></i> Employee Onboarding Progress</h2>
                </div>

                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h3><?php echo $stats['active']; ?></h3>
                                <p>Active Onboarding</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h3><?php echo $stats['completed_tasks']; ?></h3>
                                <p>Completed Tasks</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body text-center">
                                <h3><?php echo $stats['overdue_tasks']; ?></h3>
                                <p>Overdue Tasks</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h3><?php echo $stats['completion_rate']; ?>%</h3>
                                <p>Completion Rate</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Onboarding Records -->
                <?php foreach ($onboarding_records as $record): ?>
                <div class="onboarding-card">
                    <div class="card-header bg-rose text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0"><?php echo htmlspecialchars($record['employee_name']); ?></h5>
                                <small><?php echo htmlspecialchars($record['work_email']); ?></small>
                            </div>
                            <div class="text-right">
                                <span class="status-badge bg-light text-dark">
                                    <?php echo $record['status']; ?>
                                </span>
                                <br>
                                <small>
                                    <?php 
                                    $progress = $record['total_tasks'] > 0 ? round(($record['completed_tasks'] / $record['total_tasks']) * 100) : 0;
                                    echo $record['completed_tasks'] . '/' . $record['total_tasks'] . ' tasks (' . $progress . '%)';
                                    ?>
                                </small>
                            </div>
                        </div>
                        <div class="mt-2">
                            <div class="progress progress-custom">
                                <div class="progress-bar bg-light" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong>Start Date:</strong><br>
                                <?php echo date('M d, Y', strtotime($record['start_date'])); ?>
                            </div>
                            <div class="col-md-4">
                                <strong>Expected Completion:</strong><br>
                                <?php echo date('M d, Y', strtotime($record['expected_completion_date'])); ?>
                            </div>
                            <div class="col-md-4">
                                <strong>Status:</strong><br>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="update_onboarding_status">
                                    <input type="hidden" name="onboarding_id" value="<?php echo $record['onboarding_id']; ?>">
                                    <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                                        <option value="Pending" <?php echo $record['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="In Progress" <?php echo $record['status'] === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="Completed" <?php echo $record['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                    </select>
                                </form>
                            </div>
                        </div>

                        <!-- Tasks -->
                        <h6><i class="fas fa-list"></i> Onboarding Tasks</h6>
                        <?php if (isset($onboarding_tasks[$record['onboarding_id']]) && !empty($onboarding_tasks[$record['onboarding_id']])): ?>
                            <?php foreach ($onboarding_tasks[$record['onboarding_id']] as $task): ?>
                            <div class="task-item <?php echo $task['status'] === 'Completed' ? 'task-completed' : (strtotime($task['due_date']) < time() && $task['status'] !== 'Completed' ? 'task-overdue' : ''); ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            <?php if ($task['status'] === 'Completed'): ?>
                                                <i class="fas fa-check-circle text-success"></i>
                                            <?php elseif (strtotime($task['due_date']) < time()): ?>
                                                <i class="fas fa-exclamation-triangle text-danger"></i>
                                            <?php else: ?>
                                                <i class="fas fa-clock text-warning"></i>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($task['task_name']); ?>
                                        </h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($task['task_type']); ?></small>
                                        <?php if ($task['description']): ?>
                                        <p class="mb-1 small"><?php echo htmlspecialchars($task['description']); ?></p>
                                        <?php endif; ?>
                                        <small class="text-muted">Due: <?php echo date('M d, Y', strtotime($task['due_date'])); ?></small>
                                        <?php if ($task['notes']): ?>
                                        <p class="mb-0 small text-info"><strong>Notes:</strong> <?php echo htmlspecialchars($task['notes']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ml-3">
                                        <button class="btn btn-sm btn-outline-rose" data-toggle="modal" data-target="#taskModal<?php echo $task['employee_task_id']; ?>">
                                            Update
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Task Update Modal -->
                            <div class="modal fade" id="taskModal<?php echo $task['employee_task_id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-rose text-white">
                                            <h5 class="modal-title">Update Task</h5>
                                            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="action" value="update_task_status">
                                                <input type="hidden" name="employee_task_id" value="<?php echo $task['employee_task_id']; ?>">
                                                
                                                <h6><?php echo htmlspecialchars($task['task_name']); ?></h6>
                                                <p class="text-muted"><?php echo htmlspecialchars($task['description']); ?></p>
                                                
                                                <div class="form-group">
                                                    <label>Status</label>
                                                    <select name="status" class="form-control" required>
                                                        <option value="Not Started" <?php echo $task['status'] === 'Not Started' ? 'selected' : ''; ?>>Not Started</option>
                                                        <option value="In Progress" <?php echo $task['status'] === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                                        <option value="Completed" <?php echo $task['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label>Notes</label>
                                                    <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($task['notes']); ?></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-rose">Update Task</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">No tasks assigned yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>