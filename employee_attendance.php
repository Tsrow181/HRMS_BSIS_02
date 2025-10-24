<?php
session_start();

// Check if the user is logged in and is an employee, if not then redirect to login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'employee') {
    // Temporary debug: uncomment the lines below to see session variables
    // echo "<pre>"; var_dump($_SESSION); echo "</pre>"; exit;
    session_destroy();
    header('Location: login.php');
    exit;
}

// Include database connection
require_once 'dp.php';

// Get employee information
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$employee_id = null; // Initialize to prevent undefined variable errors

// Get employee_id from users table (primary source based on schema)
try {
    $stmt = $conn->prepare("SELECT employee_id FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user_employee = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user_employee && $user_employee['employee_id']) {
        $employee_id = $user_employee['employee_id'];
        error_log("Found employee_id $employee_id for user_id $user_id from users table");
    } else {
        $_SESSION['error'] = "Employee profile not found. Please contact HR.";
        error_log("No employee_id found for user_id: $user_id in users table");
        $employee_id = null;
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error retrieving employee profile.";
    error_log("Error fetching employee_id for user_id $user_id: " . $e->getMessage());
    $employee_id = null;
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $redirect = true;

    if (!$employee_id) {
        $_SESSION['error'] = "Cannot process attendance without a valid employee profile.";
        $redirect = false;
    } else {
        if (isset($_POST['clock_in'])) {
            // Clock in
            try {
        $today = date('Y-m-d');
        $clientTimeStr = $_POST['time'] ?? null;
        $nowDt = date('Y-m-d H:i:s');
        if ($clientTimeStr) {
            try {
                $dt = new DateTime($clientTimeStr);
                $nowDt = $dt->format('Y-m-d H:i:s');
            } catch (Exception $e) {
                // use default
            }
        }
        $sql = "INSERT INTO attendance (employee_id, attendance_date, clock_in, status) VALUES (?, ?, ?, 'Present')
                        ON DUPLICATE KEY UPDATE clock_in = VALUES(clock_in), status = 'Present'";
                $stmt = $conn->prepare($sql);
                $result = $stmt->execute([$employee_id, $today, $nowDt]);

                $isAjax = isset($_POST['ajax']) && $_POST['ajax'] == '1';
                $message = '';
                if ($result) {
                    // Verify insertion
                    $verifyStmt = $conn->prepare("SELECT attendance_id, clock_in, status FROM attendance WHERE employee_id = ? AND attendance_date = ?");
                    $verifyStmt->execute([$employee_id, $today]);
                    $record = $verifyStmt->fetch(PDO::FETCH_ASSOC);
                    if ($record) {
                        $message = "Clocked in successfully at " . date('h:i A', strtotime($nowDt)) . " (Record ID: " . $record['attendance_id'] . ")";
                        error_log("Clock-in verified: Employee $employee_id, Date $today, Clock-in $nowDt, Status " . $record['status']);
                    } else {
                        $message = "Clock-in failed verification.";
                        $result = false;
                        error_log("Clock-in verification failed: Employee $employee_id, Date $today");
                    }
                } else {
                    $message = "Clock-in execution failed.";
                    $result = false;
                    error_log("Clock-in execute failed: Employee $employee_id, Date $today");
                }

                if ($isAjax) {
                    if ($result) {
                        echo json_encode(['success' => true, 'message' => $message]);
                    } else {
                        echo json_encode(['success' => false, 'message' => $message]);
                    }
                    exit;
                } else {
                    if ($result) {
                        $_SESSION['success'] = $message;
                    } else {
                        $_SESSION['error'] = $message;
                        $redirect = false;
                    }
                }

                // Log the activity (commented out to prevent fatal if undefined)
                /*
                $details = [
                    'employee_id' => $employee_id,
                    'attendance_date' => $today,
                    'clock_in' => $now,
                    'status' => 'Present'
                ];
                logActivity('Employee clocked in', 'attendance', null, $details);
                */
            } catch (PDOException $e) {
                $errorMsg = "Error clocking in: " . $e->getMessage();
                error_log("Clock-in PDO error: " . $e->getMessage());
                $isAjax = isset($_POST['ajax']) && $_POST['ajax'] == '1';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $errorMsg]);
                    exit;
                } else {
                    $_SESSION['error'] = $errorMsg;
                    $redirect = false;
                }
            }
        } elseif (isset($_POST['clock_out'])) {
            // Clock out
            try {
        $today = date('Y-m-d');
        $clientTimeStr = $_POST['time'] ?? null;
        $nowDt = date('Y-m-d H:i:s');
        if ($clientTimeStr) {
            try {
                $dt = new DateTime($clientTimeStr);
                $nowDt = $dt->format('Y-m-d H:i:s');
            } catch (Exception $e) {
                // use default
            }
        }
        $sql = "UPDATE attendance SET clock_out = ?, working_hours = TIMESTAMPDIFF(HOUR, clock_in, ?) WHERE employee_id = ? AND attendance_date = ?";
                $stmt = $conn->prepare($sql);
                $result = $stmt->execute([$nowDt, $nowDt, $employee_id, $today]);

                $isAjax = isset($_POST['ajax']) && $_POST['ajax'] == '1';
                $message = '';
                if ($result) {
                    $affected = $stmt->rowCount();
                    $message = "Clocked out successfully at " . date('h:i A', strtotime($nowDt)) . " (Rows updated: $affected)";
                    error_log("Clock-out: Employee $employee_id, Date $today, Clock-out $nowDt, Rows affected: $affected");
                } else {
                    $message = "Clock-out execution failed.";
                    $result = false;
                    error_log("Clock-out execute failed: Employee $employee_id, Date $today");
                }

                if ($isAjax) {
                    if ($result) {
                        echo json_encode(['success' => true, 'message' => $message]);
                    } else {
                        echo json_encode(['success' => false, 'message' => $message]);
                    }
                    exit;
                } else {
                    if ($result) {
                        $_SESSION['success'] = $message;
                    } else {
                        $_SESSION['error'] = $message;
                        $redirect = false;
                    }
                }

                // Log the activity (commented out to prevent fatal if undefined)
                /*
                $details = [
                    'employee_id' => $employee_id,
                    'attendance_date' => $today,
                    'clock_out' => $now
                ];
                logActivity('Employee clocked out', 'attendance', null, $details);
                */
            } catch (PDOException $e) {
                $errorMsg = "Error clocking out: " . $e->getMessage();
                error_log("Clock-out PDO error: " . $e->getMessage());
                $isAjax = isset($_POST['ajax']) && $_POST['ajax'] == '1';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $errorMsg]);
                    exit;
                } else {
                    $_SESSION['error'] = $errorMsg;
                    $redirect = false;
                }
            }
        }
    }

    // Redirect to prevent form resubmission only if no errors
    if ($redirect) {
        header("Location: employee_attendance.php");
        exit;
    }
}

// Get today's attendance
function getTodayAttendance($employee_id) {
    global $conn;
    try {
        $today = date('Y-m-d');
        $sql = "SELECT * FROM attendance WHERE employee_id = ? AND attendance_date = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$employee_id, $today]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $_SESSION['error'] = "Failed to retrieve today's attendance data.";
        error_log("getTodayAttendance error for employee_id $employee_id: " . $e->getMessage());
        return null;
    }
}

// Get attendance history
function getAttendanceHistory($employee_id, $limit = 30) {
    global $conn;
    try {
        $sql = "SELECT * FROM attendance WHERE employee_id = ? ORDER BY attendance_date DESC LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$employee_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $_SESSION['error'] = "Failed to retrieve attendance history.";
        error_log("getAttendanceHistory error for employee_id $employee_id: " . $e->getMessage());
        return [];
    }
}

if (!$employee_id) {
    $todayAttendance = null;
    $attendanceHistory = [];
} else {
    $todayAttendance = getTodayAttendance($employee_id);
    $attendanceHistory = getAttendanceHistory($employee_id);
}

// Calculate attendance statistics
$totalDays = count($attendanceHistory);
$presentDays = 0;
$totalHours = 0;

foreach ($attendanceHistory as $record) {
    if ($record['status'] === 'Present') {
        $presentDays++;
    }
    if ($record['working_hours']) {
        $totalHours += $record['working_hours'];
    }
}

$attendanceRate = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : 0;
$avgHours = $presentDays > 0 ? round($totalHours / $presentDays, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - Employee Portal</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="employee_style.css">
    <style>
        .section-title {
            color: var(--primary-color);
            margin-bottom: 30px;
            font-weight: 600;
        }

        .clock-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .clock-time {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .clock-date {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 20px;
        }

        .clock-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid white;
            color: white;
            padding: 15px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin: 0 10px;
        }

        .clock-btn:hover {
            background: white;
            color: #667eea;
            transform: translateY(-2px);
        }

        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
        }

        .stats-value {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
        }

        .attendance-record {
            border-radius: 10px;
            transition: all 0.3s ease;
            border: 1px solid var(--border-light);
            margin-bottom: 10px;
        }

        .attendance-record:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--shadow-medium);
        }

        .status-present {
            background-color: #d4edda;
            color: #155724;
        }

        .status-absent {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-late {
            background-color: #fff3cd;
            color: #856404;
        }
    </style>
</head>
<body class="employee-page">
    <div class="container-fluid">
        <?php include 'employee_navigation.php'; ?>
        <div class="row">
            <?php include 'employee_sidebar.php'; ?>
            <div class="main-content">
                <h2 class="section-title"><i class="fas fa-clock mr-2"></i>Attendance Management</h2>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle mr-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle mr-2"></i><?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Clock In/Out Section -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="clock-card">
                            <div class="clock-time" id="current-time"><?php echo date('h:i:s A'); ?></div>
                            <div class="clock-date"><?php echo date('l, F j, Y'); ?></div>
                            <div class="mb-3">
                                <?php if (!$employee_id): ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>
                                        Attendance tracking is unavailable. Please contact HR to set up your employee profile.
                                    </div>
                                <?php elseif ($todayAttendance && $todayAttendance['clock_in']): ?>
                                    <div class="mb-2">
                                        <i class="fas fa-sign-in-alt mr-2"></i>
    Clocked In: <span id="clock-in-time"><?php echo ($todayAttendance['clock_in'] && $todayAttendance['clock_in'] !== '00:00:00' && $todayAttendance['clock_in'] !== '0000-00-00 00:00:00') ? date('h:i A', strtotime($todayAttendance['clock_in'])) : 'Not Recorded'; ?></span>
                                    </div>
                                    <?php if ($todayAttendance['clock_out']): ?>
                                        <div>
                                            <i class="fas fa-sign-out-alt mr-2"></i>
    Clocked Out: <span id="clock-out-time"><?php echo ($todayAttendance['clock_out'] && $todayAttendance['clock_out'] !== '00:00:00' && $todayAttendance['clock_out'] !== '0000-00-00 00:00:00') ? date('h:i A', strtotime($todayAttendance['clock_out'])) : 'Not Recorded'; ?></span>
                                        </div>
                                    <?php else: ?>
                                        <button id="clock-out-btn" class="clock-btn">
                                            <i class="fas fa-sign-out-alt mr-2"></i>Clock Out
                                        </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button id="clock-in-btn" class="clock-btn">
                                        <i class="fas fa-sign-in-alt mr-2"></i>Clock In
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendance Statistics -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stats-card">
                            <h5 class="text-center mb-3">Attendance Rate</h5>
                            <div class="stats-value text-center"><?php echo $attendanceRate; ?>%</div>
                            <small class="text-muted text-center d-block"><?php echo $presentDays; ?>/<?php echo $totalDays; ?> days</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card">
                            <h5 class="text-center mb-3">Average Hours</h5>
                            <div class="stats-value text-center"><?php echo $avgHours; ?>h</div>
                            <small class="text-muted text-center d-block">Per working day</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card">
                            <h5 class="text-center mb-3">Total Hours</h5>
                            <div class="stats-value text-center"><?php echo round($totalHours, 1); ?>h</div>
                            <small class="text-muted text-center d-block">This month</small>
                        </div>
                    </div>
                </div>

                <!-- Attendance History -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-history mr-2"></i>Attendance History</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Clock In</th>
                                                <th>Clock Out</th>
                                                <th>Hours Worked</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="attendance-history-body">
                                            <?php if (empty($attendanceHistory)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">
                                                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                                        <h5 class="text-muted">No attendance records found</h5>
                                                        <p class="text-muted">Your attendance history will appear here.</p>
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($attendanceHistory as $record): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars(date('M d, Y', strtotime($record['attendance_date']))); ?></td>
    <td><?php echo ($record['clock_in'] && $record['clock_in'] !== '00:00:00' && $record['clock_in'] !== '0000-00-00 00:00:00') ? date('h:i A', strtotime($record['clock_in'])) : '-'; ?></td>
    <td><?php echo ($record['clock_out'] && $record['clock_out'] !== '00:00:00' && $record['clock_out'] !== '0000-00-00 00:00:00') ? date('h:i A', strtotime($record['clock_out'])) : '-'; ?></td>
                                                    <td><?php echo $record['working_hours'] ? $record['working_hours'] . 'h' : '-'; ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php echo strtolower($record['status'] ?? 'secondary'); ?>">
                                                            <?php echo htmlspecialchars($record['status'] ?? 'Not Recorded'); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
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

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Update current time every second
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', {
                hour12: true,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('current-time').textContent = timeString;
        }

        setInterval(updateTime, 1000);
        updateTime(); // Initial call

        // Function to load attendance history
        function loadAttendanceHistory() {
            fetch('fetch_employee_attendance_history.php')
                .then(response => response.text())
                .then(data => {
                    document.getElementById('attendance-history-body').innerHTML = data;
                })
                .catch(error => {
                    console.error('Error loading attendance history:', error);
                });
        }

        // Function to handle clock out
        function handleClockOut() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', {
                hour12: true,
                hour: '2-digit',
                minute: '2-digit'
            });
            const localTime = now.getFullYear() + '-' +
                (now.getMonth() + 1).toString().padStart(2, '0') + '-' +
                now.getDate().toString().padStart(2, '0') + ' ' +
                now.getHours().toString().padStart(2, '0') + ':' +
                now.getMinutes().toString().padStart(2, '0') + ':' +
                now.getSeconds().toString().padStart(2, '0');

            fetch('employee_attendance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'clock_out=1&time=' + encodeURIComponent(localTime) + '&ajax=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update UI
                    const mb3 = document.querySelector('.mb-3');
                    const clockOutDiv = document.createElement('div');
                    clockOutDiv.innerHTML = `<i class="fas fa-sign-out-alt mr-2"></i> Clocked Out: <span id="clock-out-time">${timeString}</span>`;
                    const clockOutBtn = document.getElementById('clock-out-btn');
                    mb3.insertBefore(clockOutDiv, clockOutBtn);
                    clockOutBtn.style.display = 'none';
                    loadAttendanceHistory();
                } else {
                    alert(data.message || 'Error clocking out. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error clocking out:', error);
                alert('Error clocking out. Please try again.');
            });
        }

        // Handle clock in button
        document.getElementById('clock-in-btn')?.addEventListener('click', function() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', {
                hour12: true,
                hour: '2-digit',
                minute: '2-digit'
            });
            const localTime = now.getFullYear() + '-' +
                (now.getMonth() + 1).toString().padStart(2, '0') + '-' +
                now.getDate().toString().padStart(2, '0') + ' ' +
                now.getHours().toString().padStart(2, '0') + ':' +
                now.getMinutes().toString().padStart(2, '0') + ':' +
                now.getSeconds().toString().padStart(2, '0');

            fetch('employee_attendance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'clock_in=1&time=' + encodeURIComponent(localTime) + '&ajax=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update UI
                    const mb3 = document.querySelector('.mb-3');
                    const clockInDiv = document.createElement('div');
                    clockInDiv.className = 'mb-2';
                    clockInDiv.innerHTML = `<i class="fas fa-sign-in-alt mr-2"></i> Clocked In: <span id="clock-in-time">${timeString}</span>`;
                    mb3.appendChild(clockInDiv);

                    const clockInBtn = document.getElementById('clock-in-btn');
                    clockInBtn.style.display = 'none';

                    const clockOutBtn = document.createElement('button');
                    clockOutBtn.id = 'clock-out-btn';
                    clockOutBtn.className = 'clock-btn';
                    clockOutBtn.innerHTML = `<i class="fas fa-sign-out-alt mr-2"></i>Clock Out`;
                    mb3.appendChild(clockOutBtn);

                    // Attach clock out listener to the new button
                    clockOutBtn.addEventListener('click', handleClockOut);

                    loadAttendanceHistory();
                } else {
                    alert(data.message || 'Error clocking in. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error clocking in:', error);
                alert('Error clocking in. Please try again.');
            });
        });

        // Attach clock out listener for existing button (if page loads with it)
        document.getElementById('clock-out-btn')?.addEventListener('click', handleClockOut);
    </script>
</body>
</html>
