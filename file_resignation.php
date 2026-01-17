<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!-- Debug: Script started -->\n";

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<!-- Debug: Session started -->\n";

// Check if user is logged in and is an employee
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo "<!-- Debug: User not logged in -->\n";
    header("location: login.php");
    exit;
}

if ($_SESSION['role'] !== 'employee') {
    echo "<!-- Debug: User is not employee, role: " . $_SESSION['role'] . " -->\n";
    header("location: login.php");
    exit;
}

echo "<!-- Debug: User verified as employee -->\n";

// Include database configuration
if (!file_exists('config.php')) {
    die("Error: config.php file not found!");
}

require_once 'config.php';

echo "<!-- Debug: Config loaded -->\n";

// Check database connection
if (!isset($conn)) {
    die("Error: Database connection not established!");
}

echo "<!-- Debug: Database connected -->\n";

// Initialize variables
$username = $_SESSION['username'] ?? 'Employee';
$user_id = $_SESSION['user_id'] ?? 0;
$employee_id = 0;
$success_message = '';
$error_message = '';
$employee = null;
$existing_resignation = null;

echo "<!-- Debug: User ID from session: " . $user_id . " -->\n";
echo "<!-- Debug: Username from session: " . $username . " -->\n";

// Get employee_id from the employees table using the username
if ($user_id > 0 || !empty($username)) {
    try {
        // First try to find employee by username
        $lookup_query = "SELECT employee_id, first_name, last_name, employee_code, job_title, department_id, email, username 
                        FROM employees 
                        WHERE username = ? OR email = ?";
        
        if ($lookup_stmt = $conn->prepare($lookup_query)) {
            $lookup_stmt->bind_param("ss", $username, $username);
            $lookup_stmt->execute();
            $lookup_result = $lookup_stmt->get_result();
            
            if ($lookup_row = $lookup_result->fetch_assoc()) {
                $employee_id = $lookup_row['employee_id'];
                // Store employee_id in session for future use
                $_SESSION['employee_id'] = $employee_id;
                echo "<!-- Debug: Found employee_id: " . $employee_id . " for username: " . $username . " -->\n";
            } else {
                echo "<!-- Debug: No employee found for username: " . $username . " -->\n";
            }
            $lookup_stmt->close();
        }
    } catch (Exception $e) {
        echo "<!-- Debug: Error finding employee: " . $e->getMessage() . " -->\n";
    }
}

if ($employee_id == 0) {
    $error_message = "Could not find your employee record. Please contact HR to link your account.";
    echo "<!-- Debug: employee_id is 0, cannot proceed -->\n";
}

// Get employee details
if ($employee_id > 0) {
    try {
        $employee_query = "SELECT e.first_name, e.last_name, e.employee_code, e.job_title, e.department_id, d.department_name 
                           FROM employees e 
                           LEFT JOIN departments d ON e.department_id = d.department_id 
                           WHERE e.employee_id = ?";
        
        if ($stmt = $conn->prepare($employee_query)) {
            $stmt->bind_param("i", $employee_id);
            $stmt->execute();
            $employee_result = $stmt->get_result();
            $employee = $employee_result->fetch_assoc();
            $stmt->close();
            echo "<!-- Debug: Employee data loaded -->\n";
        } else {
            $error_message = "Database error preparing employee query: " . $conn->error;
            echo "<!-- Debug: " . $error_message . " -->\n";
        }
    } catch (Exception $e) {
        $error_message = "Error loading employee data: " . $e->getMessage();
        echo "<!-- Debug: " . $error_message . " -->\n";
    }
}

// Check if employee already has a pending or processing resignation
if ($employee_id > 0) {
    try {
        $check_query = "SELECT * FROM exits WHERE employee_id = ? AND status IN ('Pending', 'Processing') ORDER BY created_at DESC LIMIT 1";
        
        if ($check_stmt = $conn->prepare($check_query)) {
            $check_stmt->bind_param("i", $employee_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $existing_resignation = $check_result->fetch_assoc();
            $check_stmt->close();
            echo "<!-- Debug: Resignation check complete -->\n";
        } else {
            echo "<!-- Debug: Error preparing resignation check: " . $conn->error . " -->\n";
        }
    } catch (Exception $e) {
        echo "<!-- Debug: Exception checking resignation: " . $e->getMessage() . " -->\n";
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && !$existing_resignation) {
    echo "<!-- Debug: Processing form submission -->\n";
    
    $exit_type = trim($_POST['exit_type'] ?? '');
    $notice_date = trim($_POST['notice_date'] ?? '');
    $exit_date = trim($_POST['exit_date'] ?? '');
    $exit_reason = trim($_POST['exit_reason'] ?? '');
    
    // Validate inputs
    if (empty($exit_type) || empty($notice_date) || empty($exit_date) || empty($exit_reason)) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Validate dates
        $today = date('Y-m-d');
        if ($notice_date < $today) {
            $error_message = "Notice date cannot be in the past.";
        } elseif ($exit_date <= $notice_date) {
            $error_message = "Exit date (last working day) must be after notice date.";
        } else {
            try {
                // Insert resignation into exits table
                $insert_query = "INSERT INTO exits (employee_id, exit_type, exit_reason, notice_date, exit_date, status) 
                               VALUES (?, ?, ?, ?, ?, 'Pending')";
                
                if ($insert_stmt = $conn->prepare($insert_query)) {
                    $insert_stmt->bind_param("issss", $employee_id, $exit_type, $exit_reason, $notice_date, $exit_date);
                    
                    if ($insert_stmt->execute()) {
                        $success_message = "Your resignation has been filed successfully. HR will review it shortly.";
                        $insert_stmt->close();
                        echo "<!-- Debug: Resignation submitted successfully -->\n";
                        // Refresh to show the pending status
                        header("refresh:2;url=file_resignation.php");
                    } else {
                        $error_message = "Error filing resignation: " . $insert_stmt->error;
                        echo "<!-- Debug: " . $error_message . " -->\n";
                    }
                } else {
                    $error_message = "Database error: " . $conn->error;
                    echo "<!-- Debug: " . $error_message . " -->\n";
                }
            } catch (Exception $e) {
                $error_message = "Error submitting resignation: " . $e->getMessage();
                echo "<!-- Debug: " . $error_message . " -->\n";
            }
        }
    }
}

// Get resignation types
$resignation_types = [
    'Resignation',
    'Retirement',
    'End of Contract'
];

echo "<!-- Debug: Ready to render HTML -->\n";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Resignation - HRMS</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        body {
            background-color: #f8f9fa;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 30px;
        }
        
        .resignation-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .page-header {
            background: linear-gradient(135deg, #E91E63 0%, #C2185B 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .form-group label {
            font-weight: 600;
            color: #333;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #E91E63 0%, #C2185B 100%);
            color: white;
            padding: 12px 40px;
            border: none;
            border-radius: 5px;
            font-weight: 600;
        }
        
        .btn-submit:hover {
            background: linear-gradient(135deg, #C2185B 0%, #AD1457 100%);
            color: white;
        }
        
        .alert-warning-custom {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
        }
        
        .pending-resignation {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .processing-resignation {
            background: #cfe2ff;
            border-left: 4px solid #0d6efd;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
        }
    </style>
</head>
<body class="employee-page">
    
    <?php 
    echo "<!-- Debug: About to include sidebar -->\n";
    include 'employee_sidebar.php'; 
    echo "<!-- Debug: Sidebar included -->\n";
    ?>
    
    <div class="main-content">
        <div class="page-header">
            <h2><i class="fas fa-sign-out-alt"></i> File Resignation</h2>
            <p class="mb-0">Submit your resignation request</p>
        </div>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php endif; ?>
        
        <?php if (!$employee && $employee_id > 0): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> Unable to load employee information. Please contact HR.
            </div>
        <?php elseif ($existing_resignation): ?>
            <div class="<?php echo $existing_resignation['status'] == 'Pending' ? 'pending-resignation' : 'processing-resignation'; ?>">
                <h5>
                    <i class="fas fa-info-circle"></i> 
                    <?php 
                    if ($existing_resignation['status'] == 'Pending') {
                        echo 'Resignation Pending Review';
                    } else {
                        echo 'Resignation Being Processed';
                    }
                    ?>
                </h5>
                <p class="mb-2"><strong>Type:</strong> <?php echo htmlspecialchars($existing_resignation['exit_type']); ?></p>
                <p class="mb-2"><strong>Notice Date:</strong> <?php echo date('F d, Y', strtotime($existing_resignation['notice_date'])); ?></p>
                <p class="mb-2"><strong>Last Working Day:</strong> <?php echo date('F d, Y', strtotime($existing_resignation['exit_date'])); ?></p>
                <p class="mb-2"><strong>Reason:</strong> <?php echo nl2br(htmlspecialchars($existing_resignation['exit_reason'])); ?></p>
                <p class="mb-2"><strong>Status:</strong> 
                    <span class="badge badge-<?php 
                        echo $existing_resignation['status'] == 'Pending' ? 'warning' : 
                            ($existing_resignation['status'] == 'Processing' ? 'info' : 'success'); 
                    ?>">
                        <?php echo htmlspecialchars($existing_resignation['status']); ?>
                    </span>
                </p>
                <?php if ($existing_resignation['status'] == 'Pending'): ?>
                    <p class="mb-0 mt-3"><em>Your resignation is currently under review by HR. You will be notified once it's processed.</em></p>
                <?php elseif ($existing_resignation['status'] == 'Processing'): ?>
                    <p class="mb-0 mt-3"><em>Your resignation is being processed. HR will contact you regarding the exit procedures.</em></p>
                <?php endif; ?>
            </div>
        <?php elseif ($employee): ?>
            
            <div class="resignation-card">
                <div class="alert-warning-custom mb-4">
                    <h6><i class="fas fa-exclamation-triangle"></i> Important Notice</h6>
                    <p class="mb-0">Please carefully review all information before submitting your resignation. This action will notify HR and initiate the exit process.</p>
                </div>
                
                <form method="POST" action="" id="resignationForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Employee Name</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? '')); ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Employee ID</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($employee['employee_code'] ?? 'N/A'); ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Department</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($employee['department_name'] ?? 'N/A'); ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Position</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($employee['job_title'] ?? 'N/A'); ?>" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h5 class="mb-3">Resignation Details</h5>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Resignation Type <span class="text-danger">*</span></label>
                                <select class="form-control" name="exit_type" required>
                                    <option value="">-- Select Type --</option>
                                    <?php foreach ($resignation_types as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Notice Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="notice_date" 
                                       min="<?php echo date('Y-m-d'); ?>" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                                <small class="form-text text-muted">The date you are submitting this resignation</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Last Working Day (Exit Date) <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="exit_date" 
                                       min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                                <small class="form-text text-muted">Your intended last day at work</small>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Reason for Leaving <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="exit_reason" rows="5" 
                                          placeholder="Please provide your reason for resignation..." required></textarea>
                                <small class="form-text text-muted">Please be as detailed as possible</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-check mb-4">
                        <input type="checkbox" class="form-check-input" id="confirmCheck" required>
                        <label class="form-check-label" for="confirmCheck">
                            I confirm that the information provided is accurate and I understand that this resignation will be reviewed by HR.
                        </label>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" class="btn btn-submit">
                            <i class="fas fa-paper-plane"></i> Submit Resignation
                        </button>
                        <a href="employee_index.php" class="btn btn-secondary ml-2">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
            
        <?php endif; ?>
    </div>
    
    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        console.log('Script loaded');
        
        // Form validation
        var form = document.getElementById('resignationForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                var noticeDate = new Date(document.querySelector('input[name="notice_date"]').value);
                var exitDate = new Date(document.querySelector('input[name="exit_date"]').value);
                
                if (exitDate <= noticeDate) {
                    e.preventDefault();
                    alert('Last working day (Exit Date) must be after the notice date.');
                    return false;
                }
                
                if (!document.getElementById('confirmCheck').checked) {
                    e.preventDefault();
                    alert('Please confirm the information before submitting.');
                    return false;
                }
                
                return confirm('Are you sure you want to submit this resignation? This action cannot be undone.');
            });
        }
        
        // Auto-set minimum exit date based on notice date
        var noticeDateInput = document.querySelector('input[name="notice_date"]');
        if (noticeDateInput) {
            noticeDateInput.addEventListener('change', function() {
                var selectedDate = new Date(this.value);
                selectedDate.setDate(selectedDate.getDate() + 1);
                var minExitDate = selectedDate.toISOString().split('T')[0];
                document.querySelector('input[name="exit_date"]').min = minExitDate;
            });
        }
    </script>
</body>
</html>