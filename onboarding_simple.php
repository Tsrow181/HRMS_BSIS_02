<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
require_once 'config.php';

echo "Testing database connection...<br>";

try {
    $test = $conn->query("SELECT COUNT(*) FROM onboarding_tasks");
    echo "onboarding_tasks table exists<br>";
} catch (Exception $e) {
    echo "Error with onboarding_tasks: " . $e->getMessage() . "<br>";
}

try {
    $test = $conn->query("SELECT COUNT(*) FROM onboarding_progress");
    echo "onboarding_progress table exists<br>";
} catch (Exception $e) {
    echo "Error with onboarding_progress: " . $e->getMessage() . "<br>";
}

try {
    $candidates = $conn->query("SELECT COUNT(*) as count FROM job_applications WHERE status = 'Reference Check'")->fetch();
    echo "Candidates in Reference Check: " . $candidates['count'] . "<br>";
} catch (Exception $e) {
    echo "Error checking candidates: " . $e->getMessage() . "<br>";
}

echo "If you see this, PHP is working. Check table names in your database.";
?>