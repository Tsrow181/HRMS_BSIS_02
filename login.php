<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config.php';

// Demo credentials array
$demo_users = [
    [
        'username' => 'john.admin',
        'password' => 'admin123',
        'role' => 'admin',
        'user_id' => 1
    ],
    [
        'username' => 'sara.hr',
        'password' => 'hr123',
        'role' => 'hr',
        'user_id' => 2
    ],

];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Validate user input
    if (empty($username) || empty($password)) {
        $error = "Username and password are required.";
    } else {
        // Check against demo credentials
        $authenticated = false;
        foreach ($demo_users as $user) {
            if ($username === $user['username'] && $password === $user['password']) {
                // Store data in session variables
                $_SESSION['loggedin'] = true;
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                $authenticated = true;
                header("location: index.php");
                exit;
            }
        }
        
        if (!$authenticated) {
            $error = "Invalid username or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HR System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: linear-gradient(135deg, #F0F8FF 0%, #E3F2FD 100%);
        }
        
        /* Main animated background */
        .animated-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }
        
        /* Gradient waves */
        .wave {
            position: absolute;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, 
                rgba(0, 120, 212, 0.1) 0%,
                rgba(0, 120, 212, 0.2) 50%,
                rgba(0, 120, 212, 0.1) 100%
            );
            animation: wave 10s linear infinite;
            opacity: 0.5;
        }
        
        .wave:nth-child(1) {
            animation-delay: 0s;
        }
        
        .wave:nth-child(2) {
            animation-delay: -5s;
        }
        
        @keyframes wave {
            0% {
                transform: translateX(0) translateY(0);
            }
            100% {
                transform: translateX(-100%) translateY(-100px);
            }
        }
        
        /* Floating elements */
        .floating-elements {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }
        
        .floating-element {
            position: absolute;
            animation: float 15s ease-in-out infinite;
            opacity: 0.7;
        }
        
        .floating-element:nth-child(1) {
            width: 100px;
            height: 100px;
            background: #0078D4;
            border-radius: 50%;
            top: 10%;
            left: 20%;
            animation-delay: 0s;
        }
        
        .floating-element:nth-child(2) {
            width: 80px;
            height: 80px;
            background: #1E88E5;
            border-radius: 10px;
            top: 30%;
            left: 60%;
            animation-delay: -3s;
        }
        
        .floating-element:nth-child(3) {
            width: 60px;
            height: 60px;
            background: #005A9E;
            border-radius: 50%;
            top: 70%;
            left: 40%;
            animation-delay: -6s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-30px) rotate(180deg);
            }
        }
        
        /* Light rays */
        .light-rays {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }
        
        .ray {
            position: absolute;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, 
                rgba(0, 120, 212, 0.1) 0%,
                rgba(0, 120, 212, 0.2) 50%,
                rgba(0, 120, 212, 0.1) 100%
            );
            animation: ray 12s linear infinite;
            opacity: 0.3;
        }
        
        @keyframes ray {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }
        
        .login-container {
            max-width: 400px;
            padding: 40px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(0, 0, 0, 0.05);
            z-index: 1;
            backdrop-filter: blur(10px);
            position: relative;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header img {
            width: 100px;
            height: 100px;
            object-fit: contain;
            margin-bottom: 20px;
        }
        
        .login-header h1 {
            color: #0078D4;
            font-size: 24px;
            margin: 0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-control {
            border: 1px solid #ddd;
            padding: 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #0078D4;
            box-shadow: 0 0 0 0.2rem rgba(0, 120, 212, 0.25);
        }
        
        .btn-primary {
            background-color: #0078D4;
            border-color: #0078D4;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: #005A9E;
            border-color: #005A9E;
        }
        
        .alert {
            border-radius: 8px;
            padding: 15px;
        }
        
        .demo-credentials {
            margin-top: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        
        .demo-credentials h5 {
            color: #0078D4;
            margin-bottom: 15px;
        }
        
        .demo-credentials small {
            color: var(--text-color);
        }
        
        .form-label {
            color: var(--text-color);
            font-weight: 500;
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="animated-bg">
        <!-- Gradient Waves -->
        <div class="wave"></div>
        <div class="wave"></div>
        
        <!-- Floating Elements -->
        <div class="floating-elements">
            <div class="floating-element"></div>
            <div class="floating-element"></div>
            <div class="floating-element"></div>
        </div>
        
        <!-- Light Rays -->
        <div class="light-rays">
            <div class="ray"></div>
        </div>
    </div>
    
    <div class="login-container">
        <div class="login-header">
            <img src="image/HR Company.jpg" alt="HR System Logo">
            <h1>HR System</h1>
        </div>
        
        <?php if (isset($error)) { echo '<div class="alert alert-danger">' . $error . '</div>'; } ?>
        
        <form action="login.php" method="post">
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                    </div>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    </div>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>
        
        <div class="demo-credentials">
            <h5>Demo Credentials:</h5>
            <small>
                <strong>Admin:</strong> john.admin / admin123<br>
                <strong>HR:</strong> sara.hr / hr123<br>
            </small>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
