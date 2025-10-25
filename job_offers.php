<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
require_once 'db_connect.php';
require_once 'email_sender.php';

$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'send_custom_email':
                $to = $_POST['email_to'];
                $subject = $_POST['email_subject'];
                $message = $_POST['email_message'];
                
                $emailSender = new EmailSender();
                $sent = $emailSender->sendEmail($to, $subject, $message);
                
                $status = $sent ? 'Sent' : 'Failed';
                $stmt = $conn->prepare("INSERT INTO notification_letters (type, recipient, subject, content, status, created_by, created_at, sent_at) VALUES ('General', ?, ?, ?, ?, ?, NOW(), NOW())");
                $stmt->bind_param('ssssi', $to, $subject, $message, $status, $_SESSION['user_id']);
                $stmt->execute();
                
                $success_message = $sent ? "📧 Email sent successfully!" : "❌ Email failed to send!";
                break;
                
            case 'verify_gmail':
                $emailSender = new EmailSender();
                $test_email = $_POST['test_email'] ?? 'test@example.com';
                $verification_code = 'HRMS-' . date('YmdHis') . '-' . rand(1000, 9999);
                
                $subject = "HRMS Gmail Verification - " . date('Y-m-d H:i:s');
                $message = "Gmail verification test from HRMS system.\n\nVerification Code: $verification_code\n\nIf you received this email, your Gmail configuration is working correctly!\n\nTimestamp: " . date('Y-m-d H:i:s');
                
                $sent = $emailSender->sendEmail($test_email, $subject, $message);
                
                $stmt = $conn->prepare("INSERT INTO notification_letters (type, recipient, subject, content, status, created_by, created_at, sent_at) VALUES ('Gmail Test', ?, ?, ?, ?, ?, NOW(), NOW())");
                $status = $sent ? 'Sent' : 'Failed';
                $stmt->bind_param('ssssi', $test_email, $subject, $message, $status, $_SESSION['user_id']);
                $stmt->execute();
                
                $success_message = $sent ? "✅ Gmail verification sent! Code: $verification_code" : "❌ Gmail verification failed!";
                break;
                
            case 'create_letter':
                $type = $_POST['letter_type'];
                $recipient = $_POST['recipient'];
                $subject = $_POST['subject'];
                $content = $_POST['content'];
                
                $stmt = $conn->prepare("INSERT INTO notification_letters (type, recipient, subject, content, status, created_by, created_at) VALUES (?, ?, ?, ?, 'Draft', ?, NOW())");
                $stmt->bind_param('ssssi', $type, $recipient, $subject, $content, $_SESSION['user_id']);
                $stmt->execute();
                
                $success_message = "📝 Letter created successfully!";
                break;
                
            case 'send_letter':
                $letter_id = $_POST['letter_id'];
                
                $stmt = $conn->prepare("SELECT * FROM notification_letters WHERE letter_id = ?");
                $stmt->bind_param('i', $letter_id);
                $stmt->execute();
                $letter = $stmt->get_result()->fetch_assoc();
                
                $emailSender = new EmailSender();
                $sent = $emailSender->sendEmail($letter['recipient'], $letter['subject'], $letter['content']);
                
                $status = $sent ? 'Sent' : 'Failed';
                $stmt = $conn->prepare("UPDATE notification_letters SET status = ?, sent_at = NOW() WHERE letter_id = ?");
                $stmt->bind_param('si', $status, $letter_id);
                $stmt->execute();
                
                $success_message = $sent ? "📧 Letter sent successfully!" : "❌ Letter failed to send!";
                break;
                
            case 'mayor_approve':
                $offer_id = $_POST['offer_id'];
                
                // Get offer details for email notification
                $stmt = $conn->prepare("SELECT jo.*, c.first_name, c.last_name, c.email, job.title as job_title, d.department_name FROM job_offers jo JOIN candidates c ON jo.candidate_id = c.candidate_id JOIN job_openings job ON jo.job_opening_id = job.job_opening_id JOIN departments d ON job.department_id = d.department_id WHERE jo.offer_id = ?");
                $stmt->bind_param('i', $offer_id);
                $stmt->execute();
                $offer_data = $stmt->get_result()->fetch_assoc();
                
                // Update offer status
                $stmt = $conn->prepare("UPDATE job_offers SET approval_status = 'Approved' WHERE offer_id = ?");
                $stmt->bind_param('i', $offer_id);
                $stmt->execute();
                
                $stmt = $conn->prepare("UPDATE job_applications ja JOIN job_offers jo ON ja.application_id = jo.application_id SET ja.status = 'Hired' WHERE jo.offer_id = ?");
                $stmt->bind_param('i', $offer_id);
                $stmt->execute();
                
                // Send approval notification with dynamic template
                $emailSender = new EmailSender();
                $template_data = [
                    'name' => $offer_data['first_name'] . ' ' . $offer_data['last_name'],
                    'position' => $offer_data['job_title'],
                    'department' => $offer_data['department_name'],
                    'salary' => number_format($offer_data['offered_salary']),
                    'start_date' => date('F j, Y', strtotime($offer_data['start_date']))
                ];
                
                $subject = "Job Offer Approved - {$template_data['position']}";
                $body = "Dear {$template_data['name']},\n\nCongratulations! Your job offer has been approved by the Mayor.\n\nPosition: {$template_data['position']}\nDepartment: {$template_data['department']}\nSalary: ₱{$template_data['salary']}\nStart Date: {$template_data['start_date']}\n\nWelcome to the Municipal Team!\n\nBest regards,\nMunicipal HR Department";
                
                $sent = $emailSender->sendEmail($offer_data['email'], $subject, $body);
                
                // Log notification
                $status = $sent ? 'Sent' : 'Failed';
                $stmt = $conn->prepare("INSERT INTO notification_letters (type, recipient, subject, content, status, created_by, created_at, sent_at) VALUES ('Mayor Approval', ?, ?, ?, ?, ?, NOW(), NOW())");
                $stmt->bind_param('ssssi', $offer_data['email'], $subject, $body, $status, $_SESSION['user_id']);
                $stmt->execute();
                
                $success_message = "🏛️ Mayor approved! Candidate hired and notified.";
                break;
                
            case 'reject_offer':
                $offer_id = $_POST['offer_id'];
                
                // Get candidate and offer details for notification
                $stmt = $conn->prepare("SELECT jo.*, c.first_name, c.last_name, c.email, job.title as job_title FROM job_offers jo JOIN candidates c ON jo.candidate_id = c.candidate_id JOIN job_openings job ON jo.job_opening_id = job.job_opening_id WHERE jo.offer_id = ?");
                $stmt->bind_param('i', $offer_id);
                $stmt->execute();
                $offer_data = $stmt->get_result()->fetch_assoc();
                
                // Send rejection notification with dynamic template
                $emailSender = new EmailSender();
                $template_data = [
                    'name' => $offer_data['first_name'] . ' ' . $offer_data['last_name'],
                    'position' => $offer_data['job_title'],
                    'department' => $offer_data['department_name']
                ];
                
                $subject = "Application Status Update - {$template_data['position']}";
                $body = "Dear {$template_data['name']},\n\nThank you for your interest in the {$template_data['position']} position.\n\nAfter careful consideration, we have decided to move forward with other candidates.\n\nWe wish you success in your job search.\n\nBest regards,\nMunicipal HR Department";
                
                $sent = $emailSender->sendEmail($offer_data['email'], $subject, $body);
                
                // Log notification
                $status = $sent ? 'Sent' : 'Failed';
                $stmt = $conn->prepare("INSERT INTO notification_letters (type, recipient, subject, content, status, created_by, created_at, sent_at) VALUES ('Rejection Letter', ?, ?, ?, ?, ?, NOW(), NOW())");
                $stmt->bind_param('ssssi', $offer_data['email'], $subject, $body, $status, $_SESSION['user_id']);
                $stmt->execute();
                
                // Delete the job offer
                $stmt = $conn->prepare("DELETE FROM job_offers WHERE offer_id = ?");
                $stmt->bind_param('i', $offer_id);
                $stmt->execute();
                
                // Update application status to Rejected
                $stmt = $conn->prepare("UPDATE job_applications SET status = 'Rejected' WHERE application_id = ?");
                $stmt->bind_param('i', $offer_data['application_id']);
                $stmt->execute();
                
                $success_message = "❌ Offer rejected and rejection notification created!";
                break;
                
            case 'bulk_delete':
                if (isset($_POST['selected_letters']) && is_array($_POST['selected_letters'])) {
                    $letter_ids = $_POST['selected_letters'];
                    $placeholders = str_repeat('?,', count($letter_ids) - 1) . '?';
                    $stmt = $conn->prepare("DELETE FROM notification_letters WHERE letter_id IN ($placeholders)");
                    $stmt->bind_param(str_repeat('i', count($letter_ids)), ...$letter_ids);
                    $stmt->execute();
                    $success_message = "🗑️ " . count($letter_ids) . " notifications deleted successfully!";
                } else {
                    $success_message = "⚠️ No notifications selected for deletion.";
                }
                break;
                
            case 'toggle_email_mode':
                $config_file = 'email_config.php';
                $config_content = file_get_contents($config_file);
                
                if (strpos($config_content, 'const DEVELOPMENT_MODE = true;') !== false) {
                    $config_content = str_replace('const DEVELOPMENT_MODE = true;', 'const DEVELOPMENT_MODE = false;', $config_content);
                    $success_message = "🚀 Switched to LIVE mode!";
                } elseif (strpos($config_content, 'const DEVELOPMENT_MODE = false;') !== false) {
                    $config_content = str_replace('const DEVELOPMENT_MODE = false;', 'const DEVELOPMENT_MODE = true;', $config_content);
                    $success_message = "🛠️ Switched to DEV mode!";
                } else {
                    $success_message = "❌ Could not find DEVELOPMENT_MODE!";
                    break;
                }
                
                if (file_put_contents($config_file, $config_content) === false) {
                    $success_message = "❌ Failed to update config!";
                }
                break;
        }
    }
}

// Create notification_letters table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS notification_letters (
    letter_id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('Mayor Approval', 'Interview Letter', 'Offer Letter', 'Rejection Letter', 'General') NOT NULL,
    recipient VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    status ENUM('Draft', 'Sent', 'Failed', 'Delivered') DEFAULT 'Draft',
    created_by INT,
    created_at DATETIME,
    sent_at DATETIME
)");

// Get filter
$filter = $_GET['filter'] ?? 'all';

// Get letters based on filter
$letters_query = "SELECT * FROM notification_letters";
if ($filter != 'all') {
    $letters_query .= " WHERE type = '$filter'";
}
$letters_query .= " ORDER BY created_at DESC";
$letters = $conn->query($letters_query)->fetch_all(MYSQLI_ASSOC);

// Get job offers for mayor approval
$offers = $conn->query("SELECT jo.*, c.first_name, c.last_name, c.email, job.title as job_title, d.department_name
                       FROM job_offers jo
                       JOIN candidates c ON jo.candidate_id = c.candidate_id
                       JOIN job_openings job ON jo.job_opening_id = job.job_opening_id
                       JOIN departments d ON job.department_id = d.department_id
                       WHERE jo.approval_status = 'Pending'
                       ORDER BY jo.created_at DESC")->fetch_all(MYSQLI_ASSOC);

// Get email addresses for distribution
$emails = $conn->query("SELECT DISTINCT email FROM candidates UNION SELECT DISTINCT email FROM users")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Letter Management System - HR</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css?v=rose">
</head>
<body>
    <div class="container-fluid">
        <?php include 'navigation.php'; ?>
        <div class="row">
            <?php include 'sidebar.php'; ?>
            <div class="main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>📄 Letter Management System</h2>
                    <div>
                        <button class="btn btn-success mr-2" data-toggle="modal" data-target="#senderModal">
                            <i class="fas fa-paper-plane mr-2"></i>Email Manager
                        </button>
                        <button class="btn btn-info" data-toggle="modal" data-target="#emailModal">
                            <i class="fas fa-address-book mr-2"></i>Recipients
                        </button>
                    </div>
                </div>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo $success_message; ?>
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                <?php endif; ?>

                <!-- Filter Tabs -->
                <ul class="nav nav-tabs mb-4">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $filter == 'all' ? 'active' : ''; ?>" href="?filter=all">All Letters</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $filter == 'Mayor Approval' ? 'active' : ''; ?>" href="?filter=Mayor Approval">Mayor Approval</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $filter == 'Interview Letter' ? 'active' : ''; ?>" href="?filter=Interview Letter">Interview Letters</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $filter == 'Offer Letter' ? 'active' : ''; ?>" href="?filter=Offer Letter">Offer Letters</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $filter == 'Rejection Letter' ? 'active' : ''; ?>" href="?filter=Rejection Letter">Rejections</a>
                    </li>
                </ul>

                <!-- Mayor Approval Section -->
                <?php if ($filter == 'all' || $filter == 'Mayor Approval'): ?>
                    <?php if (count($offers) > 0): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-gavel mr-2"></i>Pending Mayor Approval (<?php echo count($offers); ?>)</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Candidate</th>
                                                <th>Position</th>
                                                <th>Salary</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($offers as $offer): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($offer['first_name'] . ' ' . $offer['last_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($offer['job_title']); ?></td>
                                                    <td>$<?php echo number_format($offer['offered_salary']); ?></td>
                                                    <td>
                                                        <?php if ($_SESSION['role'] == 'Mayor'): ?>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="mayor_approve">
                                                                <input type="hidden" name="offer_id" value="<?php echo $offer['offer_id']; ?>">
                                                                <button type="submit" class="btn btn-success btn-sm">
                                                                    <i class="fas fa-check"></i> Approve
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <span class="text-muted">Awaiting Mayor</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Letters Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <h5><i class="fas fa-envelope mr-2"></i>Letters (<?php echo count($letters); ?>)</h5>
                        <div>
                            <?php if (count($letters) > 50): ?>
                                <span class="badge badge-warning mr-2">High Volume</span>
                            <?php endif; ?>
                            <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#emailModal">
                                <i class="fas fa-paper-plane mr-1"></i>Email Distribution
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (count($letters) > 0): ?>
                            <form method="POST" id="bulkForm">
                                <input type="hidden" name="action" value="bulk_delete">
                                <div class="mb-3">
                                    <button type="button" class="btn btn-danger btn-sm" onclick="bulkDelete()">
                                        <i class="fas fa-trash mr-1"></i>Delete Selected
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-sm ml-2" onclick="selectAll()">
                                        <i class="fas fa-check-square mr-1"></i>Select All
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th><input type="checkbox" id="selectAllCheckbox" onchange="toggleAll()"></th>
                                                <th>Type</th>
                                                <th>Recipient</th>
                                                <th>Subject</th>
                                                <th>Status</th>
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                    <tbody>
                                        <?php foreach($letters as $letter): ?>
                                            <tr>
                                                <td><input type="checkbox" name="selected_letters[]" value="<?php echo $letter['letter_id']; ?>" class="letter-checkbox"></td>
                                                <td>
                                                    <span class="badge badge-primary"><?php echo $letter['type']; ?></span>
                                                </td>
                                                <td><?php echo htmlspecialchars($letter['recipient']); ?></td>
                                                <td><?php echo htmlspecialchars($letter['subject']); ?></td>
                                                <td>
                                                    <?php
                                                    $status_colors = ['Draft' => 'warning', 'Sent' => 'success', 'Failed' => 'danger', 'Delivered' => 'info'];
                                                    $color = $status_colors[$letter['status']] ?? 'secondary';
                                                    $status_icon = ['Draft' => 'fas fa-edit', 'Sent' => 'fas fa-check-circle', 'Failed' => 'fas fa-times-circle', 'Delivered' => 'fas fa-paper-plane'];
                                                    $icon = $status_icon[$letter['status']] ?? 'fas fa-question-circle';
                                                    ?>
                                                    <span class="badge badge-<?php echo $color; ?>">
                                                        <i class="<?php echo $icon; ?> mr-1"></i><?php echo $letter['status']; ?>
                                                    </span>
                                                    <?php if ($letter['status'] == 'Sent'): ?>
                                                        <br><small class="text-muted">(Simulated)</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($letter['created_at'])); ?></td>
                                                <td>
                                                    <?php if ($letter['status'] == 'Draft'): ?>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="action" value="send_letter">
                                                            <input type="hidden" name="letter_id" value="<?php echo $letter['letter_id']; ?>">
                                                            <button type="submit" class="btn btn-success btn-sm">
                                                                <i class="fas fa-paper-plane"></i> Send
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span class="text-muted">Sent</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    </table>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-info text-center">
                                <h5><i class="fas fa-info-circle"></i> No Letters</h5>
                                <p>No letters found for the selected filter.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Notification Area -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6><i class="fas fa-bell mr-2"></i>Recent Notifications (Last 3)</h6>
                    </div>
                    <div class="card-body" style="max-height: 200px; overflow-y: auto;">
                        <?php
                        $recent_letters = array_slice($letters, 0, 3);
                        if (count($recent_letters) > 0):
                        ?>
                            <?php foreach($recent_letters as $letter): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                    <div>
                                        <strong><?php echo $letter['type']; ?></strong> - <?php echo htmlspecialchars(substr($letter['subject'], 0, 40)) . (strlen($letter['subject']) > 40 ? '...' : ''); ?>
                                        <br><small class="text-muted">To: <?php echo htmlspecialchars(substr($letter['recipient'], 0, 30)) . (strlen($letter['recipient']) > 30 ? '...' : ''); ?></small>
                                    </div>
                                    <small class="text-muted"><?php echo date('M d, H:i', strtotime($letter['created_at'])); ?></small>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">No recent notifications</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <!-- Email Manager Modal -->
    <div class="modal fade" id="senderModal" tabindex="-1">
        <div class="modal-dialog modal-xl" style="max-width: 90%;">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h4 class="modal-title"><i class="fas fa-paper-plane mr-2"></i>Email Manager</h4>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <!-- Email Compose Section -->
                        <div class="col-lg-6">
                            <div class="card h-100">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-edit mr-2"></i>Compose & Send Email</h5>
                                </div>
                                <div class="card-body">
                                    <form id="emailForm">
                                        <div class="form-group">
                                            <label class="font-weight-bold">To:</label>
                                            <input type="email" id="emailTo" class="form-control form-control-lg" placeholder="Enter recipient email">
                                        </div>
                                        <div class="form-group">
                                            <label class="font-weight-bold">Subject:</label>
                                            <input type="text" id="emailSubject" class="form-control form-control-lg" placeholder="Enter email subject">
                                        </div>
                                        <div class="form-group">
                                            <label class="font-weight-bold">Message:</label>
                                            <textarea id="emailMessage" class="form-control" rows="10" placeholder="Type your message here..."></textarea>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <button type="button" class="btn btn-outline-secondary btn-lg" onclick="saveDraft()">
                                                <i class="fas fa-save mr-2"></i>Save Draft
                                            </button>
                                            <button type="button" class="btn btn-success btn-lg" onclick="sendEmail()">
                                                <i class="fas fa-paper-plane mr-2"></i>Send Email
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tools & Settings Section -->
                        <div class="col-lg-6">
                            <!-- Gmail Configuration -->
                            <div class="card mb-3">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-cog mr-2"></i>Gmail Configuration
                                        <span class="badge badge-light text-dark ml-2">LIVE</span>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="small font-weight-bold">Gmail Address:</label>
                                                <input type="email" id="gmailAddress" class="form-control" placeholder="your-email@gmail.com" value="<?php echo SMTP_USERNAME; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="small font-weight-bold">App Password:</label>
                                                <input type="password" id="gmailPassword" class="form-control" placeholder="16-character password">
                                            </div>
                                        </div>
                                    </div>
                                    <?php if (EmailConfig::DEVELOPMENT_MODE || EmailConfig::SMTP_USERNAME === 'your-gmail@gmail.com'): ?>
                                        <div class="alert alert-info mb-3">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            <strong>Development Mode:</strong> Emails are simulated and logged to file.
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Mode Toggle -->
                                    <div class="form-group">
                                        <label class="small font-weight-bold">Email Mode:</label>
                                        <div class="d-flex align-items-center">
                                            <span class="mr-2 small">DEV</span>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_email_mode">
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox" class="custom-control-input" id="emailModeSwitch" <?php echo !EmailConfig::DEVELOPMENT_MODE ? 'checked' : ''; ?> onchange="this.form.submit()">
                                                    <label class="custom-control-label" for="emailModeSwitch"></label>
                                                </div>
                                            </form>
                                            <span class="ml-2 small">LIVE</span>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mb-3">
                                        <button class="btn btn-success" onclick="updateGmailConfig()">
                                            <i class="fas fa-save mr-1"></i>Update Config
                                        </button>
                                        <button class="btn btn-info" onclick="showGmailHelp()">
                                            <i class="fas fa-question-circle mr-1"></i>Setup Guide
                                        </button>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <button class="btn btn-outline-primary btn-sm" onclick="viewEmailLog()">
                                            <i class="fas fa-file-alt mr-1"></i>View Email Log
                                        </button>
                                        <button class="btn btn-outline-success btn-sm" onclick="verifyGmail()">
                                            <i class="fas fa-check-circle mr-1"></i>Verify Gmail
                                        </button>
                                        <button class="btn btn-outline-info btn-sm ml-1" onclick="checkGmailTips()">
                                            <i class="fas fa-question-circle mr-1"></i>Not Receiving?
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Quick Templates -->
                            <div class="card mb-3">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0"><i class="fas fa-file-alt mr-2"></i>Email Templates</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <button class="btn btn-outline-info btn-block mb-2" onclick="loadTemplate('interview')">
                                                <i class="fas fa-handshake mr-1"></i>Interview
                                            </button>
                                            <button class="btn btn-outline-success btn-block mb-2" onclick="loadTemplate('offer')">
                                                <i class="fas fa-trophy mr-1"></i>Job Offer
                                            </button>
                                            <button class="btn btn-outline-primary btn-block" onclick="loadTemplate('mayor_approval')">
                                                <i class="fas fa-gavel mr-1"></i>Mayor Approval
                                            </button>
                                        </div>
                                        <div class="col-6">
                                            <button class="btn btn-outline-warning btn-block mb-2" onclick="loadTemplate('rejection')">
                                                <i class="fas fa-times-circle mr-1"></i>Rejection
                                            </button>
                                            <button class="btn btn-outline-info btn-block mb-2" onclick="loadTemplate('mayor_pending')">
                                                <i class="fas fa-clock mr-1"></i>Mayor Pending
                                            </button>
                                            <button class="btn btn-outline-secondary btn-block" onclick="loadTemplate('general')">
                                                <i class="fas fa-envelope mr-1"></i>General
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Mayor Email Config -->
                            <div class="card mb-3">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0"><i class="fas fa-user-tie mr-2"></i>Mayor Email Configuration</h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label class="small font-weight-bold">Mayor's Email Address:</label>
                                        <input type="email" id="mayorEmail" class="form-control" value="<?php echo MAYOR_EMAIL; ?>" placeholder="mayor@city.gov">
                                    </div>
                                    <button class="btn btn-warning btn-sm" onclick="updateMayorEmail()">
                                        <i class="fas fa-save mr-1"></i>Update Mayor Email
                                    </button>
                                    <small class="text-muted d-block mt-2">This email receives job offer approval requests</small>
                                </div>
                            </div>
                            
                            <!-- Quick Recipients -->
                            <div class="card">
                                <div class="card-header bg-secondary text-white">
                                    <h6 class="mb-0"><i class="fas fa-users mr-2"></i>Quick Recipients</h6>
                                </div>
                                <div class="card-body" style="max-height: 200px; overflow-y: auto;">
                                    <?php foreach($emails as $email): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                            <span class="small"><?php echo $email['email']; ?></span>
                                            <button class="btn btn-sm btn-primary" onclick="selectRecipient('<?php echo $email['email']; ?>')">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Email Distribution Modal -->
    <div class="modal fade" id="emailModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Email Distribution List</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <h6>Registered Email Addresses:</h6>
                    <div class="list-group">
                        <?php foreach($emails as $email): ?>
                            <div class="list-group-item d-flex justify-content-between">
                                <span><?php echo $email['email']; ?></span>
                                <button class="btn btn-sm btn-outline-primary" onclick="copyEmail('<?php echo $email['email']; ?>')">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function copyEmail(email) {
            navigator.clipboard.writeText(email);
            alert('Email copied: ' + email);
        }
        
        function selectRecipient(email) {
            document.getElementById('emailTo').value = email;
        }
        
        function loadTemplate(type) {
            const templates = {
                interview: {
                    subject: 'Interview Invitation - {position}',
                    message: 'Dear {name},\n\nWe are pleased to invite you for an interview for the position of {position}.\n\nInterview Details:\nDate: {date}\nTime: {time}\nLocation: Municipal Office, HR Department\n\nPlease confirm your attendance.\n\nBest regards,\nHR Department'
                },
                offer: {
                    subject: 'Job Offer - {position}',
                    message: 'Dear {name},\n\nCongratulations! We are pleased to offer you the position of {position}.\n\nOffer Details:\nPosition: {position}\nSalary: ₱{salary}\nStart Date: {start_date}\nDepartment: {department}\n\nPlease respond within 7 days.\n\nBest regards,\nHR Department'
                },
                rejection: {
                    subject: 'Application Status Update',
                    message: 'Dear {name},\n\nThank you for your interest in the position of {position}.\n\nAfter careful consideration, we have decided to move forward with other candidates.\n\nWe wish you success in your job search.\n\nBest regards,\nHR Department'
                },
                mayor_approval: {
                    subject: 'Job Offer Approved - {position}',
                    message: 'Dear {name},\n\nCongratulations! Your application for {position} has been approved by the Mayor.\n\nPosition: {position}\nDepartment: {department}\nSalary: ₱{salary}\nStart Date: {start_date}\n\nWelcome to the Municipal Team!\n\nBest regards,\nMunicipal HR Department'
                },
                mayor_pending: {
                    subject: 'Application Under Mayor Review - {position}',
                    message: 'Dear {name},\n\nYour application for {position} is under Mayor review.\n\nWe will notify you once a decision is made.\n\nBest regards,\nMunicipal HR Department'
                },
                general: {
                    subject: 'Important Notice - HR Department',
                    message: 'Dear {recipient},\n\n{message}\n\nIf you have any questions, please contact us.\n\nBest regards,\nHR Department'
                }
            };
            
            if (templates[type]) {
                document.getElementById('emailSubject').value = templates[type].subject;
                document.getElementById('emailMessage').value = templates[type].message;
            }
        }
        
        function saveDraft() {
            const to = document.getElementById('emailTo').value;
            const subject = document.getElementById('emailSubject').value;
            const message = document.getElementById('emailMessage').value;
            
            if (!to || !subject || !message) {
                alert('Please fill in all fields before saving draft.');
                return;
            }
            
            localStorage.setItem('emailDraft_' + Date.now(), JSON.stringify({ to, subject, message, timestamp: new Date().toISOString() }));
            alert('✅ Draft saved successfully!');
        }
        
        function sendEmail() {
            const to = document.getElementById('emailTo').value;
            const subject = document.getElementById('emailSubject').value;
            const message = document.getElementById('emailMessage').value;
            
            if (!to || !subject || !message) {
                alert('Please fill in all fields before sending.');
                return;
            }
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="send_custom_email">
                <input type="hidden" name="email_to" value="${to}">
                <input type="hidden" name="email_subject" value="${subject}">
                <input type="hidden" name="email_message" value="${message}">
            `;
            
            document.body.appendChild(form);
            form.submit();
        }
        
        function toggleAll() {
            const selectAll = document.getElementById('selectAllCheckbox');
            const checkboxes = document.querySelectorAll('.letter-checkbox');
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
        }
        
        function selectAll() {
            const checkboxes = document.querySelectorAll('.letter-checkbox');
            checkboxes.forEach(cb => cb.checked = true);
            document.getElementById('selectAllCheckbox').checked = true;
        }
        
        function bulkDelete() {
            const selected = document.querySelectorAll('.letter-checkbox:checked');
            if (selected.length === 0) {
                alert('Please select notifications to delete.');
                return;
            }
            if (confirm(`Delete ${selected.length} selected notifications?`)) {
                document.getElementById('bulkForm').submit();
            }
        }
        
        function testEmail() {
            alert('📧 Test email logged to email_log.txt\n\nIn production mode, this would send an actual test email.');
        }
        
        function viewEmailLog() {
            // Create a modal to show email log
            fetch('email_log.txt')
                .then(response => response.text())
                .then(data => {
                    const logContent = data || 'No email log found.';
                    const modal = `
                        <div class="modal fade" id="emailLogModal" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title"><i class="fas fa-file-alt mr-2"></i>Email Log</h5>
                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                    </div>
                                    <div class="modal-body">
                                        <pre style="background: #f8f9fa; padding: 15px; border-radius: 5px; max-height: 400px; overflow-y: auto;">${logContent}</pre>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    $('body').append(modal);
                    $('#emailLogModal').modal('show');
                    $('#emailLogModal').on('hidden.bs.modal', function () {
                        $(this).remove();
                    });
                })
                .catch(error => {
                    alert('📄 Email log file not found or empty.\n\nThis means no emails have been sent yet.');
                });
        }
        
        function verifyGmail() {
            const testEmail = prompt('Enter your Gmail address to verify:');
            if (testEmail && testEmail.includes('@')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="verify_gmail">
                    <input type="hidden" name="test_email" value="${testEmail}">
                `;
                document.body.appendChild(form);
                form.submit();
            } else if (testEmail) {
                alert('Please enter a valid email address.');
            }
        }
        
        function updateGmailConfig() {
            const gmail = document.getElementById('gmailAddress').value;
            const password = document.getElementById('gmailPassword').value;
            
            if (!gmail || !password) {
                alert('Please enter both Gmail address and App Password.');
                return;
            }
            
            if (password.length !== 16) {
                alert('Gmail App Password should be 16 characters long.');
                return;
            }
            
            // Store in localStorage for this session
            localStorage.setItem('gmail_config', JSON.stringify({ gmail, password }));
            
            alert('✅ Gmail configuration updated!\n\nNote: To make this permanent, update email_config.php with:\n- SMTP_USERNAME: ' + gmail + '\n- SMTP_PASSWORD: ' + password);
        }
        
        function showGmailHelp() {
            const helpText = `📖 GMAIL SETUP GUIDE\n\n1. Enable 2-Factor Authentication on Gmail\n2. Go to Google Account > Security > App passwords\n3. Generate app password for "Mail"\n4. Copy the 16-character password\n5. Enter your Gmail and app password above\n\n🔒 Security: Use App Password, NOT your regular Gmail password!`;
            alert(helpText);
        }
        
        function checkGmailTips() {
            const tips = `📧 LIVE MODE REQUIRES SMTP SERVER\n\n⚠️ XAMPP doesn't support Gmail SMTP by default\n\nSOLUTIONS:\n1. 🛠️ Use DEV MODE for testing (toggle switch left)\n2. 📊 Install PHPMailer library for real Gmail\n3. 🌐 Use web hosting with SMTP support\n4. 📧 Configure local SMTP server\n\nFor now: Switch to DEV mode to test email templates`;
            alert(tips);
        }
        
        function updateMayorEmail() {
            const mayorEmail = document.getElementById('mayorEmail').value;
            
            if (!mayorEmail || !mayorEmail.includes('@')) {
                alert('Please enter a valid email address for the Mayor.');
                return;
            }
            
            // Store in localStorage for this session
            localStorage.setItem('mayor_email', mayorEmail);
            
            alert('✅ Mayor email updated to: ' + mayorEmail + '\n\nNote: To make this permanent, update config.php with:\ndefine(\'MAYOR_EMAIL\', \'' + mayorEmail + '\');');
        }
        
        // Load saved Gmail config on page load
        $(document).ready(function() {
            const savedConfig = localStorage.getItem('gmail_config');
            if (savedConfig) {
                const config = JSON.parse(savedConfig);
                document.getElementById('gmailAddress').value = config.gmail;
                document.getElementById('gmailPassword').value = config.password;
            }
        });
    </script>
</body>
</html>