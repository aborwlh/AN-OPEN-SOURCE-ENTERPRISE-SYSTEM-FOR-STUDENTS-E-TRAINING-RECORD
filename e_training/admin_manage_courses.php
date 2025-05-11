<?php include 'config.php'; ?>

<?php $page_title = translate("Manage Courses"); ?>

<?php include 'header.php'; ?>

<?php
// if not logged in as admin; redirect to the login page
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != "admin") {
    header("Location: login.php");
    exit();
}

// Handle course deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $course_id = mysqli_real_escape_string($con, $_GET['id']);
    
    // Check if there are enrollments for this course
    $check_enrollments = "SELECT COUNT(*) as count FROM course_enrollments WHERE course_id = '$course_id'";
    $enrollments_result = mysqli_query($con, $check_enrollments);
    $enrollments_count = mysqli_fetch_assoc($enrollments_result)['count'];
    
    if ($enrollments_count > 0) {
        $error_message = translate('Cannot delete course. There are students enrolled in this course. Please check the unenroll option to proceed with deletion.');
    } else {
        // Begin transaction
        mysqli_begin_transaction($con);
        
        try {
            // Delete course materials
            mysqli_query($con, "DELETE FROM course_materials WHERE course_id = '$course_id'");
            
            // Delete course events
            mysqli_query($con, "DELETE FROM course_events WHERE course_id = '$course_id'");
            
            // Delete course schedules
            mysqli_query($con, "DELETE FROM schedules WHERE course_id = '$course_id'");
            
            // Delete enrollment requests
            mysqli_query($con, "DELETE FROM enrollment_requests WHERE course_id = '$course_id'");
            
            // Delete course feedback
            mysqli_query($con, "DELETE FROM course_feedback WHERE course_id = '$course_id'");
            
            // Delete student progress
            mysqli_query($con, "DELETE FROM student_progress WHERE course_id = '$course_id'");
            
            // Finally delete the course
            mysqli_query($con, "DELETE FROM courses WHERE course_id = '$course_id'");
            
            // Commit transaction
            mysqli_commit($con);
            
            $success_message = translate('Course deleted successfully');
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($con);
            $error_message = translate('Error deleting course') . ': ' . $e->getMessage();
        }
    }
}

// Get all courses with category and instructor names
$courses_query = "SELECT c.*, cat.name as category_name, u.name as instructor_name 
                FROM courses c
                LEFT JOIN category cat ON c.category_id = cat.category_id
                LEFT JOIN users u ON c.instructor_id = u.user_id
                ORDER BY c.course_id DESC";
$courses_result = mysqli_query($con, $courses_query);
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-book"></i> <?php echo translate('Manage Courses'); ?></h4>
                <a href="admin_add_course.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> <?php echo translate('Add New Course'); ?>
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($success_message) || (isset($_GET['success']) && $_GET['success'] == 'deleted')): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> 
                    <?php 
                    if (isset($success_message)) {
                        echo $success_message;
                    } else {
                        echo translate('Course deleted successfully');
                        if (isset($_SESSION['unenrolled_students_count']) && $_SESSION['unenrolled_students_count'] > 0) {
                            echo '. ' . translate('Unenrolled') . ' ' . $_SESSION['unenrolled_students_count'] . ' ' . translate('students');
                            unset($_SESSION['unenrolled_students_count']);
                        }
                        if (isset($_SESSION['deleted_course'])) {
                            echo ': ' . htmlspecialchars($_SESSION['deleted_course']['name']);
                            unset($_SESSION['deleted_course']);
                        }
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th><?php echo translate('ID'); ?></th>
                            <th><?php echo translate('Image'); ?></th>
                            <th><?php echo translate('Name'); ?></th>
                            <th><?php echo translate('Category'); ?></th>
                            <th><?php echo translate('Instructor'); ?></th>
                            <th><?php echo translate('Start Date'); ?></th>
                            <th><?php echo translate('Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($courses_result) > 0): ?>
                            <?php while ($course = mysqli_fetch_assoc($courses_result)): ?>
                                <tr>
                                    <td><?php echo $course['course_id']; ?></td>
                                    <td>
                                        <?php if (!empty($course['img'])): ?>
                                            <img src="assets/images/courses/<?php echo $course['img']; ?>" alt="<?php echo htmlspecialchars($course['name']); ?>" width="50" height="50" class="img-thumbnail">
                                        <?php else: ?>
                                            <div class="bg-light text-center" style="width: 50px; height: 50px; line-height: 50px;">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($course['name']); ?></td>
                                    <td><?php echo htmlspecialchars($course['category_name']); ?></td>
                                    <td><?php echo htmlspecialchars($course['instructor_name']); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($course['start_date'])); ?></td>
                                    <td>
                                        <a href="admin_edit_course.php?id=<?php echo $course['course_id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit"></i> <?php echo translate('Edit'); ?>
                                        </a>
                                        <a href="admin_view_course.php?id=<?php echo $course['course_id']; ?>" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i> <?php echo translate('View'); ?>
                                        </a>
                                        <a href="admin_manage_courses.php?action=delete&id=<?php echo $course['course_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('<?php echo translate('Are you sure you want to delete this course?'); ?>');">
                                            <i class="fas fa-trash"></i> <?php echo translate('Delete'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center"><?php echo translate('No courses found'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
