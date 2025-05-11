<?php include 'config.php'; ?>

<?php include 'header.php'; ?>

<?php
// if not logged in as instructor; redirect to the login page
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != "instructor") {
    header("Location: login.php");
    exit();
}

// Check if database connection exists
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

// Update the database connection variable in the SQL queries
// Change $conn to $con to match the other instructor files
// Get instructor information
$instructor_id = $_SESSION['user_id'];
$instructor_name = translate("Instructor"); // Default name in case query fails

try {
    $instructor_query = "SELECT * FROM users WHERE user_id = $instructor_id";
    $instructor_result = mysqli_query($con, $instructor_query);
    
    if ($instructor_result && mysqli_num_rows($instructor_result) > 0) {
        $instructor = mysqli_fetch_assoc($instructor_result);
        $instructor_name = $instructor['name'];
    }
} catch (Exception $e) {
    // Silently handle the error, we'll use the default name
}

// Get instructor's courses
$courses_query = "SELECT c.*, cat.name as category_name, 
                 (SELECT COUNT(*) FROM course_enrollments WHERE course_id = c.course_id) as enrolled_students
                 FROM courses c 
                 LEFT JOIN category cat ON c.category_id = cat.category_id
                 WHERE c.instructor_id = $instructor_id 
                 ORDER BY c.start_date DESC";
$courses_result = mysqli_query($con, $courses_query);

// Count total courses
$total_courses = mysqli_num_rows($courses_result);

// Get total students enrolled in instructor's courses
$students_query = "SELECT COUNT(DISTINCT student_id) as total_students 
                  FROM course_enrollments 
                  WHERE course_id IN (SELECT course_id FROM courses WHERE instructor_id = $instructor_id)";
$students_result = mysqli_query($con, $students_query);
$students_data = mysqli_fetch_assoc($students_result);
$total_students = $students_data['total_students'];

// Get total course materials
$materials_query = "SELECT COUNT(*) as total_materials 
                   FROM course_materials 
                   WHERE course_id IN (SELECT course_id FROM courses WHERE instructor_id = $instructor_id)";
$materials_result = mysqli_query($con, $materials_query);
$materials_data = mysqli_fetch_assoc($materials_result);
$total_materials = $materials_data['total_materials'];

// Get recent enrollment requests
$requests_query = "SELECT er.*, u.name AS student_name, c.name AS course_name 
                  FROM enrollment_requests er
                  JOIN users u ON er.student_id = u.user_id
                  JOIN courses c ON er.course_id = c.course_id
                  WHERE c.instructor_id = $instructor_id AND er.status = 'pending'
                  ORDER BY er.created_at DESC
                  LIMIT 5";
$requests_result = mysqli_query($con, $requests_query);

// Get upcoming events
$events_query = "SELECT ce.*, c.name AS course_name
                FROM course_events ce
                JOIN courses c ON ce.course_id = c.course_id
                WHERE c.instructor_id = $instructor_id AND ce.date >= CURDATE()
                ORDER BY ce.date ASC, ce.time ASC
                LIMIT 5";
$events_result = mysqli_query($con, $events_query);

// Get recent student activities (e.g., progress updates)
// Using the correct column name 'date' from the student_progress table
$activities_query = "SELECT sp.*, u.name AS student_name, c.name AS course_name
                    FROM student_progress sp
                    JOIN users u ON sp.student_id = u.user_id
                    JOIN courses c ON sp.course_id = c.course_id
                    WHERE c.instructor_id = $instructor_id
                    ORDER BY sp.date DESC
                    LIMIT 10";
$activities_result = mysqli_query($con, $activities_query);

// Define base URL for all links
$base_url = ""; // Leave empty if links should be relative to current directory
?>

<style>
/* Instructor Dashboard Specific Styles */
.instructor-container {
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.dashboard-title {
    font-size: 24px;
    font-weight: bold;
    color: #04639b;
}

.dashboard-actions {
    display: flex;
    gap: 10px;
}

.dashboard-btn {
    padding: 8px 15px;
    background-color: #04639b;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.dashboard-btn:hover {
    background-color: #034f7d;
}

.dashboard-btn-success {
    background-color: #28a745;
}

.dashboard-btn-success:hover {
    background-color: #218838;
}

.welcome-banner {
    background-color: #04639b;
    color: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.welcome-text h2 {
    margin: 0;
    font-size: 24px;
}

.welcome-text p {
    margin: 5px 0 0 0;
    opacity: 0.9;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.stat-card {
    background-color: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    text-align: center;
    transition: transform 0.2s;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-card h3 {
    font-size: 28px;
    margin: 10px 0;
    color: #04639b;
}

.stat-card p {
    color: #666;
    margin: 0;
}

.stat-card.primary {
    border-top: 4px solid #04639b;
}

.stat-card.success {
    border-top: 4px solid #28a745;
}

.stat-card.warning {
    border-top: 4px solid #ffc107;
}

.stat-card.info {
    border-top: 4px solid #17a2b8;
}

.dashboard-section {
    background-color: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.section-title {
    font-size: 18px;
    font-weight: bold;
    color: #04639b;
    margin: 0;
}

.section-action {
    color: #04639b;
    text-decoration: none;
    font-size: 14px;
}

.section-action:hover {
    text-decoration: underline;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
}

@media (max-width: 992px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
}

.course-card {
    border: 1px solid #eee;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 20px;
    transition: transform 0.2s, box-shadow 0.2s;
}

.course-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.course-card-header {
    background-color: #04639b;
    color: white;
    padding: 15px;
    font-weight: bold;
}

.course-card-body {
    padding: 15px;
    display: flex;
    gap: 15px;
}

.course-card-img {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: 4px;
}

.course-card-content {
    flex: 1;
}

.course-card-content h4 {
    margin-top: 0;
    margin-bottom: 10px;
    color: #333;
}

.course-card-content p {
    margin: 5px 0;
    color: #666;
}

.course-card-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.btn {
    padding: 6px 12px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 14px;
    display: inline-block;
    cursor: pointer;
    border: none;
}

.btn-primary {
    background-color: #04639b;
    color: white;
}

.btn-primary:hover {
    background-color: #034f7d;
}

.btn-success {
    background-color: #28a745;
    color: white;
}

.btn-success:hover {
    background-color: #218838;
}

.btn-info {
    background-color: #17a2b8;
    color: white;
}

.btn-info:hover {
    background-color: #138496;
}

.btn-warning {
    background-color: #ffc107;
    color: #212529;
}

.btn-warning:hover {
    background-color: #e0a800;
}

.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
}

.badge-primary {
    background-color: #cfe2ff;
    color: #084298;
}

.badge-success {
    background-color: #d1e7dd;
    color: #0f5132;
}

.badge-warning {
    background-color: #fff3cd;
    color: #664d03;
}

.badge-info {
    background-color: #cff4fc;
    color: #055160;
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th {
    background-color: #f8f9fa;
    padding: 12px;
    text-align: left;
    font-weight: bold;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
}

.table td {
    padding: 12px;
    border-bottom: 1px solid #dee2e6;
}

.table tr:hover {
    background-color: #f8f9fa;
}

.activity-item {
    display: flex;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #eee;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    flex-shrink: 0;
}

.activity-icon.progress {
    background-color: #d1e7dd;
    color: #0f5132;
}

.activity-content {
    flex-grow: 1;
}

.activity-title {
    font-weight: bold;
    margin-bottom: 3px;
}

.activity-time {
    font-size: 12px;
    color: #6c757d;
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 10px;
}

.quick-action-btn {
    background-color: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
    text-decoration: none;
    color: #333;
    transition: all 0.2s;
}

.quick-action-btn:hover {
    background-color: #f8f9fa;
    transform: translateY(-3px);
    box-shadow: 0 5px 10px rgba(0,0,0,0.1);
}

.quick-action-icon {
    font-size: 24px;
    margin-bottom: 10px;
    color: #04639b;
}

.quick-action-text {
    font-size: 14px;
    font-weight: bold;
}

.empty-state {
    text-align: center;
    padding: 30px;
    color: #6c757d;
}

.empty-state p {
    margin-bottom: 15px;
}

.progress-bar-container {
    width: 100%;
    height: 8px;
    background-color: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
    margin-top: 5px;
}

.progress-bar-fill {
    height: 100%;
    background-color: #04639b;
    border-radius: 4px;
}

.progress-bar-fill.complete {
    background-color: #28a745;
}

.progress-text {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #6c757d;
    margin-top: 5px;
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid transparent;
    border-radius: 4px;
}

.alert-info {
    color: #055160;
    background-color: #cff4fc;
    border-color: #b6effb;
}
</style>

<div class="instructor-container">
    <!-- Welcome Banner -->
    <div class="welcome-banner">
        <div class="welcome-text">
            <h2><?php echo translate('Welcome back'); ?>, <?php echo htmlspecialchars($instructor_name); ?>!</h2>
            <p><?php echo translate('Manage your courses and track student progress'); ?></p>
        </div>
    </div>

    <!-- Dashboard Stats -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <p><?php echo translate('My Courses'); ?></p>
            <h3><?php echo $total_courses; ?></h3>
            <small><?php echo translate('Active teaching courses'); ?></small>
        </div>
        <div class="stat-card success">
            <p><?php echo translate('Total Students'); ?></p>
            <h3><?php echo $total_students; ?></h3>
            <small><?php echo translate('Enrolled in your courses'); ?></small>
        </div>
        <div class="stat-card info">
            <p><?php echo translate('Course Materials'); ?></p>
            <h3><?php echo $total_materials; ?></h3>
            <small><?php echo translate('Uploaded resources'); ?></small>
        </div>
        <div class="stat-card warning">
            <p><?php echo translate('Pending Requests'); ?></p>
            <h3><?php echo mysqli_num_rows($requests_result); ?></h3>
            <small><?php echo translate('Enrollment requests'); ?></small>
        </div>
    </div>

    <div class="dashboard-grid">
        <div>
            <!-- My Courses Section -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2 class="section-title"><?php echo translate('My Courses'); ?></h2>
                    
                </div>
                
                <?php if ($total_courses > 0): ?>
                    <?php 
                    // Reset the result pointer to the beginning
                    mysqli_data_seek($courses_result, 0);
                    
                    while ($course = mysqli_fetch_assoc($courses_result)): 
                    ?>
                        <div class="course-card">
                            <div class="course-card-header">
                                <?php echo htmlspecialchars($course['name']); ?>
                            </div>
                            <div class="course-card-body">
                                <img src="<?php echo $base_url; ?>assets\images\courses/<?php echo htmlspecialchars($course['img']); ?>" 
                                     class="course-card-img" alt="<?php echo htmlspecialchars($course['name']); ?>">
                                <div class="course-card-content">
                                    <p><strong><?php echo translate('Category'); ?>:</strong> <?php echo htmlspecialchars($course['category_name']); ?></p>
                                    <p><strong><?php echo translate('Start Date'); ?>:</strong> <?php echo date('M d, Y', strtotime($course['start_date'])); ?></p>
                                    <p><strong><?php echo translate('Students Enrolled'); ?>:</strong> <?php echo $course['enrolled_students']; ?></p>
                                    <div class="course-card-actions">
                                        <a href="instructor_edit_course.php?id=<?php echo $course['course_id']; ?>" class="btn btn-primary"><?php echo translate('Edit Course'); ?></a>
                                        <a href="instructor_manage_course_materials.php?id=<?php echo $course['course_id']; ?>" class="btn btn-info"><?php echo translate('Materials'); ?></a>
                                        <a href="instructor_view_course_students.php?id=<?php echo $course['course_id']; ?>" class="btn btn-success"><?php echo translate('View Students'); ?></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <p><?php echo translate('You don\'t have any courses yet.'); ?></p>
                        <a href="add_course.php" class="btn btn-primary"><?php echo translate('Create Your First Course'); ?></a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Enrollment Requests Section -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2 class="section-title"><?php echo translate('Pending Enrollment Requests'); ?></h2>
                    
                </div>
                
                <?php if (mysqli_num_rows($requests_result) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th><?php echo translate('Student'); ?></th>
                                <th><?php echo translate('Course'); ?></th>
                                <th><?php echo translate('Date'); ?></th>
                                <th><?php echo translate('Actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($request = mysqli_fetch_assoc($requests_result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($request['course_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                                    <td>
                                        <a href="instructor_manage_course_enrollment_requests.php?id=<?php echo $request['course_id']; ?>&action=approve&request_id=<?php echo $request['request_id']; ?>" class="btn btn-success"><?php echo translate('Approve'); ?></a>
                                        <a href="instructor_manage_course_enrollment_requests.php?id=<?php echo $request['course_id']; ?>&action=reject&request_id=<?php echo $request['request_id']; ?>" class="btn btn-warning"><?php echo translate('Reject'); ?></a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <p><?php echo translate('No pending enrollment requests.'); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Student Progress Section -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2 class="section-title"><?php echo translate('Recent Student Activities'); ?></h2>
                    
                </div>
                
                <?php if (mysqli_num_rows($activities_result) > 0): ?>
                    <div>
                        <?php while ($activity = mysqli_fetch_assoc($activities_result)): ?>
                            <div class="activity-item">
                                <div class="activity-icon progress">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title">
                                        <?php echo htmlspecialchars($activity['student_name']); ?> - <?php echo htmlspecialchars($activity['course_name']); ?>
                                    </div>
                                    <div class="progress-bar-container">
                                        <div class="progress-bar-fill <?php echo ($activity['value'] == 100) ? 'complete' : ''; ?>" 
                                             style="width: <?php echo (int)$activity['value']; ?>%">
                                        </div>
                                    </div>
                                    <div class="progress-text">
                                        <span><?php echo translate('Progress'); ?>: <?php echo (int)$activity['value']; ?>%</span>
                                        <span>
                                            <?php 
                                            // Using the correct 'date' column from student_progress table
                                            echo translate('Last Updated') . ': ' . date('M d, Y', strtotime($activity['date']));
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p><?php echo translate('No recent student activities.'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div>
            <!-- Quick Actions Section -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2 class="section-title"><?php echo translate('Quick Actions'); ?></h2>
                </div>
                
                
                <div class="quick-actions">
                    <a href="instructor_add_course.php" class="quick-action-btn">
                        <div class="quick-action-icon">
                            <i class="fas fa-plus-circle"></i>
                        </div>
                        <div class="quick-action-text"><?php echo translate('Add Course'); ?></div>
                    </a>
                    <a href="instructor_manage_courses.php" class="quick-action-btn">
                        <div class="quick-action-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="quick-action-text"><?php echo translate('Manage Courses'); ?></div>
                    </a>
                  
                </div>
            </div>
            
            <!-- Upcoming Events Section -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2 class="section-title"><?php echo translate('Upcoming Events'); ?></h2>
                   
                </div>
                
                <?php if (mysqli_num_rows($events_result) > 0): ?>
                    <table class="table">
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
                                    <td><?php echo htmlspecialchars($event['title']); ?></td>
                                    <td><?php echo htmlspecialchars($event['course_name']); ?></td>
                                    <td>
                                        <?php 
                                            echo date('M d, Y', strtotime($event['date'])) . ' ' . translate('at') . ' ' . 
                                                date('h:i A', strtotime($event['time']));
                                            
                                            $event_date = new DateTime($event['date']);
                                            $today = new DateTime();
                                            $diff = $today->diff($event_date)->days;
                                            
                                            if ($diff <= 3 && $diff >= 0) {
                                                echo ' <span class="badge badge-warning">' . translate('Soon!') . '</span>';
                                            }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($event['location']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <p><?php echo translate('No upcoming events.'); ?></p>
                    
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Tips & Resources Section -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2 class="section-title"><?php echo translate('Teaching Tips'); ?></h2>
                </div>
                
                <div class="alert alert-info">
                    <strong><?php echo translate('Tip of the Day'); ?>:</strong> <?php echo translate('Engage students with interactive content and regular feedback to improve course completion rates.'); ?>
                </div>
                
                <ul>
                    <li><a href="#" class="section-action"><?php echo translate('How to create engaging course materials'); ?></a></li>
                    <li><a href="#" class="section-action"><?php echo translate('Best practices for online teaching'); ?></a></li>
                    <li><a href="#" class="section-action"><?php echo translate('Using multimedia to enhance learning'); ?></a></li>
                    <li><a href="#" class="section-action"><?php echo translate('Effective assessment strategies'); ?></a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Add confirmation message container that's initially hidden -->
<div id="confirmation-message" class="alert alert-info" style="position: fixed; bottom: 20px; right: 20px; padding: 15px; border-radius: 5px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); display: none;">
    <?php echo translate('Action completed successfully!'); ?>
</div>

<!-- Add JavaScript to handle interactions -->
<script>
    // Function to show the confirmation message
    function showConfirmation(message) {
        const confirmationEl = document.getElementById('confirmation-message');
        confirmationEl.textContent = message;
        confirmationEl.style.display = 'block';
        
        // Hide the message after 3 seconds
        setTimeout(() => {
            confirmationEl.style.display = 'none';
        }, 3000);
    }
    
    // Add event listeners for quick action buttons
    document.querySelectorAll('.quick-action-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const actionText = this.querySelector('.quick-action-text').textContent;
            showConfirmation(`<?php echo translate('Navigating to'); ?> ${actionText}...`);
        });
    });
</script>

<?php include 'footer.php'; ?>