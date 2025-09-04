<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Include database connection
require_once 'config.php';

// Use the global database connection
$pdo = $conn;

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_certification':
                try {
                    $stmt = $pdo->prepare("INSERT INTO employee_skills (employee_id, skill_id, proficiency_level, assessed_date, certification_url, expiry_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['employee_id'],
                        $_POST['skill_id'],
                        $_POST['proficiency_level'],
                        $_POST['assessed_date'],
                        $_POST['certification_url'],
                        $_POST['expiry_date'],
                        $_POST['notes']
                    ]);
                    $message = "Certification added successfully!";
                    $messageType = "success";
                } catch (PDOException $e) {
                    $message = "Error adding certification: " . $e->getMessage();
                    $messageType = "error";
                }
                break;
            
            case 'update_certification':
                try {
                    $stmt = $pdo->prepare("UPDATE employee_skills SET proficiency_level = ?, assessed_date = ?, certification_url = ?, expiry_date = ?, notes = ? WHERE employee_skill_id = ?");
                    $stmt->execute([
                        $_POST['proficiency_level'],
                        $_POST['assessed_date'],
                        $_POST['certification_url'],
                        $_POST['expiry_date'],
                        $_POST['notes'],
                        $_POST['employee_skill_id']
                    ]);
                    $message = "Certification updated successfully!";
                    $messageType = "success";
                } catch (PDOException $e) {
                    $message = "Error updating certification: " . $e->getMessage();
                    $messageType = "error";
                }
                break;
            
            case 'delete_certification':
                try {
                    $stmt = $pdo->prepare("DELETE FROM employee_skills WHERE employee_skill_id=?");
                    $stmt->execute([$_POST['employee_skill_id']]);
                    $message = "Certification deleted successfully!";
                    $messageType = "success";
                } catch (PDOException $e) {
                    $message = "Error deleting certification: " . $e->getMessage();
                    $messageType = "error";
                }
                break;
        }
    }
}

// Fetch certifications with details
try {
    $stmt = $pdo->query("
        SELECT es.*, e.first_name, e.last_name, s.skill_name, s.category 
        FROM employee_skills es 
        JOIN employee_profiles ep ON es.employee_id = ep.employee_id 
        JOIN personal_information e ON ep.personal_info_id = e.personal_info_id 
        JOIN skill_matrix s ON es.skill_id = s.skill_id 
        WHERE es.certification_url IS NOT NULL AND es.certification_url != ''
        ORDER BY es.expiry_date ASC
    ");
    $certifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $certifications = [];
    $message = "Error fetching certifications: " . $e->getMessage();
    $messageType = "error";
}

// Fetch employees for dropdowns
try {
    $stmt = $pdo->query("
        SELECT ep.employee_id, pi.first_name, pi.last_name 
        FROM employee_profiles ep 
        JOIN personal_information pi ON ep.personal_info_id = pi.personal_info_id 
        ORDER BY pi.last_name
    ");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $employees = [];
}

// Fetch skills for dropdowns
try {
    $stmt = $pdo->query("SELECT * FROM skill_matrix ORDER BY skill_name");
    $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $skills = [];
}

// Get statistics
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM employee_skills WHERE certification_url IS NOT NULL AND certification_url != ''");
    $totalCertifications = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM employee_skills WHERE certification_url IS NOT NULL AND certification_url != '' AND expiry_date >= CURDATE()");
    $activeCertifications = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM employee_skills WHERE certification_url IS NOT NULL AND certification_url != '' AND expiry_date < CURDATE()");
    $expiredCertifications = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM employee_skills WHERE certification_url IS NOT NULL AND certification_url != '' AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
    $expiringSoon = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
} catch (PDOException $e) {
    $totalCertifications = 0;
    $activeCertifications = 0;
    $expiredCertifications = 0;
    $expiringSoon = 0;
}

// Function to get status badge class
function getStatusBadgeClass($expiryDate) {
    if (!$expiryDate) return 'status-unknown';
    
    $expiry = new DateTime($expiryDate);
    $today = new DateTime();
    $diff = $today->diff($expiry);
    
    if ($expiry < $today) {
        return 'status-expired';
    } elseif ($diff->days <= 30) {
        return 'status-expiring';
    } else {
        return 'status-active';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certifications Management - HR System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css?v=rose">
    <style>
        :root {
            --azure-blue: #E91E63;
            --azure-blue-light: #F06292;
            --azure-blue-dark: #C2185B;
            --azure-blue-lighter: #F8BBD0;
            --azure-blue-pale: #FCE4EC;
        }

        .section-title {
            color: var(--azure-blue);
            margin-bottom: 30px;
            font-weight: 600;
        }
        
        body {
            background: var(--azure-blue-pale);
        }

        .main-content {
            background: var(--azure-blue-pale);
            padding: 20px;
        }

        .btn-primary {
            background: var(--azure-blue);
            border-color: var(--azure-blue);
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .card-header {
            background: linear-gradient(135deg, var(--azure-blue) 0%, var(--azure-blue-dark) 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
            font-weight: 600;
        }

        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .stats-card i {
            font-size: 3rem;
            color: var(--azure-blue);
            margin-bottom: 15px;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active { background: #d4edda; color: #155724; }
        .status-expiring { background: #fff3cd; color: #856404; }
        .status-expired { background: #f8d7da; color: #721c24; }
        .status-unknown { background: #e2e3e5; color: #383d41; }

        .certification-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-left: 5px solid var(--azure-blue);
        }

        .certification-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 15px;
        }

        .certification-title {
            color: var(--azure-blue-dark);
            font-weight: 600;
            margin: 0;
        }

        .certification-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
            color: #666;
        }

        .meta-item i {
            color: var(--azure-blue);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <?php include 'navigation.php'; ?>
        <div class="row">
            <?php include 'sidebar.php'; ?>
            <div class="main-content">
                <h2 class="section-title">Certifications Management</h2>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <i class="fas fa-certificate"></i>
                            <h3><?php echo $totalCertifications; ?></h3>
                            <h6>Total Certifications</h6>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <i class="fas fa-check-circle"></i>
                            <h3><?php echo $activeCertifications; ?></h3>
                            <h6>Active Certifications</h6>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <i class="fas fa-exclamation-triangle"></i>
                            <h3><?php echo $expiringSoon; ?></h3>
                            <h6>Expiring Soon (30 days)</h6>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <i class="fas fa-times-circle"></i>
                            <h3><?php echo $expiredCertifications; ?></h3>
                            <h6>Expired Certifications</h6>
                        </div>
                    </div>
                </div>

                <!-- Controls -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="input-group" style="max-width: 400px;">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" class="form-control" id="certificationSearch" placeholder="Search certifications...">
                    </div>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#addCertificationModal">
                        <i class="fas fa-plus"></i> Add Certification
                    </button>
                </div>

                <!-- Certifications Grid -->
                <div class="row" id="certificationsGrid">
                    <?php foreach ($certifications as $cert): ?>
                    <div class="col-md-6 col-lg-4 certification-item">
                        <div class="certification-card">
                            <div class="certification-header">
                                <h5 class="certification-title"><?php echo htmlspecialchars($cert['skill_name']); ?></h5>
                                <span class="status-badge <?php echo getStatusBadgeClass($cert['expiry_date']); ?>">
                                    <?php 
                                    if (!$cert['expiry_date']) {
                                        echo 'No Expiry';
                                    } elseif (new DateTime($cert['expiry_date']) < new DateTime()) {
                                        echo 'Expired';
                                    } elseif ((new DateTime($cert['expiry_date']))->diff(new DateTime())->days <= 30) {
                                        echo 'Expiring Soon';
                                    } else {
                                        echo 'Active';
                                    }
                                    ?>
                                </span>
                            </div>
                            
                            <div class="certification-meta">
                                <div class="meta-item">
                                    <i class="fas fa-user"></i>
                                    <span><?php echo htmlspecialchars($cert['first_name'] . ' ' . $cert['last_name']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-tag"></i>
                                    <span><?php echo htmlspecialchars($cert['category']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-star"></i>
                                    <span><?php echo htmlspecialchars($cert['proficiency_level']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-calendar"></i>
                                    <span><?php echo date('M d, Y', strtotime($cert['assessed_date'])); ?></span>
                                </div>
                            </div>
                            
                            <?php if ($cert['expiry_date']): ?>
                            <div class="mb-3">
                                <small class="text-muted">
                                    <strong>Expires:</strong> <?php echo date('M d, Y', strtotime($cert['expiry_date'])); ?>
                                    <?php 
                                    $expiry = new DateTime($cert['expiry_date']);
                                    $today = new DateTime();
                                    if ($expiry < $today) {
                                        echo ' <span class="text-danger">(' . $today->diff($expiry)->days . ' days overdue)</span>';
                                    } elseif ($expiry->diff($today)->days <= 30) {
                                        echo ' <span class="text-warning">(' . $expiry->diff($today)->days . ' days remaining)</span>';
                                    }
                                    ?>
                                </small>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($cert['notes']): ?>
                            <div class="mb-3">
                                <small class="text-muted"><?php echo htmlspecialchars($cert['notes']); ?></small>
                            </div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <?php if ($cert['certification_url']): ?>
                                    <a href="<?php echo htmlspecialchars($cert['certification_url']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-external-link-alt"></i> View Certificate
                                    </a>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <button class="btn btn-sm btn-outline-primary" onclick="editCertification(<?php echo $cert['employee_skill_id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteCertification(<?php echo $cert['employee_skill_id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Certifications Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-certificate"></i> Certifications List</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Certification</th>
                                        <th>Category</th>
                                        <th>Level</th>
                                        <th>Assessed Date</th>
                                        <th>Expiry Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($certifications as $cert): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($cert['first_name'] . ' ' . $cert['last_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($cert['skill_name']); ?></td>
                                        <td><?php echo htmlspecialchars($cert['category']); ?></td>
                                        <td><?php echo htmlspecialchars($cert['proficiency_level']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($cert['assessed_date'])); ?></td>
                                        <td><?php echo $cert['expiry_date'] ? date('M d, Y', strtotime($cert['expiry_date'])) : 'No Expiry'; ?></td>
                                        <td>
                                            <span class="status-badge <?php echo getStatusBadgeClass($cert['expiry_date']); ?>">
                                                <?php 
                                                if (!$cert['expiry_date']) {
                                                    echo 'No Expiry';
                                                } elseif (new DateTime($cert['expiry_date']) < new DateTime()) {
                                                    echo 'Expired';
                                                } elseif ((new DateTime($cert['expiry_date']))->diff(new DateTime())->days <= 30) {
                                                    echo 'Expiring Soon';
                                                } else {
                                                    echo 'Active';
                                                }
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($cert['certification_url']): ?>
                                            <a href="<?php echo htmlspecialchars($cert['certification_url']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-outline-primary" onclick="editCertification(<?php echo $cert['employee_skill_id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteCertification(<?php echo $cert['employee_skill_id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Certification Modal -->
    <div class="modal fade" id="addCertificationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus"></i> Add New Certification</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_certification">
                        <div class="form-group">
                            <label>Employee *</label>
                            <select class="form-control" name="employee_id" required>
                                <option value="">Select Employee</option>
                                <?php foreach ($employees as $employee): ?>
                                <option value="<?php echo $employee['employee_id']; ?>">
                                    <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Certification/Skill *</label>
                            <select class="form-control" name="skill_id" required>
                                <option value="">Select Certification</option>
                                <?php foreach ($skills as $skill): ?>
                                <option value="<?php echo $skill['skill_id']; ?>">
                                    <?php echo htmlspecialchars($skill['skill_name'] . ' (' . $skill['category'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Proficiency Level *</label>
                                    <select class="form-control" name="proficiency_level" required>
                                        <option value="">Select Level</option>
                                        <option value="Beginner">Beginner</option>
                                        <option value="Intermediate">Intermediate</option>
                                        <option value="Advanced">Advanced</option>
                                        <option value="Expert">Expert</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Assessment Date *</label>
                                    <input type="date" class="form-control" name="assessed_date" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Certificate URL</label>
                            <input type="url" class="form-control" name="certification_url" placeholder="Link to digital certificate">
                        </div>
                        <div class="form-group">
                            <label>Expiry Date</label>
                            <input type="date" class="form-control" name="expiry_date">
                        </div>
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea class="form-control" name="notes" rows="3" placeholder="Additional notes about the certification"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Certification</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Success/Error Message Modal -->
    <?php if ($message): ?>
    <div class="modal fade" id="messageModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header <?php echo $messageType === 'success' ? 'bg-success' : 'bg-danger'; ?> text-white">
                    <h5 class="modal-title">
                        <?php echo $messageType === 'success' ? '<i class="fas fa-check-circle"></i> Success' : '<i class="fas fa-exclamation-circle"></i> Error'; ?>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p><?php echo htmlspecialchars($message); ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        // Show message modal if there's a message
        <?php if ($message): ?>
        $(document).ready(function() {
            $('#messageModal').modal('show');
        });
        <?php endif; ?>

        // Search functionality
        $('#certificationSearch').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $('.certification-item').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });

        // Edit certification function
        function editCertification(certificationId) {
            alert('Edit certification with ID: ' + certificationId);
        }

        // Delete certification function
        function deleteCertification(certificationId) {
            if (confirm('Are you sure you want to delete this certification?')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_certification">
                    <input type="hidden" name="employee_skill_id" value="${certificationId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
