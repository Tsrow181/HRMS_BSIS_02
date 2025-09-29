<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
require_once 'config.php';

// Get recruitment analytics data
$total_applications = $conn->query("SELECT COUNT(*) as count FROM job_applications")->fetch()['count'];
$hired_count = $conn->query("SELECT COUNT(*) as count FROM job_applications WHERE status = 'Hired'")->fetch()['count'];
$rejected_count = $conn->query("SELECT COUNT(*) as count FROM job_applications WHERE status = 'Rejected'")->fetch()['count'];
$active_count = $conn->query("SELECT COUNT(*) as count FROM job_applications WHERE status IN ('Applied', 'Screening', 'Assessment', 'Reference Check')")->fetch()['count'];

$hire_rate = $total_applications > 0 ? round(($hired_count / $total_applications) * 100, 1) : 0;
$rejection_rate = $total_applications > 0 ? round(($rejected_count / $total_applications) * 100, 1) : 0;

// Applications by month
$monthly_data = $conn->query("SELECT DATE_FORMAT(application_date, '%Y-%m') as month, 
                             COUNT(*) as applications,
                             COUNT(CASE WHEN status = 'Hired' THEN 1 END) as hired
                             FROM job_applications 
                             WHERE application_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                             GROUP BY DATE_FORMAT(application_date, '%Y-%m')
                             ORDER BY month DESC")->fetchAll(PDO::FETCH_ASSOC);

// Top performing job openings
$job_performance = $conn->query("SELECT jo.title, d.department_name,
                                COUNT(ja.application_id) as total_applications,
                                COUNT(CASE WHEN ja.status = 'Hired' THEN 1 END) as hired,
                                ROUND((COUNT(CASE WHEN ja.status = 'Hired' THEN 1 END) / COUNT(ja.application_id)) * 100, 1) as hire_rate
                                FROM job_openings jo
                                JOIN departments d ON jo.department_id = d.department_id
                                LEFT JOIN job_applications ja ON jo.job_opening_id = ja.job_opening_id
                                GROUP BY jo.job_opening_id
                                HAVING total_applications > 0
                                ORDER BY hire_rate DESC, total_applications DESC
                                LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

// Time to hire analysis
$time_to_hire = $conn->query("SELECT AVG(DATEDIFF(NOW(), application_date)) as avg_days,
                             MIN(DATEDIFF(NOW(), application_date)) as min_days,
                             MAX(DATEDIFF(NOW(), application_date)) as max_days
                             FROM job_applications 
                             WHERE status = 'Hired'")->fetch(PDO::FETCH_ASSOC);

// Source analysis
$source_analysis = $conn->query("SELECT c.source, 
                                COUNT(*) as applications,
                                COUNT(CASE WHEN ja.status = 'Hired' THEN 1 END) as hired,
                                ROUND((COUNT(CASE WHEN ja.status = 'Hired' THEN 1 END) / COUNT(*)) * 100, 1) as conversion_rate
                                FROM candidates c
                                JOIN job_applications ja ON c.candidate_id = ja.candidate_id
                                WHERE c.source IN ('Job Application', 'Referral', 'LinkedIn', 'Indeed', 'Company Website')
                                GROUP BY c.source
                                ORDER BY conversion_rate DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recruitment Analytics - HR Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css?v=rose">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container-fluid">
        <?php include 'navigation.php'; ?>
        <div class="row">
            <?php include 'sidebar.php'; ?>
            <div class="main-content">
                <h2>📊 Recruitment Analytics</h2>
                
                <!-- Key Metrics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card card">
                            <div class="card-body text-center">
                                <div class="activity-icon bg-primary">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h3 class="stats-number"><?php echo $total_applications; ?></h3>
                                <p class="stats-label">Total Applications</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card card">
                            <div class="card-body text-center">
                                <div class="activity-icon bg-success">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <h3 class="stats-number"><?php echo $hire_rate; ?>%</h3>
                                <p class="stats-label">Hire Rate</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card card">
                            <div class="card-body text-center">
                                <div class="activity-icon bg-info">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <h3 class="stats-number"><?php echo round($time_to_hire['avg_days'] ?? 0); ?></h3>
                                <p class="stats-label">Avg Days to Hire</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card card">
                            <div class="card-body text-center">
                                <div class="activity-icon bg-warning">
                                    <i class="fas fa-hourglass-half"></i>
                                </div>
                                <h3 class="stats-number"><?php echo $active_count; ?></h3>
                                <p class="stats-label">Active Applications</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <!-- Monthly Trends -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-line"></i> Monthly Application Trends</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="monthlyChart" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Conversion Funnel -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-funnel-dollar"></i> Conversion Funnel</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Applications</span>
                                        <strong><?php echo $total_applications; ?></strong>
                                    </div>
                                    <div class="progress mb-2">
                                        <div class="progress-bar bg-primary" style="width: 100%"></div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Active</span>
                                        <strong><?php echo $active_count; ?></strong>
                                    </div>
                                    <div class="progress mb-2">
                                        <div class="progress-bar bg-warning" style="width: <?php echo $total_applications > 0 ? ($active_count / $total_applications) * 100 : 0; ?>%"></div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Hired</span>
                                        <strong><?php echo $hired_count; ?></strong>
                                    </div>
                                    <div class="progress mb-2">
                                        <div class="progress-bar bg-success" style="width: <?php echo $hire_rate; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <!-- Job Performance -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-trophy"></i> Top Performing Jobs</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Job Title</th>
                                                <th>Applications</th>
                                                <th>Hired</th>
                                                <th>Rate</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($job_performance as $job): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($job['title']); ?></strong><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($job['department_name']); ?></small>
                                                    </td>
                                                    <td><?php echo $job['total_applications']; ?></td>
                                                    <td><?php echo $job['hired']; ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php echo $job['hire_rate'] > 20 ? 'success' : ($job['hire_rate'] > 10 ? 'warning' : 'danger'); ?>">
                                                            <?php echo $job['hire_rate']; ?>%
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Source Analysis -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-pie"></i> Source Performance</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="sourceChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        // Monthly Trends Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_reverse(array_column($monthly_data, 'month'))); ?>,
                datasets: [{
                    label: 'Applications',
                    data: <?php echo json_encode(array_reverse(array_column($monthly_data, 'applications'))); ?>,
                    borderColor: '#E91E63',
                    backgroundColor: 'rgba(233, 30, 99, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Hired',
                    data: <?php echo json_encode(array_reverse(array_column($monthly_data, 'hired'))); ?>,
                    borderColor: '#4CAF50',
                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
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

        // Source Performance Chart
        const sourceCtx = document.getElementById('sourceChart').getContext('2d');
        new Chart(sourceCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($source_analysis, 'source')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($source_analysis, 'conversion_rate')); ?>,
                    backgroundColor: ['#E91E63', '#4CAF50', '#2196F3', '#FF9800', '#9C27B0']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>