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
    <title>Leave Types - HR Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css?v=rose">
    <style>
        .section-title {
            color: var(--primary-color);
            margin-bottom: 30px;
            font-weight: 600;
        }
        
        .leave-type-card {
            border-radius: 10px;
            transition: all 0.3s ease;
            border: 1px solid var(--border-light);
        }
        
        .leave-type-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px var(--shadow-medium);
        }
        
        .leave-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <?php include 'navigation.php'; ?>
        <div class="row">
            <?php include 'sidebar.php'; ?>
            <div class="main-content">
                <h2 class="section-title">Leave Types Management</h2>
                
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-list-alt mr-2"></i>Leave Types</h5>
                                <button class="btn btn-primary" data-toggle="modal" data-target="#addLeaveTypeModal">
                                    <i class="fas fa-plus mr-2"></i>Add Leave Type
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Leave Type</th>
                                                <th>Code</th>
                                                <th>Days Allowed</th>
                                                <th>Carry Forward</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="leave-icon mr-3">
                                                            <i class="fas fa-plane"></i>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0">Vacation Leave</h6>
                                                            <small class="text-muted">Annual vacation time</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>VL</td>
                                                <td>15 days</td>
                                                <td>5 days</td>
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
                                                        <div class="leave-icon mr-3">
                                                            <i class="fas fa-stethoscope"></i>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0">Sick Leave</h6>
                                                            <small class="text-muted">Medical absences</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>SL</td>
                                                <td>10 days</td>
                                                <td>0 days</td>
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
                                                        <div class="leave-icon mr-3">
                                                            <i class="fas fa-baby"></i>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0">Maternity Leave</h6>
                                                            <small class="text-muted">Childbirth recovery</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>ML</td>
                                                <td>105 days</td>
                                                <td>0 days</td>
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
                                                        <div class="leave-icon mr-3">
                                                            <i class="fas fa-heart"></i>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0">Paternity Leave</h6>
                                                            <small class="text-muted">New father support</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>PL</td>
                                                <td>7 days</td>
                                                <td>0 days</td>
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
                                <h5 class="mb-0"><i class="fas fa-chart-bar mr-2"></i>Leave Type Distribution</h5>
                            </div>
                            <div class="card-body">
                                <div class="progress mb-3">
                                    <div class="progress-bar bg-primary" style="width: 40%">Vacation (40%)</div>
                                </div>
                                <div class="progress mb-3">
                                    <div class="progress-bar bg-success" style="width: 30%">Sick (30%)</div>
                                </div>
                                <div class="progress mb-3">
                                    <div class="progress-bar bg-info" style="width: 20%">Maternity (20%)</div>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-warning" style="width: 10%">Paternity (10%)</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Leave Type Statistics</h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <h4 class="text-primary">8</h4>
                                        <small class="text-muted">Total Types</small>
                                    </div>
                                    <div class="col-4">
                                        <h4 class="text-success">6</h4>
                                        <small class="text-muted">Active Types</small>
                                    </div>
                                    <div class="col-4">
                                        <h4 class="text-warning">2</h4>
                                        <small class="text-muted">Inactive Types</small>
                                    </div>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Average Days Allowed:</span>
                                    <strong>25 days</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Most Used Type:</span>
                                    <strong>Vacation Leave</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Leave Type Modal -->
    <div class="modal fade" id="addLeaveTypeModal" tabindex="-1" role="dialog" aria-labelledby="addLeaveTypeModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addLeaveTypeModalLabel">Add New Leave Type</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="form-group">
                            <label for="leaveTypeName">Leave Type Name</label>
                            <input type="text" class="form-control" id="leaveTypeName" placeholder="Enter leave type name">
                        </div>
                        <div class="form-group">
                            <label for="leaveTypeCode">Leave Code</label>
                            <input type="text" class="form-control" id="leaveTypeCode" placeholder="Enter code (e.g., VL, SL)" maxlength="5">
                        </div>
                        <div class="form-group">
                            <label for="daysAllowed">Days Allowed Per Year</label>
                            <input type="number" class="form-control" id="daysAllowed" placeholder="Enter number of days">
                        </div>
                        <div class="form-group">
                            <label for="carryForward">Carry Forward Days</label>
                            <input type="number" class="form-control" id="carryForward" placeholder="Enter carry forward days">
                        </div>
                        <div class="form-group">
                            <label for="leaveDescription">Description</label>
                            <textarea class="form-control" id="leaveDescription" rows="3" placeholder="Enter leave type description"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="leaveStatus">Status</label>
                            <select class="form-control" id="leaveStatus">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary">Save Leave Type</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
