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
    <title>Public Holidays - HR Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css?v=rose">
    <style>
        .section-title {
            color: var(--primary-color);
            margin-bottom: 30px;
            font-weight: 600;
        }
        
        .holiday-card {
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
        }
        
        .holiday-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--shadow-medium);
        }
        
        .holiday-date {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 10px 15px;
            border-radius: 6px;
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
                <h2 class="section-title">Public Holidays Management</h2>
                
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-calendar-day mr-2"></i>Public Holidays List</h5>
                                <button class="btn btn-primary" data-toggle="modal" data-target="#addHolidayModal">
                                    <i class="fas fa-plus mr-2"></i>Add Holiday
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Holiday Name</th>
                                                <th>Date</th>
                                                <th>Type</th>
                                                <th>Description</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>New Year's Day</td>
                                                <td>January 1, 2024</td>
                                                <td><span class="badge badge-primary">National</span></td>
                                                <td>Celebration of the new year</td>
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
                                                <td>Independence Day</td>
                                                <td>June 12, 2024</td>
                                                <td><span class="badge badge-primary">National</span></td>
                                                <td>Philippine Independence Day</td>
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
                                                <td>Christmas Day</td>
                                                <td>December 25, 2024</td>
                                                <td><span class="badge badge-primary">National</span></td>
                                                <td>Christmas celebration</td>
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
                                <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Upcoming Holidays</h5>
                            </div>
                            <div class="card-body">
                                <div class="holiday-item mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="holiday-date mr-3">
                                            <div class="text-center">
                                                <div>Dec</div>
                                                <div class="h4 mb-0">25</div>
                                                <div>2024</div>
                                            </div>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">Christmas Day</h6>
                                            <small class="text-muted">National Holiday</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="holiday-item mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="holiday-date mr-3">
                                            <div class="text-center">
                                                <div>Jan</div>
                                                <div class="h4 mb-0">1</div>
                                                <div>2025</div>
                                            </div>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">New Year's Day</h6>
                                            <small class="text-muted">National Holiday</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-chart-pie mr-2"></i>Holiday Statistics</h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <h4 class="text-primary">12</h4>
                                        <small class="text-muted">Total Holidays</small>
                                    </div>
                                    <div class="col-4">
                                        <h4 class="text-success">8</h4>
                                        <small class="text-muted">National Holidays</small>
                                    </div>
                                    <div class="col-4">
                                        <h4 class="text-info">4</h4>
                                        <small class="text-muted">Regional Holidays</small>
                                    </div>
                                </div>
                                <hr>
                                <div class="progress mb-2">
                                    <div class="progress-bar bg-primary" style="width: 67%">National (67%)</div>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-info" style="width: 33%">Regional (33%)</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Holiday Modal -->
    <div class="modal fade" id="addHolidayModal" tabindex="-1" role="dialog" aria-labelledby="addHolidayModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addHolidayModalLabel">Add New Holiday</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="form-group">
                            <label for="holidayName">Holiday Name</label>
                            <input type="text" class="form-control" id="holidayName" placeholder="Enter holiday name">
                        </div>
                        <div class="form-group">
                            <label for="holidayDate">Date</label>
                            <input type="date" class="form-control" id="holidayDate">
                        </div>
                        <div class="form-group">
                            <label for="holidayType">Type</label>
                            <select class="form-control" id="holidayType">
                                <option value="national">National Holiday</option>
                                <option value="regional">Regional Holiday</option>
                                <option value="special">Special Non-working Day</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="holidayDescription">Description</label>
                            <textarea class="form-control" id="holidayDescription" rows="3" placeholder="Enter holiday description"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary">Save Holiday</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
