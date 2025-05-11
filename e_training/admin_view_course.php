<?php include 'config.php'; ?>

<?php $page_title = translate("View Course"); ?>

<?php include 'header.php'; ?>

<?php
// if not logged in as admin; redirect to the login page
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != "admin") {
    header("Location: login.php");
    exit();
}

// Check if course ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('" . translate('Course ID is required') . "'); window.location.href='admin_manage_courses.php';</script>";
    exit();
}

$course_id = mysqli_real_escape_string($con, $_GET['id']);

// Get course details with category and instructor names
$course_query = "SELECT c.*, cat.name as category_name, u.name as instructor_name, u.email as instructor_email 
                FROM courses c
                LEFT JOIN category cat ON c.category_id = cat.category_id
                LEFT JOIN users u ON c.instructor_id = u.user_id
                WHERE c.course_id = '$course_id'";
$course_result = mysqli_query($con, $course_query);

if (mysqli_num_rows($course_result) == 0) {
    echo "<script>alert('" . translate('Course not found') . "'); window.location.href='admin_manage_courses.php';</script>";
    exit();
}

$course = mysqli_fetch_assoc($course_result);

// Get enrollment count
$enrollment_query = "SELECT COUNT(*) as count FROM course_enrollments WHERE course_id = '$course_id'";
$enrollment_result = mysqli_query($con, $enrollment_query);
$enrollment_count = mysqli_fetch_assoc($enrollment_result)['count'];

// Get pending enrollment requests
$pending_query = "SELECT COUNT(*) as count FROM enrollment_requests WHERE course_id = '$course_id' AND status = 'pending'";
$pending_result = mysqli_query($con, $pending_query);
$pending_count = mysqli_fetch_assoc($pending_result)['count'];

// Get course materials count
$materials_query = "SELECT COUNT(*) as count FROM course_materials WHERE course_id = '$course_id'";
$materials_result = mysqli_query($con, $materials_query);
$materials_count = mysqli_fetch_assoc($materials_result)['count'];

// Get course events count
$events_query = "SELECT COUNT(*) as count FROM course_events WHERE course_id = '$course_id'";
$events_result = mysqli_query($con, $events_query);
$events_count = mysqli_fetch_assoc($events_result)['count'];

// Get average rating
$rating_query = "SELECT AVG(rating) as avg_rating FROM course_feedback WHERE course_id = '$course_id'";
$rating_result = mysqli_query($con, $rating_query);
$avg_rating = mysqli_fetch_assoc($rating_result)['avg_rating'];
$avg_rating = $avg_rating ? round($avg_rating, 1) : 0;
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-book"></i> <?php echo translate('View Course'); ?></h4>
                <div>
                    <a href="admin_edit_course.php?id=<?php echo $course_id; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-edit"></i> <?php echo translate('Edit Course'); ?>
                    </a>
                    <a href="admin_manage_courses.php" class="btn btn-secondary btn-sm ms-2">
                        <i class="fas fa-arrow-left"></i> <?php echo translate('Back to Courses'); ?>
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <?php if (!empty($course['img'])): ?>
                        <img src="assets/images/courses/<?php echo $course['img']; ?>" alt="<?php echo htmlspecialchars($course['name']); ?>" class="img-fluid rounded">
                    <?php else: ?>
                        <div class="bg-light text-center p-5 rounded">
                            <i class="fas fa-image fa-4x text-muted"></i>
                            <p class="mt-3 text-muted"><?php echo translate('No image available'); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card mt-3">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><?php echo translate('Course Statistics'); ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span><i class="fas fa-users"></i> <?php echo translate('Enrolled Students'); ?>:</span>
                                <span class="badge bg-primary"><?php echo $enrollment_count; ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span><i class="fas fa-user-clock"></i> <?php echo translate('Pending Requests'); ?>:</span>
                                <span class="badge bg-warning"><?php echo $pending_count; ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span><i class="fas fa-file-alt"></i> <?php echo translate('Course Materials'); ?>:</span>
                                <span class="badge bg-info"><?php echo $materials_count; ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span><i class="fas fa-calendar-alt"></i> <?php echo translate('Course Events'); ?>:</span>
                                <span class="badge bg-success"><?php echo $events_count; ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span><i class="fas fa-star"></i> <?php echo translate('Average Rating'); ?>:</span>
                                <span class="badge bg-warning text-dark">
                                    <?php echo $avg_rating; ?>/5
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo ($i <= $avg_rating) ? 'text-warning' : 'text-muted'; ?>"></i>
                                    <?php endfor; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <h3 class="mb-3"><?php echo htmlspecialchars($course['name']); ?></h3>
                    
                    <div class="mb-4">
                        <h5><?php echo translate('Description'); ?></h5>
                        <p><?php echo nl2br(htmlspecialchars($course['description'])); ?></p>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0"><?php echo translate('Course Details'); ?></h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <strong><i class="fas fa-calendar"></i> <?php echo translate('Start Date'); ?>:</strong>
                                        <span class="ms-2"><?php echo date('F j, Y', strtotime($course['start_date'])); ?></span>
                                    </div>
                                    <div class="mb-2">
                                        <strong><i class="fas fa-tag"></i> <?php echo translate('Category'); ?>:</strong>
                                        <span class="ms-2"><?php echo htmlspecialchars($course['category_name']); ?></span>
                                    </div>
                                    <div>
                                        <strong><i class="fas fa-list-ul"></i> <?php echo translate('Prerequisites'); ?>:</strong>
                                        <p class="ms-4 mb-0"><?php echo !empty($course['prerequisites']) ? nl2br(htmlspecialchars($course['prerequisites'])) : translate('None'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0"><?php echo translate('Instructor Information'); ?></h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <strong><i class="fas fa-chalkboard-teacher"></i> <?php echo translate('Name'); ?>:</strong>
                                        <span class="ms-2"><?php echo htmlspecialchars($course['instructor_name']); ?></span>
                                    </div>
                                    <div class="mb-2">
                                        <strong><i class="fas fa-envelope"></i> <?php echo translate('Email'); ?>:</strong>
                                        <span class="ms-2"><?php echo htmlspecialchars($course['instructor_email']); ?></span>
                                    </div>
                                    
                                    
                                </div>
                        
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                   
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
