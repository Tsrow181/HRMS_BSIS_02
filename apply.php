<?php
require_once 'config.php';
require_once 'email_config.php';

$job_id = isset($_GET['job_id']) ? $_GET['job_id'] : null;
$message = '';
$messageType = '';

if (!$job_id) {
    header('Location: public_jobs.php');
    exit;
}

// Get job details
$job_query = "SELECT jo.*, d.department_name, jr.title as role_title 
              FROM job_openings jo 
              JOIN departments d ON jo.department_id = d.department_id 
              JOIN job_roles jr ON jo.job_role_id = jr.job_role_id 
              WHERE jo.job_opening_id = ? AND jo.status = 'Open'";
$stmt = $conn->prepare($job_query);
$stmt->execute([$job_id]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    header('Location: public_jobs.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Handle file upload
        $resume_filename = '';
        
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] === 0) {
            $resume_filename = $_FILES['resume']['name'];
        }
        
        // Create new candidate record for each application
        $candidate_stmt = $conn->prepare("INSERT INTO candidates (first_name, last_name, email, phone, address, resume_filename, current_position, current_company, notice_period, expected_salary, source, email_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Job Application', 1)");
        $candidate_stmt->execute([
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['email'],
            $_POST['phone'],
            $_POST['address'],
            $resume_filename,
            $_POST['current_position'],
            $_POST['current_company'],
            $_POST['notice_period'],
            $_POST['expected_salary']
        ]);
        $candidate_id = $conn->lastInsertId();
        
        // Insert job application
        $app_stmt = $conn->prepare("INSERT INTO job_applications (job_opening_id, candidate_id, application_date, status, notes) VALUES (?, ?, NOW(), 'Applied', ?)");
        $app_stmt->execute([
            $job_id,
            $candidate_id,
            'Application submitted online with attachments'
        ]);
        
        $message = "Application submitted successfully! Your application has been received and will be reviewed by our HR team.";
        $messageType = "success";
        
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'already applied') !== false) {
            $message = $e->getMessage();
        } elseif (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            $message = "You have already applied for this position.";
        } else {
            $message = "There was an error submitting your application. Please try again.";
        }
        $messageType = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for <?php echo htmlspecialchars($job['title']); ?> - Municipal Jobs</title>
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
        
        .header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 40px 0;
        }
        
        .job-info {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin: -30px 0 30px;
            box-shadow: 0 5px 20px rgba(233, 30, 99, 0.1);
        }
        
        .application-form {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 5px 20px rgba(233, 30, 99, 0.1);
        }
        
        .form-section {
            margin-bottom: 40px;
        }
        
        .section-title {
            color: var(--primary-dark);
            font-weight: 700;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--accent);
        }
        
        .form-group label {
            color: var(--primary-dark);
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .form-control {
            border: 2px solid var(--accent);
            border-radius: 8px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 10px rgba(233, 30, 99, 0.2);
        }
        
        .photo-upload {
            text-align: center;
            padding: 30px;
            border: 2px dashed var(--accent);
            border-radius: 15px;
            background: var(--light);
            transition: all 0.3s ease;
        }
        
        .photo-upload:hover {
            border-color: var(--primary);
            background: white;
        }
        
        .photo-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary);
            margin: 20px auto;
            display: none;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 1.1rem;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(233, 30, 99, 0.4);
            color: white;
        }
        
        .back-btn {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-btn:hover {
            color: var(--primary-dark);
            text-decoration: none;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
        }
        
        .alert-error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <a href="public_jobs.php" class="back-btn">
                <i class="fas fa-arrow-left mr-2"></i>Back to Job Listings
            </a>
            <h1 class="mt-3"><i class="fas fa-file-alt mr-3"></i>Job Application</h1>
        </div>
    </div>

    <div class="container">
        <div class="job-info">
            <h2 class="text-primary"><?php echo htmlspecialchars($job['title']); ?></h2>
            <p class="text-muted mb-2">
                <i class="fas fa-building mr-2"></i><?php echo htmlspecialchars($job['department_name']); ?>
                <span class="ml-4"><i class="fas fa-map-marker-alt mr-2"></i><?php echo htmlspecialchars($job['location']); ?></span>
            </p>
            <p><?php echo htmlspecialchars($job['description']); ?></p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> mr-2"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (!$message || $messageType !== 'success'): ?>
        <div class="application-form">
            <form method="POST" enctype="multipart/form-data">
                <!-- Personal Information -->
                <div class="form-section">
                    <h3 class="section-title"><i class="fas fa-user mr-2"></i>Personal Information</h3>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>First Name *</label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Last Name *</label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email Address *</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Phone Number *</label>
                                <input type="tel" name="phone" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Complete Address *</label>
                        <textarea name="address" class="form-control" rows="3" required></textarea>
                    </div>
                </div>

                <!-- Photo Upload -->
                <div class="form-section">
                    <h3 class="section-title"><i class="fas fa-camera mr-2"></i>Profile Photo</h3>
                    <div class="photo-upload" onclick="document.getElementById('photo').click()">
                        <img id="photoPreview" class="photo-preview">
                        <div id="photoPlaceholder">
                            <i class="fas fa-camera fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Click to upload your photo</p>
                            <small class="text-muted">JPG, PNG (Max 5MB)</small>
                        </div>
                        <input type="file" id="photo" name="photo" accept="image/*" style="display: none;" onchange="previewPhoto(this)">
                    </div>
                </div>

                <!-- Work Experience -->
                <div class="form-section">
                    <h3 class="section-title"><i class="fas fa-briefcase mr-2"></i>Work Experience</h3>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Current Position</label>
                                <input type="text" name="current_position" class="form-control" placeholder="e.g., Administrative Assistant">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Current Company</label>
                                <input type="text" name="current_company" class="form-control" placeholder="e.g., ABC Corporation">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Notice Period</label>
                                <select name="notice_period" class="form-control">
                                    <option value="Immediate">Immediate</option>
                                    <option value="15 days">15 days</option>
                                    <option value="30 days">30 days</option>
                                    <option value="60 days">60 days</option>
                                    <option value="90 days">90 days</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Expected Salary (₱)</label>
                                <input type="number" name="expected_salary" class="form-control" step="0.01">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resume Upload -->
                <div class="form-section">
                    <h3 class="section-title"><i class="fas fa-file-pdf mr-2"></i>Resume/CV</h3>
                    <div class="form-group">
                        <label>Upload Resume *</label>
                        <input type="file" name="resume" class="form-control-file" accept=".pdf,.doc,.docx" required>
                        <small class="text-muted">PDF, DOC, DOCX (Max 10MB)</small>
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-paper-plane mr-2"></i>Submit Application
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
    function previewPhoto(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('photoPreview').src = e.target.result;
                document.getElementById('photoPreview').style.display = 'block';
                document.getElementById('photoPlaceholder').style.display = 'none';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    </script>
</body>
</html>