<?php include 'config.php'; ?>

<?php
// Get course info for the page title
$course_query = "SELECT name FROM courses WHERE course_id = '$_GET[course_id]'";
$course_result = mysqli_query($con, $course_query) or die('error: ' . mysqli_error($con));
$course_row = mysqli_fetch_array($course_result);
$page_title = translate("My Progress") . " - " . translate($course_row['name']);
include 'header.php';

// Check if user is logged in as student
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != "student") {
    header("Location: index.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$course_id = $_GET['course_id'];

// Check if student is enrolled in this course
$enrollment_check = "SELECT * FROM course_enrollments 
                    WHERE student_id = '$student_id' AND course_id = '$course_id'";
$enrollment_result = mysqli_query($con, $enrollment_check);

if (mysqli_num_rows($enrollment_result) == 0) {
    echo "<script>alert('" . translate('You are not enrolled in this course') . "');</script>";
    echo "<meta http-equiv='Refresh' content='0; url=student_view_my_courses.php'>";
    exit;
}

// Get course details
$course_query = "SELECT c.*, u.name as instructor_name, cat.name as category_name
                FROM courses c
                JOIN users u ON c.instructor_id = u.user_id
                JOIN category cat ON c.category_id = cat.category_id
                WHERE c.course_id = '$course_id'";
$course_result = mysqli_query($con, $course_query) or die('error: ' . mysqli_error($con));
$course = mysqli_fetch_array($course_result);

// Get student progress information
$progress_query = "SELECT * FROM student_progress 
                  WHERE student_id = '$student_id' AND course_id = '$course_id'";
$progress_result = mysqli_query($con, $progress_query) or die('error: ' . mysqli_error($con));
$progress = mysqli_fetch_array($progress_result);

// If no progress record exists, create a default one
if (!$progress) {
    $progress = [
        'value' => 0,
        'notes' => translate('Not started'),
        'date' => date('Y-m-d H:i:s')
    ];
}

// Get number of materials in the course
$materials_count_query = "SELECT COUNT(*) as total FROM course_materials WHERE course_id = '$course_id'";
$materials_count_result = mysqli_query($con, $materials_count_query);
$materials_count = mysqli_fetch_array($materials_count_result)['total'];
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4><?php echo translate($course['name']); ?> - <?php echo translate('My Progress'); ?></h4>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <img src="assets\images\courses/<?php echo $course['img']; ?>" class="img-fluid rounded" alt="<?php echo translate($course['name']); ?>">
                        </div>
                        <div class="col-md-8">
                            <h5><?php echo translate('Course Details'); ?></h5>
                            <p><strong><?php echo translate('Description'); ?>:</strong> <?php echo translate($course['description']); ?></p>
                            <p><strong><?php echo translate('Category'); ?>:</strong> <?php echo translate($course['category_name']); ?></p>
                            <p><strong><?php echo translate('Instructor'); ?>:</strong> <?php echo $course['instructor_name']; ?></p>
                            <p><strong><?php echo translate('Prerequisites'); ?>:</strong> <?php echo translate($course['prerequisites']); ?></p>
                            <p><strong><?php echo translate('Start Date'); ?>:</strong> <?php echo $course['start_date']; ?></p>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <h5><?php echo translate('Current Progress'); ?></h5>
                            <div class="progress mb-3" style="height: 25px;">
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: <?php echo $progress['value']; ?>%" 
                                     aria-valuenow="<?php echo $progress['value']; ?>" 
                                     aria-valuemin="0" aria-valuemax="100">
                                    <?php echo $progress['value']; ?>%
                                </div>
                            </div>
                            
                            <div class="card mb-4">
                                <div class="card-header"><?php echo translate('Progress Details'); ?></div>
                                <div class="card-body">
                                    <p><strong><?php echo translate('Last Updated'); ?>:</strong> <?php echo $progress['date']; ?></p>
                                    <p><strong><?php echo translate('Notes'); ?>:</strong> <?php echo translate($progress['notes']); ?></p>
                                    <p><strong><?php echo translate('Status'); ?>:</strong> 
                                        <?php if ($progress['value'] == 0): ?>
                                            <span class="badge bg-danger"><?php echo translate('Not Started'); ?></span>
                                        <?php elseif ($progress['value'] < 50): ?>
                                            <span class="badge bg-warning"><?php echo translate('In Progress'); ?></span>
                                        <?php elseif ($progress['value'] < 100): ?>
                                            <span class="badge bg-info"><?php echo translate('Advanced'); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-success"><?php echo translate('Completed'); ?></span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            
                            <?php if ($progress['value'] == 100): ?>
                                <div class="alert alert-success">
                                    <h5><?php echo translate('Congratulations!'); ?></h5>
                                    <p><?php echo translate('You have completed this course. You can now'); ?> <a href="student_print_certificate.php?course_id=<?php echo $course_id; ?>" class="btn btn-success btn-sm" target="_blank"><?php echo translate('Print Your Certificate'); ?></a></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
