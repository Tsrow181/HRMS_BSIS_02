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
            case 'create_offer':
                $candidate_id = $_POST['candidate_id'] ?? null;
                $employee_id = $_POST['employee_id'] ?? null;
                $job_opening_id = $_POST['job_opening_id'];
                $offer_type = $_POST['offer_type']; // 'hire' or 'promotion'
                $salary_offered = $_POST['salary_offered'];
                $start_date = $_POST['start_date'];
                $benefits = $_POST['benefits'];
                $notes = $_POST['notes'];
                
                $stmt = $conn->prepare("INSERT INTO job_offers (candidate_id, employee_id, job_opening_id, offer_type, salary_offered, start_date, benefits, notes, offer_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'Pending')");
                $stmt->execute([$candidate_id, $employee_id, $job_opening_id, $offer_type, $salary_offered, $start_date, $benefits, $notes]);
                break;
                
            case 'update_offer_status':
                $offer_id = $_POST['offer_id'];
                $status = $_POST['status'];
                
                $stmt = $conn->prepare("UPDATE job_offers SET status = ?, response_date = NOW() WHERE offer_id = ?");
                $stmt->execute([$status, $offer_id]);
                
                // If accepted, update candidate/employee status and create onboarding
                if ($status === 'Accepted') {
                    $offer = $conn->prepare("SELECT * FROM job_offers WHERE offer_id = ?");
                    $offer->execute([$offer_id]);
                    $offer_data = $offer->fetch(PDO::FETCH_ASSOC);
                    
                    try {
                        $conn->beginTransaction();
                        
                        if ($offer_data['offer_type'] === 'hire' && $offer_data['candidate_id']) {
                            // Update candidate status to hired
                            $stmt = $conn->prepare("UPDATE candidates SET source = 'Hired' WHERE candidate_id = ?");
                            $stmt->execute([$offer_data['candidate_id']]);
                            
                            // Create new employee record
                            $candidate = $conn->prepare("SELECT * FROM candidates WHERE candidate_id = ?");
                            $candidate->execute([$offer_data['candidate_id']]);
                            $candidate_data = $candidate->fetch(PDO::FETCH_ASSOC);
                            
                            $stmt = $conn->prepare("INSERT INTO employee_profiles (employee_number, hire_date, employment_status, current_salary, work_email) VALUES (?, ?, 'Full-time', ?, ?)");
                            $employee_number = 'EMP' . str_pad($offer_data['candidate_id'], 4, '0', STR_PAD_LEFT);
                            $stmt->execute([$employee_number, $offer_data['start_date'], $offer_data['salary_offered'], $candidate_data['email']]);
                            $new_employee_id = $conn->lastInsertId();
                            
                            // Create onboarding record for new hire
                            $completion_date = date('Y-m-d', strtotime($offer_data['start_date'] . ' +30 days'));
                            $stmt = $conn->prepare("INSERT INTO employee_onboarding (employee_id, start_date, expected_completion_date, status) VALUES (?, ?, ?, 'Pending')");
                            $stmt->execute([$new_employee_id, $offer_data['start_date'], $completion_date]);
                            
                        } elseif ($offer_data['offer_type'] === 'promotion' && $offer_data['employee_id']) {
                            // Update employee position (promotion accepted)
                            $job_query = $conn->prepare("SELECT title FROM job_openings WHERE job_opening_id = ?");
                            $job_query->execute([$offer_data['job_opening_id']]);
                            $job_title = $job_query->fetch(PDO::FETCH_ASSOC)['title'];
                            
                            $stmt = $conn->prepare("UPDATE employees SET current_position = ? WHERE employee_id = ?");
                            $stmt->execute([$job_title, $offer_data['employee_id']]);
                            
                            // Create employee onboarding for promotion (shorter process)
                            $completion_date = date('Y-m-d', strtotime($offer_data['start_date'] . ' +14 days'));
                            $stmt = $conn->prepare("INSERT INTO employee_onboarding (employee_id, start_date, expected_completion_date, status) VALUES (?, ?, ?, 'Pending')");
                            $stmt->execute([$offer_data['employee_id'], $offer_data['start_date'], $completion_date]);
                        }
                        
                        $conn->commit();
                    } catch (Exception $e) {
                        $conn->rollback();
                        throw $e;
                    }
                }
                break;
        }
        header('Location: job_offers_modern.php');
        exit;
    }
}

// Get job offers with details
try {
    $offers_query = "SELECT jo.*, 
                     CASE 
                        WHEN jo.candidate_id IS NOT NULL THEN CONCAT(c.first_name, ' ', c.last_name)
                        WHEN jo.employee_id IS NOT NULL THEN CONCAT(e.first_name, ' ', e.last_name)
                     END as recipient_name,
                     CASE 
                        WHEN jo.candidate_id IS NOT NULL THEN c.email
                        WHEN jo.employee_id IS NOT NULL THEN e.email
                     END as recipient_email,
                     job.title as job_title, d.department_name
                     FROM job_offers jo
                     LEFT JOIN candidates c ON jo.candidate_id = c.candidate_id
                     LEFT JOIN employees e ON jo.employee_id = e.employee_id
                     JOIN job_openings job ON jo.job_opening_id = job.job_opening_id
                     JOIN departments d ON job.department_id = d.department_id
                     ORDER BY jo.offer_date DESC";
    
    $offers = $conn->query($offers_query)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $offers = [];
}

// Get eligible candidates (those who passed interviews)
try {
    $candidates_query = "SELECT DISTINCT c.candidate_id, c.first_name, c.last_name, c.email,
                         'General Application' as job_title
                         FROM candidates c
                         WHERE c.source = 'Interview Passed' OR c.source = 'Hired'
                         ORDER BY c.first_name, c.last_name";
    $eligible_candidates = $conn->query($candidates_query)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $eligible_candidates = [];
}

// Get employees for promotions
try {
    $employees_query = "SELECT employee_id, first_name, last_name, email, current_position 
                        FROM employees 
                        WHERE status = 'Active' 
                        ORDER BY first_name, last_name";
    $employees = $conn->query($employees_query)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $employees = [];
}

// Get job openings
try {
    $job_openings = $conn->query("SELECT jo.job_opening_id, jo.title, d.department_name FROM job_openings jo JOIN departments d ON jo.department_id = d.department_id WHERE jo.status = 'Open' ORDER BY jo.title")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $job_openings = [];
}

// Get statistics
$stats = ['pending' => 0, 'accepted' => 0, 'hires' => 0, 'promotions' => 0];
try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM job_offers WHERE status = 'Pending'");
    $stats['pending'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    $stmt = $conn->query("SELECT COUNT(*) as count FROM job_offers WHERE status = 'Accepted'");
    $stats['accepted'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    $stmt = $conn->query("SELECT COUNT(*) as count FROM job_offers WHERE offer_type = 'hire'");
    $stats['hires'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    $stmt = $conn->query("SELECT COUNT(*) as count FROM job_offers WHERE offer_type = 'promotion'");
    $stats['promotions'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
} catch (Exception $e) {
    // Keep default values
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Offers - HR Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css?v=rose">
    <style>
        .offer-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(233, 30, 99, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .offer-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(233, 30, 99, 0.15);
        }
        
        .offer-header {
            background: linear-gradient(135deg, #F8BBD0 0%, #FCE4EC 100%);
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .offer-type-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .offer-type-hire {
            background: #28a745;
            color: white;
        }
        
        .offer-type-promotion {
            background: #007bff;
            color: white;
        }
        
        .status-pending { background: #ffc107; color: #000; }
        .status-accepted { background: #28a745; color: white; }
        .status-rejected { background: #dc3545; color: white; }
        .status-withdrawn { background: #6c757d; color: white; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <?php include 'navigation.php'; ?>
        <div class="row">
            <?php include 'sidebar.php'; ?>
            <div class="main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-handshake text-rose"></i> Job Offers Management</h2>
                    <button class="btn btn-rose" data-toggle="modal" data-target="#createOfferModal">
                        <i class="fas fa-plus"></i> Create Job Offer
                    </button>
                </div>

                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <h3><?php echo $stats['pending']; ?></h3>
                                <p>Pending Offers</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h3><?php echo $stats['accepted']; ?></h3>
                                <p>Accepted Offers</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h3><?php echo $stats['hires']; ?></h3>
                                <p>New Hires</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h3><?php echo $stats['promotions']; ?></h3>
                                <p>Promotions</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Job Offers List -->
                <div class="row">
                    <?php foreach ($offers as $offer): ?>
                    <div class="col-md-6">
                        <div class="offer-card">
                            <div class="offer-header">
                                <div>
                                    <h5 class="mb-1"><?php echo htmlspecialchars($offer['recipient_name']); ?></h5>
                                    <p class="mb-0 text-muted"><?php echo htmlspecialchars($offer['job_title']); ?></p>
                                    <small class="text-muted"><?php echo htmlspecialchars($offer['department_name']); ?></small>
                                </div>
                                <div class="text-right">
                                    <span class="offer-type-badge <?php echo $offer['offer_type'] === 'hire' ? 'offer-type-hire' : 'offer-type-promotion'; ?>">
                                        <?php echo ucfirst($offer['offer_type']); ?>
                                    </span>
                                    <br>
                                    <span class="status-badge status-<?php echo strtolower($offer['status']); ?>">
                                        <?php echo $offer['status']; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6">
                                        <strong>Salary Offered:</strong><br>
                                        ₱<?php echo number_format($offer['salary_offered'], 2); ?>
                                    </div>
                                    <div class="col-6">
                                        <strong>Start Date:</strong><br>
                                        <?php echo date('M d, Y', strtotime($offer['start_date'])); ?>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <strong>Benefits:</strong><br>
                                    <small><?php echo htmlspecialchars($offer['benefits']); ?></small>
                                </div>
                                <?php if ($offer['notes']): ?>
                                <div class="mt-2">
                                    <strong>Notes:</strong><br>
                                    <small><?php echo htmlspecialchars($offer['notes']); ?></small>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($offer['status'] === 'Pending'): ?>
                                <div class="mt-3">
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="update_offer_status">
                                        <input type="hidden" name="offer_id" value="<?php echo $offer['offer_id']; ?>">
                                        <button type="submit" name="status" value="Accepted" class="btn btn-success btn-sm">Accept</button>
                                        <button type="submit" name="status" value="Rejected" class="btn btn-danger btn-sm">Reject</button>
                                        <button type="submit" name="status" value="Withdrawn" class="btn btn-secondary btn-sm">Withdraw</button>
                                    </form>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Offer Modal -->
    <div class="modal fade" id="createOfferModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-rose text-white">
                    <h5 class="modal-title">Create Job Offer</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_offer">
                        
                        <div class="form-group">
                            <label>Offer Type</label>
                            <select name="offer_type" class="form-control" required onchange="toggleRecipientType(this.value)">
                                <option value="">Select Type</option>
                                <option value="hire">New Hire</option>
                                <option value="promotion">Employee Promotion</option>
                            </select>
                        </div>
                        
                        <div class="form-group" id="candidateGroup" style="display: none;">
                            <label>Select Candidate</label>
                            <select name="candidate_id" class="form-control">
                                <option value="">Select Candidate</option>
                                <?php foreach ($eligible_candidates as $candidate): ?>
                                <option value="<?php echo $candidate['candidate_id']; ?>">
                                    <?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name'] . ' - ' . $candidate['job_title']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" id="employeeGroup" style="display: none;">
                            <label>Select Employee</label>
                            <select name="employee_id" class="form-control">
                                <option value="">Select Employee</option>
                                <?php foreach ($employees as $employee): ?>
                                <option value="<?php echo $employee['employee_id']; ?>">
                                    <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name'] . ' - ' . $employee['current_position']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Job Position</label>
                            <select name="job_opening_id" class="form-control" required>
                                <option value="">Select Position</option>
                                <?php foreach ($job_openings as $job): ?>
                                <option value="<?php echo $job['job_opening_id']; ?>">
                                    <?php echo htmlspecialchars($job['title'] . ' - ' . $job['department_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Salary Offered</label>
                                    <input type="number" name="salary_offered" class="form-control" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Start Date</label>
                                    <input type="date" name="start_date" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Benefits Package</label>
                            <textarea name="benefits" class="form-control" rows="3" placeholder="Health insurance, vacation days, etc."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Additional Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-rose">Create Offer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function toggleRecipientType(type) {
            const candidateGroup = document.getElementById('candidateGroup');
            const employeeGroup = document.getElementById('employeeGroup');
            
            if (type === 'hire') {
                candidateGroup.style.display = 'block';
                employeeGroup.style.display = 'none';
                candidateGroup.querySelector('select').required = true;
                employeeGroup.querySelector('select').required = false;
            } else if (type === 'promotion') {
                candidateGroup.style.display = 'none';
                employeeGroup.style.display = 'block';
                candidateGroup.querySelector('select').required = false;
                employeeGroup.querySelector('select').required = true;
            } else {
                candidateGroup.style.display = 'none';
                employeeGroup.style.display = 'none';
                candidateGroup.querySelector('select').required = false;
                employeeGroup.querySelector('select').required = false;
            }
        }
    </script>
</body>
</html>