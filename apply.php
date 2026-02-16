<?php
require_once 'config.php';
require_once 'link_candidate_documents.php';

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

$success = false;
$error = '';

// Check for existing candidate or create draft
$candidateId = null;
if (isset($_POST['email']) && !empty($_POST['email'])) {
    // Check by email first
    $stmt = $conn->prepare("SELECT candidate_id, first_name, last_name FROM candidates WHERE email = ?");
    $stmt->execute([$_POST['email']]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        $candidateId = $existing['candidate_id'];
        
        // If names are provided and different, check for potential duplicate person
        if (isset($_POST['first_name']) && isset($_POST['last_name'])) {
            $providedName = strtolower(trim($_POST['first_name'] . ' ' . $_POST['last_name']));
            $existingName = strtolower(trim($existing['first_name'] . ' ' . $existing['last_name']));
            
            if (!empty($existingName) && $providedName !== $existingName) {
                throw new Exception('This email is already registered with a different name. Please use a different email or contact support.');
            }
        }
    }
    
    // Check for duplicate full name with different email
    if (isset($_POST['first_name']) && isset($_POST['last_name'])) {
        $fullName = trim($_POST['first_name'] . ' ' . $_POST['last_name']);
        $stmt = $conn->prepare("SELECT candidate_id, email FROM candidates WHERE CONCAT(first_name, ' ', last_name) = ? AND email != ?");
        $stmt->execute([$fullName, $_POST['email']]);
        $nameMatch = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($nameMatch && !isset($_POST['confirm_duplicate'])) {
            throw new Exception('DUPLICATE_NAME:' . $nameMatch['email']);
        }
    }
}

// Handle email confirmation
if (isset($_GET['confirm']) && isset($_GET['token'])) {
    $token = $_GET['token'];
    // Use source field to store token temporarily
    $stmt = $conn->prepare("SELECT * FROM candidates WHERE source = ?");
    $stmt->execute(['TOKEN:' . $token]);
    $candidate = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($candidate) {
        // Update candidate source and application status
        $stmt = $conn->prepare("UPDATE candidates SET source = 'Website' WHERE candidate_id = ?");
        $stmt->execute([$candidate['candidate_id']]);
        
        // Update application status to Applied
        $stmt = $conn->prepare("UPDATE job_applications SET status = 'Applied' WHERE candidate_id = ? AND job_opening_id = ?");
        $stmt->execute([$candidate['candidate_id'], $job_id]);
        
        $success = true;
    } else {
        $error = 'Invalid or expired confirmation link.';
    }
}

// Process application submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Email verification removed - direct application
        // Create uploads directories if not exist
        $resumeDir = 'uploads/resumes/';
        $coverLetterDir = 'uploads/cover_letters/';
        
        if (!file_exists($resumeDir)) mkdir($resumeDir, 0777, true);
        if (!file_exists($coverLetterDir)) mkdir($coverLetterDir, 0777, true);
        
        // Handle file uploads
        $resumePath = null;
        $coverLetterPath = null;
        
        // Handle PDS upload - Store file in database as BLOB
        $pdsFileBlob = null;
        $pdsFileName = null;
        $pdsFileType = null;
        $pdsFileSize = null;
        $pdsValidated = false;
        
        if (isset($_FILES['pds']) && $_FILES['pds']['error'] === UPLOAD_ERR_OK) {
            $fileExt = strtolower(pathinfo($_FILES['pds']['name'], PATHINFO_EXTENSION));
            $pdsFileName = $_FILES['pds']['name'];
            $pdsFileType = $_FILES['pds']['type'];
            $pdsFileSize = $_FILES['pds']['size'];
            
            // Read file content into BLOB
            $pdsFileBlob = file_get_contents($_FILES['pds']['tmp_name']);
            
            // Validate PDS if it's JSON format
            if ($fileExt === 'json') {
                $pdsData = json_decode($pdsFileBlob, true);
                
                // Validate JSON structure - check for required PDS fields
                if ($pdsData && isset($pdsData['first_name']) && isset($pdsData['last_name']) && isset($pdsData['email'])) {
                    $pdsValidated = true;
                    
                    // Optional: Override form data with PDS data if provided
                    $_POST['first_name'] = $pdsData['first_name'] ?? $_POST['first_name'];
                    $_POST['last_name'] = $pdsData['last_name'] ?? $_POST['last_name'];
                    $_POST['email'] = $pdsData['email'] ?? $_POST['email'];
                    $_POST['phone'] = $pdsData['phone'] ?? $_POST['phone'];
                } else {
                    throw new Exception('Invalid PDS JSON format - missing required fields (first_name, last_name, email)');
                }
            } else if (in_array($fileExt, ['pdf', 'doc', 'docx'])) {
                // For PDF/DOC files, just mark as uploaded (AI will extract later)
                $pdsValidated = true;
            } else {
                throw new Exception('Invalid PDS file format. Only JSON, PDF, DOC, DOCX are accepted.');
            }
        } else {
            throw new Exception('PDS file is required');
        }
        
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
            $fileName = time() . '_resume_' . $_FILES['resume']['name'];
            $resumePath = $resumeDir . $fileName;
            if (!move_uploaded_file($_FILES['resume']['tmp_name'], $resumePath)) {
                throw new Exception('Failed to upload resume');
            }
        }
        
        if (isset($_FILES['cover_letter']) && $_FILES['cover_letter']['error'] === UPLOAD_ERR_OK) {
            $fileName = time() . '_cover_' . $_FILES['cover_letter']['name'];
            $coverLetterPath = $coverLetterDir . $fileName;
            if (!move_uploaded_file($_FILES['cover_letter']['tmp_name'], $coverLetterPath)) {
                throw new Exception('Failed to upload cover letter');
            }
        }
        
        // Set source
        $source = 'Website';
        
        if ($candidateId) {
            
            // Update existing candidate
            $stmt = $conn->prepare("UPDATE candidates SET first_name = ?, last_name = ?, phone = ?, address = ?, resume_url = ?, current_position = ?, current_company = ?, expected_salary = ?, cover_letter_url = ?, source = ? WHERE candidate_id = ?");
            $stmt->execute([
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['phone'],
                $_POST['address'],
                $resumePath ?: null,
                $_POST['current_position'] ?: null,
                $_POST['current_company'] ?: null,
                $_POST['expected_salary'] ?: null,
                $coverLetterPath ?: null,
                $source,
                $candidateId
            ]);
        } else {
            
            // Insert new candidate
            $stmt = $conn->prepare("INSERT INTO candidates (first_name, last_name, email, phone, address, resume_url, current_position, current_company, expected_salary, cover_letter_url, source) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['first_name'],
                $_POST['last_name'], 
                $_POST['email'],
                $_POST['phone'],
                $_POST['address'],
                $resumePath ?: null,
                $_POST['current_position'] ?: null,
                $_POST['current_company'] ?: null,
                $_POST['expected_salary'] ?: null,
                $coverLetterPath ?: null,
                $source
            ]);
            $candidateId = $conn->lastInsertId();
        }
        
        // Store PDS file in database as BLOB (for AI extraction later)
        if ($pdsValidated && $pdsFileBlob) {
            // Check if PDS record exists for this candidate
            $stmt = $conn->prepare("SELECT pds_id FROM pds_data WHERE candidate_id = ?");
            $stmt->execute([$candidateId]);
            $existingPds = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingPds) {
                // Update existing PDS record
                $stmt = $conn->prepare("UPDATE pds_data SET 
                    pds_file_blob = ?, 
                    pds_file_name = ?, 
                    pds_file_type = ?, 
                    pds_file_size = ?,
                    updated_at = NOW()
                    WHERE candidate_id = ?");
                $stmt->execute([
                    $pdsFileBlob,
                    $pdsFileName,
                    $pdsFileType,
                    $pdsFileSize,
                    $candidateId
                ]);
            } else {
                // Insert new PDS record with BLOB only (AI will extract later)
                $stmt = $conn->prepare("INSERT INTO pds_data (
                    candidate_id, 
                    pds_file_blob, 
                    pds_file_name, 
                    pds_file_type, 
                    pds_file_size,
                    application_source
                ) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $candidateId,
                    $pdsFileBlob,
                    $pdsFileName,
                    $pdsFileType,
                    $pdsFileSize,
                    'Website Application'
                ]);
            }
        }

        
        // Check if application already exists
        $stmt = $conn->prepare("SELECT application_id FROM job_applications WHERE job_opening_id = ? AND candidate_id = ?");
        $stmt->execute([$job_id, $candidateId]);
        $existingApp = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingApp) {
            throw new Exception('You have already applied for this position.');
        }
        
        // Insert job application with Applied status
        $stmt = $conn->prepare("INSERT INTO job_applications (job_opening_id, candidate_id, application_date, status) VALUES (?, ?, NOW(), 'Applied')");
        $stmt->execute([$job_id, $candidateId]);
        

        
        // Insert documents into document management
        $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        $candidateName = $_POST['first_name'] . ' ' . $_POST['last_name'];
        $jobTitle = $job['title'];
        
        // Handle Resume
        if ($resumePath) {
            $documentName = 'Resume - ' . $candidateName . ' - ' . $jobTitle;
            // Look for existing candidate-uploaded resume by searching notes for Candidate ID
            $stmt = $conn->prepare("SELECT document_id FROM document_management WHERE document_type = 'Resume' AND notes LIKE CONCAT('%Candidate ID: ', ?, '%') LIMIT 1");
            $stmt->execute([$candidateId]);
            $existingDoc = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingDoc) {
                $stmt = $conn->prepare("UPDATE document_management SET document_name = ?, file_path = ?, notes = ? WHERE document_id = ?");
                $stmt->execute([$documentName, $resumePath, 'Resume updated during job application. Candidate ID: ' . $candidateId, $existingDoc['document_id']]);
            } else {
                // Save candidate documents with employee_id = 0 (placeholder) and include candidate id in notes
                $stmt = $conn->prepare("INSERT INTO document_management (employee_id, document_type, document_name, file_path, document_status, notes) VALUES (0, 'Resume', ?, ?, 'Active', ?)");
                $stmt->execute([$documentName, $resumePath, 'Resume uploaded during job application. Candidate ID: ' . $candidateId]);
            }
        }
        
        // Handle Cover Letter
        if ($coverLetterPath) {
            $documentName = 'Cover Letter - ' . $candidateName . ' - ' . $jobTitle;
            $stmt = $conn->prepare("INSERT INTO document_management (employee_id, document_type, document_name, file_path, document_status, notes) VALUES (0, 'Cover Letter', ?, ?, 'Active', ?)");
            $stmt->execute([$documentName, $coverLetterPath, 'Cover Letter uploaded during job application. Candidate ID: ' . $candidateId]);
        }
        
        // Handle PDS - Store reference to database BLOB
        if ($pdsValidated) {
            $documentName = 'PDS - ' . $candidateName . ' - ' . $jobTitle;
            $stmt = $conn->prepare("INSERT INTO document_management (employee_id, document_type, document_name, file_path, document_status, notes) VALUES (0, 'PDS', ?, ?, 'Active', ?)");
            $stmt->execute([$documentName, 'BLOB:candidate_' . $candidateId, 'PDS stored in database. Candidate ID: ' . $candidateId . '. AI extraction pending.']);
        }
        
        $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        // Resume extraction removed - using PDS JSON data instead
        
        $success = true;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
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
        
        .form-check {
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            transition: all 0.3s ease;
            background: #f8f9fa;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .form-check:hover {
            border-color: var(--primary-light);
            background: var(--light);
        }
        
        .form-check-input:checked ~ .form-check-label {
            color: var(--primary-dark);
            font-weight: 600;
        }
        
        .form-check-input {
            width: 20px;
            height: 20px;
            margin-top: 0;
            margin-right: 10px;
            flex-shrink: 0;
        }
        
        .form-check-label {
            font-size: 16px;
            margin-left: 0;
            cursor: pointer;
            flex: 1;
        }
        
        .form-control-file {
            margin-top: 10px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="application-container">
            <?php if ($success): ?>
                <?php if (isset($_GET['confirm'])): ?>
                    <div class="application-header">
                        <h2><i class="fas fa-check-circle mr-3"></i>Application Submitted!</h2>
                        <p>Your application has been successfully submitted</p>
                    </div>
                    <div class="success-container">
                        <i class="fas fa-check-circle success-icon"></i>
                        <h4>Application Submitted Successfully</h4>
                        <p class="text-muted mb-4">Thank you for applying! Your application has been received and is being processed.</p>
                        
                        <!-- Real-time PDS Extraction Progress -->
                        <div id="extractionProgress" class="extraction-progress mt-4">
                            <h5 class="mb-3"><i class="fas fa-robot mr-2"></i>AI PDS Extraction in Progress</h5>
                            <div class="progress mb-3" style="height: 30px;">
                                <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%">0%</div>
                            </div>
                            <div id="extractionSteps" class="extraction-steps-list"></div>
                            <div id="extractionComplete" class="alert alert-success mt-3" style="display: none;">
                                <i class="fas fa-check-circle mr-2"></i>
                                <strong>Extraction Complete!</strong> Your PDS data has been successfully processed.
                            </div>
                        </div>
                        
                        <a href="public_jobs.php" class="btn btn-primary mt-4">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Job Listings
                        </a>
                    </div>
                    
                    <script>
                    // Start real-time PDS extraction
                    const candidateId = <?php echo $candidateId; ?>;
                    const eventSource = new EventSource('process_pds_extraction.php?candidate_id=' + candidateId);
                    
                    const progressBar = document.getElementById('progressBar');
                    const stepsContainer = document.getElementById('extractionSteps');
                    const completeAlert = document.getElementById('extractionComplete');
                    
                    let currentStep = 0;
                    const totalSteps = 6;
                    
                    eventSource.addEventListener('status', function(e) {
                        const data = JSON.parse(e.data);
                        currentStep = data.step;
                        
                        // Update progress bar
                        const progress = (currentStep / totalSteps) * 100;
                        progressBar.style.width = progress + '%';
                        progressBar.textContent = Math.round(progress) + '%';
                        
                        // Add step to list
                        const stepDiv = document.createElement('div');
                        stepDiv.className = 'extraction-step ' + data.status;
                        stepDiv.innerHTML = `
                            <div class="step-icon">
                                ${data.status === 'success' ? '<i class="fas fa-check-circle text-success"></i>' : 
                                  data.status === 'processing' ? '<i class="fas fa-spinner fa-spin text-primary"></i>' : 
                                  '<i class="fas fa-times-circle text-danger"></i>'}
                            </div>
                            <div class="step-content">
                                <strong>Step ${data.step}:</strong> ${data.message}
                                ${data.details ? '<div class="text-muted small">' + JSON.stringify(data.details) + '</div>' : ''}
                            </div>
                        `;
                        stepsContainer.appendChild(stepDiv);
                        
                        // Scroll to bottom
                        stepsContainer.scrollTop = stepsContainer.scrollHeight;
                    });
                    
                    eventSource.addEventListener('complete', function(e) {
                        const data = JSON.parse(e.data);
                        
                        // Update progress to 100%
                        progressBar.style.width = '100%';
                        progressBar.textContent = '100%';
                        progressBar.classList.remove('progress-bar-animated');
                        progressBar.classList.add('bg-success');
                        
                        // Show completion message
                        completeAlert.style.display = 'block';
                        
                        // Close event source
                        eventSource.close();
                    });
                    
                    eventSource.addEventListener('error', function(e) {
                        const data = e.data ? JSON.parse(e.data) : {message: 'Connection error'};
                        
                        // Show error
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'alert alert-danger mt-3';
                        errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i><strong>Error:</strong> ' + data.message;
                        stepsContainer.appendChild(errorDiv);
                        
                        // Close event source
                        eventSource.close();
                    });
                    
                    // Handle connection errors
                    eventSource.onerror = function() {
                        console.error('EventSource connection error');
                    };
                    </script>
                    
                    <style>
                    .extraction-progress {
                        background: #f8f9fa;
                        padding: 25px;
                        border-radius: 15px;
                        border: 2px solid #e9ecef;
                    }
                    
                    .extraction-steps-list {
                        max-height: 400px;
                        overflow-y: auto;
                        background: white;
                        padding: 15px;
                        border-radius: 10px;
                        border: 1px solid #dee2e6;
                    }
                    
                    .extraction-step {
                        display: flex;
                        align-items: flex-start;
                        padding: 12px;
                        margin-bottom: 10px;
                        border-radius: 8px;
                        background: #f8f9fa;
                        border-left: 4px solid #e9ecef;
                    }
                    
                    .extraction-step.success {
                        border-left-color: #28a745;
                        background: #f0fff4;
                    }
                    
                    .extraction-step.processing {
                        border-left-color: #007bff;
                        background: #f0f7ff;
                    }
                    
                    .extraction-step.error {
                        border-left-color: #dc3545;
                        background: #fff5f5;
                    }
                    
                    .step-icon {
                        font-size: 20px;
                        margin-right: 12px;
                        min-width: 25px;
                    }
                    
                    .step-content {
                        flex: 1;
                        font-size: 14px;
                    }
                    </style>l review your application and contact you soon.</p>
                        <a href="public_jobs.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Job Listings
                        </a>
                    </div>
                <?php else: ?>
                    <div class="application-header">
                        <h2><i class="fas fa-envelope mr-3"></i>Check Your Email!</h2>
                        <p>Confirmation email sent</p>
                    </div>
                    <div class="success-container">
                        <i class="fas fa-envelope success-icon"></i>
                        <h4>Confirmation Email Sent</h4>
                        <p class="text-muted mb-4">We've sent a confirmation email to <strong><?php echo htmlspecialchars($_POST['email'] ?? ''); ?></strong>. Please check your inbox and click the confirmation link to complete your application.</p>
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>Important:</strong> The confirmation link will expire in 24 hours. If you don't see the email, please check your spam folder.
                        </div>
                        <a href="public_jobs.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Job Listings
                        </a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="application-header">
                    <h2><i class="fas fa-briefcase mr-3"></i><?php echo htmlspecialchars($job['title']); ?></h2>
                    <p><?php echo htmlspecialchars($job['department_name']); ?></p>
                </div>
                
                <?php if ($error): ?>
                    <?php if (strpos($error, 'DUPLICATE_NAME:') === 0): ?>
                        <?php $existingEmail = substr($error, 15); ?>
                        <div class="alert alert-warning m-4">
                            <h6><i class="fas fa-exclamation-triangle mr-2"></i>Duplicate Name Detected</h6>
                            <p>A candidate with the same name already exists with email: <strong><?php echo htmlspecialchars($existingEmail); ?></strong></p>
                            <p>Are you sure this is a different person?</p>
                            <form method="POST" enctype="multipart/form-data" style="display: inline;">
                                <?php foreach($_POST as $key => $value): ?>
                                    <?php if ($key !== 'confirm_duplicate'): ?>
                                        <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <input type="hidden" name="confirm_duplicate" value="1">
                                <button type="submit" class="btn btn-warning mr-2">
                                    <i class="fas fa-check mr-1"></i>Yes, Continue
                                </button>
                            </form>
                            <a href="apply.php?job_id=<?php echo $job_id; ?>" class="btn btn-secondary">
                                <i class="fas fa-times mr-1"></i>No, Review
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger m-4"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <div class="form-section">
                    <form method="POST" enctype="multipart/form-data" id="applicationForm">
                        <input type="hidden" name="confirm_duplicate" id="confirmDuplicate" value="">
                        <!-- Personal Information -->
                        <h5 class="mb-4"><i class="fas fa-user mr-2"></i>Personal Information</h5>
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
                            <div class="col-md-12">
                                <div id="nameAlert"></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email *</label>
                                    <input type="email" name="email" class="form-control" required>
                                    <div id="emailAlert" class="mt-2"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Phone</label>
                                    <input type="tel" name="phone" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Address</label>
                            <textarea name="address" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <hr class="my-4">
                        
                        <!-- Professional Questions -->
                        <h5 class="mb-4"><i class="fas fa-question-circle mr-2"></i>Professional Questions</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Current Position <small class="text-muted">(Leave blank if new graduate)</small></label>
                                    <input type="text" name="current_position" class="form-control" placeholder="e.g., Software Developer, Student, etc.">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Current Company <small class="text-muted">(Leave blank if unemployed/student)</small></label>
                                    <input type="text" name="current_company" class="form-control" placeholder="e.g., ABC Corporation, University, etc.">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Expected Salary <small class="text-muted">(Monthly in PHP)</small></label>
                            <input type="number" name="expected_salary" class="form-control" step="1000" placeholder="e.g., 25000">
                            <small class="text-muted">Please provide your salary expectation for this position</small>
                        </div>
                        
                        <hr class="my-4">
                        
                        <!-- Documents -->
                        <h5 class="mb-4"><i class="fas fa-file-upload mr-2"></i>Upload Documents</h5>
                        <div class="form-group">
                            <label>Resume/CV <small class="text-muted">(Optional - for reference only)</small></label>
                            <input type="file" name="resume" class="form-control-file" accept=".pdf,.doc,.docx">
                            <small class="text-muted">Accepted formats: PDF, DOC, DOCX (Max 5MB)</small>
                            <div id="resumePreview" class="mt-2"></div>
                        </div>
                        
                        <div class="form-group">
                            <label>Cover Letter <small class="text-muted">(Optional)</small></label>
                            <input type="file" name="cover_letter" class="form-control-file" accept=".pdf,.doc,.docx">
                            <small class="text-muted">Accepted formats: PDF, DOC, DOCX (Max 5MB)</small>
                            <div id="coverLetterPreview" class="mt-2"></div>
                        </div>
                        
                        <div class="form-group">
                            <label>Personal Data Sheet (PDS) * <span class="text-danger">(Required)</span></label>
                            <input type="file" name="pds" class="form-control-file" accept=".json,.pdf,.doc,.docx" required>
                            <small class="text-muted">Accepted formats: JSON (from PDS form), PDF, DOC, DOCX (Max 5MB)</small>
                            <div class="alert alert-warning mt-2">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <strong>IMPORTANT:</strong> PDS is mandatory for your application.
                                <br>
                                <strong>Need PDS Template?</strong> 
                                <a href="uploads/pds_templates/PDS_Template.html" target="_blank" class="btn btn-sm btn-outline-primary ml-2">
                                    <i class="fas fa-download mr-1"></i>Fill Out PDS Form
                                </a>
                                <small class="d-block mt-1 text-muted">
                                    <strong>Recommended:</strong> Fill out the online PDS form - it will generate a JSON file that auto-fills your application data!
                                    <br>Or upload a scanned PDF/DOC of your completed PDS.
                                </small>
                            </div>
                            <div id="pdsPreview" class="mt-2"></div>
                        </div>
                        
                        <div class="text-center mt-5">
                            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                <i class="fas fa-paper-plane mr-2"></i>Submit Application
                            </button>
                            <a href="public_jobs.php" class="btn btn-secondary btn-lg ml-3">
                                <i class="fas fa-arrow-left mr-2"></i>Back to Jobs
                            </a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        let candidateDraftCreated = false;
        let nameCheckTimeout, emailCheckTimeout;
        
        // Real-time name duplicate checker
        function checkNameDuplicate() {
            const firstName = document.querySelector('input[name="first_name"]').value.trim();
            const lastName = document.querySelector('input[name="last_name"]').value.trim();
            
            if (firstName && lastName) {
                clearTimeout(nameCheckTimeout);
                nameCheckTimeout = setTimeout(() => {
                    fetch('check_duplicate.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `type=name&first_name=${encodeURIComponent(firstName)}&last_name=${encodeURIComponent(lastName)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        const nameAlert = document.getElementById('nameAlert');
                        if (data.exists) {
                            nameAlert.innerHTML = `
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    <strong>Duplicate Name Found!</strong><br>
                                    Email: ${data.data.email}<br>
                                    Phone: ${data.data.phone || 'Not provided'}<br>
                                    <small>Is this a different person? You can continue if confirmed.</small>
                                </div>
                            `;
                        } else {
                            nameAlert.innerHTML = '<div class="alert alert-success"><i class="fas fa-check mr-2"></i>Name available</div>';
                        }
                    });
                }, 500);
            } else {
                document.getElementById('nameAlert').innerHTML = '';
            }
        }
        
        // Real-time email duplicate checker
        function checkEmailDuplicate() {
            const email = document.querySelector('input[name="email"]').value.trim();
            
            if (email) {
                clearTimeout(emailCheckTimeout);
                emailCheckTimeout = setTimeout(() => {
                    fetch('check_duplicate.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `type=email&email=${encodeURIComponent(email)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        const emailAlert = document.getElementById('emailAlert');
                        if (data.exists) {
                            emailAlert.innerHTML = `
                                <div class="alert alert-danger">
                                    <i class="fas fa-times mr-2"></i>
                                    <strong>Email Already Registered!</strong><br>
                                    Name: ${data.data.name}<br>
                                    Phone: ${data.data.phone || 'Not provided'}<br>
                                    <small>Please use a different email address.</small>
                                </div>
                            `;
                        } else {
                            emailAlert.innerHTML = `
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    <strong>Email Available</strong>
                                </div>
                            `;
                        }
                    });
                }, 500);
            } else {
                document.getElementById('emailAlert').innerHTML = '';
            }
        }
        
        // Attach event listeners
        document.querySelector('input[name="first_name"]').addEventListener('input', checkNameDuplicate);
        document.querySelector('input[name="last_name"]').addEventListener('input', checkNameDuplicate);
        document.querySelector('input[name="email"]').addEventListener('input', checkEmailDuplicate);
        
        // File preview and validation function
        function setupFileValidation(inputName, previewId) {
            document.querySelector(`input[name="${inputName}"]`).addEventListener('change', function(e) {
                const file = e.target.files[0];
                const preview = document.getElementById(previewId);
                
                if (file) {
                    const fileSize = (file.size / 1024 / 1024).toFixed(2);
                    const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                    
                    if (!allowedTypes.includes(file.type)) {
                        preview.innerHTML = '<div class="alert alert-danger">Invalid file type. Please upload PDF, DOC, or DOCX files only.</div>';
                        e.target.value = '';
                        return;
                    }
                    
                    if (fileSize > 5) {
                        preview.innerHTML = '<div class="alert alert-danger">File too large. Maximum size is 5MB.</div>';
                        e.target.value = '';
                        return;
                    }
                    
                    preview.innerHTML = `<div class="alert alert-success"><i class="fas fa-file mr-2"></i>${file.name} (${fileSize} MB) - Ready to upload</div>`;
                } else {
                    preview.innerHTML = '';
                }
            });
        }
        
        // Setup file validation for all file inputs
        setupFileValidation('resume', 'resumePreview');
        setupFileValidation('cover_letter', 'coverLetterPreview');
        setupFileValidation('pds', 'pdsPreview');
        
        // Form submission confirmation
        document.getElementById('applicationForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Submitting...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>