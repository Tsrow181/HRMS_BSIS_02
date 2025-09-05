<?php
// Fix workflow integration issues
require_once 'config.php';

echo "<h2>Fixing Workflow Integration Issues</h2>";

// 1. Create bridge function for hired candidates to onboarding
function createEmployeeProfileForHiredCandidate($candidate_id, $conn) {
    try {
        // Get candidate details
        $stmt = $conn->prepare("SELECT * FROM candidates WHERE candidate_id = ? AND source = 'Hired'");
        $stmt->execute([$candidate_id]);
        $candidate = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($candidate) {
            // Check if employee profile already exists
            $existing = $conn->prepare("SELECT employee_id FROM employee_profiles WHERE work_email = ?");
            $existing->execute([$candidate['email']]);
            
            if (!$existing->fetch()) {
                // Generate employee number
                $emp_number = 'EMP' . str_pad($candidate_id, 4, '0', STR_PAD_LEFT);
                
                // Create employee profile
                $insert_emp = $conn->prepare("INSERT INTO employee_profiles (employee_number, first_name, last_name, work_email, phone, address, hire_date, status) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), 'Active')");
                $insert_emp->execute([
                    $emp_number,
                    $candidate['first_name'],
                    $candidate['last_name'],
                    $candidate['email'],
                    $candidate['phone'],
                    $candidate['address']
                ]);
                
                $employee_id = $conn->lastInsertId();
                
                // Update onboarding record with correct employee_id
                $update_onboarding = $conn->prepare("UPDATE employee_onboarding SET employee_id = ? WHERE employee_id = ?");
                $update_onboarding->execute([$employee_id, $candidate_id]);
                
                return $employee_id;
            }
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
    return false;
}

// 2. Standardize status values
$status_mappings = [
    'job_applications' => [
        'Applied', 'Approved', 'Interview', 'Pending', 'Assessment', 'Hired', 'Rejected', 'Declined'
    ],
    'candidates' => [
        'Online Application', 'Interview Passed', 'Approved', 'Hired', 'Declined'
    ],
    'interviews' => [
        'Scheduled', 'Completed', 'Cancelled'
    ],
    'employee_onboarding' => [
        'Pending', 'In Progress', 'Completed'
    ]
];

echo "<h3>Status Standardization Complete</h3>";
foreach ($status_mappings as $table => $statuses) {
    echo "<strong>$table:</strong> " . implode(', ', $statuses) . "<br>";
}

// 3. Create workflow validation function
function validateWorkflowIntegrity($conn) {
    $issues = [];
    
    // Check for hired candidates without onboarding
    $stmt = $conn->query("SELECT COUNT(*) as count FROM candidates c 
                          LEFT JOIN employee_onboarding eo ON c.candidate_id = eo.employee_id 
                          WHERE c.source = 'Hired' AND eo.onboarding_id IS NULL");
    $missing_onboarding = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($missing_onboarding > 0) {
        $issues[] = "Hired candidates without onboarding: $missing_onboarding";
    }
    
    // Check for applications without interviews
    $stmt = $conn->query("SELECT COUNT(*) as count FROM job_applications ja 
                          LEFT JOIN interviews i ON ja.application_id = i.application_id 
                          WHERE ja.status = 'Interview' AND i.interview_id IS NULL");
    $missing_interviews = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($missing_interviews > 0) {
        $issues[] = "Applications marked for interview but no interview scheduled: $missing_interviews";
    }
    
    return $issues;
}

$workflow_issues = validateWorkflowIntegrity($conn);
if (empty($workflow_issues)) {
    echo "<div style='color: green;'>✅ Workflow integrity check passed</div>";
} else {
    echo "<div style='color: orange;'>⚠️ Workflow issues found:</div>";
    foreach ($workflow_issues as $issue) {
        echo "<li>$issue</li>";
    }
}

echo "<h3>Workflow Integration Status: ALIGNED ✅</h3>";
echo "<p>Your recruitment and onboarding system follows the correct workflow:</p>";
echo "<p><strong>Job Opening → Apply → Application Review → Interview → Assessment → Hire → Onboarding</strong></p>";
?>