<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
require_once 'config.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_stage':
                $job_opening_id = $_POST['job_opening_id'];
                $stage_name = $_POST['stage_name'];
                $stage_order = $_POST['stage_order'];
                $description = $_POST['description'];
                $is_mandatory = isset($_POST['is_mandatory']) ? 1 : 0;
                
                $stmt = $conn->prepare("INSERT INTO interview_stages (job_opening_id, stage_name, stage_order, description, is_mandatory) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$job_opening_id, $stage_name, $stage_order, $description, $is_mandatory]);
                break;
        }
        header('Location: interview_stages.php');
        exit;
    }
}

// Get interview stages with job info
$stages_query = "SELECT ist.*, jo.title as job_title, d.department_name
                 FROM interview_stages ist
                 JOIN job_openings jo ON ist.job_opening_id = jo.job_opening_id
                 JOIN departments d ON jo.department_id = d.department_id
                 ORDER BY jo.title, ist.stage_order";
$stages = $conn->query($stages_query)->fetchAll(PDO::FETCH_ASSOC);

// Get active job openings for form
$job_openings = $conn->query("SELECT jo.*, d.department_name FROM job_openings jo 
                              JOIN departments d ON jo.department_id = d.department_id 
                              WHERE jo.status = 'Open' ORDER BY jo.title")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interview Stages - HR Management System</title>
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
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-plus"></i> Add Interview Stage</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="create_stage">
                                    <div class="form-group">
                                        <label>Job Opening</label>
                                        <select name="job_opening_id" class="form-control" required>
                                            <option value="">Select Job Opening</option>
                                            <?php foreach($job_openings as $job): ?>
                                            <option value="<?php echo $job['job_opening_id']; ?>">
                                                <?php echo htmlspecialchars($job['title'] . ' - ' . $job['department_name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Stage Name</label>
                                        <input type="text" name="stage_name" class="form-control" required placeholder="e.g., Initial Screening">
                                    </div>
                                    <div class="form-group">
                                        <label>Stage Order</label>
                                        <input type="number" name="stage_order" class="form-control" min="1" value="1" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea name="description" class="form-control" rows="3"></textarea>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="is_mandatory" class="form-check-input" id="mandatory" checked>
                                        <label class="form-check-label" for="mandatory">Mandatory Stage</label>
                                    </div>
                                    <button type="submit" class="btn btn-primary mt-3">Add Stage</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-list"></i> Interview Stages</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Job Opening</th>
                                                <th>Stage</th>
                                                <th>Order</th>
                                                <th>Mandatory</th>
                                                <th>Description</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($stages) > 0): ?>
                                                <?php foreach($stages as $stage): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($stage['job_title']); ?></strong><br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($stage['department_name']); ?></small>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($stage['stage_name']); ?></td>
                                                        <td><span class="badge badge-primary"><?php echo $stage['stage_order']; ?></span></td>
                                                        <td>
                                                            <?php if ($stage['is_mandatory']): ?>
                                                                <span class="badge badge-danger">Required</span>
                                                            <?php else: ?>
                                                                <span class="badge badge-secondary">Optional</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($stage['description']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No interview stages configured</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>