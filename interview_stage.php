<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
require_once 'config.php';

$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $application_id = $_POST['application_id'];
        $candidate_id = $_POST['candidate_id'];
        
        switch ($_POST['action']) {
            case 'complete_documents':
                $stmt = $conn->prepare("UPDATE candidates SET source = 'Documents Complete' WHERE candidate_id = ?");
                $stmt->execute([$candidate_id]);
                $success_message = "📄 Documents completed!";
                break;
                
            case 'complete_medical':
                $stmt = $conn->prepare("UPDATE candidates SET source = 'Medical Complete' WHERE candidate_id = ?");
                $stmt->execute([$candidate_id]);
                $success_message = "🏥 Medical exam completed!";
                break;
                
            case 'complete_background':
                $stmt = $conn->prepare("UPDATE candidates SET source = 'Background Complete' WHERE candidate_id = ?");
                $stmt->execute([$candidate_id]);
                $success_message = "🔍 Background check completed!";
                break;
                
            case 'complete_orientation':
                $stmt = $conn->prepare("UPDATE candidates SET source = 'Orientation Complete' WHERE candidate_id = ?");
                $stmt->execute([$candidate_id]);
                $success_message = "🎓 Orientation completed!";
                break;
                
            case 'complete_onboarding':
                $stmt = $conn->prepare("UPDATE candidates SET source = 'Hired' WHERE candidate_id = ?");
                $stmt->execute([$candidate_id]);
                $success_message = "✅ Onboarding completed! Employee is now active.";
                break;
        }
    }
}

// Get candidates in different onboarding stages
$documents_stage = $conn->query("SELECT c.*, ja.application_id, ja.application_date, jo.title as job_title, d.department_name
                                FROM candidates c 
                                JOIN job_applications ja ON c.candidate_id = ja.candidate_id
                                JOIN job_openings jo ON ja.job_opening_id = jo.job_opening_id
                                JOIN departments d ON jo.department_id = d.department_id
                                WHERE ja.status = 'Hired' AND c.source = 'Onboarding'
                                ORDER BY ja.application_date DESC")->fetchAll(PDO::FETCH_ASSOC);

$medical_stage = $conn->query("SELECT c.*, ja.application_id, ja.application_date, jo.title as job_title, d.department_name
                              FROM candidates c 
                              JOIN job_applications ja ON c.candidate_id = ja.candidate_id
                              JOIN job_openings jo ON ja.job_opening_id = jo.job_opening_id
                              JOIN departments d ON jo.department_id = d.department_id
                              WHERE ja.status = 'Hired' AND c.source = 'Documents Complete'
                              ORDER BY ja.application_date DESC")->fetchAll(PDO::FETCH_ASSOC);

$background_stage = $conn->query("SELECT c.*, ja.application_id, ja.application_date, jo.title as job_title, d.department_name
                                 FROM candidates c 
                                 JOIN job_applications ja ON c.candidate_id = ja.candidate_id
                                 JOIN job_openings jo ON ja.job_opening_id = jo.job_opening_id
                                 JOIN departments d ON jo.department_id = d.department_id
                                 WHERE ja.status = 'Hired' AND c.source = 'Medical Complete'
                                 ORDER BY ja.application_date DESC")->fetchAll(PDO::FETCH_ASSOC);

$orientation_stage = $conn->query("SELECT c.*, ja.application_id, ja.application_date, jo.title as job_title, d.department_name
                                  FROM candidates c 
                                  JOIN job_applications ja ON c.candidate_id = ja.candidate_id
                                  JOIN job_openings jo ON ja.job_opening_id = jo.job_opening_id
                                  JOIN departments d ON jo.department_id = d.department_id
                                  WHERE ja.status = 'Hired' AND c.source = 'Background Complete'
                                  ORDER BY ja.application_date DESC")->fetchAll(PDO::FETCH_ASSOC);

$final_stage = $conn->query("SELECT c.*, ja.application_id, ja.application_date, jo.title as job_title, d.department_name
                            FROM candidates c 
                            JOIN job_applications ja ON c.candidate_id = ja.candidate_id
                            JOIN job_openings jo ON ja.job_opening_id = jo.job_opening_id
                            JOIN departments d ON jo.department_id = d.department_id
                            WHERE ja.status = 'Hired' AND c.source = 'Orientation Complete'
                            ORDER BY ja.application_date DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Onboarding Process - HR Management System</title>
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
                <h2>📋 Onboarding Process Stages</h2>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Stage 1: Document Submission -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-file-alt"></i> Stage 1: Document Submission (<?php echo count($documents_stage); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($documents_stage) > 0): ?>
                            <div class="row">
                                <?php foreach($documents_stage as $candidate): ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card border-warning">
                                            <div class="card-body">
                                                <h6><i class="fas fa-user"></i> <?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?></h6>
                                                <p class="mb-1"><strong>Job:</strong> <?php echo htmlspecialchars($candidate['job_title']); ?></p>
                                                <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($candidate['email']); ?></p>
                                                <p class="mb-3"><strong>Hired:</strong> <?php echo date('M d, Y', strtotime($candidate['application_date'])); ?></p>
                                                
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="complete_documents">
                                                    <input type="hidden" name="application_id" value="<?php echo $candidate['application_id']; ?>">
                                                    <input type="hidden" name="candidate_id" value="<?php echo $candidate['candidate_id']; ?>">
                                                    <button type="submit" class="btn btn-primary btn-sm action-btn" onclick="return confirm('Mark documents as complete?')">
                                                        <i class="fas fa-check"></i> Complete Documents
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> No candidates in document submission stage.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Stage 2: Medical Examination -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-stethoscope"></i> Stage 2: Medical Examination (<?php echo count($medical_stage); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($medical_stage) > 0): ?>
                            <div class="row">
                                <?php foreach($medical_stage as $candidate): ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card border-info">
                                            <div class="card-body">
                                                <h6><i class="fas fa-user"></i> <?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?></h6>
                                                <p class="mb-1"><strong>Job:</strong> <?php echo htmlspecialchars($candidate['job_title']); ?></p>
                                                <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($candidate['email']); ?></p>
                                                <p class="mb-3"><strong>Status:</strong> Documents Complete</p>
                                                
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="complete_medical">
                                                    <input type="hidden" name="application_id" value="<?php echo $candidate['application_id']; ?>">
                                                    <input type="hidden" name="candidate_id" value="<?php echo $candidate['candidate_id']; ?>">
                                                    <button type="submit" class="btn btn-success btn-sm action-btn" onclick="return confirm('Mark medical exam as complete?')">
                                                        <i class="fas fa-check"></i> Complete Medical
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> No candidates in medical examination stage.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Stage 3: Background Check -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-search"></i> Stage 3: Background Check (<?php echo count($background_stage); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($background_stage) > 0): ?>
                            <div class="row">
                                <?php foreach($background_stage as $candidate): ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card border-primary">
                                            <div class="card-body">
                                                <h6><i class="fas fa-user"></i> <?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?></h6>
                                                <p class="mb-1"><strong>Job:</strong> <?php echo htmlspecialchars($candidate['job_title']); ?></p>
                                                <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($candidate['email']); ?></p>
                                                <p class="mb-3"><strong>Status:</strong> Medical Complete</p>
                                                
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="complete_background">
                                                    <input type="hidden" name="application_id" value="<?php echo $candidate['application_id']; ?>">
                                                    <input type="hidden" name="candidate_id" value="<?php echo $candidate['candidate_id']; ?>">
                                                    <button type="submit" class="btn btn-warning btn-sm action-btn" onclick="return confirm('Mark background check as complete?')">
                                                        <i class="fas fa-check"></i> Complete Background Check
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> No candidates in background check stage.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Stage 4: Orientation -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-graduation-cap"></i> Stage 4: Orientation (<?php echo count($orientation_stage); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($orientation_stage) > 0): ?>
                            <div class="row">
                                <?php foreach($orientation_stage as $candidate): ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card border-secondary">
                                            <div class="card-body">
                                                <h6><i class="fas fa-user"></i> <?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?></h6>
                                                <p class="mb-1"><strong>Job:</strong> <?php echo htmlspecialchars($candidate['job_title']); ?></p>
                                                <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($candidate['email']); ?></p>
                                                <p class="mb-3"><strong>Status:</strong> Background Complete</p>
                                                
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="complete_orientation">
                                                    <input type="hidden" name="application_id" value="<?php echo $candidate['application_id']; ?>">
                                                    <input type="hidden" name="candidate_id" value="<?php echo $candidate['candidate_id']; ?>">
                                                    <button type="submit" class="btn btn-info btn-sm action-btn" onclick="return confirm('Mark orientation as complete?')">
                                                        <i class="fas fa-check"></i> Complete Orientation
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> No candidates in orientation stage.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Stage 5: Final Onboarding -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-clipboard-check"></i> Stage 5: Final Onboarding (<?php echo count($final_stage); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($final_stage) > 0): ?>
                            <div class="row">
                                <?php foreach($final_stage as $candidate): ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card border-success">
                                            <div class="card-body">
                                                <h6><i class="fas fa-user"></i> <?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?></h6>
                                                <p class="mb-1"><strong>Job:</strong> <?php echo htmlspecialchars($candidate['job_title']); ?></p>
                                                <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($candidate['email']); ?></p>
                                                <p class="mb-3"><strong>Status:</strong> Orientation Complete</p>
                                                
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
                                <i class="fas fa-info-circle"></i> No candidates in final onboarding stage.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>