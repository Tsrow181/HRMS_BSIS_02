<?php
require_once 'config.php';

try {
    // Add missing columns to candidates table
    $conn->exec("ALTER TABLE candidates ADD COLUMN resume_data LONGBLOB");
    echo "Added resume_data column<br>";
    
    $conn->exec("ALTER TABLE candidates ADD COLUMN resume_filename VARCHAR(255)");
    echo "Added resume_filename column<br>";
    
    $conn->exec("ALTER TABLE candidates ADD COLUMN photo_data LONGBLOB");
    echo "Added photo_data column<br>";
    
    $conn->exec("ALTER TABLE candidates ADD COLUMN photo_filename VARCHAR(255)");
    echo "Added photo_filename column<br>";
    
    $conn->exec("ALTER TABLE candidates ADD COLUMN email_verified TINYINT(1) DEFAULT 0");
    echo "Added email_verified column<br>";
    
    $conn->exec("ALTER TABLE candidates ADD COLUMN verification_token VARCHAR(64) NULL");
    echo "Added verification_token column<br>";
    
    echo "<br>All columns added successfully!";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>