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
        switch ($_POST['action']) {
            case 'create_opening':
                // Set default closing date if not provided
                $closing_date = $_POST['closing_date'] ?: date('Y-m-d', strtotime('+30 days'));
                
                $stmt = $conn->prepare("INSERT INTO job_openings (job_role_id, department_id, title, description, requirements, responsibilities, location, employment_type, salary_range_min, salary_range_max, vacancy_count, posting_date, closing_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?)");
                $stmt->execute([$_POST['job_role_id'], $_POST['department_id'], $_POST['title'], $_POST['description'], $_POST['requirements'], $_POST['responsibilities'], $_POST['location'], $_POST['employment_type'], $_POST['salary_min'], $_POST['salary_max'], $_POST['vacancy_count'], $closing_date, $_POST['status']]);
                $success_message = "✨ Job opening '" . htmlspecialchars($_POST['title']) . "' created successfully!";
                break;
            case 'update_status':
                if ($_POST['new_status'] == 'Closed') {
                    // Check for pending applications
                    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM job_applications WHERE job_opening_id = ? AND status IN ('Applied', 'Approved', 'Interview')");
                    $check_stmt->execute([$_POST['job_opening_id']]);
                    $pending_count = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    
                    if ($pending_count > 0) {
                        $success_message = "⚠️ Cannot close job! There are " . $pending_count . " pending applications that need to be processed first.";
                        break;
                    }
                }
                
                $stmt = $conn->prepare("UPDATE job_openings SET status = ? WHERE job_opening_id = ?");
                $stmt->execute([$_POST['new_status'], $_POST['job_opening_id']]);
                $emoji = $_POST['new_status'] == 'Open' ? '🚀' : '🚫';
                $success_message = $emoji . " Job status updated to " . $_POST['new_status'] . " successfully!";
                break;
        }
    }
}

// Check and auto-close filled positions
$conn->exec("UPDATE job_openings jo SET status = 'Closed', closing_date = CURDATE() WHERE jo.status = 'Open' AND (SELECT COUNT(*) FROM job_applications ja WHERE ja.job_opening_id = jo.job_opening_id AND ja.status = 'Hired') >= jo.vacancy_count");

$stats = [];
$stmt = $conn->query("SELECT COUNT(*) as count FROM job_openings WHERE status = 'Draft'");
$stats['draft'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
$stmt = $conn->query("SELECT COUNT(*) as count FROM job_openings WHERE status = 'Open'");
$stats['open'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
$stmt = $conn->query("SELECT COUNT(*) as count FROM job_applications");
$stats['applications'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$job_openings_query = "SELECT jo.*, 
                       COALESCE(d.department_name, 'Unknown Department') as department_name, 
                       COALESCE(jr.title, 'Unknown Role') as role_title,
                       COUNT(ja.application_id) as total_applications,
                       SUM(CASE WHEN ja.status = 'Applied' THEN 1 ELSE 0 END) as pending_applications,
                       SUM(CASE WHEN ja.status = 'Interview' THEN 1 ELSE 0 END) as interview_stage,
                       SUM(CASE WHEN ja.status = 'Hired' THEN 1 ELSE 0 END) as hired_count
                       FROM job_openings jo 
                       LEFT JOIN departments d ON jo.department_id = d.department_id 
                       LEFT JOIN job_roles jr ON jo.job_role_id = jr.job_role_id 
                       LEFT JOIN job_applications ja ON jo.job_opening_id = ja.job_opening_id
                       WHERE jo.status != 'Archived'
                       GROUP BY jo.job_opening_id
                       ORDER BY jo.posting_date DESC";
$job_openings_result = $conn->query($job_openings_query);

try {
    $departments = $conn->query("SELECT * FROM departments ORDER BY department_name")->fetchAll(PDO::FETCH_ASSOC);
    $job_roles = $conn->query("SELECT * FROM job_roles ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $departments = [];
    $job_roles = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Openings - HR Management System</title>
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
                <h2>💼 Job Openings Management</h2>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert <?php echo strpos($success_message, 'Cannot') !== false ? 'alert-warning' : 'alert-success'; ?> alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stats-card card">
                            <div class="card-body text-center">
                                <div class="activity-icon bg-warning">
                                    <i class="fas fa-edit"></i>
                                </div>
                                <h3 class="stats-number"><?php echo $stats['draft']; ?></h3>
                                <p class="stats-label">Draft Openings</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card card">
                            <div class="card-body text-center">
                                <div class="activity-icon bg-success">
                                    <i class="fas fa-rocket"></i>
                                </div>
                                <h3 class="stats-number"><?php echo $stats['open']; ?></h3>
                                <p class="stats-label">Active Openings</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card card">
                            <div class="card-body text-center">
                                <div class="activity-icon bg-info">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h3 class="stats-number"><?php echo $stats['applications']; ?></h3>
                                <p class="stats-label">Total Applications</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">💼 Job Openings Management</h5>
                        <div class="d-flex">
                            <input type="text" id="searchJobs" class="form-control mr-2" placeholder="🔍 Search jobs..." style="width: 200px;">
                            <button class="btn btn-secondary btn-sm mr-2" id="toggleClosed">👁️ Show Closed</button>
                            <button class="btn btn-primary" data-toggle="modal" data-target="#addJobModal">✨ Create Job</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Department</th>
                                        <th>Role</th>
                                        <th>Vacancies</th>
                                        <th>Posted</th>
                                        <th>Closing</th>
                                        <th>Status</th>
                                        <th>Applications</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($job_openings_result && $job_openings_result->rowCount() > 0): ?>
                                        <?php while($row = $job_openings_result->fetch(PDO::FETCH_ASSOC)): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                                <td><?php echo htmlspecialchars($row['department_name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['role_title']); ?></td>
                                                <td><?php echo $row['vacancy_count']; ?></td>
                                                <td><?php echo date('M d, Y', strtotime($row['posting_date'])); ?></td>
                                                <td><?php echo $row['closing_date'] ? date('M d, Y', strtotime($row['closing_date'])) : 'No deadline'; ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $row['status'] == 'Open' ? 'success' : ($row['status'] == 'Draft' ? 'warning' : 'danger'); ?>">
                                                        <?php 
                                                        $emoji = $row['status'] == 'Open' ? '🚀' : ($row['status'] == 'Draft' ? '📝' : '🚫');
                                                        echo $emoji . ' ' . $row['status']; 
                                                        ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small>
                                                        📈 Total: <strong><?php echo $row['total_applications']; ?></strong><br>
                                                        ⏳ Pending: <?php echo $row['pending_applications']; ?><br>
                                                        💬 Interview: <?php echo $row['interview_stage']; ?><br>
                                                        ✅ Hired: <?php echo $row['hired_count']; ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-column" style="min-width: 120px;">
                                                        <a href="job_applications.php?job_id=<?php echo $row['job_opening_id']; ?>" class="btn btn-info btn-sm mb-1 text-left">👥 Applications</a>
                                                        <?php if ($row['status'] == 'Draft'): ?>
                                                            <form method="POST" class="mb-1" onsubmit="return confirm('🚀 Publish this job opening?')">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="job_opening_id" value="<?php echo $row['job_opening_id']; ?>">
                                                                <input type="hidden" name="new_status" value="Open">
                                                                <button type="submit" class="btn btn-success btn-sm w-100 text-left">🚀 Publish</button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <?php if ($row['status'] == 'Open'): ?>
                                                            <form method="POST" class="mb-1" onsubmit="return confirm('🚫 Close this job opening?')">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="job_opening_id" value="<?php echo $row['job_opening_id']; ?>">
                                                                <input type="hidden" name="new_status" value="Closed">
                                                                <button type="submit" class="btn btn-danger btn-sm w-100 text-left">🚫 Close</button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <?php if ($row['status'] == 'Closed'): ?>
                                                            <form method="POST" class="mb-1" onsubmit="return confirm('🔄 Reopen this job opening?')">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="job_opening_id" value="<?php echo $row['job_opening_id']; ?>">
                                                                <input type="hidden" name="new_status" value="Open">
                                                                <button type="submit" class="btn btn-warning btn-sm w-100 text-left">🔄 Reopen</button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center">No job openings found.</td>
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

    <div class="modal fade" id="addJobModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">💼 Create Job Opening</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form method="POST" id="jobForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_opening">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Department *</label>
                                    <select name="department_id" id="department_select" class="form-control" required>
                                        <option value="">Select Department</option>
                                        <?php foreach($departments as $dept): ?>
                                            <option value="<?php echo $dept['department_id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Job Role *</label>
                                    <select name="job_role_id" id="job_role_select" class="form-control" required>
                                        <option value="">Select Job Role</option>
                                        <?php foreach($job_roles as $role): ?>
                                            <option value="<?php echo $role['job_role_id']; ?>" data-department="<?php echo $role['department']; ?>"><?php echo htmlspecialchars($role['title']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Job Title *</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Description *</label>
                            <textarea name="description" class="form-control" rows="3" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Requirements *</label>
                                    <textarea name="requirements" class="form-control" rows="3" required></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Responsibilities *</label>
                                    <textarea name="responsibilities" class="form-control" rows="3" required></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Location *</label>
                                    <input type="text" name="location" class="form-control" value="Municipal Office" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Employment Type *</label>
                                    <select name="employment_type" class="form-control" required>
                                        <option value="">Select Type</option>
                                        <option value="Full-time">Full-time</option>
                                        <option value="Part-time">Part-time</option>
                                        <option value="Contract">Contract</option>
                                        <option value="Temporary">Temporary</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Vacancies *</label>
                                    <input type="number" name="vacancy_count" class="form-control" value="1" min="1" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Min Salary (₱)</label>
                                    <input type="number" name="salary_min" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Max Salary (₱)</label>
                                    <input type="number" name="salary_max" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Closing Date</label>
                                    <input type="date" name="closing_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                                    <small class="text-muted">Default: 30 days from today</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Status *</label>
                            <select name="status" class="form-control" required>
                                <option value="">Select Status</option>
                                <option value="Draft">Draft (Save for later)</option>
                                <option value="Open">Open (Publish immediately)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">✨ Create Job Opening</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
    $(document).ready(function(){
        // Hide closed jobs by default
        $('tbody tr').each(function(){
            if($(this).find('.badge-danger').length > 0) {
                $(this).hide();
            }
        });
        
        $('#toggleClosed').on('click', function(){
            var closedRows = $('tbody tr').filter(function(){
                return $(this).find('.badge-danger').length > 0;
            });
            
            if(closedRows.is(':visible')) {
                closedRows.hide();
                $(this).text('👁️ Show Closed');
            } else {
                closedRows.show();
                $(this).text('🙈 Hide Closed');
            }
        });
        
        $('#searchJobs').on('keyup', function(){
            var value = $(this).val().toLowerCase();
            $('tbody tr').filter(function(){
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
        
        $('#department_select').on('change', function(){
            var selectedDept = $(this).find('option:selected').text();
            var roleSelect = $('#job_role_select');
            
            roleSelect.find('option').each(function(){
                if($(this).val() === '') {
                    $(this).show();
                } else {
                    var roleDept = $(this).data('department');
                    if(selectedDept === '' || roleDept === selectedDept) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                }
            });
            
            roleSelect.val('');
        });
        
        $('#jobForm').on('submit', function(e){
            var salaryMin = parseFloat($('input[name="salary_min"]').val()) || 0;
            var salaryMax = parseFloat($('input[name="salary_max"]').val()) || 0;
            
            if(salaryMin > 0 && salaryMax > 0 && salaryMin >= salaryMax) {
                e.preventDefault();
                alert('Maximum salary must be greater than minimum salary.');
                return false;
            }
        });
    });
    </script>
</body>
</html>