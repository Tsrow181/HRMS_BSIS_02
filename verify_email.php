<?php
require_once 'config.php';

$message = '';
$success = false;

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Find candidate with this token
    $stmt = $conn->prepare("SELECT candidate_id, first_name FROM candidates WHERE verification_token = ? AND email_verified = 0");
    $stmt->execute([$token]);
    $candidate = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($candidate) {
        // Verify email
        $stmt = $conn->prepare("UPDATE candidates SET email_verified = 1, verification_token = NULL WHERE candidate_id = ?");
        $stmt->execute([$candidate['candidate_id']]);
        
        $message = "Email verified successfully! You will now receive application updates.";
        $success = true;
    } else {
        $message = "Invalid or expired verification link.";
    }
} else {
    $message = "No verification token provided.";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Email Verification</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header <?php echo $success ? 'bg-success' : 'bg-danger'; ?> text-white">
                        <h5><?php echo $success ? 'Email Verified' : 'Verification Failed'; ?></h5>
                    </div>
                    <div class="card-body text-center">
                        <p><?php echo $message; ?></p>
                        <?php if ($success): ?>
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <?php else: ?>
                            <i class="fas fa-times-circle fa-3x text-danger mb-3"></i>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>