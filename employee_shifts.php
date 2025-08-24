<?php
session_start();

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Include database connection
require_once 'dp.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Shifts - HR Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css?v=rose">
    <style>
        .section-title {
            color: var(--primary-color);
            margin-bottom: 30px;
            font-weight: 600;
        }
        
        .employee-shift-card {
            border-radius: 10px;
            transition: all 0.3s ease;
            border: 1px solid var(--border-light);
        }
        
        .employee-shift-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--shadow-medium);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <?php include 'navigation.php'; ?>
        <div class="row">
            <?php include 'sidebar.php'; ?>
            <div class="main-content">
                <h2 class="section-title">Employee Shifts Management</h2>
                
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-user-clock mr-2"></i>Employee Shifts Overview</h5>
                                <button class="btn btn-primary" data-toggle="modal" data-target="#addEmployeeShiftModal">
                                    <i class="fas fa-plus mr-2"></i>Add Employee Shift
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Employee</th>
                                                <th>Shift</th>
                                                <th>Start Time</th>
                                                <th>End Time</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="https://ui-avatars.com/api/?name=John+Doe&background=E91E63&color=fff&size=35" 
                                                             alt="Profile" class="profile-image mr-2">
                                                        <div>
                                                            <h6 class="mb-0">John Doe</h6>
                                                            <small class="text-muted">IT Department</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>Morning Shift</td>
                                                <td>08:00 AM</td>
                                                <td>04:00 PM</td>
                                                <td><span class="badge badge-success">Active</span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary mr-2">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="https://ui-avatars.com/api/?name=Jane+Smith&background=E91E63&color=fff&size=35" 
                                                             alt="Profile" class="profile-image mr-2">
                                                        <div>
                                                            <h6 class="mb-0">Jane Smith</h6>
                                                            <small class="text-muted">HR Department</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>Evening Shift</td>
                                                <td>04:00 PM</td>
                                                <td>12:00 AM</td>
                                                <td><span class="badge badge-success">Active</span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary mr-2">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="https://ui-avatars.com/api/?name=Mike+Johnson&background=E91E63&color=fff&size=35" 
                                                             alt="Profile" class="profile-image mr-2">
                                                        <div>
                                                            <h6 class="mb-0">Mike Johnson</h6>
                                                            <small class="text-muted">Finance</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>Night Shift</td>
                                                <td>12:00 AM</td>
                                                <td>08:00 AM</td>
                                                <td><span class="badge badge-danger">Inactive</span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary mr-2">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-chart-bar mr-2"></i>Employee Shift Statistics</h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center mb-4">
                                    <div class="col-4">
                                        <h4 class="text-primary">3</h4>
                                        <small class="text-muted">Total Shifts</small>
                                    </div>
                                    <div class="col-4">
                                        <h4 class="text-success">2</h4>
                                        <small class="text-muted">Active Shifts</small>
                                    </div>
                                    <div class="col-4">
                                        <h4 class="text-danger">1</h4>
                                        <small class="text-muted">Inactive Shifts</small>
                                    </div>
                                </div>
                                <div class="progress mb-2">
                                    <div class="progress-bar bg-success" style="width: 67%">Active (67%)</div>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-danger" style="width: 33%">Inactive (33%)</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Shift Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <strong>Note:</strong> Ensure to assign shifts based on employee availability and preferences.
                                </div>
                                <div class="alert alert-warning">
                                    <strong>Warning:</strong> Inactive shifts will not be assigned to employees.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Employee Shift Modal -->
    <div class="modal fade" id="addEmployeeShiftModal" tabindex="-1" role="dialog" aria-labelledby="addEmployeeShiftModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addEmployeeShiftModalLabel">Add New Employee Shift</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="form-group">
                            <label for="employeeName">Employee Name</label>
                            <input type="text" class="form-control" id="employeeName" placeholder="Enter employee name">
                        </div>
                        <div class="form-group">
                            <label for="shiftType">Shift Type</label>
                            <select class="form-control" id="shiftType">
                                <option value="morning">Morning Shift</option>
                                <option value="evening">Evening Shift</option>
                                <option value="night">Night Shift</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="shiftStartTime">Start Time</label>
                            <input type="time" class="form-control" id="shiftStartTime">
                        </div>
                        <div class="form-group">
                            <label for="shiftEndTime">End Time</label>
                            <input type="time" class="form-control" id="shiftEndTime">
                        </div>
                        <div class="form-group">
                            <label for="shiftStatus">Status</label>
                            <select class="form-control" id="shiftStatus">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary">Save Shift</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
