<?php include 'config.php'; ?>

<?php 
// Get course info for the page title
$course_query = "SELECT name FROM courses WHERE course_id = '$_GET[id]'";
$course_result = mysqli_query($con, $course_query) or die('error: ' . mysqli_error($con));
$course_row = mysqli_fetch_array($course_result);
$page_title = translate("Students Enrolled in") . " [ " . translate($course_row['name']) . " ]";
?>

<?php include 'header.php';?>

<?php
// if user not logged in as instructor; redirect to the index page
if ($_SESSION['user_type'] != "instructor") {
    header("Location: index.php");
}

// Check if the course belongs to this instructor
$instructor_id = $_SESSION['user_id'];
$course_check_query = "SELECT * FROM courses WHERE course_id = '$_GET[id]' AND instructor_id = '$instructor_id'";
$course_check_result = mysqli_query($con, $course_check_query) or die('error: ' . mysqli_error($con));

if (mysqli_num_rows($course_check_result) == 0) {
    echo "<script>alert('" . translate('You do not have permission to access this course') . "');</script>";
    echo "<meta http-equiv='Refresh' content='0; url=instructor_manage_courses.php'>";
    exit;
}
?>

<?php
// Handle student removal if requested
if (isset($_GET['action']) && $_GET['action'] == 'remove' && isset($_GET['enrollment_id'])) {
    $enrollment_id = $_GET['enrollment_id'];
    
    // Get the enrollment details before deleting
    $enrollment_query = "SELECT student_id FROM course_enrollments WHERE enrollment_id = '$enrollment_id'";
    $enrollment_result = mysqli_query($con, $enrollment_query) or die('error: ' . mysqli_error($con));
    $enrollment_data = mysqli_fetch_array($enrollment_result);
    
    // Delete the enrollment
    $delete_query = "DELETE FROM course_enrollments WHERE enrollment_id = '$enrollment_id'";
    
    if (mysqli_query($con, $delete_query)) {
        // Also delete any progress or assignments associated with this enrollment
        $delete_progress_query = "DELETE FROM student_progress WHERE course_id = '$_GET[id]' AND student_id = '{$enrollment_data['student_id']}'";
        mysqli_query($con, $delete_progress_query);
        
        echo "<script>alert('" . translate('Student removed from course successfully') . "');</script>";
    } else {
        echo "<script>alert('" . translate('Error in removing student: ') . mysqli_error($con) . "');</script>";
    }
}

// Get all enrolled students for this course
$students_query = "SELECT ce.enrollment_id, ce.enrollment_date, u.user_id, u.name, u.email, u.mobile 
                  FROM course_enrollments ce 
                  JOIN users u ON ce.student_id = u.user_id 
                  WHERE ce.course_id = '$_GET[id]' 
                  ORDER BY u.name";
$students_result = mysqli_query($con, $students_query) or die('error: ' . mysqli_error($con));

// Count total enrolled students
$count_query = "SELECT COUNT(*) as total FROM course_enrollments WHERE course_id = '$_GET[id]'";
$count_result = mysqli_query($con, $count_query) or die('error: ' . mysqli_error($con));
$count_data = mysqli_fetch_array($count_result);
$total_students = $count_data['total'];
?>

<br/>

<div class="mt-4">
    <p><?php echo translate('Total Enrolled'); ?>: <strong><?php echo $total_students; ?></strong></p><br/>
    
    <table width="100%" align="center" cellpadding=5 cellspacing=5 class="admin-table">
        <tr>
            <th><?php echo translate('Student Name'); ?></th>
            <th><?php echo translate('Email'); ?></th>
            <th><?php echo translate('Mobile'); ?></th>
            <th><?php echo translate('Enrollment Date'); ?></th>
            <th><?php echo translate('Actions'); ?></th>
        </tr>
        <?php if (mysqli_num_rows($students_result) > 0) { ?>
            <?php while ($student = mysqli_fetch_array($students_result)) { ?>
                <tr>
                    <td><?php echo $student['name']; ?></td>
                    <td><?php echo $student['email']; ?></td>
                    <td><?php echo $student['mobile']; ?></td>
                    <td><?php echo $student['enrollment_date']; ?></td>
                    <td>
                        <a href="instructor_view_student_progress.php?course_id=<?php echo $_GET['id']; ?>&student_id=<?php echo $student['user_id']; ?>"><?php echo translate('View Progress'); ?></a> | 
                        <a href="instructor_view_course_students.php?id=<?php echo $_GET['id']; ?>&action=remove&enrollment_id=<?php echo $student['enrollment_id']; ?>" 
                           onclick="return confirm('<?php echo translate('Are you sure you want to remove this student from the course? This will also delete their progress and assignments.'); ?>');"><?php echo translate('Remove'); ?></a>
                    </td>
                </tr>
            <?php } ?>
        <?php } else { ?>
            <tr>
                <td colspan="5" align="center"><?php echo translate('No students enrolled in this course yet.'); ?></td>
            </tr>
        <?php } ?>
    </table>
</div>

<?php include 'footer.php';?>