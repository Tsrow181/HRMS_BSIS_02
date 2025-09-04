<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'config.php';

echo "Job Offers Page<br>";

try {
    $candidates = $conn->query("SELECT c.*, ja.application_id FROM candidates c JOIN job_applications ja ON c.candidate_id = ja.candidate_id WHERE ja.status = 'Reference Check'")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($candidates) . " candidates ready for offers:<br>";
    
    foreach($candidates as $candidate) {
        echo "- " . $candidate['first_name'] . " " . $candidate['last_name'] . " (App ID: " . $candidate['application_id'] . ")<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

echo "<br><a href='job_offers.php'>Back to Job Offers</a>";
?>