<?php include 'config.php'; ?>

<?php include 'header.php'; ?>

<?php
// if not logged in as admin; redirect to the login page
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != "admin") {
    header("Location: login.php");
    exit();
}

// Check if database connection exists
if (!isset($conn) || $conn === null) {
    // Try to establish connection if it doesn't exist
    $servername = "sql300.infinityfree.com";
    $username = "if0_38712527";
    $password = "dlm8tS7wRoN";
    $dbname = "if0_38712527_e_training";
    
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
}

// Fetch system statistics
$statistics_query = "SELECT 
                (SELECT COUNT(*) FROM users) AS total_users, 
                (SELECT COUNT(*) FROM users WHERE role = 'student') AS total_students,
                (SELECT COUNT(*) FROM users WHERE role = 'instructor') AS total_instructors,
                (SELECT COUNT(*) FROM courses) AS total_courses, 
                (SELECT COUNT(*) FROM course_enrollments) AS total_enrollments,
                (SELECT COUNT(*) FROM enrollment_requests WHERE status = 'pending') AS pending_requests,
                (SELECT COUNT(*) FROM course_materials) AS total_materials,
                (SELECT COUNT(*) FROM login_history WHERE login_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)) AS recent_logins";

$statistics_result = mysqli_query($conn, $statistics_query);
$statistics = mysqli_fetch_assoc($statistics_result);

// Get recent enrollment requests
$requests_query = "SELECT er.*, u.name AS student_name, c.name AS course_name 
                  FROM enrollment_requests er
                  JOIN users u ON er.student_id = u.user_id
                  JOIN courses c ON er.course_id = c.course_id
                  WHERE er.status = 'pending'
                  ORDER BY er.created_at DESC
                  LIMIT 5";
$requests_result = mysqli_query($conn, $requests_query);

// Get recent login activity
$login_query = "SELECT lh.*, u.name, u.role
               FROM login_history lh
               JOIN users u ON lh.user_id = u.user_id
               ORDER BY lh.login_time DESC
               LIMIT 10";
$login_result = mysqli_query($conn, $login_query);

// Get users
$users_query = "SELECT * FROM users WHERE role != 'admin' ORDER BY registration_date DESC";
$users_result = mysqli_query($conn, $users_query);

// Get courses
$courses_query = "SELECT c.*, cat.name AS category_name, u.name AS instructor_name 
                 FROM courses c
                 LEFT JOIN category cat ON cat.category_id = c.category_id
                 LEFT JOIN users u ON u.user_id = c.instructor_id
                 ORDER BY c.start_date DESC";
$courses_result = mysqli_query($conn, $courses_query);

// Get upcoming events
$events_query = "SELECT ce.*, c.name AS course_name
                FROM course_events ce
                JOIN courses c ON ce.course_id = c.course_id
                WHERE ce.date >= CURDATE()
                ORDER BY ce.date ASC, ce.time ASC
                LIMIT 5";
$events_result = mysqli_query($conn, $events_query);
?>

<style>
/* Admin Dashboard Styles */
:root {
    --primary: #04639b;
    --primary-dark: #035483;
    --primary-light: #e6f1f8;
    --secondary: #ff7e00;
    --success: #28a745;
    --success-light: #d1e7dd;
    --success-dark: #0f5132;
    --danger: #dc3545;
    --danger-light: #f8d7da;
    --danger-dark: #842029;
    --warning: #ffc107;
    --warning-light: #fff3cd;
    --warning-dark: #664d03;
    --info: #17a2b8;
    --info-light: #cff4fc;
    --info-dark: #055160;
    --light: #f8f9fa;
    --dark: #343a40;
    --gray: #6c757d;
    --gray-light: #e9ecef;
    --gray-dark: #495057;
    --white: #ffffff;
    --border: #dee2e6;
    --shadow-sm: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    --shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    --shadow-lg: 0 1rem 3rem rgba(0, 0, 0, 0.175);
    --radius: 0.375rem;
    --radius-lg: 0.5rem;
    --radius-sm: 0.25rem;
    --transition: all 0.3s ease;
}

/* Base Styles */
body {
    background-color: #f5f7fa;
    color: #333;
    font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
}

.admin-container {
    padding: 1.5rem;
    max-width: 1400px;
    margin: 0 auto;
}

/* Dashboard Header */
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    background-color: var(--white);
    padding: 1.25rem;
    border-radius: var(--radius);
    box-shadow: var(--shadow-sm);
}

.dashboard-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.dashboard-title i {
    font-size: 1.75rem;
}

.dashboard-welcome {
    font-size: 0.875rem;
    color: var(--gray);
}

.dashboard-actions {
    display: flex;
    gap: 0.75rem;
}

.dashboard-btn {
    padding: 0.625rem 1rem;
    background-color: var(--primary);
    color: var(--white);
    border: none;
    border-radius: var(--radius);
    cursor: pointer;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: var(--transition);
    box-shadow: var(--shadow-sm);
}

.dashboard-btn:hover {
    background-color: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: var(--shadow);
}

.dashboard-btn-success {
    background-color: var(--success);
}

.dashboard-btn-success:hover {
    background-color: #218838;
}

.dashboard-btn-danger {
    background-color: var(--danger);
}

.dashboard-btn-danger:hover {
    background-color: #c82333;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1.25rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    background-color: var(--white);
    border-radius: var(--radius);
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
    position: relative;
    overflow: hidden;
    transition: var(--transition);
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow);
}

.stat-icon {
    position: absolute;
    top: 1.25rem;
    right: 1.25rem;
    font-size: 2.5rem;
    color: rgba(4, 99, 155, 0.1);
}

.stat-card h3 {
    font-size: 2rem;
    font-weight: 700;
    margin: 0.5rem 0;
    color: var(--primary);
}

.stat-card p {
    color: var(--gray);
    margin: 0;
    font-weight: 500;
    font-size: 0.875rem;
}

.stat-card small {
    color: var(--gray);
    font-size: 0.75rem;
    margin-top: 0.5rem;
}

.stat-card.primary {
    border-left: 4px solid var(--primary);
}

.stat-card.success {
    border-left: 4px solid var(--success);
}

.stat-card.success h3 {
    color: var(--success);
}

.stat-card.success .stat-icon {
    color: rgba(40, 167, 69, 0.1);
}

.stat-card.warning {
    border-left: 4px solid var(--warning);
}

.stat-card.warning h3 {
    color: var(--warning-dark);
}

.stat-card.warning .stat-icon {
    color: rgba(255, 193, 7, 0.1);
}

.stat-card.info {
    border-left: 4px solid var(--info);
}

.stat-card.info h3 {
    color: var(--info);
}

.stat-card.info .stat-icon {
    color: rgba(23, 162, 184, 0.1);
}

.stat-card.danger {
    border-left: 4px solid var(--danger);
}

.stat-card.danger h3 {
    color: var(--danger);
}

.stat-card.danger .stat-icon {
    color: rgba(220, 53, 69, 0.1);
}

/* Dashboard Sections */
.dashboard-section {
    background-color: var(--white);
    border-radius: var(--radius);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: var(--shadow-sm);
    transition: var(--transition);
}

.dashboard-section:hover {
    box-shadow: var(--shadow);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.25rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--gray-light);
}

.section-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--primary);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.section-title i {
    font-size: 1.25rem;
}

.section-action {
    color: var(--primary);
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    transition: var(--transition);
}

.section-action:hover {
    color: var(--primary-dark);
    text-decoration: underline;
}

/* Tables */
.admin-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.admin-table th {
    background-color: var(--light);
    padding: 0.75rem 1rem;
    text-align: left;
    font-weight: 600;
    color: var(--gray-dark);
    border-bottom: 2px solid var(--border);
    font-size: 0.875rem;
    position: sticky;
    top: 0;
    z-index: 10;
}

.admin-table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--border);
    vertical-align: middle;
    font-size: 0.875rem;
    transition: var(--transition);
}

.admin-table tr:hover td {
    background-color: var(--primary-light);
}

.admin-table tr:last-child td {
    border-bottom: none;
}

.admin-table img {
    border-radius: var(--radius-sm);
    object-fit: cover;
    box-shadow: var(--shadow-sm);
    transition: var(--transition);
}

.admin-table img:hover {
    transform: scale(1.05);
    box-shadow: var(--shadow);
}

.table-responsive {
    overflow-x: auto;
    border-radius: var(--radius);
    max-height: 400px;
    overflow-y: auto;
}

/* Badges */
.badge {
    display: inline-flex;
    align-items: center;
    padding: 0.35rem 0.65rem;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 600;
    gap: 0.25rem;
}

.badge-primary {
    background-color: var(--primary-light);
    color: var(--primary-dark);
}

.badge-success {
    background-color: var(--success-light);
    color: var(--success-dark);
}

.badge-warning {
    background-color: var(--warning-light);
    color: var(--warning-dark);
}

.badge-danger {
    background-color: var(--danger-light);
    color: var(--danger-dark);
}

.badge-info {
    background-color: var(--info-light);
    color: var(--info-dark);
}

/* Buttons */
.btn-group {
    display: flex;
    gap: 0.5rem;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
    border-radius: var(--radius-sm);
    cursor: pointer;
    border: none;
    color: var(--white);
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    transition: var(--transition);
    text-decoration: none;
}

.btn-sm:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

.btn-primary {
    background-color: var(--primary);
}

.btn-primary:hover {
    background-color: var(--primary-dark);
}

.btn-success {
    background-color: var(--success);
}

.btn-success:hover {
    background-color: #218838;
}

.btn-danger {
    background-color: var(--danger);
}

.btn-danger:hover {
    background-color: #c82333;
}

.btn-warning {
    background-color: var(--warning);
    color: #212529;
}

.btn-warning:hover {
    background-color: #e0a800;
}

.btn-info {
    background-color: var(--info);
}

.btn-info:hover {
    background-color: #138496;
}

/* Search */
.search-container {
    margin-bottom: 1rem;
    position: relative;
}

.search-input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.5rem;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    font-size: 0.875rem;
    transition: var(--transition);
    background-color: var(--light);
}

.search-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 0.2rem rgba(4, 99, 155, 0.25);
    background-color: var(--white);
}

.search-icon {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray);
    font-size: 1rem;
}

/* Dashboard Grid */
.dashboard-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1.5rem;
}

@media (max-width: 992px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
}

/* Activity Feed */
.activity-feed {
    max-height: 500px;
    overflow-y: auto;
    padding-right: 0.5rem;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--gray-light);
    transition: var(--transition);
}

.activity-item:hover {
    background-color: var(--primary-light);
    border-radius: var(--radius-sm);
    padding-left: 0.5rem;
    padding-right: 0.5rem;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    background-color: var(--gray-light);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    flex-shrink: 0;
    box-shadow: var(--shadow-sm);
}

.activity-icon.login {
    background-color: var(--primary-light);
    color: var(--primary-dark);
}

.activity-icon.enrollment {
    background-color: var(--success-light);
    color: var(--success-dark);
}

.activity-icon.course {
    background-color: var(--warning-light);
    color: var(--warning-dark);
}

.activity-content {
    flex-grow: 1;
}

.activity-title {
    font-weight: 600;
    margin-bottom: 0.25rem;
    font-size: 0.875rem;
    color: var(--dark);
}

.activity-time {
    font-size: 0.75rem;
    color: var(--gray);
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

/* Quick Actions */
.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
}

.quick-action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1.25rem;
    background-color: var(--light);
    border-radius: var(--radius);
    text-decoration: none;
    color: var(--dark);
    transition: var(--transition);
    text-align: center;
    gap: 0.5rem;
}

.quick-action-btn:hover {
    background-color: var(--primary-light);
    color: var(--primary-dark);
    transform: translateY(-3px);
    box-shadow: var(--shadow);
}

.quick-action-btn i {
    font-size: 1.5rem;
    color: var(--primary);
}

.quick-action-btn span {
    font-size: 0.875rem;
    font-weight: 500;
}

/* System Info */
.system-info-table {
    width: 100%;
}

.system-info-table td {
    padding: 0.625rem 0;
    border-bottom: 1px solid var(--gray-light);
    font-size: 0.875rem;
}

.system-info-table tr:last-child td {
    border-bottom: none;
}

.system-info-table td:first-child {
    font-weight: 600;
    color: var(--gray-dark);
    width: 40%;
}

.system-info-table td:last-child {
    color: var(--gray);
}

/* Utilities */
.truncate {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 200px;
}

.status-indicator {
    width: 0.625rem;
    height: 0.625rem;
    border-radius: 50%;
    display: inline-block;
    margin-right: 0.375rem;
}

.status-active {
    background-color: var(--success);
}

.status-inactive {
    background-color: var(--danger);
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    margin-top: 1.25rem;
    gap: 0.375rem;
}

.pagination a {
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    color: var(--primary);
    text-decoration: none;
    font-size: 0.875rem;
    transition: var(--transition);
}

.pagination a:hover {
    background-color: var(--primary-light);
    border-color: var(--primary);
}

.pagination a.active {
    background-color: var(--primary);
    color: var(--white);
    border-color: var(--primary);
}

/* Scrollbar */
::-webkit-scrollbar {
    width: 0.5rem;
    height: 0.5rem;
}

::-webkit-scrollbar-track {
    background: var(--light);
    border-radius: 0.25rem;
}

::-webkit-scrollbar-thumb {
    background: var(--gray);
    border-radius: 0.25rem;
}

::-webkit-scrollbar-thumb:hover {
    background: var(--primary);
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .admin-container {
        padding: 1rem;
    }
    
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .dashboard-actions {
        width: 100%;
        justify-content: flex-start;
        overflow-x: auto;
        padding-bottom: 0.5rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(auto-fill, minmax(100%, 1fr));
    }
    
    .quick-actions-grid {
        grid-template-columns: 1fr;
    }
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.fade-in {
    animation: fadeIn 0.3s ease-in-out forwards;
}

.delay-1 { animation-delay: 0.1s; }
.delay-2 { animation-delay: 0.2s; }
.delay-3 { animation-delay: 0.3s; }
.delay-4 { animation-delay: 0.4s; }
</style>

<div class="admin-container">
    <div class="dashboard-header fade-in">
        <div>
            <h1 class="dashboard-title"><i class="fas fa-tachometer-alt"></i> <?php echo translate('Admin Dashboard'); ?></h1>
            <p class="dashboard-welcome"><?php echo translate('Welcome back'); ?>, <?php echo isset($_SESSION['name']) ? $_SESSION['name'] : 'Admin'; ?>!</p>
        </div>
        
        <div class="dashboard-actions">
           
            <a href="reports.php" class="dashboard-btn">
                <i class="fas fa-chart-bar"></i> <?php echo translate('Reports'); ?>
            </a>
        </div>
    </div>

    <!-- Statistics Section -->
    <div class="stats-grid">
        <div class="stat-card primary fade-in">
            <i class="fas fa-users stat-icon"></i>
            <p><?php echo translate('Total Users'); ?></p>
            <h3><?php echo $statistics['total_users']; ?></h3>
            <small><i class="fas fa-user-graduate"></i> <?php echo $statistics['total_students']; ?> <?php echo translate('Students'); ?>, <i class="fas fa-chalkboard-teacher"></i> <?php echo $statistics['total_instructors']; ?> <?php echo translate('Instructors'); ?></small>
        </div>
        <div class="stat-card success fade-in delay-1">
            <i class="fas fa-book stat-icon"></i>
            <p><?php echo translate('Total Courses'); ?></p>
            <h3><?php echo $statistics['total_courses']; ?></h3>
            <small><i class="fas fa-file-alt"></i> <?php echo $statistics['total_materials']; ?> <?php echo translate('Learning Materials'); ?></small>
        </div>
        <div class="stat-card info fade-in delay-2">
            <i class="fas fa-user-plus stat-icon"></i>
            <p><?php echo translate('Total Enrollments'); ?></p>
            <h3><?php echo $statistics['total_enrollments']; ?></h3>
            <small><i class="fas fa-clock"></i> <?php echo $statistics['pending_requests']; ?> <?php echo translate('Pending Requests'); ?></small>
        </div>
        <div class="stat-card warning fade-in delay-3">
            <i class="fas fa-sign-in-alt stat-icon"></i>
            <p><?php echo translate('Recent Activity'); ?></p>
            <h3><?php echo $statistics['recent_logins']; ?></h3>
            <small><i class="fas fa-calendar-alt"></i> <?php echo translate('Logins in the last 7 days'); ?></small>
        </div>
    </div>

    <div class="dashboard-grid">
        <div>
            <!-- Pending Enrollment Requests -->
            <div class="dashboard-section fade-in">
                <div class="section-header">
                    <h2 class="section-title"><i class="fas fa-user-clock"></i> <?php echo translate('Pending Enrollment Requests'); ?></h2>
                    
                </div>
                
                <?php if (mysqli_num_rows($requests_result) > 0): ?>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th><?php echo translate('Student'); ?></th>
                                    <th><?php echo translate('Course'); ?></th>
                                    <th><?php echo translate('Date'); ?></th>
                                    
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($request = mysqli_fetch_assoc($requests_result)): ?>
                                    <tr>
                                        <td><i class="fas fa-user-graduate text-primary"></i> <?php echo htmlspecialchars($request['student_name']); ?></td>
                                        <td><i class="fas fa-book text-info"></i> <?php echo htmlspecialchars($request['course_name']); ?></td>
                                        <td><i class="fas fa-calendar-alt text-warning"></i> <?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                                        
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center" style="padding: 2rem; color: var(--gray);">
                        <i class="fas fa-check-circle" style="font-size: 3rem; margin-bottom: 1rem; color: var(--success);"></i>
                        <p><?php echo translate('No pending enrollment requests at this time.'); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Upcoming Events -->
            <div class="dashboard-section fade-in delay-1">
                <div class="section-header">
                    <h2 class="section-title"><i class="fas fa-calendar-day"></i> <?php echo translate('Upcoming Events'); ?></h2>
                    <a href="admin_events.php" class="section-action"><?php echo translate('Manage Events'); ?> <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <?php if (mysqli_num_rows($events_result) > 0): ?>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th><?php echo translate('Event'); ?></th>
                                    <th><?php echo translate('Course'); ?></th>
                                    <th><?php echo translate('Date & Time'); ?></th>
                                    <th><?php echo translate('Location'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($event = mysqli_fetch_assoc($events_result)): ?>
                                    <tr>
                                        <td><i class="fas fa-calendar-check text-primary"></i> <?php echo htmlspecialchars($event['title']); ?></td>
                                        <td><i class="fas fa-book text-info"></i> <?php echo htmlspecialchars($event['course_name']); ?></td>
                                        <td>
                                            <?php 
                                                echo '<i class="far fa-clock text-warning"></i> ' . 
                                                    date('M d, Y', strtotime($event['date'])) . ' ' . translate('at') . ' ' . 
                                                    date('h:i A', strtotime($event['time']));
                                                
                                                $event_date = new DateTime($event['date']);
                                                $today = new DateTime();
                                                $diff = $today->diff($event_date)->days;
                                                
                                                if ($diff <= 3 && $diff >= 0) {
                                                    echo ' <span class="badge badge-warning"><i class="fas fa-exclamation-circle"></i> ' . translate('Soon!') . '</span>';
                                                }
                                            ?>
                                        </td>
                                        <td><i class="fas fa-map-marker-alt text-danger"></i> <?php echo htmlspecialchars($event['location']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center" style="padding: 2rem; color: var(--gray);">
                        <i class="far fa-calendar-times" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <p><?php echo translate('No upcoming events scheduled.'); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Users Management Section -->
            <div class="dashboard-section fade-in delay-2">
                <div class="section-header">
                    <h2 class="section-title"><i class="fas fa-users-cog"></i> <?php echo translate('Manage Users'); ?></h2>
                  
                </div>
                
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="userSearch" class="search-input" placeholder="<?php echo translate('Search users by name, email or role...'); ?>">
                </div>
                
                <div class="table-responsive">
                    <table class="admin-table" id="usersTable">
                        <thead>
                            <tr>
                                <th><?php echo translate('ID'); ?></th>
                                <th><?php echo translate('Name'); ?></th>
                                <th><?php echo translate('Email'); ?></th>
                                <th><?php echo translate('Mobile'); ?></th>
                                <th><?php echo translate('Role'); ?></th>
                                <th><?php echo translate('Registration Date'); ?></th>
                                <th><?php echo translate('Actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                                <tr>
                                    <td><?php echo $user['user_id']; ?></td>
                                    <td>
                                        <?php if ($user['role'] == 'student'): ?>
                                            <i class="fas fa-user-graduate text-primary"></i>
                                        <?php else: ?>
                                            <i class="fas fa-chalkboard-teacher text-info"></i>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($user['name']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><i class="fas fa-phone text-success"></i> <?php echo htmlspecialchars($user['mobile']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $user['role'] == 'student' ? 'badge-primary' : 'badge-info'; ?>">
                                            <i class="fas fa-<?php echo $user['role'] == 'student' ? 'user-graduate' : 'chalkboard-teacher'; ?>"></i>
                                            <?php echo ucfirst(htmlspecialchars($user['role'])); ?>
                                        </span>
                                    </td>
                                    <td><i class="far fa-calendar-alt text-warning"></i> <?php echo date('M d, Y', strtotime($user['registration_date'])); ?></td>
                                    <td class="btn-group">
                                        <a href="admin_edit_<?php echo $user['role']; ?>.php?id=<?php echo $user['user_id']; ?>" class="btn-sm btn-primary"><i class="fas fa-edit"></i> <?php echo translate('Edit'); ?></a>
                                        <a href="admin_delete_<?php echo $user['role']; ?>.php?id=<?php echo $user['user_id']; ?>" class="btn-sm btn-danger" onclick="return confirm('<?php echo translate('Are you sure you want to delete this user?'); ?>');"><i class="fas fa-trash-alt"></i> <?php echo translate('Delete'); ?></a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="pagination">
                    <a href="#"><i class="fas fa-angle-double-left"></i></a>
                    <a href="#" class="active">1</a>
                    <a href="#">2</a>
                    <a href="#">3</a>
                    <a href="#"><i class="fas fa-angle-double-right"></i></a>
                </div>
            </div>

            <!-- Courses Management Section -->
            <div class="dashboard-section fade-in delay-3">
                <div class="section-header">
                    <h2 class="section-title"><i class="fas fa-book-open"></i> <?php echo translate('Manage Courses'); ?></h2>
                    
                </div>
                
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="courseSearch" class="search-input" placeholder="<?php echo translate('Search courses by name, category or instructor...'); ?>">
                </div>
                
                <div class="table-responsive">
                    <table class="admin-table" id="coursesTable">
                        <thead>
                            <tr>
                                <th><?php echo translate('Image'); ?></th>
                                <th><?php echo translate('Name'); ?></th>
                                <th><?php echo translate('Description'); ?></th>
                                <th><?php echo translate('Start Date'); ?></th>
                                <th><?php echo translate('Category'); ?></th>
                                <th><?php echo translate('Instructor'); ?></th>
                                <th><?php echo translate('Actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($course = mysqli_fetch_assoc($courses_result)): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($course['img'])): ?>
                                            <img src="assets/images/courses/<?php echo htmlspecialchars($course['img']); ?>" width="60" height="60" alt="<?php echo htmlspecialchars($course['name']); ?>">
                                        <?php else: ?>
                                            <div style="width: 60px; height: 60px; background-color: #f0f0f0; display: flex; align-items: center; justify-content: center; border-radius: 4px;">
                                                <i class="fas fa-image" style="color: #aaa;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($course['name']); ?></td>
                                    <td class="truncate"><?php echo htmlspecialchars($course['description']); ?></td>
                                    <td><i class="far fa-calendar-alt text-warning"></i> <?php echo date('M d, Y', strtotime($course['start_date'])); ?></td>
                                    <td>
                                        <span class="badge badge-info">
                                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($course['category_name']); ?>
                                        </span>
                                    </td>
                                    <td><i class="fas fa-chalkboard-teacher text-primary"></i> <?php echo htmlspecialchars($course['instructor_name']); ?></td>
                                    <td class="btn-group">
                                        <a href="admin_view_course.php?id=<?php echo $course['course_id']; ?>" class="btn-sm btn-info"><i class="fas fa-eye"></i> <?php echo translate('View'); ?></a>
                                        <a href="admin_edit_course.php?id=<?php echo $course['course_id']; ?>" class="btn-sm btn-primary"><i class="fas fa-edit"></i> <?php echo translate('Edit'); ?></a>
                                        <a href="admin_delete_course.php?id=<?php echo $course['course_id']; ?>" class="btn-sm btn-danger" onclick="return confirm('<?php echo translate('Are you sure you want to delete this course?'); ?>');"><i class="fas fa-trash-alt"></i> <?php echo translate('Delete'); ?></a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="pagination">
                    <a href="#"><i class="fas fa-angle-double-left"></i></a>
                    <a href="#" class="active">1</a>
                    <a href="#">2</a>
                    <a href="#">3</a>
                    <a href="#"><i class="fas fa-angle-double-right"></i></a>
                </div>
            </div>
        </div>
        
        <div>
            <!-- Recent Activity Section -->
            <div class="dashboard-section fade-in delay-1">
                <div class="section-header">
                    <h2 class="section-title"><i class="fas fa-history"></i> <?php echo translate('Recent Activity'); ?></h2>
                    <a href="admin_monitor_users.php" class="section-action"><?php echo translate('View All'); ?> <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <?php if (mysqli_num_rows($login_result) > 0): ?>
                    <div class="activity-feed">
                        <?php while ($login = mysqli_fetch_assoc($login_result)): ?>
                            <div class="activity-item">
                                <div class="activity-icon login">
                                    <?php if ($login['role'] == 'student'): ?>
                                        <i class="fas fa-user-graduate"></i>
                                    <?php elseif ($login['role'] == 'instructor'): ?>
                                        <i class="fas fa-chalkboard-teacher"></i>
                                    <?php else: ?>
                                        <i class="fas fa-user-shield"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title">
                                        <?php echo htmlspecialchars($login['name']); ?> <span class="badge <?php echo $login['role'] == 'student' ? 'badge-primary' : ($login['role'] == 'instructor' ? 'badge-info' : 'badge-warning'); ?>"><?php echo ucfirst($login['role']); ?></span> <?php echo translate('logged in'); ?>
                                    </div>
                                    <div class="activity-time">
                                        <i class="far fa-clock"></i>
                                        <?php 
                                            $login_time = new DateTime($login['login_time']);
                                            $now = new DateTime();
                                            $diff = $now->diff($login_time);
                                            
                                            if ($diff->days == 0) {
                                                if ($diff->h == 0) {
                                                    if ($diff->i == 0) {
                                                        echo translate('Just now');
                                                    } else {
                                                        echo $diff->i . " " . ($diff->i > 1 ? translate('minutes ago') : translate('minute ago'));
                                                    }
                                                } else {
                                                    echo $diff->h . " " . ($diff->h > 1 ? translate('hours ago') : translate('hour ago'));
                                                }
                                            } else if ($diff->days == 1) {
                                                echo translate('Yesterday at') . " " . $login_time->format('h:i A');
                                            } else {
                                                echo $login_time->format('M d, Y') . " " . translate('at') . " " . $login_time->format('h:i A');
                                            }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center" style="padding: 2rem; color: var(--gray);">
                        <i class="fas fa-history" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <p><?php echo translate('No recent activity to display.'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Quick Actions -->
            <div class="dashboard-section fade-in delay-2">
                <div class="section-header">
                    <h2 class="section-title"><i class="fas fa-bolt"></i> <?php echo translate('Quick Actions'); ?></h2>
                </div>
                
                <div class="quick-actions-grid">
                    <a href="admin_add_student.php" class="quick-action-btn">
                        <i class="fas fa-user-graduate"></i>
                        <span><?php echo translate('Add Student'); ?></span>
                    </a>
                    <a href="admin_add_instructor.php" class="quick-action-btn">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span><?php echo translate('Add Instructor'); ?></span>
                    </a>
                   
                    <a href="reports.php" class="quick-action-btn">
                        <i class="fas fa-chart-bar"></i>
                        <span><?php echo translate('View Reports'); ?></span>
                    </a>
                    <a href="admin_manage_categories.php" class="quick-action-btn">
                        <i class="fas fa-tags"></i>
                        <span><?php echo translate('Manage Categories'); ?></span>
                    </a>
                
                </div>
            </div>
            
            <!-- System Information -->
            <div class="dashboard-section fade-in delay-3">
                <div class="section-header">
                    <h2 class="section-title"><i class="fas fa-server"></i> <?php echo translate('System Information'); ?></h2>
                </div>
                
                <table class="system-info-table">
                    <tr>
                        <td><i class="fab fa-php"></i> <?php echo translate('PHP Version'); ?></td>
                        <td><?php echo phpversion(); ?></td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-database"></i> <?php echo translate('MySQL Version'); ?></td>
                        <td><?php echo mysqli_get_server_info($conn); ?></td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-server"></i> <?php echo translate('Server'); ?></td>
                        <td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
                    </tr>
                    <tr>
                        <td><i class="far fa-clock"></i> <?php echo translate('Current Time'); ?></td>
                        <td><?php echo date('Y-m-d H:i:s'); ?></td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-memory"></i> <?php echo translate('Memory Usage'); ?></td>
                        <td><?php echo round(memory_get_usage() / 1024 / 1024, 2); ?> MB</td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-hdd"></i> <?php echo translate('Disk Space'); ?></td>
                        <td><?php echo round(disk_free_space("/") / 1024 / 1024 / 1024, 2); ?> <?php echo translate('GB free'); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Search functionality for users table
document.getElementById('userSearch').addEventListener('keyup', function() {
    const searchValue = this.value.toLowerCase();
    const table = document.getElementById('usersTable');
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const name = rows[i].getElementsByTagName('td')[1].textContent.toLowerCase();
        const email = rows[i].getElementsByTagName('td')[2].textContent.toLowerCase();
        const role = rows[i].getElementsByTagName('td')[4].textContent.toLowerCase();
        
        if (name.includes(searchValue) || email.includes(searchValue) || role.includes(searchValue)) {
            rows[i].style.display = '';
        } else {
            rows[i].style.display = 'none';
        }
    }
});

// Search functionality for courses table
document.getElementById('courseSearch').addEventListener('keyup', function() {
    const searchValue = this.value.toLowerCase();
    const table = document.getElementById('coursesTable');
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const name = rows[i].getElementsByTagName('td')[1].textContent.toLowerCase();
        const description = rows[i].getElementsByTagName('td')[2].textContent.toLowerCase();
        const category = rows[i].getElementsByTagName('td')[4].textContent.toLowerCase();
        const instructor = rows[i].getElementsByTagName('td')[5].textContent.toLowerCase();
        
        if (name.includes(searchValue) || description.includes(searchValue) || 
            category.includes(searchValue) || instructor.includes(searchValue)) {
            rows[i].style.display = '';
        } else {
            rows[i].style.display = 'none';
        }
    }
});

// Add fade-in animation to table rows
document.addEventListener('DOMContentLoaded', function() {
    const tables = document.querySelectorAll('.admin-table');
    
    tables.forEach(table => {
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach((row, index) => {
            row.classList.add('fade-in');
            row.style.animationDelay = `${0.05 * (index + 1)}s`;
        });
    });
});
</script>

<?php include 'footer.php'; ?>
