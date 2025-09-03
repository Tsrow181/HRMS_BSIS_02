<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
require_once 'config.php';
require_once 'email_config.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_status':
                $application_id = $_POST['application_id'];
                $new_status = $_POST['new_status'];
                $stmt = $conn->prepare("UPDATE job_applications SET status = ? WHERE application_id = ?");
                $stmt->execute([$new_status, $application_id]);
                
                // Get applicant email for notification
                $email_stmt = $conn->prepare("SELECT c.email, c.first_name, c.last_name, jo.title FROM job_applications ja JOIN candidates c ON ja.candidate_id = c.candidate_id JOIN job_openings jo ON ja.job_opening_id = jo.job_opening_id WHERE ja.application_id = ?");
                $email_stmt->execute([$application_id]);
                $applicant = $email_stmt->fetch(PDO::FETCH_ASSOC);
                
                // Send AI-generated email notification
                sendEmail($applicant['email'], '', '', $applicant['first_name'] . ' ' . $applicant['last_name'], $applicant['title'], $new_status);
                
                // Auto-schedule interview when approved
                if ($new_status == 'Approved') {
                    // Update status to Interview since we're scheduling
                    $stmt = $conn->prepare("UPDATE job_applications SET status = 'Interview' WHERE application_id = ?");
                    $stmt->execute([$application_id]);
                    // Get application details
                    $app_stmt = $conn->prepare("SELECT ja.*, jo.job_opening_id FROM job_applications ja JOIN job_openings jo ON ja.job_opening_id = jo.job_opening_id WHERE ja.application_id = ?");
                    $app_stmt->execute([$application_id]);
                    $app_data = $app_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Get or create interview stage
                    $stage_stmt = $conn->prepare("SELECT stage_id FROM interview_stages WHERE job_opening_id = ? ORDER BY stage_order LIMIT 1");
                    $stage_stmt->execute([$app_data['job_opening_id']]);
                    $stage = $stage_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$stage) {
                        // Create default interview stage
                        $create_stage = $conn->prepare("INSERT INTO interview_stages (job_opening_id, stage_name, stage_order, description, is_mandatory) VALUES (?, 'Initial Interview', 1, 'First round screening interview', 1)");
                        $create_stage->execute([$app_data['job_opening_id']]);
                        $stage_id = $conn->lastInsertId();
                    } else {
                        $stage_id = $stage['stage_id'];
                    }
                    
                    // AI-suggested optimal interview time (next business day, 10 AM)
                    $tomorrow = new DateTime('tomorrow');
                    while ($tomorrow->format('N') >= 6) { // Skip weekends
                        $tomorrow->add(new DateInterval('P1D'));
                    }
                    $suggested_time = $tomorrow->format('Y-m-d') . ' 10:00:00';
                    
                    // Check for conflicts and adjust time
                    $conflict_check = $conn->prepare("SELECT COUNT(*) as count FROM interviews WHERE DATE(schedule_date) = ? AND HOUR(schedule_date) = 10");
                    $conflict_check->execute([$tomorrow->format('Y-m-d')]);
                    $conflicts = $conflict_check->fetch(PDO::FETCH_ASSOC)['count'];
                    
                    // Adjust time if conflicts (add hours: 10 AM, 11 AM, 2 PM, 3 PM)
                    $time_slots = ['10:00:00', '11:00:00', '14:00:00', '15:00:00'];
                    $selected_time = $time_slots[$conflicts % 4];
                    $final_schedule = $tomorrow->format('Y-m-d') . ' ' . $selected_time;
                    
                    // Create automated interview
                    $interview_stmt = $conn->prepare("INSERT INTO interviews (application_id, stage_id, schedule_date, duration, location, interview_type, status) VALUES (?, ?, ?, 60, 'HR Office - Conference Room', 'In-person', 'Scheduled')");
                    $interview_stmt->execute([$application_id, $stage_id, $final_schedule]);
                    
                    // Send AI-generated interview notification
                    sendEmail($applicant['email'], '', '', $applicant['first_name'] . ' ' . $applicant['last_name'], $applicant['title'], 'Interview');
                    
                    $success_message = "Application approved! Interview scheduled for " . date('M d, Y \a\t g:i A', strtotime($final_schedule)) . ". AI-generated email sent.";
                } else {
                    $success_message = "Application status updated successfully! AI-generated email sent.";
                }
                break;
                
            case 'hire_candidate':
                $application_id = $_POST['application_id'];
                $candidate_id = $_POST['candidate_id'];
                
                // Update application status to hired
                $stmt = $conn->prepare("UPDATE job_applications SET status = 'Hired' WHERE application_id = ?");
                $stmt->execute([$application_id]);
                
                // Update candidate source to Hired
                $stmt = $conn->prepare("UPDATE candidates SET source = 'Hired' WHERE candidate_id = ?");
                $stmt->execute([$candidate_id]);
                
                // Update job offer status to Accepted
                $stmt = $conn->prepare("UPDATE job_offers SET status = 'Accepted', response_date = NOW() WHERE candidate_id = ? AND status = 'Pending'");
                $stmt->execute([$candidate_id]);
                
                // Create onboarding record
                $stmt = $conn->prepare("INSERT INTO employee_onboarding (employee_id, start_date, expected_completion_date, status) VALUES (?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'Pending')");
                $stmt->execute([$candidate_id]);
                break;
        }
        if (!isset($success_message)) {
            $success_message = "Action completed successfully!";
            if ($_POST['action'] == 'hire_candidate') {
                $success_message = "Candidate hired successfully! Onboarding process has been initiated.";
            }
        }
    }
}

$job_id = isset($_GET['job_id']) ? $_GET['job_id'] : null;

// Get job opening info if job_id is provided
$job_info = null;
if ($job_id) {
    $stmt = $conn->prepare("SELECT jo.*, d.department_name, jr.title as role_title FROM job_openings jo 
                           JOIN departments d ON jo.department_id = d.department_id 
                           JOIN job_roles jr ON jo.job_role_id = jr.job_role_id 
                           WHERE jo.job_opening_id = ?");
    $stmt->execute([$job_id]);
    $job_info = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get applications
$applications_query = "SELECT ja.*, c.first_name, c.last_name, c.email, c.phone, c.current_position, 
                       jo.title as job_title, d.department_name
                       FROM job_applications ja 
                       JOIN candidates c ON ja.candidate_id = c.candidate_id 
                       JOIN job_openings jo ON ja.job_opening_id = jo.job_opening_id
                       JOIN departments d ON jo.department_id = d.department_id";

if ($job_id) {
    $applications_query .= " WHERE ja.job_opening_id = ?";
    $stmt = $conn->prepare($applications_query . " ORDER BY ja.application_date DESC");
    $stmt->execute([$job_id]);
} else {
    $stmt = $conn->query($applications_query . " ORDER BY ja.application_date DESC");
}
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Applications - HR Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css?v=rose">
</head>
<body>
    <div class="container-fluid">
        <?php include 'navigation.php'; ?>
        <div class="row">
            <?php include 'sidebar.php'; ?>
            <div class="main-content">
                <?php if ($job_info): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($job_info['title']); ?></h5>
                        <small class="text-muted"><?php echo htmlspecialchars($job_info['department_name']); ?> • <?php echo htmlspecialchars($job_info['role_title']); ?></small>
                    </div>
                </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Job Applications</h5>
                        <?php if ($job_id): ?>
                        <a href="job_openings.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Job Openings
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Candidate</th>
                                        <th>Job Position</th>
                                        <th>Applied Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($applications) > 0): ?>
                                        <?php foreach($applications as $app): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($app['email']); ?></small><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($app['phone']); ?></small>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($app['job_title']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($app['department_name']); ?></small>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($app['application_date'])); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php 
                                                        echo $app['status'] == 'Applied' ? 'primary' : 
                                                            ($app['status'] == 'Approved' ? 'success' :
                                                            ($app['status'] == 'Interview' ? 'warning' : 
                                                            ($app['status'] == 'Pending' ? 'info' :
                                                            ($app['status'] == 'Assessment' ? 'info' :
                                                            ($app['status'] == 'Hired' ? 'success' : 'secondary'))))); 
                                                    ?>">
                                                        <?php echo $app['status']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group-vertical btn-group-sm">
                                                        <?php if ($app['status'] == 'Applied'): ?>
                                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Approve this application? Interview will be automatically scheduled.');">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="application_id" value="<?php echo $app['application_id']; ?>">
                                                                <input type="hidden" name="new_status" value="Approved">
                                                                <button type="submit" class="btn btn-success btn-sm mb-1">
                                                                    <i class="fas fa-check"></i> Approve & Schedule
                                                                </button>
                                                            </form>
                                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Decline this application?');">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="application_id" value="<?php echo $app['application_id']; ?>">
                                                                <input type="hidden" name="new_status" value="Declined">
                                                                <button type="submit" class="btn btn-danger btn-sm mb-1">
                                                                    <i class="fas fa-times"></i> Decline
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($app['status'] == 'Approved'): ?>
                                                            <span class="badge badge-success">Interview Scheduled</span><br>
                                                            <a href="interviews.php" class="btn btn-info btn-sm mb-1">
                                                                <i class="fas fa-calendar-alt"></i> View Interview
                                                            </a>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($app['status'] == 'Pending'): ?>
                                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Move to Assessment phase?');">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="application_id" value="<?php echo $app['application_id']; ?>">
                                                                <input type="hidden" name="new_status" value="Assessment">
                                                                <button type="submit" class="btn btn-info btn-sm mb-1">
                                                                    <i class="fas fa-clipboard-check"></i> Move to Assessment
                                                                </button>
                                                            </form>
                                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Reject this candidate?');">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="application_id" value="<?php echo $app['application_id']; ?>">
                                                                <input type="hidden" name="new_status" value="Rejected">
                                                                <button type="submit" class="btn btn-danger btn-sm mb-1">
                                                                    <i class="fas fa-times"></i> Reject
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($app['status'] == 'Assessment'): ?>
                                                            <a href="job_offers.php?candidate_id=<?php echo $app['candidate_id']; ?>" class="btn btn-info btn-sm mb-1">
                                                                <i class="fas fa-file-contract"></i> View Offer
                                                            </a>
                                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Hire this candidate?');">
                                                                <input type="hidden" name="action" value="hire_candidate">
                                                                <input type="hidden" name="application_id" value="<?php echo $app['application_id']; ?>">
                                                                <input type="hidden" name="candidate_id" value="<?php echo $app['candidate_id']; ?>">
                                                                <button type="submit" class="btn btn-success btn-sm mb-1">
                                                                    <i class="fas fa-user-check"></i> Hire
                                                                </button>
                                                            </form>
                                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Reject this candidate?');">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="application_id" value="<?php echo $app['application_id']; ?>">
                                                                <input type="hidden" name="new_status" value="Rejected">
                                                                <button type="submit" class="btn btn-danger btn-sm mb-1">
                                                                    <i class="fas fa-times"></i> Reject
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($app['status'] == 'Hired'): ?>
                                                            <a href="candidates.php" class="btn btn-primary btn-sm mb-1">
                                                                <i class="fas fa-users"></i> View Candidate
                                                            </a>
                                                            <a href="employee_onboarding.php?candidate_id=<?php echo $app['candidate_id']; ?>" class="btn btn-info btn-sm">
                                                                <i class="fas fa-clipboard-list"></i> Onboarding
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No applications found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <?php if (isset($success_message)): ?>
    <div class="modal fade" id="successModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-check-circle"></i> Success</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p><?php echo $success_message; ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" data-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <?php if (isset($success_message)): ?>
    <script>
        $(document).ready(function() {
            $('#successModal').modal('show');
        });
    </script>
    <?php endif; ?>
</body>
</html>