<?php
// Function to link candidate documents to employee when hired
function linkCandidateDocuments($candidate_id, $employee_id, $conn) {
    try {
        // Update document_management records to link candidate documents to employee
        $stmt = $conn->prepare("
            UPDATE document_management 
            SET employee_id = ?, 
                notes = REPLACE(notes, CONCAT('Candidate ID: ', ?), CONCAT('Employee ID: ', ?))
            WHERE employee_id = 0 
            AND notes LIKE CONCAT('%Candidate ID: ', ?, '%')
        ");
        
        $stmt->execute([$employee_id, $candidate_id, $employee_id, $candidate_id]);
        
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Error linking candidate documents: " . $e->getMessage());
        return false;
    }
}

// Example usage when candidate is hired:
// $documents_linked = linkCandidateDocuments($candidate_id, $new_employee_id, $conn);
?>