<?php include 'config.php'; ?>
<?php
// Get course ID from URL
if (!isset($_GET['id'])) {
    header("Location: public_courses.php");
    exit();
}

$course_id = mysqli_real_escape_string($con, $_GET['id']);

// Get course details
$course_query = "SELECT c.*, cat.name as category_name, u.name as instructor_name, u.email as instructor_email
                FROM courses c
                JOIN category cat ON c.category_id = cat.category_id
                JOIN users u ON c.instructor_id = u.user_id
                WHERE c.course_id = '$course_id'";
$course_result = mysqli_query($con, $course_query);

if (mysqli_num_rows($course_result) == 0) {
    header("Location: public_courses.php");
    exit();
}

$course = mysqli_fetch_assoc($course_result);
$page_title = translate($course['name']);
include 'header.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_type']) && !empty($_SESSION['user_type']);
$is_student = $is_logged_in && $_SESSION['user_type'] == 'student';
$student_id = $is_student ? $_SESSION['user_id'] : 0;

// Check if student is already enrolled (if logged in)
$is_enrolled = false;
$has_pending_request = false;

if ($is_student) {
    // Check enrollment
    $enrollment_check = "SELECT * FROM course_enrollments 
                        WHERE student_id = '$student_id' AND course_id = '$course_id'";
    $enrollment_result = mysqli_query($con, $enrollment_check);
    $is_enrolled = (mysqli_num_rows($enrollment_result) > 0);
    
    // Check pending requests
    $request_check = "SELECT * FROM enrollment_requests 
                     WHERE student_id = '$student_id' AND course_id = '$course_id' AND status = 'pending'";
    $request_result = mysqli_query($con, $request_check);
    $has_pending_request = (mysqli_num_rows($request_result) > 0);
}

// Handle enrollment request if user is logged in as student
if ($is_student && isset($_POST['send_request']) && !$is_enrolled && !$has_pending_request) {
    // Insert the enrollment request
    $insert_query = "INSERT INTO enrollment_requests (student_id, course_id, created_at, status) 
                    VALUES ('$student_id', '$course_id', NOW(), 'pending')";
    
    if (mysqli_query($con, $insert_query)) {
        $has_pending_request = true;
        $success_message = translate("Enrollment request sent successfully!");
    } else {
        $error_message = translate("Error sending enrollment request:") . " " . mysqli_error($con);
    }
}

// Get course schedule
$schedule_query = "SELECT * FROM schedules WHERE course_id = '$course_id' ORDER BY FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')";
$schedule_result = mysqli_query($con, $schedule_query);

// Get upcoming events
$events_query = "SELECT * FROM course_events 
               WHERE course_id = '$course_id' AND date >= CURDATE() 
               ORDER BY date ASC, time ASC LIMIT 3";
$events_result = mysqli_query($con, $events_query);
?>

<style>
.course-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.course-header {
    display: flex;
    flex-wrap: wrap;
    gap: 30px;
    margin-bottom: 30px;
}

.course-image {
    width: 300px;
    height: 200px;
    object-fit: cover;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.course-info {
    flex: 1;
    min-width: 300px;
}

.course-title {
    font-size: 28px;
    color: #04639b;
    margin-bottom: 15px;
}

.course-meta {
    margin-bottom: 20px;
}

.course-meta p {
    margin: 8px 0;
    display: flex;
    align-items: center;
}

.course-meta i {
    width: 20px;
    margin-right: 10px;
    color: #04639b;
}

.course-actions {
    margin-top: 20px;
}

.btn {
    display: inline-block;
    padding: 10px 20px;
    border-radius: 4px;
    text-decoration: none;
    font-weight: bold;
    cursor: pointer;
    border: none;
    margin-right: 10px;
    margin-bottom: 10px;
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

.btn-warning {
    background-color: #ffc107;
    color: #212529;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.login-prompt {
    background-color: rgba(4, 99, 155, 0.1);
    border: 1px dashed #04639b;
    border-radius: 4px;
    padding: 15px;
    margin-top: 20px;
    text-align: center;
}

.login-prompt a {
    color: #04639b;
    font-weight: bold;
    text-decoration: none;
}

.login-prompt a:hover {
    text-decoration: underline;
}

.course-section {
    background-color: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.section-title {
    font-size: 20px;
    color: #04639b;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.course-description {
    line-height: 1.6;
    color: #333;
}

.schedule-table, .events-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.schedule-table th, .events-table th {
    background-color: #f8f9fa;
    padding: 10px;
    text-align: left;
    border-bottom: 2px solid #dee2e6;
}

.schedule-table td, .events-table td {
    padding: 10px;
    border-bottom: 1px solid #dee2e6;
}

.schedule-table tr:hover, .events-table tr:hover {
    background-color: #f8f9fa;
}

.alert {
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.instructor-info {
    display: flex;
    align-items: center;
    margin-top: 20px;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 8px;
}

.instructor-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background-color: #04639b;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-right: 15px;
}

.instructor-details {
    flex: 1;
}

.instructor-name {
    font-weight: bold;
    font-size: 18px;
    margin-bottom: 5px;
}

.instructor-contact {
    color: #6c757d;
    font-size: 14px;
}

@media (max-width: 768px) {
    .course-header {
        flex-direction: column;
    }
    
    .course-image {
        width: 100%;
        height: auto;
    }
}
</style>

<div class="course-container">
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <div class="course-header">
        <img src="assets/images/courses/<?php echo $course['img'] ? $course['img'] : 'default-course.jpg'; ?>" 
             class="course-image" alt="<?php echo htmlspecialchars(translate($course['name'])); ?>">
        
        <div class="course-info">
            <h1 class="course-title"><?php echo htmlspecialchars(translate($course['name'])); ?></h1>
            
            <div class="course-meta">
                <p><i class="fas fa-folder"></i> <strong><?php echo translate('Category'); ?>:</strong> <?php echo htmlspecialchars(translate($course['category_name'])); ?></p>
                <p><i class="fas fa-user"></i> <strong><?php echo translate('Instructor'); ?>:</strong> <?php echo htmlspecialchars($course['instructor_name']); ?></p>
                <p><i class="fas fa-calendar"></i> <strong><?php echo translate('Start Date'); ?>:</strong> <?php echo date('F d, Y', strtotime($course['start_date'])); ?></p>
                <?php if (!empty($course['prerequisites'])): ?>
                    <p><i class="fas fa-list-check"></i> <strong><?php echo translate('Prerequisites'); ?>:</strong> <?php echo htmlspecialchars(translate($course['prerequisites'])); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="course-actions">
                <?php if ($is_student): ?>
                    <?php if ($is_enrolled): ?>
                        <a href="student_view_my_courses.php" class="btn btn-success"><?php echo translate('Go to My Courses'); ?></a>
                    <?php elseif ($has_pending_request): ?>
                        <button class="btn btn-warning" disabled><?php echo translate('Request Pending'); ?></button>
                    <?php else: ?>
                        <form method="post" action="" style="display: inline;">
                            <button type="submit" name="send_request" class="btn btn-primary">
                                <?php echo translate('Send Enrollment Request'); ?>
                            </button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="login-prompt">
                        <p><?php echo translate('Want to enroll in this course?'); ?> <a href="login.php"><?php echo translate('Login'); ?></a> <?php echo translate('or'); ?> <a href="student_register.php"><?php echo translate('Register'); ?></a> <?php echo translate('first!'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="course-section">
        <h2 class="section-title"><?php echo translate('Course Description'); ?></h2>
        <div class="course-description">
            <?php echo nl2br(htmlspecialchars(translate($course['description']))); ?>
        </div>
        
        <div class="instructor-info">
            <div class="instructor-avatar">
                <?php echo strtoupper(substr($course['instructor_name'], 0, 1)); ?>
            </div>
            <div class="instructor-details">
                <div class="instructor-name"><?php echo htmlspecialchars($course['instructor_name']); ?></div>
                <div class="instructor-contact">
                    <?php if ($is_logged_in): ?>
                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($course['instructor_email']); ?>
                    <?php else: ?>
                        <i class="fas fa-envelope"></i> <?php echo translate('Login to see contact information'); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (mysqli_num_rows($schedule_result) > 0): ?>
        <div class="course-section">
            <h2 class="section-title"><?php echo translate('Course Schedule'); ?></h2>
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th><?php echo translate('Day'); ?></th>
                        <th><?php echo translate('Start Time'); ?></th>
                        <th><?php echo translate('End Time'); ?></th>
                        <th><?php echo translate('Location'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($schedule = mysqli_fetch_assoc($schedule_result)): ?>
                        <tr>
                            <td><?php echo translate(htmlspecialchars($schedule['day'])); ?></td>
                            <td><?php echo date('h:i A', strtotime($schedule['start_time'])); ?></td>
                            <td><?php echo date('h:i A', strtotime($schedule['end_time'])); ?></td>
                            <td><?php echo htmlspecialchars(translate($schedule['location'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if (mysqli_num_rows($events_result) > 0): ?>
        <div class="course-section">
            <h2 class="section-title"><?php echo translate('Upcoming Events'); ?></h2>
            <table class="events-table">
                <thead>
                    <tr>
                        <th><?php echo translate('Event'); ?></th>
                        <th><?php echo translate('Date'); ?></th>
                        <th><?php echo translate('Time'); ?></th>
                        <th><?php echo translate('Location'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($event = mysqli_fetch_assoc($events_result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(translate($event['title'])); ?></td>
                            <td><?php echo date('F d, Y', strtotime($event['date'])); ?></td>
                            <td><?php echo date('h:i A', strtotime($event['time'])); ?></td>
                            <td><?php echo htmlspecialchars(translate($event['location'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <?php if (!$is_logged_in): ?>
                <div class="login-prompt" style="margin-top: 15px;">
                    <p><?php echo translate('Login to see all events and course details!'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="course-actions" style="text-align: center; margin-top: 30px;">
        <a href="public_courses.php" class="btn btn-secondary"><?php echo translate('Back to Courses'); ?></a>
        
        <?php if (!$is_logged_in): ?>
            <a href="login.php" class="btn btn-primary"><?php echo translate('Login to Enroll'); ?></a>
            <a href="student_register.php" class="btn btn-success"><?php echo translate('Register Now'); ?></a>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>