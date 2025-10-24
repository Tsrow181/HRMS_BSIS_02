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
    <link rel="stylesheet" href="styles.css">
    <style>
        .hero {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="white" opacity="0.1"/><circle cx="80" cy="80" r="2" fill="white" opacity="0.1"/><circle cx="40" cy="60" r="1" fill="white" opacity="0.1"/></svg>');
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
        }
        
        .search-filter {
            background: var(--bg-card);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 40px var(--shadow-light);
            margin: -40px auto 50px;
            max-width: 900px;
            backdrop-filter: blur(10px);
        }
        
        .job-card {
            background: var(--bg-card);
            border-radius: 20px;
            box-shadow: 0 8px 30px var(--shadow-light);
            margin-bottom: 30px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid var(--border-light);
            overflow: hidden;
            position: relative;
        }
        
        .job-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }
        
        .job-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 60px var(--shadow-medium);
        }
        
        .job-header {
            background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);
            padding: 30px;
            border-bottom: none;
        }
        
        .job-title {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 12px;
            line-height: 1.3;
        }
        
        .job-meta {
            color: var(--text-secondary);
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .job-body {
            padding: 30px;
        }
        
        .job-summary {
            background: linear-gradient(135deg, var(--bg-primary) 0%, #f8f9fa 100%);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary-color);
            position: relative;
        }
        
        .job-details {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 25px;
        }
        
        .job-detail {
            background: linear-gradient(135deg, var(--primary-lighter) 0%, var(--bg-secondary) 100%);
            padding: 10px 16px;
            border-radius: 25px;
            font-size: 0.9rem;
            color: var(--primary-dark);
            font-weight: 600;
            border: 1px solid var(--border-light);
            transition: all 0.3s ease;
        }
        
        .job-detail:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--shadow-light);
        }
        
        .modern-btn {
            border: none;
            padding: 14px 28px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .modern-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .modern-btn:hover::before {
            left: 100%;
        }
        
        .btn-primary-modern {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
        }
        
        .btn-primary-modern:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px var(--shadow-medium);
            color: white;
        }
        
        .btn-success-modern {
            background: linear-gradient(135deg, var(--success) 0%, #20c997 100%);
            color: white;
        }
        
        .btn-success-modern:hover {
            background: linear-gradient(135deg, #20c997 0%, var(--success) 100%);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(40, 167, 69, 0.3);
            color: white;
        }
        
        .filter-btn {
            background: var(--bg-card);
            border: 2px solid var(--border-light);
            color: var(--text-primary);
            padding: 10px 20px;
            border-radius: 25px;
            margin: 5px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px var(--shadow-light);
        }
        
        .section-title {
            text-align: center;
            color: var(--text-primary);
            font-weight: 700;
            margin: 60px 0 40px;
            font-size: 2.5rem;
            position: relative;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            border-radius: 2px;
        }
        
        @media (max-width: 768px) {
            .hero h1 { font-size: 2rem; }
            .job-card { margin-bottom: 20px; }
            .job-header, .job-body { padding: 20px; }
            .section-title { font-size: 2rem; }
        }
    </style>
</head>
<body>
    <div class="hero">
        <div class="container hero-content">
            <h1><i class="fas fa-city mr-3"></i>Join Our Municipal Team</h1>
            <p class="lead">Discover meaningful career opportunities in public service. Make a difference in your community.</p>
            <div class="mt-4">
                <span class="badge badge-light mr-2 p-2"><i class="fas fa-users mr-1"></i><?php echo count($jobs); ?> Open Positions</span>
                <span class="badge badge-light p-2"><i class="fas fa-clock mr-1"></i>Apply Today</span>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="search-filter">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" class="form-control" id="jobSearch" placeholder="Search by title, department, location, employment type...">
                    </div>
                </div>
                <div class="col-md-6 text-center">
                    <button class="filter-btn" onclick="filterJobs('all')">All Jobs</button>
                    <button class="filter-btn" onclick="filterJobs('Full-time')">Full-time</button>
                    <button class="filter-btn" onclick="filterJobs('Part-time')">Part-time</button>
                    <button class="filter-btn" onclick="filterJobs('Contract')">Contract</button>
                </div>
            </div>
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
                                <div class="job-summary">
                                    <h6 class="text-primary mb-2"><i class="fas fa-info-circle mr-2"></i>Position Overview</h6>
                                    <p class="mb-0"><?php echo htmlspecialchars(substr($job['description'], 0, 180)) . (strlen($job['description']) > 180 ? '...' : ''); ?></p>
                                </div>
                                
                                <div class="job-details">
                                    <span class="job-detail">
                                        <i class="fas fa-users mr-1"></i><?php echo $job['vacancy_count']; ?> Position<?php echo $job['vacancy_count'] > 1 ? 's' : ''; ?>
                                    </span>
                                    <span class="job-detail">
                                        <i class="fas fa-clock mr-1"></i><?php echo $job['employment_type']; ?>
                                    </span>
                                    <span class="job-detail">
                                        <i class="fas fa-map-marker-alt mr-1"></i><?php echo htmlspecialchars($job['location']); ?>
                                    </span>
                                    <?php if ($job['salary_range_min'] && $job['salary_range_max']): ?>
                                        <span class="job-detail">
                                            <i class="fas fa-peso-sign mr-1"></i><?php echo number_format($job['salary_range_min']); ?> - <?php echo number_format($job['salary_range_max']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="d-flex gap-3">
                                    <button class="btn modern-btn btn-primary-modern flex-fill" onclick="showJobDetails(<?php echo $job['job_opening_id']; ?>)">
                                        <i class="fas fa-eye mr-2"></i>View Details
                                    </button>
                                    <button class="btn modern-btn btn-success-modern" onclick="applyForJob(<?php echo $job['job_opening_id']; ?>)">
                                        <i class="fas fa-paper-plane mr-2"></i>Apply
                                    </button>
                                </div>
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
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <!-- Job Details Modal -->
    <div class="modal fade" id="jobDetailsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%); color: white;">
                    <h5 class="modal-title"><i class="fas fa-briefcase mr-2"></i>Job Details</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="jobDetailsContent">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Close
                    </button>
                    <button type="button" class="btn btn-success" id="modalApplyBtn">
                        <i class="fas fa-paper-plane mr-2"></i>Apply for This Position
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function applyForJob(jobId) {
        // Clear any existing session data for new application
        fetch('clear_session.php', { method: 'POST' })
            .then(() => {
                window.location.href = 'apply.php?job_id=' + jobId;
            });
    }
    
    let currentFilter = 'all';
    
    function filterJobs(type) {
        currentFilter = type;
        $('.filter-btn').removeClass('active');
        $(event.target).addClass('active');
        
        $('.job-card').parent().show();
        if (type !== 'all') {
            $('.job-card').parent().each(function() {
                const employmentType = $(this).find('.job-detail:contains("' + type + '")').length;
                if (employmentType === 0) {
                    $(this).hide();
                }
            });
        }
    }
    
    $('#jobSearch').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('.job-card').parent().each(function() {
            const jobText = $(this).text().toLowerCase();
            const matches = jobText.includes(searchTerm);
            
            if (matches) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    function showJobDetails(jobId) {
        // Find job data
        const jobs = <?php echo json_encode($jobs); ?>;
        const job = jobs.find(j => j.job_opening_id == jobId);
        
        if (job) {
            const content = `
                <div class="job-detail-header mb-4">
                    <h4 class="text-primary">${job.title}</h4>
                    <p class="text-muted mb-2">
                        <i class="fas fa-building mr-2"></i>${job.department_name}
                        <span class="ml-3"><i class="fas fa-calendar mr-2"></i>Posted: ${new Date(job.posting_date).toLocaleDateString()}</span>
                    </p>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="detail-card">
                            <h6><i class="fas fa-users text-primary mr-2"></i>Positions Available</h6>
                            <p>${job.vacancy_count} position${job.vacancy_count > 1 ? 's' : ''}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-card">
                            <h6><i class="fas fa-clock text-primary mr-2"></i>Employment Type</h6>
                            <p>${job.employment_type}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-card">
                            <h6><i class="fas fa-map-marker-alt text-primary mr-2"></i>Location</h6>
                            <p>${job.location}</p>
                        </div>
                    </div>
                    ${job.salary_range_min && job.salary_range_max ? `
                    <div class="col-md-6">
                        <div class="detail-card">
                            <h6><i class="fas fa-peso-sign text-primary mr-2"></i>Salary Range</h6>
                            <p>₱${parseInt(job.salary_range_min).toLocaleString()} - ₱${parseInt(job.salary_range_max).toLocaleString()}</p>
                        </div>
                    </div>
                    ` : ''}
                </div>
                
                <div class="mb-4">
                    <h6 class="text-primary"><i class="fas fa-info-circle mr-2"></i>Job Description</h6>
                    <div class="detail-content">${job.description.replace(/\n/g, '<br>')}</div>
                </div>
                
                ${job.requirements ? `
                <div class="mb-4">
                    <h6 class="text-primary"><i class="fas fa-check-circle mr-2"></i>Requirements</h6>
                    <div class="detail-content">${job.requirements.replace(/\n/g, '<br>')}</div>
                </div>
                ` : ''}
                
                ${job.responsibilities ? `
                <div class="mb-4">
                    <h6 class="text-primary"><i class="fas fa-tasks mr-2"></i>Key Responsibilities</h6>
                    <div class="detail-content">${job.responsibilities.replace(/\n/g, '<br>')}</div>
                </div>
                ` : ''}
            `;
            
            document.getElementById('jobDetailsContent').innerHTML = content;
            document.getElementById('modalApplyBtn').onclick = () => applyForJob(jobId);
            $('#jobDetailsModal').modal('show');
        }
    }
    </script>
    
    <style>
    .detail-card {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 15px;
        border-left: 3px solid var(--primary);
    }
    
    .detail-card h6 {
        margin-bottom: 8px;
        font-weight: 600;
    }
    
    .detail-card p {
        margin: 0;
        color: #666;
    }
    
    .detail-content {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        line-height: 1.6;
        color: #555;
    }
    
    .job-detail-header {
        border-bottom: 2px solid var(--accent);
        padding-bottom: 15px;
    }
    </style>
</body>
</html>