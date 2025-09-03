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
            case 'schedule_interview':
                $application_id = $_POST['application_id'];
                $stage_id = $_POST['stage_id'];
                $schedule_date = $_POST['schedule_date'];
                $duration = $_POST['duration'];
                $location = $_POST['location'];
                $interview_type = $_POST['interview_type'];
                
                $stmt = $conn->prepare("INSERT INTO interviews (application_id, stage_id, schedule_date, duration, location, interview_type, status) VALUES (?, ?, ?, ?, ?, ?, 'Scheduled')");
                $stmt->execute([$application_id, $stage_id, $schedule_date, $duration, $location, $interview_type]);
                break;
                
            case 'complete_interview':
                $interview_id = $_POST['interview_id'];
                $feedback = $_POST['feedback'];
                $rating = $_POST['rating'];
                $recommendation = $_POST['recommendation'];
                
                $stmt = $conn->prepare("UPDATE interviews SET status = 'Completed', feedback = ?, rating = ?, recommendation = ?, completed_date = NOW() WHERE interview_id = ?");
                $stmt->execute([$feedback, $rating, $recommendation, $interview_id]);
                
                // Get interview and application details
                $stmt = $conn->prepare("SELECT i.application_id, ja.candidate_id FROM interviews i JOIN job_applications ja ON i.application_id = ja.application_id WHERE i.interview_id = ?");
                $stmt->execute([$interview_id]);
                $interview_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Get candidate email for notification
                $candidate_stmt = $conn->prepare("SELECT c.email, c.first_name, c.last_name, jo.title FROM candidates c JOIN job_applications ja ON c.candidate_id = ja.candidate_id JOIN job_openings jo ON ja.job_opening_id = jo.job_opening_id WHERE ja.application_id = ?");
                $candidate_stmt->execute([$interview_data['application_id']]);
                $candidate = $candidate_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($recommendation == 'Strong Yes' || $recommendation == 'Yes') {
                    // Check if there are more interview stages
                    $next_stage_stmt = $conn->prepare("SELECT stage_id FROM interview_stages WHERE job_opening_id = (SELECT ja.job_opening_id FROM job_applications ja WHERE ja.application_id = ?) AND stage_order > (SELECT stage_order FROM interview_stages WHERE stage_id = ?) ORDER BY stage_order LIMIT 1");
                    $next_stage_stmt->execute([$interview_data['application_id'], $_POST['stage_id']]);
                    $next_stage = $next_stage_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($next_stage) {
                        // Keep status as Interview for next stage
                        $stmt = $conn->prepare("UPDATE job_applications SET status = 'Interview' WHERE application_id = ?");
                        $stmt->execute([$interview_data['application_id']]);
                        
                        // Send next stage email
                        sendEmail($candidate['email'], '', '', $candidate['first_name'] . ' ' . $candidate['last_name'], $candidate['title'], 'Interview', $conn);
                    } else {
                        // No more stages, move to Pending
                        $stmt = $conn->prepare("UPDATE job_applications SET status = 'Pending' WHERE application_id = ?");
                        $stmt->execute([$interview_data['application_id']]);
                        
                        // Send completion email
                        sendEmail($candidate['email'], '', '', $candidate['first_name'] . ' ' . $candidate['last_name'], $candidate['title'], 'Pending', $conn);
                    }
                } else {
                    // Update application status to Rejected
                    $stmt = $conn->prepare("UPDATE job_applications SET status = 'Rejected' WHERE application_id = ?");
                    $stmt->execute([$interview_data['application_id']]);
                    
                    // Send AI-generated rejection email
                    sendEmail($candidate['email'], '', '', $candidate['first_name'] . ' ' . $candidate['last_name'], $candidate['title'], 'Rejected', $conn);
                }
                break;
        }
        $success_message = "Interview completed successfully! AI-generated email sent to candidate.";
        if ($recommendation == 'Strong Yes' || $recommendation == 'Yes') {
            // Check if moved to next stage or pending
            $next_stage_stmt = $conn->prepare("SELECT stage_id FROM interview_stages WHERE job_opening_id = (SELECT ja.job_opening_id FROM job_applications ja WHERE ja.application_id = ?) AND stage_order > (SELECT stage_order FROM interview_stages WHERE stage_id = ?) ORDER BY stage_order LIMIT 1");
            $next_stage_stmt->execute([$interview_data['application_id'], $_POST['stage_id']]);
            $has_next_stage = $next_stage_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($has_next_stage) {
                $success_message .= " Candidate progressed to next interview stage.";
            } else {
                $success_message .= " All interview stages completed. Moved to Pending status.";
            }
        }
    }
}

// Get interviews with application and candidate info
$interviews_query = "SELECT i.*, ja.application_id, c.first_name, c.last_name, c.email, 
                     jo.title as job_title, d.department_name, ist.stage_name
                     FROM interviews i
                     JOIN job_applications ja ON i.application_id = ja.application_id
                     JOIN candidates c ON ja.candidate_id = c.candidate_id
                     JOIN job_openings jo ON ja.job_opening_id = jo.job_opening_id
                     JOIN departments d ON jo.department_id = d.department_id
                     JOIN interview_stages ist ON i.stage_id = ist.stage_id
                     ORDER BY i.schedule_date DESC";
$interviews = $conn->query($interviews_query)->fetchAll(PDO::FETCH_ASSOC);

// Get pending applications for scheduling
$pending_apps = $conn->query("SELECT ja.*, c.first_name, c.last_name, jo.title as job_title, jo.job_opening_id
                              FROM job_applications ja 
                              JOIN candidates c ON ja.candidate_id = c.candidate_id
                              JOIN job_openings jo ON ja.job_opening_id = jo.job_opening_id
                              WHERE ja.status = 'Interview'")->fetchAll(PDO::FETCH_ASSOC);

// Get interview stages
$stages = $conn->query("SELECT * FROM interview_stages ORDER BY stage_order")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interviews - HR Management System</title>
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
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-calendar-plus"></i> Schedule Interview</h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($pending_apps) > 0): ?>
                                <form method="POST">
                                    <input type="hidden" name="action" value="schedule_interview">
                                    <div class="form-group">
                                        <label>Candidate</label>
                                        <select name="application_id" class="form-control" required>
                                            <option value="">Select Candidate</option>
                                            <?php foreach($pending_apps as $app): ?>
                                            <option value="<?php echo $app['application_id']; ?>">
                                                <?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name'] . ' - ' . $app['job_title']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Interview Stage</label>
                                                <select name="stage_id" class="form-control" required>
                                                    <option value="">Select Stage</option>
                                                    <?php foreach($stages as $stage): ?>
                                                    <option value="<?php echo $stage['stage_id']; ?>">
                                                        <?php echo htmlspecialchars($stage['stage_name']); ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Interview Type</label>
                                                <select name="interview_type" class="form-control" required>
                                                    <option value="In-person">In-person</option>
                                                    <option value="Video Call">Video Call</option>
                                                    <option value="Phone">Phone</option>
                                                    <option value="Technical Assessment">Technical Assessment</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Schedule Date & Time</label>
                                                <input type="datetime-local" name="schedule_date" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Duration (minutes)</label>
                                                <input type="number" name="duration" class="form-control" value="60" min="15" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Location</label>
                                        <input type="text" name="location" class="form-control" placeholder="HR Office / Zoom Link / Phone Number">
                                    </div>
                                    <button type="submit" class="btn btn-primary">Schedule Interview</button>
                                </form>
                                <?php else: ?>
                                <p class="text-muted">No candidates pending interview scheduling.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-bar"></i> Interview Statistics</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $scheduled = count(array_filter($interviews, function($i) { return $i['status'] == 'Scheduled'; }));
                                $completed = count(array_filter($interviews, function($i) { return $i['status'] == 'Completed'; }));
                                ?>
                                <div class="row text-center">
                                    <div class="col-md-6">
                                        <h3 class="text-warning"><?php echo $scheduled; ?></h3>
                                        <p>Scheduled</p>
                                    </div>
                                    <div class="col-md-6">
                                        <h3 class="text-success"><?php echo $completed; ?></h3>
                                        <p>Completed</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-calendar-alt"></i> Interview Schedule</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Candidate</th>
                                        <th>Position</th>
                                        <th>Stage</th>
                                        <th>Schedule</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($interviews) > 0): ?>
                                        <?php foreach($interviews as $interview): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($interview['first_name'] . ' ' . $interview['last_name']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($interview['email']); ?></small>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($interview['job_title']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($interview['department_name']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($interview['stage_name']); ?></td>
                                                <td>
                                                    <?php echo date('M d, Y', strtotime($interview['schedule_date'])); ?><br>
                                                    <small class="text-muted"><?php echo date('h:i A', strtotime($interview['schedule_date'])); ?></small>
                                                </td>
                                                <td><?php echo $interview['interview_type']; ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $interview['status'] == 'Scheduled' ? 'warning' : 'success'; ?>">
                                                        <?php echo $interview['status']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($interview['status'] == 'Scheduled'): ?>
                                                        <button class="btn btn-success btn-sm" onclick="openCompleteModal(<?php echo $interview['interview_id']; ?>)">
                                                            <i class="fas fa-check"></i> Complete
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="badge badge-info">Rating: <?php echo $interview['rating']; ?></span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>


                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No interviews scheduled</td>
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
    
    <!-- Complete Interview Modal -->
    <div class="modal fade" id="completeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Complete Interview</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="complete_interview">
                        <input type="hidden" name="interview_id" id="modalInterviewId">
                        
                        <div class="form-group">
                            <label>Feedback</label>
                            <textarea name="feedback" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Rating (1-5)</label>
                                    <input type="number" name="rating" class="form-control" min="1" max="5" step="0.1" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Recommendation</label>
                                    <select name="recommendation" class="form-control" required>
                                        <option value="Strong Yes">Strong Yes</option>
                                        <option value="Yes">Yes</option>
                                        <option value="Maybe">Maybe</option>
                                        <option value="No">No</option>
                                        <option value="Strong No">Strong No</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Complete Interview</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openCompleteModal(interviewId) {
            document.getElementById('modalInterviewId').value = interviewId;
            $('#completeModal').modal('show');
        }
    </script>

    <?php if (isset($success_message)): ?>
    <script>
        $(document).ready(function() {
            $('#successModal').modal('show');
        });
    </script>
    <?php endif; ?>
</body>
</html>