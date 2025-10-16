<?php
session_start();

// Check if the user is logged in and has admin/hr role
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'hr')) {
    header('Location: login.php');
    exit;
}

// Include database connection
require_once 'dp.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['simulate_clock_in'])) {
        $employeeId = intval($_POST['employee_id']);
        $today = date('Y-m-d');
        $now = date('H:i:s');

        try {
            $sql = "INSERT INTO attendance (employee_id, attendance_date, clock_in, status) VALUES (?, ?, ?, 'Present')
                    ON DUPLICATE KEY UPDATE clock_in = VALUES(clock_in), status = 'Present'";
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([$employeeId, $today, $now]);

            if ($result) {
                $message = "Simulated clock-in for employee ID $employeeId at $now";
            } else {
                $message = "Failed to simulate clock-in";
            }
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
        }
    }
}

// Get employees for dropdown
$employees = [];
try {
    $stmt = $conn->query("SELECT ep.employee_id, pi.first_name, pi.last_name FROM employee_profiles ep JOIN personal_information pi ON ep.personal_info_id = pi.personal_info_id ORDER BY pi.first_name");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Error fetching employees: " . $e->getMessage();
}

// Get today's attendance records
$attendanceRecords = [];
try {
    $stmt = $conn->query("SELECT a.*, pi.first_name, pi.last_name FROM attendance a JOIN employee_profiles ep ON a.employee_id = ep.employee_id JOIN personal_information pi ON ep.personal_info_id = pi.personal_info_id WHERE a.attendance_date = CURDATE() ORDER BY a.clock_in DESC");
    $attendanceRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Error fetching attendance: " . $e->getMessage();
}

// Count employees
$totalEmployees = 0;
try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM employee_profiles WHERE employment_status IN ('Full-time', 'Part-time')");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalEmployees = $result['count'];
} catch (PDOException $e) {
    $totalEmployees = 'Error';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Attendance - HR Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Debug Attendance Tool</h1>
        <p class="text-muted">Temporary tool for testing attendance functionality. Delete after fixing.</p>

        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Simulate Clock-In</div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-group">
                                <label for="employee_id">Employee</label>
                                <select class="form-control" id="employee_id" name="employee_id" required>
                                    <option value="">Select Employee</option>
                                    <?php foreach ($employees as $emp): ?>
                                        <option value="<?php echo $emp['employee_id']; ?>"><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?> (ID: <?php echo $emp['employee_id']; ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" name="simulate_clock_in" class="btn btn-primary">Simulate Clock-In</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Statistics</div>
                    <div class="card-body">
                        <p>Total Active Employees: <?php echo $totalEmployees; ?></p>
                        <p>Today's Attendance Records: <?php echo count($attendanceRecords); ?></p>
                        <a href="fetch_attendance_overview.php?debug=1" target="_blank" class="btn btn-secondary">View Raw JSON from fetch_attendance_overview.php</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">Today's Attendance Records</div>
                    <div class="card-body">
                        <?php if (empty($attendanceRecords)): ?>
                            <p>No attendance records for today.</p>
                        <?php else: ?>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Clock In</th>
                                        <th>Clock Out</th>
                                        <th>Status</th>
                                        <th>Hours</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attendanceRecords as $record): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?> (ID: <?php echo $record['employee_id']; ?>)</td>
                                            <td><?php echo $record['clock_in'] ? date('h:i A', strtotime($record['clock_in'])) : '-'; ?></td>
                                            <td><?php echo $record['clock_out'] ? date('h:i A', strtotime($record['clock_out'])) : '-'; ?></td>
                                            <td><?php echo htmlspecialchars($record['status'] ?? 'Not Recorded'); ?></td>
                                            <td><?php echo $record['working_hours'] ? $record['working_hours'] . 'h' : '-'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <a href="attendance.php" class="btn btn-primary">Back to Attendance Page</a>
        </div>
    </div>
</body>
</html>
