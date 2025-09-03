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
            case 'create_opening':
                $job_role_id = $_POST['job_role_id'];
                $department_id = $_POST['department_id'];
                $title = $_POST['title'];
                $description = $_POST['description'];
                $requirements = $_POST['requirements'];
                $responsibilities = $_POST['responsibilities'];
                $location = $_POST['location'];
                $employment_type = $_POST['employment_type'];
                $salary_min = $_POST['salary_min'];
                $salary_max = $_POST['salary_max'];
                $vacancy_count = $_POST['vacancy_count'];
                $closing_date = $_POST['closing_date'];
                $status = $_POST['status'];
                
                $stmt = $conn->prepare("INSERT INTO job_openings (job_role_id, department_id, title, description, requirements, responsibilities, location, employment_type, salary_range_min, salary_range_max, vacancy_count, posting_date, closing_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?)");
                $stmt->execute([$job_role_id, $department_id, $title, $description, $requirements, $responsibilities, $location, $employment_type, $salary_min, $salary_max, $vacancy_count, $closing_date, $status]);
                break;
                
            case 'update_status':
                $job_opening_id = $_POST['job_opening_id'];
                $new_status = $_POST['new_status'];
                $stmt = $conn->prepare("UPDATE job_openings SET status = ? WHERE job_opening_id = ?");
                $stmt->execute([$new_status, $job_opening_id]);
                break;
        }
        header('Location: job_openings.php');
        exit;
    }
}

// Get statistics
$stats = [];
$stmt = $conn->query("SELECT COUNT(*) as count FROM job_openings WHERE status = 'Draft'");
$stats['draft'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
$stmt = $conn->query("SELECT COUNT(*) as count FROM job_openings WHERE status = 'Open'");
$stats['open'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
$stmt = $conn->query("SELECT COUNT(*) as count FROM job_applications");
$stats['applications'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get job openings with department info and application counts
$job_openings_query = "SELECT jo.*, d.department_name, jr.title as role_title,
                       COUNT(ja.application_id) as total_applications,
                       SUM(CASE WHEN ja.status = 'Applied' THEN 1 ELSE 0 END) as pending_applications,
                       SUM(CASE WHEN ja.status = 'Interview' THEN 1 ELSE 0 END) as interview_stage,
                       SUM(CASE WHEN ja.status = 'Hired' THEN 1 ELSE 0 END) as hired_count
                       FROM job_openings jo 
                       JOIN departments d ON jo.department_id = d.department_id 
                       JOIN job_roles jr ON jo.job_role_id = jr.job_role_id 
                       LEFT JOIN job_applications ja ON jo.job_opening_id = ja.job_opening_id
                       GROUP BY jo.job_opening_id
                       ORDER BY jo.posting_date DESC";
$job_openings_result = $conn->query($job_openings_query);

// Get departments and job roles for form
$departments = $conn->query("SELECT * FROM departments ORDER BY department_name")->fetchAll(PDO::FETCH_ASSOC);
$job_roles = $conn->query("SELECT * FROM job_roles ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);
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
    <style>
        .section-title {
            color: var(--primary-color);
            margin-bottom: 30px;
            font-weight: 600;
        }
        .job-card {
            border-radius: 10px;
            transition: all 0.3s ease;
            border: 1px solid var(--border-light);
            margin-bottom: 20px;
        }
        .job-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--shadow-medium);
        }
        .job-card .card-body {
            padding: 20px;
        }
        .job-title {
            font-weight: 600;
            color: var(--text-dark);
        }
        .job-department {
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        .job-status {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 15px;
        }
        .status-open {
            background-color: var(--success-light);
            color: var(--success-dark);
        }
        .status-closed {
            background-color: var(--danger-light);
            color: var(--danger-dark);
        }
        .status-on-hold {
            background-color: var(--warning-light);
            color: var(--warning-dark);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <?php include 'navigation.php'; ?>
        <div class="row">
            <?php include 'sidebar.php'; ?>
            <div class="main-content">
                <!-- Dashboard Stats -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h4><?php echo $stats['draft']; ?></h4>
                                <p>Draft Openings</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h4><?php echo $stats['open']; ?></h4>
                                <p>Active Openings</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h4><?php echo $stats['applications']; ?></h4>
                                <p>Total Applications</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <button class="btn btn-light btn-sm" data-toggle="modal" data-target="#addJobModal">
                                    <i class="fas fa-plus"></i> Add Job Opening
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Job Openings List -->
                <div class="card">
                    <div class="card-header">
                        <h5>Job Openings Management</h5>
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
                                    <?php if ($job_openings_result->rowCount() > 0): ?>
                                        <?php while($row = $job_openings_result->fetch(PDO::FETCH_ASSOC)): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                                <td><?php echo htmlspecialchars($row['department_name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['role_title']); ?></td>
                                                <td><?php echo $row['vacancy_count']; ?></td>
                                                <td><?php echo date('M d, Y', strtotime($row['posting_date'])); ?></td>
                                                <td><?php echo $row['closing_date'] ? date('M d, Y', strtotime($row['closing_date'])) : 'No deadline'; ?></td>
                                                <td>
                                                    <span class="badge badge-<?php 
                                                        echo $row['status'] == 'Open' ? 'success' : 
                                                            ($row['status'] == 'Draft' ? 'warning' : 
                                                            ($row['status'] == 'Closed' ? 'danger' : 'secondary')); 
                                                    ?>">
                                                        <?php echo $row['status']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small>
                                                        Total: <strong><?php echo $row['total_applications']; ?></strong><br>
                                                        Pending: <?php echo $row['pending_applications']; ?><br>
                                                        Interview: <?php echo $row['interview_stage']; ?><br>
                                                        Hired: <?php echo $row['hired_count']; ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="btn-group-vertical btn-group-sm">
                                                        <a href="job_applications.php?job_id=<?php echo $row['job_opening_id']; ?>" class="btn btn-info btn-sm mb-1">
                                                            <i class="fas fa-users"></i> Applications
                                                        </a>
                                                        <?php if ($row['status'] == 'Draft'): ?>
                                                            <form method="POST" style="display:inline;">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="job_opening_id" value="<?php echo $row['job_opening_id']; ?>">
                                                                <input type="hidden" name="new_status" value="Open">
                                                                <button type="submit" class="btn btn-success btn-sm">Publish</button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <?php if ($row['status'] == 'Open'): ?>
                                                            <form method="POST" style="display:inline;">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="job_opening_id" value="<?php echo $row['job_opening_id']; ?>">
                                                                <input type="hidden" name="new_status" value="Closed">
                                                                <button type="submit" class="btn btn-danger btn-sm">Close</button>
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

    <!-- Add Job Opening Modal -->
    <div class="modal fade" id="addJobModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content" style="border-radius: 15px; border: none; box-shadow: 0 10px 30px rgba(233, 30, 99, 0.3);">
                <div class="modal-header" style="background: linear-gradient(135deg, #E91E63 0%, #F06292 100%); color: white; border-radius: 15px 15px 0 0; border-bottom: none;">
                    <h5 class="modal-title" style="font-weight: 600;"><i class="fas fa-briefcase mr-2"></i>Create Job Opening</h5>
                    <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 0.8; text-shadow: none;">
                        <span>&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body" style="padding: 30px;">
                        <input type="hidden" name="action" value="create_opening">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label style="color: #C2185B; font-weight: 600;">Department</label>
                                    <select name="department_id" class="form-control" style="border: 2px solid #F8BBD0; border-radius: 8px; padding: 12px;" required onchange="loadJobRoles(this.value)">
                                        <option value="">Select Department</option>
                                        <?php foreach($departments as $dept): ?>
                                            <option value="<?php echo $dept['department_id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label style="color: #C2185B; font-weight: 600;">Job Role</label>
                                    <select name="job_role_id" id="job_role_select" class="form-control" style="border: 2px solid #F8BBD0; border-radius: 8px; padding: 12px;" required>
                                        <option value="">Select Job Role</option>
                                        <?php foreach($job_roles as $role): ?>
                                            <option value="<?php echo $role['job_role_id']; ?>" data-department="<?php echo htmlspecialchars($role['department']); ?>"><?php echo htmlspecialchars($role['title']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label style="color: #C2185B; font-weight: 600;">Job Title</label>
                            <input type="text" name="title" class="form-control" style="border: 2px solid #F8BBD0; border-radius: 8px; padding: 12px;" required>
                        </div>
                        
                        <div class="form-group">
                            <label style="color: #C2185B; font-weight: 600;">Description</label>
                            <textarea name="description" class="form-control" rows="3" style="border: 2px solid #F8BBD0; border-radius: 8px; padding: 12px;" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label style="color: #C2185B; font-weight: 600;">Requirements</label>
                                    <textarea name="requirements" class="form-control" rows="3" style="border: 2px solid #F8BBD0; border-radius: 8px; padding: 12px;" required></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label style="color: #C2185B; font-weight: 600;">Responsibilities</label>
                                    <textarea name="responsibilities" class="form-control" rows="3" style="border: 2px solid #F8BBD0; border-radius: 8px; padding: 12px;" required></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label style="color: #C2185B; font-weight: 600;">Location</label>
                                    <input type="text" name="location" class="form-control" style="border: 2px solid #F8BBD0; border-radius: 8px; padding: 12px;" value="Municipal Office">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label style="color: #C2185B; font-weight: 600;">Employment Type</label>
                                    <select name="employment_type" class="form-control" style="border: 2px solid #F8BBD0; border-radius: 8px; padding: 12px;" required>
                                        <option value="Full-time">Full-time</option>
                                        <option value="Part-time">Part-time</option>
                                        <option value="Contract">Contract</option>
                                        <option value="Temporary">Temporary</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label style="color: #C2185B; font-weight: 600;">Vacancies</label>
                                    <input type="number" name="vacancy_count" class="form-control" style="border: 2px solid #F8BBD0; border-radius: 8px; padding: 12px;" value="1" min="1" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label style="color: #C2185B; font-weight: 600;">Min Salary (₱)</label>
                                    <input type="number" name="salary_min" class="form-control" style="border: 2px solid #F8BBD0; border-radius: 8px; padding: 12px;" step="0.01">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label style="color: #C2185B; font-weight: 600;">Max Salary (₱)</label>
                                    <input type="number" name="salary_max" class="form-control" style="border: 2px solid #F8BBD0; border-radius: 8px; padding: 12px;" step="0.01">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label style="color: #C2185B; font-weight: 600;">Closing Date</label>
                                    <input type="date" name="closing_date" class="form-control" style="border: 2px solid #F8BBD0; border-radius: 8px; padding: 12px;">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label style="color: #C2185B; font-weight: 600;">Status</label>
                            <select name="status" class="form-control" style="border: 2px solid #F8BBD0; border-radius: 8px; padding: 12px;" required>
                                <option value="Draft">Draft (Save for later)</option>
                                <option value="Open">Open (Publish immediately)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid #F8BBD0; padding: 20px 30px;">
                        <button type="button" class="btn btn-light" data-dismiss="modal" style="border: 2px solid #F8BBD0; color: #C2185B; font-weight: 600; border-radius: 25px; padding: 10px 25px;">Cancel</button>
                        <button type="submit" class="btn" style="background: linear-gradient(135deg, #E91E63 0%, #F06292 100%); color: white; border: none; font-weight: 600; border-radius: 25px; padding: 10px 25px;"><i class="fas fa-plus mr-2"></i>Create Job Opening</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
    function loadJobRoles(departmentId) {
        const roleSelect = document.getElementById('job_role_select');
        const options = roleSelect.querySelectorAll('option');
        
        // Reset and show all options first
        options.forEach(option => {
            if (option.value === '') {
                option.style.display = 'block';
                return;
            }
            option.style.display = 'block';
        });
        
        // If no department selected, show all roles
        if (!departmentId) {
            roleSelect.value = '';
            return;
        }
        
        // Find department name
        const deptSelect = document.querySelector('select[name="department_id"]');
        const selectedDept = deptSelect.options[deptSelect.selectedIndex].text;
        
        // Filter roles by department
        options.forEach(option => {
            if (option.value === '') return;
            
            const roleDept = option.getAttribute('data-department');
            if (roleDept && !roleDept.includes(selectedDept)) {
                option.style.display = 'none';
            }
        });
        
        roleSelect.value = '';
    }
    </script>
</body>
</html>
