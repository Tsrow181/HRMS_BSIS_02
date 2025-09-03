<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
require_once 'config.php';

// Handle approval/decline actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $candidate_id = $_POST['candidate_id'];
        if ($_POST['action'] === 'approve') {
            $conn->prepare("UPDATE candidates SET source = 'Approved' WHERE candidate_id = ?")->execute([$candidate_id]);
        } elseif ($_POST['action'] === 'decline') {
            $conn->prepare("UPDATE candidates SET source = 'Declined' WHERE candidate_id = ?")->execute([$candidate_id]);
        }
    }
}

// Get all candidates
$candidates = $conn->query("SELECT * FROM candidates ORDER BY candidate_id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidates - HR Management System</title>
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
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-users"></i> Candidates Management</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($candidates) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Current Position</th>
                                            <th>Expected Salary</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($candidates as $candidate): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($candidate['email']); ?></td>
                                                <td><?php echo htmlspecialchars($candidate['phone']); ?></td>
                                                <td><?php echo htmlspecialchars($candidate['current_position'] ?: 'Not specified'); ?></td>
                                                <td>₱<?php echo number_format($candidate['expected_salary'] ?: 0); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php 
                                                        echo $candidate['source'] == 'Approved' ? 'success' : 
                                                            ($candidate['source'] == 'Declined' ? 'danger' : 'warning'); 
                                                    ?>">
                                                        <?php echo $candidate['source']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($candidate['source'] == 'Interview Passed'): ?>
                                                        <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="candidate_id" value="<?php echo $candidate['candidate_id']; ?>">
                                                            <input type="hidden" name="action" value="approve">
                                                            <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Approve this candidate?')">
                                                                <i class="fas fa-check"></i> Approve
                                                            </button>
                                                        </form>
                                                        <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="candidate_id" value="<?php echo $candidate['candidate_id']; ?>">
                                                            <input type="hidden" name="action" value="decline">
                                                            <button type="submit" class="btn btn-danger btn-sm ml-1" onclick="return confirm('Decline this candidate?')">
                                                                <i class="fas fa-times"></i> Decline
                                                            </button>
                                                        </form>
                                                    <?php elseif ($candidate['source'] == 'Approved'): ?>
                                                        <a href="job_offers.php?candidate_id=<?php echo $candidate['candidate_id']; ?>" class="btn btn-primary btn-sm">
                                                            <i class="fas fa-file-contract"></i> Job Offer
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($candidate['resume_url']): ?>
                                                        <a href="<?php echo $candidate['resume_url']; ?>" target="_blank" class="btn btn-info btn-sm ml-1">
                                                            <i class="fas fa-file-pdf"></i> Resume
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <h5><i class="fas fa-info-circle"></i> No Candidates</h5>
                                <p>No candidates found in the system.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>