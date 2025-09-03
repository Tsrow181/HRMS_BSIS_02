<?php
require_once 'email_config.php';

// Test email functionality
echo "<h2>Email Notification System Test</h2>";

$test_email = "test@example.com";
$test_name = "John Doe";
$test_job = "Software Developer";

$statuses = ['Approved', 'Interview', 'Pending', 'Assessment', 'Hired', 'Rejected'];

echo "<div style='font-family: Arial; margin: 20px;'>";

foreach ($statuses as $status) {
    echo "<h3>Testing: $status Status</h3>";
    
    // Test email generation (without actually sending)
    $result = sendEmail($test_email, '', '', $test_name, $test_job, $status);
    
    echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0; background: #f9f9f9;'>";
    echo "<strong>Status:</strong> $status<br>";
    echo "<strong>To:</strong> $test_email<br>";
    echo "<strong>Name:</strong> $test_name<br>";
    echo "<strong>Job:</strong> $test_job<br>";
    echo "<strong>Result:</strong> " . ($result ? "✅ Email sent successfully" : "❌ Email failed") . "<br>";
    echo "</div>";
}

echo "</div>";

// Test with actual email if provided
if (isset($_GET['email']) && filter_var($_GET['email'], FILTER_VALIDATE_EMAIL)) {
    $real_email = $_GET['email'];
    echo "<h3>Sending Test Email to: $real_email</h3>";
    
    $result = sendEmail($real_email, '', '', "Test User", "Test Position", "Approved");
    echo $result ? "✅ Test email sent!" : "❌ Failed to send test email";
}

echo "<br><br><a href='?email=your-email@example.com'>Test with your email</a>";
?>