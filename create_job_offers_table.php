<?php
require_once 'config.php';

try {
    $conn->exec("CREATE TABLE IF NOT EXISTS job_offers (
        offer_id INT PRIMARY KEY AUTO_INCREMENT,
        application_id INT NOT NULL,
        salary DECIMAL(10,2) NOT NULL,
        start_date DATE NOT NULL,
        benefits TEXT,
        offer_date DATETIME NOT NULL,
        response_date DATETIME NULL,
        status ENUM('Sent', 'Accepted', 'Rejected') DEFAULT 'Sent'
    )");
    
    echo "✅ job_offers table created successfully!<br>";
    echo "<a href='job_offers.php'>Go to Job Offers</a>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>