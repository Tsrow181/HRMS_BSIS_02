<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Include database connection
require_once 'config.php';

// Use the global database connection
$pdo = $conn;

// Get analytics data
try {
    // Overall statistics
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM training_courses WHERE status = 'Active'");
    $totalCourses = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM training_sessions WHERE status = 'Scheduled' OR status = 'In Progress'");
    $upcomingSessions = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM training_enrollments WHERE status = 'Completed'");
    $completedTrainings = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM training_enrollments");
    $totalEnrollments = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Calculate completion rate
    $completionRate = $totalEnrollments > 0 ? round(($completedTrainings / $totalEnrollments) * 100, 1) : 0;
    
    // Average score
    $stmt = $pdo->query("SELECT AVG(score) as avg_score FROM training_enrollments WHERE score IS NOT NULL");
    $avgScore = $stmt->fetch(PDO::FETCH_ASSOC)['avg_score'] ? round($stmt->fetch(PDO::FETCH_ASSOC)['avg_score'], 1) : 0;
    
    // Department-wise training statistics
    $stmt = $pdo->query("
        SELECT 
            d.department_name,
            COUNT(DISTINCT te.enrollment_id) as enrollments,
            COUNT(CASE WHEN te.status = 'Completed' THEN 1 END) as completed,
            AVG(te.score) as avg_score
        FROM departments d
        LEFT JOIN employee_profiles ep ON d.department_id = ep.department_id
        LEFT JOIN training_enrollments te ON ep.employee_id = te.employee_id
        GROUP BY d.department_id, d.department_name
        ORDER BY enrollments DESC
    ");
    $departmentStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Course popularity
    $stmt = $pdo->query("
        SELECT 
            tc.course_name,
            COUNT(te.enrollment_id) as enrollments,
            COUNT(CASE WHEN te.status = 'Completed' THEN 1 END) as completed,
            AVG(te.score) as avg_score
        FROM training_courses tc
        LEFT JOIN training_sessions ts ON tc.course_id = ts.course_id
        LEFT JOIN training_enrollments te ON ts.session_id = te.session_id
        WHERE tc.status = 'Active'
        GROUP BY tc.course_id, tc.course_name
        ORDER BY enrollments DESC
        LIMIT 10
    ");
    $courseStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Monthly training trends
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(te.enrollment_date, '%Y-%m') as month,
            COUNT(te.enrollment_id) as enrollments,
            COUNT(CASE WHEN te.status = 'Completed' THEN 1 END) as completed
        FROM training_enrollments te
        WHERE te.enrollment_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(te.enrollment_date, '%Y-%m')
        ORDER BY month DESC
        LIMIT 12
    ");
    $monthlyTrends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Trainer performance
    $stmt = $pdo->query("
        SELECT 
            CONCAT(t.first_name, ' ', t.last_name) as trainer_name,
            COUNT(DISTINCT ts.session_id) as sessions_conducted,
            COUNT(te.enrollment_id) as total_enrollments,
            COUNT(CASE WHEN te.status = 'Completed' THEN 1 END) as completed_enrollments,
            AVG(te.score) as avg_score
        FROM trainers t
        LEFT JOIN training_sessions ts ON t.trainer_id = ts.trainer_id
        LEFT JOIN training_enrollments te ON ts.session_id = te.session_id
        GROUP BY t.trainer_id, t.first_name, t.last_name
        ORDER BY sessions_conducted DESC
    ");
    $trainerStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $totalCourses = 0;
    $upcomingSessions = 0;
    $completedTrainings = 0;
    $totalEnrollments = 0;
    $completionRate = 0;
    $avgScore = 0;
    $departmentStats = [];
    $courseStats = [];
    $monthlyTrends = [];
    $trainerStats = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Analytics - HR System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css?v=rose">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --azure-blue: #E91E63;
            --azure-blue-light: #F06292;
            --azure-blue-dark: #C2185B;
            --azure-blue-lighter: #F8BBD0;
            --azure-blue-pale: #FCE4EC;
        }

        .section-title {
            color: var(--azure-blue);
            margin-bottom: 30px;
            font-weight: 600;
        }
        
        body {
            background: var(--azure-blue-pale);
        }

        .main-content {
            background: var(--azure-blue-pale);
            padding: 20px;
        }

        .btn-primary {
            background: var(--azure-blue);
            border-color: var(--azure-blue);
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .card-header {
            background: linear-gradient(135deg, var(--azure-blue) 0%, var(--azure-blue-dark) 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
            font-weight: 600;
        }

        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .stats-card i {
            font-size: 3rem;
            color: var(--azure-blue);
            margin-bottom: 15px;
        }

        .progress {
            height: 10px;
            border-radius: 5px;
            background-color: #e9ecef;
        }

        .progress-bar {
            background: var(--azure-blue);
            border-radius: 5px;
        }

        .metric-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .metric-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--azure-blue);
            margin-bottom: 5px;
        }

        .metric-label {
            color: #666;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <?php include 'navigation.php'; ?>
        <div class="row">
            <?php include 'sidebar.php'; ?>
            <div class="main-content">
                <h2 class="section-title">Training Analytics Dashboard</h2>
                
                <!-- Key Metrics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <i class="fas fa-book"></i>
                            <h3><?php echo $totalCourses; ?></h3>
                            <h6>Active Courses</h6>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <i class="fas fa-calendar-alt"></i>
                            <h3><?php echo $upcomingSessions; ?></h3>
                            <h6>Upcoming Sessions</h6>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <i class="fas fa-check-circle"></i>
                            <h3><?php echo $completedTrainings; ?></h3>
                            <h6>Completed Trainings</h6>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <i class="fas fa-chart-line"></i>
                            <h3><?php echo $completionRate; ?>%</h3>
                            <h6>Completion Rate</h6>
                        </div>
                    </div>
                </div>

                <!-- Performance Metrics -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="metric-card">
                            <h5><i class="fas fa-graduation-cap"></i> Training Completion Rate</h5>
                            <div class="metric-value"><?php echo $completionRate; ?>%</div>
                            <div class="metric-label"><?php echo $completedTrainings; ?> of <?php echo $totalEnrollments; ?> enrollments completed</div>
                            <div class="progress mt-3">
                                <div class="progress-bar" style="width: <?php echo $completionRate; ?>%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="metric-card">
                            <h5><i class="fas fa-star"></i> Average Training Score</h5>
                            <div class="metric-value"><?php echo $avgScore; ?>%</div>
                            <div class="metric-label">Based on completed training assessments</div>
                            <div class="progress mt-3">
                                <div class="progress-bar" style="width: <?php echo $avgScore; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Department Training Participation</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="departmentChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-chart-line"></i> Monthly Training Trends</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="trendsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Course Popularity -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-trophy"></i> Most Popular Training Courses</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Course Name</th>
                                        <th>Enrollments</th>
                                        <th>Completed</th>
                                        <th>Completion Rate</th>
                                        <th>Average Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($courseStats as $course): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($course['course_name']); ?></strong></td>
                                        <td><?php echo $course['enrollments']; ?></td>
                                        <td><?php echo $course['completed']; ?></td>
                                        <td>
                                            <?php 
                                            $courseCompletionRate = $course['enrollments'] > 0 ? round(($course['completed'] / $course['enrollments']) * 100, 1) : 0;
                                            echo $courseCompletionRate . '%';
                                            ?>
                                        </td>
                                        <td>
                                            <?php echo $course['avg_score'] ? round($course['avg_score'], 1) . '%' : 'N/A'; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Trainer Performance -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chalkboard-teacher"></i> Trainer Performance</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Trainer</th>
                                        <th>Sessions Conducted</th>
                                        <th>Total Enrollments</th>
                                        <th>Completed</th>
                                        <th>Completion Rate</th>
                                        <th>Average Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($trainerStats as $trainer): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($trainer['trainer_name']); ?></strong></td>
                                        <td><?php echo $trainer['sessions_conducted']; ?></td>
                                        <td><?php echo $trainer['total_enrollments']; ?></td>
                                        <td><?php echo $trainer['completed_enrollments']; ?></td>
                                        <td>
                                            <?php 
                                            $trainerCompletionRate = $trainer['total_enrollments'] > 0 ? round(($trainer['completed_enrollments'] / $trainer['total_enrollments']) * 100, 1) : 0;
                                            echo $trainerCompletionRate . '%';
                                            ?>
                                        </td>
                                        <td>
                                            <?php echo $trainer['avg_score'] ? round($trainer['avg_score'], 1) . '%' : 'N/A'; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        // Department Chart
        const departmentCtx = document.getElementById('departmentChart').getContext('2d');
        new Chart(departmentCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($departmentStats, 'department_name')); ?>,
                datasets: [{
                    label: 'Enrollments',
                    data: <?php echo json_encode(array_column($departmentStats, 'enrollments')); ?>,
                    backgroundColor: '#E91E63',
                    borderColor: '#C2185B',
                    borderWidth: 1
                }, {
                    label: 'Completed',
                    data: <?php echo json_encode(array_column($departmentStats, 'completed')); ?>,
                    backgroundColor: '#00B4D8',
                    borderColor: '#0096C7',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Trends Chart
        const trendsCtx = document.getElementById('trendsChart').getContext('2d');
        new Chart(trendsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($monthlyTrends, 'month')); ?>,
                datasets: [{
                    label: 'Enrollments',
                    data: <?php echo json_encode(array_column($monthlyTrends, 'enrollments')); ?>,
                    borderColor: '#E91E63',
                    backgroundColor: 'rgba(233, 30, 99, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Completed',
                    data: <?php echo json_encode(array_column($monthlyTrends, 'completed')); ?>,
                    borderColor: '#00B4D8',
                    backgroundColor: 'rgba(0, 180, 216, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
