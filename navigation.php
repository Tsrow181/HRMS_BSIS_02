<!-- Top Navigation Bar -->
<nav class="top-navbar d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center">
        <img src="image/HR SYSTEM LOGO.jpg" alt="Logo" style="height:40px; width:auto; border-radius:6px; margin-right:12px;">
        <span style="font-size:1.3rem; font-weight:bold; color:#800000; letter-spacing:1px;">Human Resources Management</span>
    </div>
    <ul class="nav align-items-center ml-auto">
        <li class="nav-item dropdown mr-3">
            <a class="nav-link-custom" href="#" id="notificationsDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-bell" style="color: #800000;"></i>
                <span class="notification-badge">3</span>
            </a>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="notificationsDropdown">
                <h6 class="dropdown-header">Notifications</h6>
                <a class="dropdown-item" href="#">
                    <small class="text-muted">Just now</small>
                    <p class="mb-0">New leave request from John</p>
                </a>
                <a class="dropdown-item" href="#">
                    <small class="text-muted">30 minutes ago</small>
                    <p class="mb-0">Performance review due</p>
                </a>
                <a class="dropdown-item" href="#">
                    <small class="text-muted">1 hour ago</small>
                    <p class="mb-0">New training session scheduled</p>
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item text-center" href="#">View all notifications</a>
            </div>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link-custom" href="#" id="profileDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <img src="https://via.placeholder.com/35" alt="Profile" class="profile-image">
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </a>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="profileDropdown">
                <a class="dropdown-item" href="profile.php">
                    <i class="fas fa-user mr-2"></i> My Profile
                </a>
                <a class="dropdown-item" href="settings.php">
                    <i class="fas fa-cog mr-2"></i> Settings
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="logout.php">
                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                </a>
            </div>
        </li>
    </ul>
</nav> 