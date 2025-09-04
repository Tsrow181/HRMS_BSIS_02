<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
require_once 'config.php';

$success_message = '';
$show_delete_modal = false;
$modal_application_id = '';
$modal_candidate_id = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $application_id = $_POST['application_id'];
        
        switch ($_POST['action']) {
            case 'move_to_interview':
                $candidate_id = $_POST['candidate_id'];
                $stmt = $conn->prepare("UPDATE job_applications SET status = 'Interview' WHERE application_id = ?");
                $stmt->execute([$application_id]);
                
                $stmt = $conn->prepare("UPDATE candidates SET source = 'Interview' WHERE candidate_id = ?");
                $stmt->execute([$candidate_id]);
                
                // Check if interview already exists for this application
                $check_stmt = $conn->prepare("SELECT interview_id FROM interviews WHERE application_id = ? AND status IN ('Rescheduled', 'Scheduled')");
                $check_stmt->execute([$application_id]);
                
                if (!$check_stmt->fetch()) {
                    // Create interview record for scheduler only if none exists
                    $stmt = $conn->prepare("INSERT INTO interviews (application_id, stage_id, schedule_date, duration, interview_type, status) VALUES (?, 1, NOW(), 60, 'In-person', 'Rescheduled')");
                    $stmt->execute([$application_id]);
                }
                
                $success_message = "🗣️ Candidate moved to Interview stage! Interview ready for scheduling.";
                break;
                
            case 'move_to_assessment':
                $candidate_id = $_POST['candidate_id'];
                $stmt = $conn->prepare("UPDATE job_applications SET status = 'Assessment' WHERE application_id = ?");
                $stmt->execute([$application_id]);
                
                $stmt = $conn->prepare("UPDATE candidates SET source = 'Assessment' WHERE candidate_id = ?");
                $stmt->execute([$candidate_id]);
                
                $success_message = "👍 Mayor approved! Candidate moved to Assessment stage!";
                break;
                
            case 'move_back_to_applications':
                $candidate_id = $_POST['candidate_id'];
                $stmt = $conn->prepare("UPDATE job_applications SET status = 'Applied' WHERE application_id = ?");
                $stmt->execute([$application_id]);
                
                // Reset candidate source so they don't appear in candidates dashboard
                $stmt = $conn->prepare("UPDATE candidates SET source = 'Job Application' WHERE candidate_id = ?");
                $stmt->execute([$candidate_id]);
                
                // Remove any pending interviews for this application
                $stmt = $conn->prepare("DELETE FROM interviews WHERE application_id = ? AND status IN ('Rescheduled', 'Scheduled')");
                $stmt->execute([$application_id]);
                
                $success_message = "⬅️ Candidate moved back to Job Applications for review!";
                break;
                
            case 'hire_candidate':
                $candidate_id = $_POST['candidate_id'];
                $stmt = $conn->prepare("UPDATE job_applications SET status = 'Onboarding' WHERE application_id = ?");
                $stmt->execute([$application_id]);
                
                $stmt = $conn->prepare("UPDATE candidates SET source = 'Onboarding' WHERE candidate_id = ?");
                $stmt->execute([$candidate_id]);
                
                $success_message = "🎉 Assessment & Interview passed! Candidate moved to Onboarding process.";
                break;
                
            case 'complete_onboarding':
                $application_id = $_POST['application_id'];
                $candidate_id = $_POST['candidate_id'];
                
                $stmt = $conn->prepare("UPDATE job_applications SET status = 'Hired' WHERE application_id = ?");
                $stmt->execute([$application_id]);
                
                $stmt = $conn->prepare("UPDATE candidates SET source = 'Hired' WHERE candidate_id = ?");
                $stmt->execute([$candidate_id]);
                
                // Create employee profile
                $stmt = $conn->prepare("SELECT * FROM candidates WHERE candidate_id = ?");
                $stmt->execute([$candidate_id]);
                $candidate = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $emp_number = 'EMP' . str_pad($candidate_id, 4, '0', STR_PAD_LEFT);
                $stmt = $conn->prepare("INSERT INTO employee_profiles (employee_number, first_name, last_name, work_email, phone, address, hire_date, status) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), 'Active')");
                $stmt->execute([$emp_number, $candidate['first_name'], $candidate['last_name'], $candidate['email'], $candidate['phone'], $candidate['address']]);
                
                $success_message = "✅ Onboarding completed! Employee is now active.";
                break;
                
            case 'move_back':
                $candidate_id = $_POST['candidate_id'];
                $current_stmt = $conn->prepare("SELECT status FROM job_applications WHERE application_id = ?");
                $current_stmt->execute([$application_id]);
                $current_status = $current_stmt->fetch(PDO::FETCH_ASSOC)['status'];
                
                if ($current_status == 'Assessment') {
                    // Delete existing interviews
                    $stmt = $conn->prepare("DELETE FROM interviews WHERE application_id = ?");
                    $stmt->execute([$application_id]);
                    
                    // Move back to Applied status
                    $stmt = $conn->prepare("UPDATE job_applications SET status = 'Applied' WHERE application_id = ?");
                    $stmt->execute([$application_id]);
                    
                    // Reset candidate source
                    $stmt = $conn->prepare("UPDATE candidates SET source = 'Job Application' WHERE candidate_id = ?");
                    $stmt->execute([$candidate_id]);
                    
                    $success_message = "⬅️ Candidate moved back to Job Applications! Interview history deleted.";
                } elseif ($current_status == 'Interview') {
                    $stmt = $conn->prepare("UPDATE job_applications SET status = 'Screening' WHERE application_id = ?");
                    $stmt->execute([$application_id]);
                    
                    $stmt = $conn->prepare("UPDATE candidates SET source = 'Approved' WHERE candidate_id = ?");
                    $stmt->execute([$candidate_id]);
                    
                    // Remove any pending interviews for this application
                    $stmt = $conn->prepare("DELETE FROM interviews WHERE application_id = ? AND status IN ('Rescheduled', 'Scheduled')");
                    $stmt->execute([$application_id]);
                    
                    $success_message = "⬅️ Candidate moved back to Approved stage!";
                }
                break;
                

                
            case 'delete_interview_and_move_back':
                $candidate_id = $_POST['candidate_id'];
                
                // Delete existing interviews
                $stmt = $conn->prepare("DELETE FROM interviews WHERE application_id = ?");
                $stmt->execute([$application_id]);
                
                // Move back to Applied status
                $stmt = $conn->prepare("UPDATE job_applications SET status = 'Applied' WHERE application_id = ?");
                $stmt->execute([$application_id]);
                
                // Reset candidate source
                $stmt = $conn->prepare("UPDATE candidates SET source = 'Job Application' WHERE candidate_id = ?");
                $stmt->execute([$candidate_id]);
                
                $success_message = "⬅️ Candidate moved back to Job Applications! Interview history deleted.";
                break;
                
            case 'reject_candidate':
                $candidate_id = $_POST['candidate_id'];
                $stmt = $conn->prepare("UPDATE job_applications SET status = 'Rejected' WHERE application_id = ?");
                $stmt->execute([$application_id]);
                
                $stmt = $conn->prepare("UPDATE candidates SET source = 'Rejected' WHERE candidate_id = ?");
                $stmt->execute([$candidate_id]);
                
                // Remove any pending interviews for this application
                $stmt = $conn->prepare("DELETE FROM interviews WHERE application_id = ? AND status IN ('Rescheduled', 'Scheduled')");
                $stmt->execute([$application_id]);
                
                $success_message = "❌ Candidate rejected!";
                break;
        }
    }
}

// Get candidates by status (only show approved candidates)
$approved_candidates = $conn->query("SELECT c.*, ja.application_id, ja.application_date, jo.title as job_title, d.department_name
                                    FROM candidates c 
                                    JOIN job_applications ja ON c.candidate_id = ja.candidate_id
                                    JOIN job_openings jo ON ja.job_opening_id = jo.job_opening_id
                                    JOIN departments d ON jo.department_id = d.department_id
                                    WHERE ja.status = 'Screening' AND c.source = 'Approved'
                                    ORDER BY ja.application_date DESC")->fetchAll(PDO::FETCH_ASSOC);

$interview_candidates = $conn->query("SELECT c.*, ja.application_id, ja.application_date, jo.title as job_title, d.department_name
                                     FROM candidates c 
                                     JOIN job_applications ja ON c.candidate_id = ja.candidate_id
                                     JOIN job_openings jo ON ja.job_opening_id = jo.job_opening_id
                                     JOIN departments d ON jo.department_id = d.department_id
                                     WHERE ja.status = 'Interview' AND c.source IN ('Approved', 'Interview')
                                     ORDER BY ja.application_date DESC")->fetchAll(PDO::FETCH_ASSOC);

$assessment_candidates = $conn->query("SELECT c.*, ja.application_id, ja.application_date, jo.title as job_title, d.department_name
                                      FROM candidates c 
                                      JOIN job_applications ja ON c.candidate_id = ja.candidate_id
                                      JOIN job_openings jo ON ja.job_opening_id = jo.job_opening_id
                                      JOIN departments d ON jo.department_id = d.department_id
                                      WHERE ja.status = 'Assessment' AND c.source IN ('Approved', 'Interview', 'Assessment')
                                      ORDER BY ja.application_date DESC")->fetchAll(PDO::FETCH_ASSOC);

$onboarding_candidates = $conn->query("SELECT c.*, ja.application_id, ja.application_date, jo.title as job_title, d.department_name
                                        FROM candidates c 
                                        JOIN job_applications ja ON c.candidate_id = ja.candidate_id
                                        JOIN job_openings jo ON ja.job_opening_id = jo.job_opening_id
                                        JOIN departments d ON jo.department_id = d.department_id
                                        WHERE ja.status = 'Onboarding' AND c.source = 'Onboarding'
                                        ORDER BY ja.application_date DESC")->fetchAll(PDO::FETCH_ASSOC);

$hired_candidates = $conn->query("SELECT c.*, ja.application_id, ja.application_date, jo.title as job_title, d.department_name
                                 FROM candidates c 
                                 JOIN job_applications ja ON c.candidate_id = ja.candidate_id
                                 JOIN job_openings jo ON ja.job_opening_id = jo.job_opening_id
                                 JOIN departments d ON jo.department_id = d.department_id
                                 WHERE ja.status = 'Hired' AND c.source = 'Hired'
                                 ORDER BY ja.application_date DESC")->fetchAll(PDO::FETCH_ASSOC);

$stats = [
    'Approved' => count($approved_candidates),
    'Mayor Approval' => count($interview_candidates),
    'Assessment & Interview' => count($assessment_candidates),
    'Onboarding' => count($onboarding_candidates),
    'Hired' => count($hired_candidates)
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidates Dashboard - HR Management System</title>
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
                <h2>👥 Candidates Progress Dashboard</h2>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card card">
                            <div class="card-body text-center">
                                <div class="activity-icon bg-success">
                                    <i class="fas fa-check"></i>
                                </div>
                                <h3 class="stats-number"><?php echo $stats['Approved']; ?></h3>
                                <p class="stats-label">Approved</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card card">
                            <div class="card-body text-center">
                                <div class="activity-icon bg-primary">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <h3 class="stats-number"><?php echo $stats['Mayor Approval']; ?></h3>
                                <p class="stats-label">Mayor Approval</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card card">
                            <div class="card-body text-center">
                                <div class="activity-icon bg-warning">
                                    <i class="fas fa-clipboard-check"></i>
                                </div>
                                <h3 class="stats-number"><?php echo $stats['Assessment & Interview']; ?></h3>
                                <p class="stats-label">Assessment & Interview</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card card">
                            <div class="card-body text-center">
                                <div class="activity-icon bg-danger">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <h3 class="stats-number"><?php echo $stats['Onboarding']; ?></h3>
                                <p class="stats-label">Onboarding</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card card">
                            <div class="card-body text-center">
                                <div class="activity-icon bg-info">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <h3 class="stats-number"><?php echo $stats['Hired']; ?></h3>
                                <p class="stats-label">Hired</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Approved Stage -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-check"></i> Approved Stage (<?php echo count($approved_candidates); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($approved_candidates) > 0): ?>
                            <div class="row">
                                <?php foreach($approved_candidates as $candidate): ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card">
                                            <div class="card-body">
                                                <h6><i class="fas fa-user"></i> <?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?></h6>
                                                <p class="mb-1"><strong>Job:</strong> <?php echo htmlspecialchars($candidate['job_title']); ?></p>
                                                <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($candidate['email']); ?></p>
                                                <p class="mb-3"><strong>Applied:</strong> <?php echo date('M d, Y', strtotime($candidate['application_date'])); ?></p>
                                                
                                                <div class="btn-group-vertical w-100">
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="action" value="move_to_interview">
                                                        <input type="hidden" name="application_id" value="<?php echo $candidate['application_id']; ?>">
                                                        <input type="hidden" name="candidate_id" value="<?php echo $candidate['candidate_id']; ?>">
                                                        <button type="submit" class="btn btn-primary btn-sm action-btn mb-1">
                                                            <i class="fas fa-arrow-right"></i> Move to Interview
                                                        </button>
                                                    </form>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="action" value="move_back_to_applications">
                                                        <input type="hidden" name="application_id" value="<?php echo $candidate['application_id']; ?>">
                                                        <input type="hidden" name="candidate_id" value="<?php echo $candidate['candidate_id']; ?>">
                                                        <button type="submit" class="btn btn-secondary btn-sm action-btn mb-1">
                                                            <i class="fas fa-arrow-left"></i> Back to Applications
                                                        </button>
                                                    </form>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="action" value="reject_candidate">
                                                        <input type="hidden" name="application_id" value="<?php echo $candidate['application_id']; ?>">
                                                        <input type="hidden" name="candidate_id" value="<?php echo $candidate['candidate_id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm action-btn" onclick="return confirm('Reject this candidate?')">
                                                            <i class="fas fa-times"></i> Reject
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> No candidates in Approved stage.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Mayor Approval Stage -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-user-tie"></i> Mayor Approval Stage (<?php echo count($interview_candidates); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($interview_candidates) > 0): ?>
                            <div class="row">
                                <?php foreach($interview_candidates as $candidate): ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card">
                                            <div class="card-body">
                                                <h6><i class="fas fa-user"></i> <?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?></h6>
                                                <p class="mb-1"><strong>Job:</strong> <?php echo htmlspecialchars($candidate['job_title']); ?></p>
                                                <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($candidate['email']); ?></p>
                                                <p class="mb-3"><strong>Applied:</strong> <?php echo date('M d, Y', strtotime($candidate['application_date'])); ?></p>
                                                
                                                <div class="btn-group-vertical w-100">
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="action" value="move_to_assessment">
                                                        <input type="hidden" name="application_id" value="<?php echo $candidate['application_id']; ?>">
                                                        <input type="hidden" name="candidate_id" value="<?php echo $candidate['candidate_id']; ?>">
                                                        <button type="submit" class="btn btn-success btn-sm action-btn mb-1" onclick="return confirm('Mayor approves this candidate?')">
                                                            <i class="fas fa-stamp"></i> Mayor Approve - Move to Assessment
                                                        </button>
                                                    </form>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="action" value="move_back_to_applications">
                                                        <input type="hidden" name="application_id" value="<?php echo $candidate['application_id']; ?>">
                                                        <input type="hidden" name="candidate_id" value="<?php echo $candidate['candidate_id']; ?>">
                                                        <button type="submit" class="btn btn-secondary btn-sm action-btn mb-1">
                                                            <i class="fas fa-arrow-left"></i> Back to Applications
                                                        </button>
                                                    </form>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="action" value="reject_candidate">
                                                        <input type="hidden" name="application_id" value="<?php echo $candidate['application_id']; ?>">
                                                        <input type="hidden" name="candidate_id" value="<?php echo $candidate['candidate_id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm action-btn" onclick="return confirm('Reject this candidate?')">
                                                            <i class="fas fa-times"></i> Reject
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> No candidates in Interview stage.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Assessment & Interview Stage -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-clipboard-check"></i> Assessment & Interview Stage (<?php echo count($assessment_candidates); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($assessment_candidates) > 0): ?>
                            <div class="row">
                                <?php foreach($assessment_candidates as $candidate): ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card">
                                            <div class="card-body">
                                                <h6><i class="fas fa-user"></i> <?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?></h6>
                                                <p class="mb-1"><strong>Job:</strong> <?php echo htmlspecialchars($candidate['job_title']); ?></p>
                                                <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($candidate['email']); ?></p>
                                                <p class="mb-3"><strong>Applied:</strong> <?php echo date('M d, Y', strtotime($candidate['application_date'])); ?></p>
                                                
                                                <div class="btn-group-vertical w-100">
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="action" value="hire_candidate">
                                                        <input type="hidden" name="application_id" value="<?php echo $candidate['application_id']; ?>">
                                                        <input type="hidden" name="candidate_id" value="<?php echo $candidate['candidate_id']; ?>">
                                                        <button type="submit" class="btn btn-success btn-sm action-btn mb-1" onclick="return confirm('Pass assessment & interview and hire this candidate?')">
                                                            <i class="fas fa-check"></i> Pass Assessment & Interview - Hire
                                                        </button>
                                                    </form>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="action" value="move_back">
                                                        <input type="hidden" name="application_id" value="<?php echo $candidate['application_id']; ?>">
                                                        <input type="hidden" name="candidate_id" value="<?php echo $candidate['candidate_id']; ?>">
                                                        <button type="submit" class="btn btn-warning btn-sm action-btn mb-1" onclick="return confirm('Do you want to delete existing interview records for this candidate?')">
                                                            <i class="fas fa-arrow-left"></i> Failed Assessment - Back to Mayor
                                                        </button>
                                                    </form>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="action" value="reject_candidate">
                                                        <input type="hidden" name="application_id" value="<?php echo $candidate['application_id']; ?>">
                                                        <input type="hidden" name="candidate_id" value="<?php echo $candidate['candidate_id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm action-btn" onclick="return confirm('Reject this candidate permanently?')">
                                                            <i class="fas fa-times"></i> Reject Candidate
                                                        </button>
                                                    </form>
                                                </div>
                                                
                                                <!-- Hidden form for delete interview confirmation -->
                                                <form method="POST" id="deleteInterviewForm_<?php echo $candidate['application_id']; ?>" style="display:none;">
                                                    <input type="hidden" name="action" value="delete_interview_and_move_back">
                                                    <input type="hidden" name="application_id" value="<?php echo $candidate['application_id']; ?>">
                                                    <input type="hidden" name="candidate_id" value="<?php echo $candidate['candidate_id']; ?>">
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> No candidates in Assessment stage.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Onboarding Stage -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-clipboard-list"></i> Onboarding Process (<?php echo count($onboarding_candidates); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($onboarding_candidates) > 0): ?>
                            <div class="row">
                                <?php foreach($onboarding_candidates as $candidate): ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card border-info">
                                            <div class="card-body">
                                                <h6><i class="fas fa-clipboard-list"></i> <?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?></h6>
                                                <p class="mb-1"><strong>Job:</strong> <?php echo htmlspecialchars($candidate['job_title']); ?></p>
                                                <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($candidate['email']); ?></p>
                                                <p class="mb-3"><strong>Hired:</strong> <?php echo date('M d, Y', strtotime($candidate['application_date'])); ?></p>
                                                
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="complete_onboarding">
                                                    <input type="hidden" name="application_id" value="<?php echo $candidate['application_id']; ?>">
                                                    <input type="hidden" name="candidate_id" value="<?php echo $candidate['candidate_id']; ?>">
                                                    <button type="submit" class="btn btn-success btn-sm action-btn" onclick="return confirm('Complete onboarding process?')">
                                                        <i class="fas fa-check-circle"></i> Complete Onboarding
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> No candidates in onboarding process.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Hired Candidates -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-user-check"></i> Hired Candidates (<?php echo count($hired_candidates); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($hired_candidates) > 0): ?>
                            <div class="row">
                                <?php foreach($hired_candidates as $candidate): ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card border-success">
                                            <div class="card-body">
                                                <h6><i class="fas fa-user-check"></i> <?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?></h6>
                                                <p class="mb-1"><strong>Job:</strong> <?php echo htmlspecialchars($candidate['job_title']); ?></p>
                                                <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($candidate['email']); ?></p>
                                                <p class="mb-3"><strong>Hired:</strong> <?php echo date('M d, Y', strtotime($candidate['application_date'])); ?></p>
                                                
                                                <a href="employee_profiles.php" class="btn btn-info btn-sm action-btn">
                                                    <i class="fas fa-id-card"></i> View Employee Profile
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> No hired candidates yet.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Interview Confirmation Modal -->
    <?php if ($show_delete_modal): ?>
    <div class="modal fade" id="deleteInterviewModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Interview History</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Do you want to delete existing interview records for this candidate?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Decline</button>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="delete_interview_and_move_back">
                        <input type="hidden" name="application_id" value="<?php echo $modal_application_id; ?>">
                        <input type="hidden" name="candidate_id" value="<?php echo $modal_candidate_id; ?>">
                        <button type="submit" class="btn btn-success">Approve</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            $('#deleteInterviewModal').modal('show');
        });
    </script>
    <?php endif; ?>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>