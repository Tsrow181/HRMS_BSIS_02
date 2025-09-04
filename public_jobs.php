<?php
require_once 'config.php';

// Get active job openings
$jobs_query = "SELECT jo.*, d.department_name, jr.title as role_title 
               FROM job_openings jo 
               JOIN departments d ON jo.department_id = d.department_id 
               JOIN job_roles jr ON jo.job_role_id = jr.job_role_id 
               WHERE jo.status = 'Open' 
               ORDER BY jo.posting_date DESC";
$jobs = $conn->query($jobs_query)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Municipal Job Opportunities - Apply Now</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        :root {
            --primary: #E91E63;
            --primary-light: #F06292;
            --primary-dark: #C2185B;
            --accent: #F8BBD0;
            --light: #FCE4EC;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--light) 0%, #fff 100%);
            min-height: 100vh;
        }
        
        .hero {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
        }
        
        .hero h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .hero p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .job-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(233, 30, 99, 0.1);
            margin-bottom: 30px;
            transition: all 0.3s ease;
            border: none;
            overflow: hidden;
        }
        
        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(233, 30, 99, 0.2);
        }
        
        .job-header {
            background: linear-gradient(135deg, var(--accent) 0%, var(--light) 100%);
            padding: 25px;
            border-bottom: 1px solid var(--accent);
        }
        
        .job-title {
            color: var(--primary-dark);
            font-weight: 700;
            font-size: 1.4rem;
            margin-bottom: 10px;
        }
        
        .job-meta {
            color: #666;
            font-size: 0.9rem;
        }
        
        .job-body {
            padding: 25px;
        }
        
        .job-description {
            color: #555;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .job-details {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .job-detail {
            background: var(--light);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            color: var(--primary-dark);
            font-weight: 600;
        }
        
        .apply-btn {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .apply-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(233, 30, 99, 0.4);
            color: white;
        }
        
        .section-title {
            text-align: center;
            color: var(--primary-dark);
            font-weight: 700;
            margin: 60px 0 40px;
        }
        
        .no-jobs {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .no-jobs i {
            font-size: 4rem;
            color: var(--accent);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="hero">
        <div class="container">
            <h1><i class="fas fa-building mr-3"></i>Municipal Career Opportunities</h1>
            <p>Join our team and serve the community. Build your career in public service.</p>
        </div>
    </div>

    <div class="container">
        <h2 class="section-title">Available Positions</h2>
        
        <div class="row">
            <?php if (count($jobs) > 0): ?>
                <?php foreach($jobs as $job): ?>
                    <div class="col-lg-6">
                        <div class="job-card">
                            <div class="job-header">
                                <h3 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                                <div class="job-meta">
                                    <i class="fas fa-building mr-2"></i><?php echo htmlspecialchars($job['department_name']); ?>
                                    <span class="ml-3"><i class="fas fa-calendar mr-2"></i>Posted: <?php echo date('M d, Y', strtotime($job['posting_date'])); ?></span>
                                </div>
                            </div>
                            <div class="job-body">
                                <p class="job-description"><?php echo htmlspecialchars($job['description']); ?></p>
                                
                                <div class="job-details">
                                    <span class="job-detail">
                                        <i class="fas fa-users mr-1"></i><?php echo $job['vacancy_count']; ?> Position<?php echo $job['vacancy_count'] > 1 ? 's' : ''; ?>
                                    </span>
                                    <span class="job-detail">
                                        <i class="fas fa-clock mr-1"></i><?php echo $job['employment_type']; ?>
                                    </span>
                                    <?php if ($job['salary_range_min'] && $job['salary_range_max']): ?>
                                        <span class="job-detail">
                                            <i class="fas fa-peso-sign mr-1"></i><?php echo number_format($job['salary_range_min']); ?> - <?php echo number_format($job['salary_range_max']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($job['closing_date']): ?>
                                        <span class="job-detail">
                                            <i class="fas fa-hourglass-end mr-1"></i>Closes: <?php echo date('M d, Y', strtotime($job['closing_date'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <button class="btn apply-btn" onclick="applyForJob(<?php echo $job['job_opening_id']; ?>)">
                                    <i class="fas fa-paper-plane mr-2"></i>Apply Now
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="no-jobs">
                        <i class="fas fa-briefcase"></i>
                        <h3>No Open Positions</h3>
                        <p>There are currently no job openings available. Please check back later.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
    function applyForJob(jobId) {
        window.location.href = 'apply.php?job_id=' + jobId;
    }
    </script>
</body>
</html>