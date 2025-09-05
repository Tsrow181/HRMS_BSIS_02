<?php
require_once 'config.php';

echo "<h3>Debug: Candidates with 'Completed All Stages' status</h3>";

$query = "SELECT c.first_name, c.last_name, c.source, ja.status, ja.application_id 
          FROM candidates c 
          JOIN job_applications ja ON c.candidate_id = ja.candidate_id 
          WHERE ja.status = 'Completed All Stages'";

$result = $conn->query($query);
$candidates = $result->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Found " . count($candidates) . " candidates with 'Completed All Stages' status:</p>";

if (count($candidates) > 0) {
    echo "<table border='1'>";
    echo "<tr><th>Name</th><th>Source</th><th>Status</th><th>App ID</th></tr>";
    foreach ($candidates as $candidate) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']) . "</td>";
        echo "<td>" . htmlspecialchars($candidate['source']) . "</td>";
        echo "<td>" . htmlspecialchars($candidate['status']) . "</td>";
        echo "<td>" . htmlspecialchars($candidate['application_id']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No candidates found with 'Completed All Stages' status.</p>";
    
    echo "<h4>All candidates statuses:</h4>";
    $all_query = "SELECT c.first_name, c.last_name, c.source, ja.status 
                  FROM candidates c 
                  JOIN job_applications ja ON c.candidate_id = ja.candidate_id 
                  ORDER BY ja.status";
    
    $all_result = $conn->query($all_query);
    $all_candidates = $all_result->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Name</th><th>Source</th><th>Status</th></tr>";
    foreach ($all_candidates as $candidate) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']) . "</td>";
        echo "<td>" . htmlspecialchars($candidate['source']) . "</td>";
        echo "<td>" . htmlspecialchars($candidate['status']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>