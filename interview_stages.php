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
        $candidate_id = $_POST['candidate_id'];
        $stage_id = $_POST['stage_id'];
        
        switch ($_POST['action']) {
            case 'approve_stage':
                // Get next stage
                $current_stage = $conn->prepare("SELECT stage_order, job_opening_id FROM interview_stages WHERE stage_id = ?");
                $current_stage->execute([$stage_id]);
                $stage_info = $current_stage->fetch(PDO::FETCH_ASSOC);
                
                $next_stage = $conn->prepare("SELECT stage_name FROM interview_stages WHERE job_opening_id = ? AND stage_order = ?");
                $next_stage->execute([$stage_info['job_opening_id'], $stage_info['stage_order'] + 1]);
                $next_stage_name = $next_stage->fetch(PDO::FETCH_ASSOC);
                
                if ($next_stage_name) {
                    $stmt = $conn->prepare("UPDATE candidates SET source = ? WHERE candidate_id = ?");
                    $stmt->execute([$next_stage_name['stage_name'], $candidate_id]);
                    $success_message = "✅ Stage approved! Moved to {$next_stage_name['stage_name']}.";
                } else {
                    // Final stage - complete onboarding
                    $application_id = $_POST['application_id'];
                    $stmt = $conn->prepare("UPDATE job_applications SET status = 'Hired' WHERE application_id = ?");
                    $stmt->execute([$application_id]);
                    
                    $stmt = $conn->prepare("UPDATE candidates SET source = 'Hired' WHERE candidate_id = ?");
                    $stmt->execute([$candidate_id]);
                    $success_message = "✅ Final stage completed! Employee is now hired.";
                }
                break;
                
            case 'reject_candidate':
                $application_id = $_POST['application_id'];
                $stmt = $conn->prepare("UPDATE job_applications SET status = 'Rejected' WHERE application_id = ?");
                $stmt->execute([$application_id]);
                
                $stmt = $conn->prepare("UPDATE candidates SET source = 'Rejected' WHERE candidate_id = ?");
                $stmt->execute([$candidate_id]);
                
                $success_message = "❌ Candidate rejected from onboarding process!";
                break;
        }
    }
}

// Get all candidates in Onboarding status with their job's interview stages
$all_candidates = $conn->query("SELECT c.*, ja.application_id, ja.application_date, jo.job_opening_id, jo.title as job_title, d.department_name
                               FROM candidates c 
                               JOIN job_applications ja ON c.candidate_id = ja.candidate_id
                               JOIN job_openings jo ON ja.job_opening_id = jo.job_opening_id
                               JOIN departments d ON jo.department_id = d.department_id
                               WHERE ja.status = 'Onboarding'
                               ORDER BY jo.title, ja.application_date DESC")->fetchAll(PDO::FETCH_ASSOC);

// Get all interview stages for jobs with onboarding candidates
$job_stages = [];
if (!empty($all_candidates)) {
    $job_ids = array_unique(array_column($all_candidates, 'job_opening_id'));
    $placeholders = str_repeat('?,', count($job_ids) - 1) . '?';
    $stages_query = $conn->prepare("SELECT * FROM interview_stages WHERE job_opening_id IN ($placeholders) ORDER BY job_opening_id, stage_order");
    $stages_query->execute($job_ids);
    $stages_result = $stages_query->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($stages_result as $stage) {
        $job_stages[$stage['job_opening_id']][] = $stage;
    }
}

// Group candidates by job opening and stage
$candidates_by_job = [];
$total_stats = [];

foreach ($all_candidates as $candidate) {
    $job_id = $candidate['job_opening_id'];
    if (!isset($candidates_by_job[$job_id])) {
        $candidates_by_job[$job_id] = [
            'job_title' => $candidate['job_title'],
            'department_name' => $candidate['department_name'],
            'stages' => []
        ];
        
        // Initialize stages for this job
        if (isset($job_stages[$job_id])) {
            foreach ($job_stages[$job_id] as $stage) {
                $candidates_by_job[$job_id]['stages'][$stage['stage_name']] = [];
                if (!isset($total_stats[$stage['stage_name']])) {
                    $total_stats[$stage['stage_name']] = 0;
                }
            }
        }
    }
    
    // Add candidate to appropriate stage
    if (isset($candidates_by_job[$job_id]['stages'][$candidate['source']])) {
        $candidates_by_job[$job_id]['stages'][$candidate['source']][] = $candidate;
        $total_stats[$candidate['source']]++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Onboarding Stages - HR Management System</title>
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
                <h2>📋 Onboarding Stages Dashboard</h2>
                
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
                    <?php 
                    $colors = ['bg-primary', 'bg-info', 'bg-warning', 'bg-success', 'bg-danger'];
                    $icons = ['fas fa-file-alt', 'fas fa-stethoscope', 'fas fa-search', 'fas fa-graduation-cap', 'fas fa-clipboard-check'];
                    $i = 0;
                    foreach ($total_stats as $stage_name => $count): 
                        $color = $colors[$i % count($colors)];
                        $icon = $icons[$i % count($icons)];
                    ?>
                        <div class="col-md-3">
                            <div class="stats-card card">
                                <div class="card-body text-center">
                                    <div class="activity-icon <?php echo $color; ?>">
                                        <i class="<?php echo $icon; ?>"></i>
                                    </div>
                                    <h3 class="stats-number"><?php echo $count; ?></h3>
                                    <p class="stats-label"><?php echo htmlspecialchars($stage_name); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php 
                        $i++;
                        if ($i % 4 == 0) echo '</div><div class="row mb-4">';
                    endforeach; 
                    ?>
                </div>

                <!-- Job-Specific Onboarding Stages -->
                <?php if (count($candidates_by_job) > 0): ?>
                    <?php foreach($candidates_by_job as $job_id => $job_data): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>
                                    <i class="fas fa-briefcase"></i> 
                                    <?php echo htmlspecialchars($job_data['job_title']); ?>
                                    <small class="text-muted">- <?php echo htmlspecialchars($job_data['department_name']); ?></small>
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- Dynamic Interview Stages -->
                                <?php 
                                $stage_colors = ['border-primary', 'border-info', 'border-warning', 'border-success', 'border-danger'];
                                $stage_icons = ['fas fa-file-alt', 'fas fa-stethoscope', 'fas fa-search', 'fas fa-graduation-cap', 'fas fa-clipboard-check'];
                                $stage_index = 0;
                                
                                if (isset($job_stages[$job_id])) {
                                    foreach ($job_stages[$job_id] as $stage):
                                        $stage_candidates = $job_data['stages'][$stage['stage_name']] ?? [];
                                        if (count($stage_candidates) > 0):
                                            $color = $stage_colors[$stage_index % count($stage_colors)];
                                            $icon = $stage_icons[$stage_index % count($stage_icons)];
                                ?>
                                    <h6><i class="<?php echo $icon; ?>"></i> <?php echo htmlspecialchars($stage['stage_name']); ?> (<?php echo count($stage_candidates); ?>)</h6>
                                    <div class="row mb-3">
                                        <?php foreach($stage_candidates as $candidate): ?>
                                            <div class="col-md-6 col-lg-4 mb-2">
                                                <div class="card <?php echo $color; ?>">
                                                    <div class="card-body p-2">
                                                        <h6 class="mb-1"><i class="fas fa-user"></i> <?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?></h6>
                                                        <p class="mb-1 small"><strong>Email:</strong> <?php echo htmlspecialchars($candidate['email']); ?></p>
                                                        <div class="btn-group w-100">
                                                            <form method="POST" style="display:inline;">
                                                                <input type="hidden" name="action" value="approve_stage">
                                                                <input type="hidden" name="candidate_id" value="<?php echo $candidate['candidate_id']; ?>">
                                                                <input type="hidden" name="application_id" value="<?php echo $candidate['application_id']; ?>">
                                                                <input type="hidden" name="stage_id" value="<?php echo $stage['stage_id']; ?>">
                                                                <button type="submit" class="btn btn-success btn-sm">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                            </form>
                                                            <form method="POST" style="display:inline;">
                                                                <input type="hidden" name="action" value="reject_candidate">
                                                                <input type="hidden" name="application_id" value="<?php echo $candidate['application_id']; ?>">
                                                                <input type="hidden" name="candidate_id" value="<?php echo $candidate['candidate_id']; ?>">
                                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Reject?')">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php 
                                        endif;
                                        $stage_index++;
                                    endforeach;
                                }
                                ?>


                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <h5><i class="fas fa-info-circle"></i> No Candidates in Onboarding Process</h5>
                        <p>Candidates will appear here after passing Assessment & Interview stage.</p>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>



    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>