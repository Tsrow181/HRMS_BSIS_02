<?php
require_once 'config.php';

try {
    $conn->exec("CREATE TABLE onboarding_progress (
        progress_id INT PRIMARY KEY AUTO_INCREMENT,
        application_id INT NOT NULL,
        task_id INT NOT NULL,
        status ENUM('Pending', 'Completed') DEFAULT 'Pending',
        completed_date DATETIME NULL
    )");
    
    echo "✅ onboarding_progress table created successfully!<br>";
    
    // Insert some default tasks if onboarding_tasks is empty
    $count = $conn->query("SELECT COUNT(*) as count FROM onboarding_tasks")->fetch()['count'];
    if ($count == 0) {
        $conn->exec("INSERT INTO onboarding_tasks (task_name, description, task_order) VALUES
            ('Document Verification', 'Verify ID and certificates', 1),
            ('Contract Signing', 'Sign employment contract', 2),
            ('IT Setup', 'Setup computer and email', 3),
            ('Orientation', 'Company orientation', 4)");
        echo "✅ Default tasks added!<br>";
    }
    
    echo "<a href='onboarding.php'>Go to Onboarding Page</a>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>