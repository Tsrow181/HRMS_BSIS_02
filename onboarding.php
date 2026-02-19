<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
require_once 'db_connect.php';

$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'complete_task') {
        $stmt = $conn->prepare("UPDATE employee_onboarding_tasks SET status = 'Completed', completion_date = CURDATE() WHERE employee_task_id = ?");
        $stmt->bind_param('i', $_POST['progress_id']);
        $stmt->execute();
        
        $remaining = $conn->prepare("SELECT COUNT(*) as count FROM employee_onboarding_tasks WHERE onboarding_id = ? AND status != 'Completed'");
        $remaining->bind_param('i', $_POST['onboarding_id']);
        $remaining->execute();
        $result = $remaining->get_result();
        
        if ($result->fetch_assoc()['count'] == 0) {
            $stmt1 = $conn->prepare("UPDATE employee_onboarding SET status = 'Completed' WHERE onboarding_id = ?");
            $stmt1->bind_param('i', $_POST['onboarding_id']);
            $stmt1->execute();
            $stmt2 = $conn->prepare("UPDATE job_applications ja JOIN employee_onboarding eo ON ja.application_id = eo.employee_id SET ja.status = 'Offer' WHERE eo.onboarding_id = ?");
            $stmt2->bind_param('i', $_POST['onboarding_id']);
            $stmt2->execute();
            $success_message = "🎉 All tasks completed! Candidate moved to Offer status.";
        } else {
            $success_message = "✅ Task completed!";
        }
    } elseif ($_POST['action'] === 'fail_task') {
        $stmt = $conn->prepare("UPDATE employee_onboarding_tasks SET status = 'Cancelled', completion_date = CURDATE(), notes = ? WHERE employee_task_id = ?");
        $notes = $_POST['notes'] ?? 'Task failed';
        $stmt->bind_param('si', $notes, $_POST['progress_id']);
        $stmt->execute();
        $success_message = "❌ Task marked as failed.";
    } elseif ($_POST['action'] === 'assign_tasks') {
        $onboarding_id = $_POST['onboarding_id'];
        $application_id = $_POST['application_id'];
        
        // Get department for this application
        $dept_query = $conn->prepare("SELECT jo.department_id FROM job_applications ja 
                                     JOIN job_openings jo ON ja.job_opening_id = jo.job_opening_id 
                                     WHERE ja.application_id = ?");
        $dept_query->bind_param('i', $application_id);
        $dept_query->execute();
        $dept_result = $dept_query->get_result();
        $dept_row = $dept_result->fetch_assoc();
        $dept_id = $dept_row['department_id'];
        
        // Get tasks for this department only
        $tasks_query = $conn->prepare("SELECT task_id FROM onboarding_tasks WHERE department_id = ? OR department_id IS NULL");
        $tasks_query->bind_param('i', $dept_id);
        $tasks_query->execute();
        $tasks_result = $tasks_query->get_result();
        $tasks = [];
        while ($row = $tasks_result->fetch_assoc()) {
            $tasks[] = $row['task_id'];
        }
        
        $assigned_count = 0;
        foreach ($tasks as $task_id) {
            $check = $conn->prepare("SELECT COUNT(*) as count FROM employee_onboarding_tasks WHERE onboarding_id = ? AND task_id = ?");
            $check->bind_param('ii', $onboarding_id, $task_id);
            $check->execute();
            $check_result = $check->get_result();
            
            if ($check_result->fetch_assoc()['count'] == 0) {
                $due_date = date('Y-m-d', strtotime('+7 days'));
                $insert = $conn->prepare("INSERT INTO employee_onboarding_tasks (onboarding_id, task_id, due_date, status) VALUES (?, ?, ?, 'Not Started')");
                $status = 'Not Started';
                $insert->bind_param('iiss', $onboarding_id, $task_id, $due_date, $status);
                $insert->execute();
                $assigned_count++;
            }
        }
        
        $success_message = "✅ Assigned {$assigned_count} tasks to candidate.";
    }
}

$result = $conn->query("SELECT c.*, ja.application_id, jo.title as job_title, d.department_name,
                                      eo.onboarding_id, eo.start_date, eo.status as onboarding_status
                                      FROM candidates c 
                                      JOIN job_applications ja ON c.candidate_id = ja.candidate_id
                                      JOIN job_openings jo ON ja.job_opening_id = jo.job_opening_id
                                      JOIN departments d ON jo.department_id = d.department_id
                                      -- Match employee_profiles by work email (employee_profiles doesn't store candidate_id)
                                      LEFT JOIN employee_profiles ep ON ep.work_email = c.email
                                      LEFT JOIN employee_onboarding eo ON eo.employee_id = ep.employee_id
                                      WHERE ja.status IN ('Reference Check', 'Offer', 'Hired')
                                      ORDER BY ja.application_date DESC");
$onboarding_candidates = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Onboarding - HR Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container-fluid">
        <?php include 'navigation.php'; ?>
        <div class="row">
            <?php include 'sidebar.php'; ?>
            <div class="main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="section-title">🎯 Applicant Onboarding</h2>
                    <a href="onboarding_tasks.php" class="btn btn-primary">
                        <i class="fas fa-tasks"></i> Manage Tasks
                    </a>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= $success_message ?>
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($onboarding_candidates)): ?>
                    <?php foreach ($onboarding_candidates as $candidate): ?>
                        <?php
                        $tasks = $conn->prepare("SELECT eot.*, ot.task_name, ot.description
                                                FROM employee_onboarding_tasks eot
                                                JOIN onboarding_tasks ot ON eot.task_id = ot.task_id
                                                WHERE eot.onboarding_id = ?
                                                ORDER BY ot.task_name");
                        $tasks->bind_param('i', $candidate['onboarding_id']);
                        $tasks->execute();
                        $task_result = $tasks->get_result();
                        $task_list = $task_result->fetch_all(MYSQLI_ASSOC);
                        
                        // Debug: Get candidate's department
                        $debug_dept = $conn->prepare("SELECT jo.department_id, d.department_name FROM job_applications ja 
                                                     JOIN job_openings jo ON ja.job_opening_id = jo.job_opening_id
                                                     JOIN departments d ON jo.department_id = d.department_id
                                                     WHERE ja.application_id = ?");
                        $debug_dept->bind_param('i', $candidate['application_id']);
                        $debug_dept->execute();
                        $debug_result = $debug_dept->get_result();
                        $candidate_dept = $debug_result->fetch_assoc();
                        
                        // Debug: Get all tasks in system
                        $all_tasks_result = $conn->query("SELECT task_id, task_name, department_id FROM onboarding_tasks ORDER BY task_name");
                        $all_tasks_debug = $all_tasks_result->fetch_all(MYSQLI_ASSOC);
                        
                        // Get department-specific tasks
                        $dept_tasks_query = $conn->prepare("SELECT ot.* FROM onboarding_tasks ot 
                                                           WHERE ot.department_id = ? OR ot.department_id IS NULL
                                                           ORDER BY ot.task_name");
                        $dept_tasks_query->bind_param('i', $candidate_dept['department_id']);
                        $dept_tasks_query->execute();
                        $dept_tasks_result = $dept_tasks_query->get_result();
                        $dept_tasks = $dept_tasks_result->fetch_all(MYSQLI_ASSOC);
                        ?>
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <?= htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']) ?>
                                    <small class="ml-2"><?= htmlspecialchars($candidate['job_title']) ?> - <?= htmlspecialchars($candidate['department_name']) ?></small>
                                </h5>
                                <small>Started: <?= $candidate['start_date'] ? date('M j, Y', strtotime($candidate['start_date'])) : 'Not started' ?></small>
                            </div>
                            <div class="card-body">
                                <?php if (empty($task_list)): ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i> No tasks assigned yet.
                                        <br><small><strong>Debug Info:</strong></small>
                                        <br><small>Candidate Dept ID: <?= $candidate_dept['department_id'] ?? 'Unknown' ?></small>
                                        <br><small>Candidate Dept Name: <?= $candidate_dept['department_name'] ?? 'Unknown' ?></small>
                                        <br><small>Available dept tasks: <?= count($dept_tasks) ?></small>
                                        <br><small>Total tasks in system: <?= count($all_tasks_debug) ?></small>
                                        <?php if (!empty($all_tasks_debug)): ?>
                                            <br><small>All tasks: 
                                            <?php foreach($all_tasks_debug as $t): ?>
                                                <?= $t['task_name'] ?> (Dept: <?= $t['department_id'] ?? 'NULL' ?>), 
                                            <?php endforeach; ?>
                                            </small>
                                        <?php endif; ?>
                                        <?php if (!empty($dept_tasks)): ?>
                                            <br><small>Matching tasks: <?= implode(', ', array_column($dept_tasks, 'task_name')) ?></small>
                                            <form method="POST" class="mt-2">
                                                <input type="hidden" name="action" value="assign_tasks">
                                                <input type="hidden" name="onboarding_id" value="<?= $candidate['onboarding_id'] ?>">
                                                <input type="hidden" name="application_id" value="<?= $candidate['application_id'] ?>">
                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-plus"></i> Assign Department Tasks (<?= count($dept_tasks) ?>)
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <div class="mt-2">
                                            <button type="button" class="btn btn-outline-primary btn-sm open-tasks" data-onboarding-id="<?= htmlspecialchars($candidate['onboarding_id']) ?>">
                                                <i class="fas fa-tasks"></i> View Tasks
                                            </button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="row">
                                        <?php foreach ($task_list as $task): ?>
                                            <div class="col-md-6 mb-3">
                                                <div class="card border-<?= $task['status'] === 'Completed' ? 'success' : ($task['status'] === 'Failed' ? 'danger' : 'warning') ?>">
                                                    <div class="card-body p-3">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <h6 class="mb-1"><?= htmlspecialchars($task['task_name']) ?></h6>
                                                            <span class="badge badge-<?= $task['status'] === 'Completed' ? 'success' : ($task['status'] === 'Failed' ? 'danger' : 'warning') ?>">
                                                                <?= $task['status'] ?>
                                                            </span>
                                                        </div>
                                                        <p class="text-muted small mb-2"><?= htmlspecialchars($task['description']) ?></p>
                                                        <?php if ($task['status'] === 'Not Started' || $task['status'] === 'In Progress'): ?>
                                                            <div class="btn-group btn-group-sm">
                                                                <form method="POST" class="d-inline">
                                                                    <input type="hidden" name="progress_id" value="<?= $task['employee_task_id'] ?>">
                                                                    <input type="hidden" name="onboarding_id" value="<?= $candidate['onboarding_id'] ?>">
                                                                    <button type="submit" name="action" value="complete_task" class="btn btn-success btn-sm">
                                                                        <i class="fas fa-check"></i> Complete
                                                                    </button>
                                                                </form>
                                                                <button type="button" class="btn btn-danger btn-sm ml-1" onclick="failTask(<?= $task['employee_task_id'] ?>, <?= $candidate['onboarding_id'] ?>)">
                                                                    <i class="fas fa-times"></i> Fail
                                                                </button>
                                                            </div>
                                                        <?php elseif ($task['completion_date']): ?>
                                                            <small class="text-muted">Date: <?= date('M j, Y', strtotime($task['completion_date'])) ?></small>
                                                        <?php endif; ?>
                                                        <?php if ($task['notes']): ?>
                                                            <div class="mt-2">
                                                                <small class="text-muted"><strong>Notes:</strong> <?= htmlspecialchars($task['notes']) ?></small>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No candidates in onboarding</h4>
                        <p class="text-muted">Candidates will appear here when moved to onboarding status.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Fail Task Modal -->
    <div class="modal fade" id="failTaskModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Fail Task</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="progress_id" id="failProgressId">
                        <input type="hidden" name="onboarding_id" id="failOnboardingId">
                        <div class="form-group">
                            <label>Reason for failure:</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Enter reason..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="action" value="fail_task" class="btn btn-danger">Mark as Failed</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Tasks Modal -->
    <div class="modal fade" id="tasksModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-tasks"></i> Onboarding Tasks</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body" id="tasksModalBody">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="mt-2">Loading tasks...</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script>
        function failTask(progressId, onboardingId) {
            $('#failProgressId').val(progressId);
            $('#failOnboardingId').val(onboardingId);
            $('#failTaskModal').modal('show');
        }
        
        // Load tasks into modal when View Tasks clicked
        $(document).on('click', '.open-tasks', function (e) {
            e.preventDefault();
            var onboardingId = $(this).data('onboarding-id');
            $('#tasksModalBody').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><div class="mt-2">Loading tasks...</div></div>');
            $('#tasksModal').modal('show');
            $.get('tasks_fragment.php', { onboarding_id: onboardingId }, function (html) {
                $('#tasksModalBody').html(html);
            }).fail(function () {
                $('#tasksModalBody').html('<div class="alert alert-danger">Failed to load tasks.</div>');
            });
        });
    </script>
</body>
</html>