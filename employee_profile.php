<?php
session_start();

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Include database connection
require_once 'dp.php';

// Test database connection
$dbConnected = false;
try {
    global $conn;
    $conn->query("SELECT 1");
    $dbConnected = true;
} catch (PDOException $e) {
    $dbConnected = false;
}

// Fetch employee profiles if DB connected
$employeeProfiles = [];
if ($dbConnected) {
    $sql = "SELECT ep.employee_number, pi.first_name, pi.last_name, jr.title AS job_role, d.department_name, ep.work_email, ep.work_phone, ep.location, ep.hire_date, ep.employment_status, ep.current_salary
            FROM employee_profiles ep
            LEFT JOIN personal_information pi ON ep.personal_info_id = pi.personal_info_id
            LEFT JOIN job_roles jr ON ep.job_role_id = jr.job_role_id
            LEFT JOIN departments d ON jr.department = d.department_name";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $employeeProfiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Profiles</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f5f5;
            margin: 0;
            padding: 0;
        }
        .sidebar {
            height: 100vh;
            background-color: #800000;
            color: #fff;
            padding-top: 20px;
            position: fixed;
            width: 250px;
            transition: all 0.3s;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #fff #800000;
            z-index: 1030;
        }
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        .sidebar::-webkit-scrollbar-track {
            background: #800000;
        }
        .sidebar::-webkit-scrollbar-thumb {
            background-color: #fff;
            border-radius: 3px;
        }
        .sidebar::-webkit-scrollbar-thumb:hover {
            background-color: #f0f0f0;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 5px;
            padding: 12px 20px;
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: #fff;
        }
        .sidebar .nav-link.active {
            background-color: #fff;
            color: #800000;
        }
        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        .dropdown-menu {
            background-color: #ffffff;
            border: none;
            border-radius: 4px;
            padding-left: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .dropdown-menu .dropdown-item {
            color: #666;
            padding: 10px 20px;
            transition: all 0.3s;
        }
        .dropdown-menu .dropdown-item:hover {
            background-color: #fff0f0;
            color: #800000;
        }
        .main-content {
            margin-left: 250px;
            padding: 90px 20px 20px;
            transition: margin-left 0.3s;
            width: calc(100% - 250px);
        }
        .card {
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(128, 0, 0, 0.05);
            border: none;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(128, 0, 0, 0.1);
            padding: 15px 20px;
            font-weight: bold;
            color: #800000;
        }
        .card-header i {
            color: #800000;
        }
        .card-body {
            padding: 20px;
        }
        .table th {
            border-top: none;
            color: #800000;
            font-weight: 600;
        }
        .table td {
            vertical-align: middle;
            color: #333;
            border-color: rgba(128, 0, 0, 0.1);
        }
        .badge-status {
            background-color: #800000;
            color: #fff;
            font-size: 0.85rem;
            padding: 5px 10px;
            border-radius: 12px;
        }
        .badge-active {
            background-color: #28a745;
        }
        .badge-inactive {
            background-color: #dc3545;
        }
        .badge-pending {
            background-color: #ffc107;
            color: #212529;
        }
        /* Navigation Bar Styles */
        .top-navbar {
            background: #fff;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(128, 0, 0, 0.1);
            position: fixed;
            top: 0;
            right: 0;
            left: 250px;
            z-index: 1020;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }
        .nav-item .dropdown-menu {
            position: absolute;
            right: 0;
            left: auto;
        }
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            padding: 3px 6px;
            border-radius: 50%;
            background: #800000;
            color: white;
            font-size: 0.7rem;
        }
        .profile-image {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .nav-link-custom {
            color: #800000;
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
            position: relative;
        }
        .nav-link-custom:hover {
            color: #600000;
            text-decoration: none;
        }
        .dropdown-header {
            color: #800000;
            font-weight: 600;
        }
        .dropdown-divider {
            border-top-color: #ffe6e6;
        }
        .dropdown-item.text-center {
            color: #800000;
        }
        .dropdown-item.text-center:hover {
            background-color: #fff0f0;
        }
        .section-title {
            color: #800000;
            margin-bottom: 25px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <?php include 'navigation.php'; ?>
        <div class="row">
            <?php include 'sidebar.php'; ?>
            <div class="main-content">
                <h2 class="section-title">Employee Profiles</h2>
                <?php if (!$dbConnected): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <strong>Database Connection Issue:</strong> The system is running in demo mode. Some features may not work properly.
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php endif; ?>
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-users mr-2"></i> Employee List</span>
                        <a href="add_employee.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Employee</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Employee Number</th>
                                        <th>Name</th>
                                        <th>Job Role</th>
                                        <th>Department</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Location</th>
                                        <th>Hire Date</th>
                                        <th>Status</th>
                                        <th>Current Salary</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($dbConnected && count($employeeProfiles) > 0): ?>
                                        <?php foreach ($employeeProfiles as $emp): ?>
                                            <tr id="row-<?php echo htmlspecialchars($emp['employee_number']); ?>">
                                                <td><?php echo htmlspecialchars($emp['employee_number']); ?></td>
                                                <td><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($emp['job_role']); ?></td>
                                                <td><?php echo htmlspecialchars($emp['department_name']); ?></td>
                                                <td><?php echo htmlspecialchars($emp['work_email']); ?></td>
                                                <td><?php echo htmlspecialchars($emp['work_phone']); ?></td>
                                                <td><?php echo htmlspecialchars($emp['location']); ?></td>
                                                <td><?php echo htmlspecialchars($emp['hire_date']); ?></td>
                                                <td>
                                                    <?php 
                                                    $status = strtolower($emp['employment_status']);
                                                    $badgeClass = 'badge-status';
                                                    if ($status === 'active') {
                                                        $badgeClass .= ' badge-active';
                                                    } elseif ($status === 'inactive' || $status === 'terminated') {
                                                        $badgeClass .= ' badge-inactive';
                                                    } elseif ($status === 'pending') {
                                                        $badgeClass .= ' badge-pending';
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $badgeClass; ?>">
                                                        <?php echo htmlspecialchars(ucfirst($emp['employment_status'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars(number_format($emp['current_salary'], 2)); ?></td>
                                                <td>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton-<?php echo htmlspecialchars($emp['employee_number']); ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                            <i class="fas fa-ellipsis-v"></i>
                                                        </button>
                                                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton-<?php echo htmlspecialchars($emp['employee_number']); ?>">
                                                            <a class="dropdown-item" href="view_employee.php?employee_number=<?php echo urlencode($emp['employee_number']); ?>">
                                                                <i class="fas fa-eye mr-2"></i>View
                                                            </a>
                                                            <a class="dropdown-item" href="edit_employee.php?employee_number=<?php echo urlencode($emp['employee_number']); ?>">
                                                                <i class="fas fa-edit mr-2"></i>Edit
                                                            </a>
                                                            <div class="dropdown-divider"></div>
                                                            <form method="post" action="delete_employee.php" onsubmit="return confirm('Are you sure you want to delete this employee?');" style="display: inline;">
                                                                <input type="hidden" name="employee_number" value="<?php echo htmlspecialchars($emp['employee_number']); ?>">
                                                                <button type="submit" class="dropdown-item text-danger" style="cursor: pointer; border: none; background: none; width: 100%; text-align: left;">
                                                                    <i class="fas fa-trash-alt mr-2"></i>Delete
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php elseif ($dbConnected): ?>
                                        <tr><td colspan="11" class="text-center">No employee profiles found.</td></tr>
                                    <?php else: ?>
                                        <tr><td colspan="11" class="text-center">Database not connected</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Simple script initialization - matches dashboard functionality
        $(document).ready(function() {
            // Initialize any necessary components here
            console.log('Employee Profiles page loaded');
        });
    </script>
</body>
</html>