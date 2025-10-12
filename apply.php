<?php
require_once 'config.php';

$job_id = $_GET['job_id'] ?? null;
if (!$job_id) {
    header('Location: public_jobs.php');
    exit;
}

// Get job details
$stmt = $conn->prepare("SELECT jo.*, d.department_name FROM job_openings jo JOIN departments d ON jo.department_id = d.department_id WHERE jo.job_opening_id = ?");
$stmt->execute([$job_id]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    header('Location: public_jobs.php');
    exit;
}

$step = $_GET['step'] ?? 1;
$success = false;
$processing = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 1) {
        session_start();
        $email = $_POST['email'];
        
        // Check if email already applied for this job
        $stmt = $conn->prepare("SELECT ja.application_id FROM job_applications ja 
                               JOIN candidates c ON ja.candidate_id = c.candidate_id 
                               WHERE c.email = ? AND ja.job_opening_id = ?");
        $stmt->execute([$email, $job_id]);
        $existing_application = $stmt->fetch();
        
        if ($existing_application) {
            $error_message = "This email has already been used to apply for this position.";
        } else {
            // Store step 1 data in session
            $_SESSION['application_data'] = [
                'first_name' => $_POST['first_name'],
                'last_name' => $_POST['last_name'],
                'email' => $_POST['email'],
                'phone' => $_POST['phone']
            ];
            header('Location: apply.php?job_id=' . $job_id . '&step=2');
            exit;
        }
    } elseif ($step == 2) {
        session_start();
        $_SESSION['application_data']['current_position'] = $_POST['current_position'];
        $_SESSION['application_data']['current_company'] = $_POST['current_company'];
        header('Location: apply.php?job_id=' . $job_id . '&step=3');
        exit;
    } elseif ($step == 3) {
        session_start();
        $app_data = $_SESSION['application_data'];
        
        // Check for duplicate email
        $stmt = $conn->prepare("SELECT candidate_id FROM candidates WHERE email = ?");
        $stmt->execute([$app_data['email']]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Use existing candidate
            $candidate_id = $existing['candidate_id'];
        } else {
            // Insert new candidate
            $stmt = $conn->prepare("INSERT INTO candidates (first_name, last_name, email, phone, current_position, current_company, source) VALUES (?, ?, ?, ?, ?, ?, 'Job Application')");
            $stmt->execute([
                $app_data['first_name'], 
                $app_data['last_name'], 
                $app_data['email'], 
                $app_data['phone'],
                $app_data['current_position'],
                $app_data['current_company']
            ]);
            $candidate_id = $conn->lastInsertId();
        }
        
        // Check for duplicate application
        $stmt = $conn->prepare("SELECT application_id FROM job_applications WHERE job_opening_id = ? AND candidate_id = ?");
        $stmt->execute([$job_id, $candidate_id]);
        $existing_app = $stmt->fetch();
        
        if (!$existing_app) {
            // Insert application
            $stmt = $conn->prepare("INSERT INTO job_applications (job_opening_id, candidate_id, application_date, status) VALUES (?, ?, NOW(), 'Applied')");
            $stmt->execute([$job_id, $candidate_id]);
        }
        
        unset($_SESSION['application_data']);
        $success = true;
    }
}

if ($step == 3 && !$success) {
    $processing = true;
}

if (($step == 2 || $step == 3) && !isset($_SESSION)) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for <?php echo htmlspecialchars($job['title']); ?></title>
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
        
        .application-container {
            background: white;
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(233, 30, 99, 0.2);
            overflow: hidden;
            margin: 50px auto;
            max-width: 900px;
            position: relative;
            animation: slideUp 0.6s ease-out;
        }
        
        .application-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light), #ff6b9d, var(--primary));
            background-size: 200% 100%;
            animation: shimmer 2s infinite;
        }
        
        .application-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 50%, #ff6b9d 100%);
            color: white;
            padding: 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .application-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }
        
        .application-header h2 {
            position: relative;
            z-index: 2;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        .application-header p {
            position: relative;
            z-index: 2;
            opacity: 0.9;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin: 40px 0;
            position: relative;
        }
        
        .step {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #e9ecef, #f8f9fa);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 15px;
            font-weight: bold;
            position: relative;
            transition: all 0.4s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: 3px solid #e9ecef;
        }
        
        .step.active {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            transform: scale(1.1);
            box-shadow: 0 8px 25px rgba(233, 30, 99, 0.4);
            border-color: var(--primary-light);
            animation: pulse 2s infinite;
        }
        
        .step.completed {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border-color: #28a745;
        }
        
        .step::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 100%;
            width: 30px;
            height: 3px;
            background: linear-gradient(90deg, #e9ecef, #dee2e6);
            transform: translateY(-50%);
            border-radius: 2px;
        }
        
        .step:last-child::after {
            display: none;
        }
        
        .form-section {
            padding: 50px;
            background: linear-gradient(135deg, #fafafa 0%, #ffffff 100%);
            position: relative;
        }
        
        .form-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50px;
            right: 50px;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--accent), transparent);
        }
        
        .form-group label {
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 8px;
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(233, 30, 99, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(233, 30, 99, 0.4);
        }
        
        .btn-secondary {
            background: #6c757d;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
        }
        
        .success-container {
            text-align: center;
            padding: 80px 40px;
            background: linear-gradient(135deg, #f8fff9 0%, #ffffff 100%);
            position: relative;
        }
        
        .success-icon {
            font-size: 5rem;
            color: #28a745;
            margin-bottom: 30px;
            animation: bounce 1s ease-out;
            filter: drop-shadow(0 4px 15px rgba(40, 167, 69, 0.3));
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        
        @keyframes float {
            0%, 100% { transform: translate(-50%, -50%) rotate(0deg); }
            50% { transform: translate(-45%, -55%) rotate(180deg); }
        }
        
        @keyframes pulse {
            0%, 100% { box-shadow: 0 8px 25px rgba(233, 30, 99, 0.4); }
            50% { box-shadow: 0 8px 35px rgba(233, 30, 99, 0.6); }
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-20px); }
            60% { transform: translateY(-10px); }
        }
        
        .processing-animation {
            margin: 40px 0;
        }
        
        .spinner {
            width: 80px;
            height: 80px;
            border: 4px solid var(--accent);
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .progress-steps {
            max-width: 400px;
            margin: 0 auto;
        }
        
        .progress-step {
            padding: 15px 20px;
            margin: 10px 0;
            border-radius: 10px;
            background: #f8f9fa;
            color: #6c757d;
            transition: all 0.3s ease;
            border-left: 4px solid #e9ecef;
        }
        
        .progress-step.processing {
            background: linear-gradient(135deg, var(--light), #fff);
            color: var(--primary-dark);
            border-left-color: var(--primary);
            box-shadow: 0 2px 10px rgba(233, 30, 99, 0.1);
        }
        
        .progress-step.completed {
            background: linear-gradient(135deg, #d4edda, #f8fff9);
            color: #155724;
            border-left-color: #28a745;
        }
        
        .review-card {
            background: linear-gradient(135deg, var(--light), #fff);
            border: 2px solid var(--accent);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .review-card h5 {
            color: var(--primary-dark);
            border-bottom: 2px solid var(--accent);
            padding-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="application-container">
            <?php if ($success): ?>
                <div class="application-header">
                    <h2><i class="fas fa-check-circle mr-3"></i>Application Submitted!</h2>
                    <p>Thank you for your interest in joining our team</p>
                </div>
                <div class="success-container">
                    <i class="fas fa-paper-plane success-icon"></i>
                    <h4>Your application has been successfully submitted</h4>
                    <p class="text-muted mb-4">We will review your application and contact you soon.</p>
                    <a href="public_jobs.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Job Listings
                    </a>
                </div>
            <?php else: ?>
                <div class="application-header">
                    <h2><i class="fas fa-briefcase mr-3"></i><?php echo htmlspecialchars($job['title']); ?></h2>
                    <p><?php echo htmlspecialchars($job['department_name']); ?></p>
                </div>
                
                <div class="step-indicator">
                    <div class="step <?php echo $step >= 1 ? ($step > 1 ? 'completed' : 'active') : ''; ?>">1</div>
                    <div class="step <?php echo $step >= 2 ? ($step > 2 ? 'completed' : 'active') : ''; ?>">2</div>
                    <div class="step <?php echo $step >= 3 ? 'active' : ''; ?>">3</div>
                </div>
                
                <div class="form-section">
                    <?php if ($processing): ?>
                        <div class="text-center">
                            <div class="processing-animation mb-4">
                                <div class="spinner"></div>
                            </div>
                            <h4 class="mb-3" style="color: var(--primary-dark);">Processing Your Application</h4>
                            <div class="progress-steps">
                                <div class="progress-step completed" id="step1">
                                    <i class="fas fa-check"></i> Validating Information
                                </div>
                                <div class="progress-step processing" id="step2">
                                    <i class="fas fa-spinner fa-spin"></i> Checking for Duplicates
                                </div>
                                <div class="progress-step" id="step3">
                                    <i class="fas fa-database"></i> Saving to Database
                                </div>
                                <div class="progress-step" id="step4">
                                    <i class="fas fa-envelope"></i> Sending Confirmation
                                </div>
                            </div>
                        </div>
                        <script>
                        setTimeout(() => {
                            document.getElementById('step2').className = 'progress-step completed';
                            document.getElementById('step2').innerHTML = '<i class="fas fa-check"></i> Checking for Duplicates';
                            document.getElementById('step3').className = 'progress-step processing';
                            document.getElementById('step3').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving to Database';
                        }, 1500);
                        
                        setTimeout(() => {
                            document.getElementById('step3').className = 'progress-step completed';
                            document.getElementById('step3').innerHTML = '<i class="fas fa-check"></i> Saving to Database';
                            document.getElementById('step4').className = 'progress-step processing';
                            document.getElementById('step4').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending Confirmation';
                        }, 3000);
                        
                        setTimeout(() => {
                            document.getElementById('step4').className = 'progress-step completed';
                            document.getElementById('step4').innerHTML = '<i class="fas fa-check"></i> Sending Confirmation';
                            
                            // Submit form to complete process
                            const form = document.createElement('form');
                            form.method = 'POST';
                            document.body.appendChild(form);
                            form.submit();
                        }, 4500);
                        </script>
                    <?php elseif ($step == 1): ?>
                        <div class="mb-4">
                            <h4 class="text-center mb-3" style="color: var(--primary-dark); font-weight: 700;">
                                <i class="fas fa-user-circle mr-3" style="font-size: 1.5em; color: var(--primary);"></i>
                                Personal Information
                            </h4>
                            <p class="text-center text-muted">Let's start with your basic details</p>
                        </div>
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-user mr-2"></i>First Name</label>
                                        <input type="text" name="first_name" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-user mr-2"></i>Last Name</label>
                                        <input type="text" name="last_name" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-envelope mr-2"></i>Email Address</label>
                                        <input type="email" name="email" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-phone mr-2"></i>Phone Number</label>
                                        <input type="text" name="phone" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right mt-4">
                                <a href="public_jobs.php" class="btn btn-secondary mr-3">
                                    <i class="fas fa-times mr-2"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    Next Step <i class="fas fa-arrow-right ml-2"></i>
                                </button>
                            </div>
                        </form>
                    <?php elseif ($step == 2): ?>
                        <div class="mb-4">
                            <h4 class="text-center mb-3" style="color: var(--primary-dark); font-weight: 700;">
                                <i class="fas fa-briefcase mr-3" style="font-size: 1.5em; color: var(--primary);"></i>
                                Professional Information
                            </h4>
                            <p class="text-center text-muted">Tell us about your current role</p>
                        </div>
                        <form method="POST">
                            <div class="form-group">
                                <label><i class="fas fa-id-badge mr-2"></i>Current Position</label>
                                <input type="text" name="current_position" class="form-control" placeholder="e.g. Software Developer">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-building mr-2"></i>Current Company</label>
                                <input type="text" name="current_company" class="form-control" placeholder="e.g. ABC Corporation">
                            </div>
                            <div class="review-card">
                                <h5 class="mb-3"><i class="fas fa-eye mr-2"></i>Review Your Information</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Name:</strong> <?php echo htmlspecialchars($_SESSION['application_data']['first_name'] . ' ' . $_SESSION['application_data']['last_name']); ?></p>
                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['application_data']['email']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($_SESSION['application_data']['phone']); ?></p>
                                        <p><strong>Position:</strong> <?php echo htmlspecialchars($job['title']); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="text-center mt-4">
                                <a href="apply.php?job_id=<?php echo $job_id; ?>&step=1" class="btn btn-secondary mr-3">
                                    <i class="fas fa-arrow-left mr-2"></i>Previous
                                </a>
                                <a href="apply.php?job_id=<?php echo $job_id; ?>&step=3" class="btn btn-primary btn-lg">
                                    <i class="fas fa-rocket mr-2"></i>Submit Application
                                </a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>