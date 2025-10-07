<?php
// Made by Hitansh Parikh - 23CS054
session_start();
require_once 'config/database.php';
require_once 'classes/Auth.php';

$auth = new Auth();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = $auth->getCurrentUser();
$userStats = $auth->getUserStats($user['id']);

// Initialize database if needed
try {
    $db = getDB();
    // Check if tables exist, if not create them
    $tablesExist = $db->fetchOne("SHOW TABLES LIKE 'users'");
    if (!$tablesExist) {
        $db->createTables();
    }
} catch (Exception $e) {
    // Database might not exist yet
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Student Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 0;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 1rem 1.5rem;
            border-radius: 0;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }
        
        .main-content {
            padding: 2rem;
        }
        
        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
            border: none;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .navbar {
            background: white !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }
        
        .profile-dropdown .dropdown-toggle::after {
            display: none;
        }
        
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .quick-actions {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .quick-action-btn {
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            margin: 0.25rem;
            transition: all 0.3s ease;
        }
        
        .quick-action-btn:hover {
            transform: translateY(-2px);
        }
        
        .activity-feed {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            max-height: 400px;
            overflow-y: auto;
        }
        
        .activity-item {
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarCollapse">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="ms-auto">
                <div class="dropdown profile-dropdown">
                    <a class="dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                        <div class="avatar me-2">
                            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                        </div>
                        <span class="d-none d-md-inline"><?php echo htmlspecialchars($user['full_name']); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 sidebar collapse" id="sidebarCollapse">
                <div class="position-sticky pt-3">
                    <div class="text-center text-white mb-4">
                        <i class="fas fa-graduation-cap fa-2x mb-2"></i>
                        <h5>Student Portal</h5>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#books">
                                <i class="fas fa-book"></i>Books
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#loans">
                                <i class="fas fa-hand-holding"></i>My Loans
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#reservations">
                                <i class="fas fa-bookmark"></i>Reservations
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#reviews">
                                <i class="fas fa-star"></i>Reviews
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#profile">
                                <i class="fas fa-user"></i>Profile
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
                <!-- Welcome Card -->
                <div class="welcome-card">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="h3 mb-2">Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
                            <p class="mb-0">Ready to explore your library dashboard?</p>
                            <small class="opacity-75">Role: <?php echo ucfirst($user['role']); ?> | Last login: <?php echo date('M d, Y g:i A'); ?></small>
                        </div>
                        <div class="col-md-4 text-end">
                            <i class="fas fa-user-graduate fa-4x opacity-50"></i>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: rgba(13, 202, 240, 0.1); color: #0dcaf0;">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="stat-number text-info"><?php echo $userStats['total_loans'] ?? 0; ?></div>
                            <div class="text-muted">Total Loans</div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: rgba(25, 135, 84, 0.1); color: #198754;">
                                <i class="fas fa-hand-holding"></i>
                            </div>
                            <div class="stat-number text-success"><?php echo $userStats['active_loans'] ?? 0; ?></div>
                            <div class="text-muted">Active Loans</div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                                <i class="fas fa-bookmark"></i>
                            </div>
                            <div class="stat-number text-warning"><?php echo $userStats['total_reservations'] ?? 0; ?></div>
                            <div class="text-muted">Reservations</div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: rgba(220, 53, 69, 0.1); color: #dc3545;">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="stat-number text-danger"><?php echo $userStats['reviews_written'] ?? 0; ?></div>
                            <div class="text-muted">Reviews</div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Quick Actions -->
                    <div class="col-md-6 mb-4">
                        <div class="quick-actions">
                            <h5 class="mb-3"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                            <div class="d-grid gap-2">
                                <button class="btn btn-primary quick-action-btn">
                                    <i class="fas fa-search me-2"></i>Search Books
                                </button>
                                <button class="btn btn-success quick-action-btn">
                                    <i class="fas fa-plus me-2"></i>New Reservation
                                </button>
                                <button class="btn btn-info quick-action-btn">
                                    <i class="fas fa-history me-2"></i>Loan History
                                </button>
                                <button class="btn btn-warning quick-action-btn">
                                    <i class="fas fa-user-edit me-2"></i>Update Profile
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="col-md-6 mb-4">
                        <div class="activity-feed">
                            <h5 class="mb-3"><i class="fas fa-clock me-2"></i>Recent Activity</h5>
                            
                            <div class="activity-item">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <div class="avatar" style="width: 30px; height: 30px; font-size: 0.8rem;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="fw-bold">Welcome to the System!</div>
                                        <small class="text-muted">Account created successfully</small>
                                        <div class="small text-muted">Just now</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="activity-item">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <div class="avatar" style="width: 30px; height: 30px; font-size: 0.8rem; background: #198754;">
                                            <i class="fas fa-check"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="fw-bold">System Ready</div>
                                        <small class="text-muted">Database initialized</small>
                                        <div class="small text-muted">A moment ago</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center mt-3">
                                <small class="text-muted">Start using the system to see more activities</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Information -->
                <div class="row">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="card-title"><i class="fas fa-info-circle me-2"></i>System Information</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <small class="text-muted">Version:</small><br>
                                        <span class="fw-bold">1.0.0</span>
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted">Last Updated:</small><br>
                                        <span class="fw-bold"><?php echo date('Y-m-d'); ?></span>
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted">Developer:</small><br>
                                        <span class="fw-bold">Hitansh Parikh - 23CS054</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Add click effects to quick action buttons
        document.querySelectorAll('.quick-action-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Add ripple effect
                const ripple = document.createElement('span');
                ripple.className = 'position-absolute bg-white opacity-25 rounded-circle';
                ripple.style.width = ripple.style.height = '100px';
                ripple.style.left = (e.clientX - e.target.getBoundingClientRect().left - 50) + 'px';
                ripple.style.top = (e.clientY - e.target.getBoundingClientRect().top - 50) + 'px';
                ripple.style.animation = 'ripple 0.6s linear';
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
                
                // Show notification
                const actionName = this.textContent.trim();
                showNotification(`${actionName} feature coming soon!`, 'info');
            });
        });
        
        function showNotification(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type} border-0 position-fixed`;
            toast.style.top = '20px';
            toast.style.right = '20px';
            toast.style.zIndex = '9999';
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            
            document.body.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            
            toast.addEventListener('hidden.bs.toast', () => {
                toast.remove();
            });
        }
    </script>
    
    <style>
        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        
        .toast {
            min-width: 250px;
        }
    </style>
</body>
</html>