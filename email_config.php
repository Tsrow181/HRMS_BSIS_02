<?php
// Email configuration
function sendEmail($to, $subject, $message, $applicant_name = '', $job_title = '', $status = '', $conn = null) {
    // Check if email is verified
    if ($conn) {
        $verify_stmt = $conn->prepare("SELECT email_verified FROM candidates WHERE email = ?");
        $verify_stmt->execute([$to]);
        $verified = $verify_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$verified || !$verified['email_verified']) {
            return false; // Don't send to unverified emails
        }
    }
    // AI-generated email templates
    $templates = [
        'Approved' => [
            'subject' => "🎉 Great News! Your Application for {job_title} Has Been Approved",
            'body' => "Dear {name},\n\nWe're excited to inform you that your application for the {job_title} position has been approved!\n\nNext Steps:\n• Your interview has been automatically scheduled\n• You'll receive interview details shortly\n• Please prepare your portfolio and questions\n\nWe look forward to meeting you!\n\nBest regards,\nHR Recruitment Team"
        ],
        'Interview' => [
            'subject' => "📅 Interview Scheduled - {job_title} Position",
            'body' => "Dear {name},\n\nYour interview has been scheduled!\n\n📍 Location: HR Office - Conference Room\n⏰ Please arrive 10 minutes early\n📋 Bring: Resume, ID, and portfolio\n\nInterview Tips:\n• Research our company values\n• Prepare specific examples of your work\n• Think of questions about the role\n\nWe're excited to meet you!\n\nBest regards,\nHR Team"
        ],
        'Pending' => [
            'subject' => "✅ Interview Complete - {job_title} Application Under Review",
            'body' => "Dear {name},\n\nThank you for attending your interview for the {job_title} position!\n\nYour interview went well and we were impressed with your qualifications.\n\nNext Steps:\n• Your application is now under final review\n• We'll contact you within 3-5 business days\n• Feel free to reach out with any questions\n\nThank you for your patience!\n\nBest regards,\nHR Team"
        ],
        'Assessment' => [
            'subject' => "🎯 Moving Forward - {job_title} Assessment Phase",
            'body' => "Dear {name},\n\nExcellent news! You've progressed to the assessment phase for the {job_title} position.\n\nWhat's Next:\n• Final evaluation and reference checks\n• Job offer preparation in progress\n• We'll contact you soon with details\n\nYou're doing great - we're almost there!\n\nBest regards,\nHR Team"
        ],
        'Hired' => [
            'subject' => "🎊 Congratulations! Job Offer - {job_title}",
            'body' => "Dear {name},\n\nCongratulations! We're thrilled to offer you the {job_title} position!\n\n🎉 Welcome to our team!\n\nNext Steps:\n• Check your email for the formal offer letter\n• Onboarding process will begin shortly\n• HR will contact you with start date details\n\nWe can't wait to have you on board!\n\nWelcome to the team!\nHR Team"
        ],
        'Rejected' => [
            'subject' => "Thank You - {job_title} Application Update",
            'body' => "Dear {name},\n\nThank you for your interest in the {job_title} position and for taking the time to interview with us.\n\nAfter careful consideration, we have decided to move forward with another candidate whose experience more closely matches our current needs.\n\nWe were impressed with your qualifications and encourage you to apply for future opportunities that match your skills.\n\nThank you again for your interest in our organization.\n\nBest regards,\nHR Team"
        ]
    ];
    
    // Get template or use default
    $template = isset($templates[$status]) ? $templates[$status] : ['subject' => $subject, 'body' => $message];
    
    // Replace placeholders
    $email_subject = str_replace(['{job_title}', '{name}'], [$job_title, $applicant_name], $template['subject']);
    $email_body = str_replace(['{job_title}', '{name}'], [$job_title, $applicant_name], $template['body']);
    
    // Email headers
    $headers = "From: HR Team <hr@company.com>\r\n";
    $headers .= "Reply-To: hr@company.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    // Send email (using PHP mail function - can be replaced with SMTP)
    return mail($to, $email_subject, $email_body, $headers);
}

// Generate verification token
function generateVerificationToken() {
    return bin2hex(random_bytes(32));
}

// Send verification email
function sendVerificationEmail($email, $name, $token) {
    $verify_link = "http://localhost/Human-Resources-Management-System-BSIS-02/verify_email.php?token=" . $token;
    $subject = "Verify Your Email - Job Application";
    $message = "Dear $name,\n\nPlease verify your email by clicking: $verify_link\n\nBest regards,\nHR Team";
    $headers = "From: HR Team <hr@company.com>\r\n";
    
    return mail($email, $subject, $message, $headers);
}
?>