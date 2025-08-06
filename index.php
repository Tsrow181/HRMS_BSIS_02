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
    <title>HR Dashboard</title>
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
        
        /* Webkit browsers custom scrollbar */
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
        .container-fluid {
            padding: 0;
        }
        .row {
            margin-right: 0;
            margin-left: 0;
        }
        .card {
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(128, 0, 0, 0.05);
            border: none;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(128, 0, 0, 0.1);
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
        .card-title {
            font-size: 2rem;
            margin-bottom: 0;
            color: #800000;
        }
        .stats-card {
            text-align: center;
            background: linear-gradient(145deg, #fff 0%, #fff8f8 100%);
        }
        .stats-card i {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #800000;
        }
        .stats-card .text-muted {
            color: #996666 !important;
        }
        .stats-card .card-title {
            color: #800000;
        }
        .text-success {
            color: #800000 !important;
        }
        .text-primary {
            color: #800000 !important;
        }
        .text-info {
            color: #800000 !important;
        }
        .text-warning {
            color: #cc6600 !important;
        }
        .badge-success {
            background-color: #800000;
        }
        .activity-icon {
            width: 40px;
            height: 40px;
            background-color: #fff8f8;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            color: #800000;
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
        .event-item {
            transition: all 0.3s;
        }
        .event-item:hover {
            background-color: #fff8f8;
        }
        .event-item .activity-icon {
            background-color: #fff0f0;
        }
        .event-item .activity-icon i {
            color: #800000;
        }
        .btn-primary {
            background-color: #800000;
            border-color: #800000;
        }
        .btn-primary:hover {
            background-color: #660000;
            border-color: #660000;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <?php include 'navigation.php'; ?>
        <div class="row">
            <?php include 'sidebar.php'; ?>
            <div class="main-content">
                <h2 class="section-title">HR Dashboard Overview</h2>
                <div class="row">
                    <div class="col-md-3">
                        <div class="card stats-card">
                            <div class="card-body">
                                <i class="fas fa-users"></i>
                                <h6 class="text-muted">Total Employees</h6>
                                <h3 class="card-title">
                                    <?php
                                    echo getTotalEmployees();
                                    ?>
                                </h3>
                                <p class="text-success"><i class="fas fa-arrow-up"></i> 5% from last month</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card">
                            <div class="card-body">
                                <i class="fas fa-building"></i>
                                <h6 class="text-muted">Departments</h6>
                                <h3 class="card-title">
                                    <?php echo getTotalDepartments(); ?>
                                </h3>
                                <p class="text-muted">Active departments</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card">
                            <div class="card-body">
                                <i class="fas fa-briefcase"></i>
                                <h6 class="text-muted">Active Jobs</h6>
                                <h3 class="card-title">
                                    <?php echo getActiveJobOpenings(); ?>
                                </h3>
                                <p class="text-primary">12 applications pending</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card">
                            <div class="card-body">
                                <i class="fas fa-graduation-cap"></i>
                                <h6 class="text-muted">Training Sessions</h6>
                                <h3 class="card-title">
                                    <?php echo getUpcomingTrainingSessions(); ?>
                                </h3>
                                <p class="text-info">Next session in 2 days</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-chart-line mr-2"></i>
                                Performance Overview
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6">
                                        <h6 class="text-muted">Completed Reviews</h6>
                                        <h4>24</h4>
                                        <small class="text-success">+8 this month</small>
                                    </div>
                                    <div class="col-6">
                                        <h6 class="text-muted">Pending Reviews</h6>
                                        <h4>12</h4>
                                        <small class="text-warning">Due this week</small>
                                    </div>
                                </div>
                                <hr>
                                <div class="row mt-3">
                                    <div class="col-6">
                                        <h6 class="text-muted">Average Rating</h6>
                                        <h4>4.2/5</h4>
                                        <small class="text-success">↑ 0.3 from last cycle</small>
                                    </div>
                                    <div class="col-6">
                                        <h6 class="text-muted">Top Performers</h6>
                                        <h4>15</h4>
                                        <small class="text-muted">Exceeded goals</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-calendar-alt mr-2"></i>
                                Leave Statistics
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6">
                                        <h6 class="text-muted">On Leave Today</h6>
                                        <h4>5</h4>
                                        <small class="text-muted">3 Planned, 2 Sick</small>
                                    </div>
                                    <div class="col-6">
                                        <h6 class="text-muted">Pending Requests</h6>
                                        <h4>8</h4>
                                        <small class="text-warning">Requires approval</small>
                                    </div>
                                </div>
                                <hr>
                                <div class="row mt-3">
                                    <div class="col-6">
                                        <h6 class="text-muted">Average Leave Balance</h6>
                                        <h4>12.5 days</h4>
                                        <small class="text-muted">Per employee</small>
                                    </div>
                                    <div class="col-6">
                                        <h6 class="text-muted">Upcoming Leaves</h6>
                                        <h4>15</h4>
                                        <small class="text-muted">Next 30 days</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-money-bill-wave mr-2"></i>
                                Payroll Overview
                            </div>
                            <div class="card-body">
                                <h6 class="text-muted">Next Payroll Date</h6>
                                <h4>25th Aug 2023</h4>
                                <hr>
                                <div class="row">
                                    <div class="col-6">
                                        <h6 class="text-muted">Processed</h6>
                                        <h4>142</h4>
                                    </div>
                                    <div class="col-6">
                                        <h6 class="text-muted">Pending</h6>
                                        <h4>8</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-user-graduate mr-2"></i>
                                Training Metrics
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6">
                                        <h6 class="text-muted">Active Courses</h6>
                                        <h4>12</h4>
                                    </div>
                                    <div class="col-6">
                                        <h6 class="text-muted">Completion Rate</h6>
                                        <h4>87%</h4>
                                    </div>
                                </div>
                                <hr>
                                <h6 class="text-muted">Most Popular Course</h6>
                                <p>Leadership Essentials</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-user-plus mr-2"></i>
                                Recruitment Status
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6">
                                        <h6 class="text-muted">Open Positions</h6>
                                        <h4>8</h4>
                                    </div>
                                    <div class="col-6">
                                        <h6 class="text-muted">Applications</h6>
                                        <h4>45</h4>
                                    </div>
                                </div>
                                <hr>
                                <h6 class="text-muted">Interviews This Week</h6>
                                <h4>12</h4>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-history mr-2"></i>
                                Recent Activities
                            </div>
                            <div class="card-body">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Activity</th>
                                            <th>User</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $recentActivities = getRecentActivities();
                                        if (count($recentActivities) > 0) {
                                            foreach ($recentActivities as $activity) {
                                                echo "<tr>";
                                                echo "<td><div class='activity-icon'><i class='fas fa-check-circle text-success'></i></div>" . $activity["action"] . "</td>";
                                                echo "<td>" . $activity["username"] . "</td>";
                                                echo "<td>" . $activity["created_at"] . "</td>";
                                                echo "<td><span class='badge badge-success'>Completed</span></td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='4' class='text-center'>No recent activities</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-bell mr-2"></i>
                                Upcoming Events
                            </div>
                            <div class="card-body">
                                <div class="event-item mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="activity-icon">
                                            <i class="fas fa-birthday-cake text-primary"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">Team Birthdays</h6>
                                            <small class="text-muted">2 upcoming this week</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="event-item mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="activity-icon">
                                            <i class="fas fa-users text-success"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">Team Meeting</h6>
                                            <small class="text-muted">Tomorrow, 10:00 AM</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="event-item mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="activity-icon">
                                            <i class="fas fa-certificate text-warning"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">Training Workshop</h6>
                                            <small class="text-muted">Friday, 2:00 PM</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
