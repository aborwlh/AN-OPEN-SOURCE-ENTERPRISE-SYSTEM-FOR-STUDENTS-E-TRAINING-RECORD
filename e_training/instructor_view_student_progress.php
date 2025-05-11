<?php include 'config.php'; ?>

<?php 
// Get student and course info for the page title
$student_query = "SELECT u.name as student_name, c.name as course_name 
                 FROM users u, courses c 
                 WHERE u.user_id = '$_GET[student_id]' AND c.course_id = '$_GET[course_id]'";
$student_result = mysqli_query($con, $student_query) or die('error: ' . mysqli_error($con));
$student_row = mysqli_fetch_array($student_result);
$page_title = translate("Progress for") . " [ " . $student_row['student_name'] . " ] " . translate("in") . " [ " . $student_row['course_name'] . " ]";
?>

<?php include 'header.php';?>

<?php
// if user not logged in as instructor; redirect to the index page
if ($_SESSION['user_type'] != "instructor") {
    header("Location: index.php");
}

// Check if the course belongs to this instructor
$instructor_id = $_SESSION['user_id'];
$course_check_query = "SELECT * FROM courses WHERE course_id = '$_GET[course_id]' AND instructor_id = '$instructor_id'";
$course_check_result = mysqli_query($con, $course_check_query) or die('error: ' . mysqli_error($con));

if (mysqli_num_rows($course_check_result) == 0) {
    echo "<script>alert('" . translate('You do not have permission to access this course') . "');</script>";
    echo "<meta http-equiv='Refresh' content='0; url=instructor_manage_courses.php'>";
    exit;
}

// Check if the student is enrolled in this course
$enrollment_check_query = "SELECT * FROM course_enrollments 
                          WHERE course_id = '$_GET[course_id]' AND student_id = '$_GET[student_id]'";
$enrollment_check_result = mysqli_query($con, $enrollment_check_query) or die('error: ' . mysqli_error($con));

if (mysqli_num_rows($enrollment_check_result) == 0) {
    echo "<script>alert('" . translate('This student is not enrolled in this course') . "');</script>";
    echo "<meta http-equiv='Refresh' content='0; url=instructor_view_course_students.php?id=" . $_GET['course_id'] . "'>";
    exit;
}
?>

<?php
// Get current progress
$progress_query = "SELECT * FROM student_progress 
                  WHERE course_id = '$_GET[course_id]' AND student_id = '$_GET[student_id]'";
$progress_result = mysqli_query($con, $progress_query) or die('error: ' . mysqli_error($con));

$progress_value = 0;
$progress_notes = '';
$progress_date = '';
$progress_exists = false;

if (mysqli_num_rows($progress_result) > 0) {
    $progress_data = mysqli_fetch_array($progress_result);
    $progress_value = $progress_data['value'];
    $progress_notes = $progress_data['notes'];
    $progress_date = $progress_data['date'];
    $progress_exists = true;
}

// Handle form submission for updating progress
if (isset($_POST['btn-update'])) {
    $new_progress = $_POST['progress_value'];
    $new_notes = $_POST['notes'];
    
    // Validate progress value
    if ($new_progress < 0) $new_progress = 0;
    if ($new_progress > 100) $new_progress = 100;
    
    if ($progress_exists) {
        // Update existing progress
        $update_query = "UPDATE student_progress 
                        SET value = '$new_progress', notes = '$new_notes', date = NOW() 
                        WHERE course_id = '$_GET[course_id]' AND student_id = '$_GET[student_id]'";
        
        if (mysqli_query($con, $update_query)) {
            echo "<script>alert('" . translate('Progress updated successfully') . "');</script>";
            // Update the displayed values
            $progress_value = $new_progress;
            $progress_notes = $new_notes;
            $progress_date = date('Y-m-d H:i:s');
        } else {
            echo "<script>alert('" . translate('Error in updating progress: ') . mysqli_error($con) . "');</script>";
        }
    } else {
        // Insert new progress record
        $insert_query = "INSERT INTO student_progress (course_id, student_id, value, notes, date) 
                        VALUES ('$_GET[course_id]', '$_GET[student_id]', '$new_progress', '$new_notes', NOW())";
        
        if (mysqli_query($con, $insert_query)) {
            echo "<script>alert('" . translate('Progress saved successfully') . "');</script>";
            // Update the displayed values
            $progress_value = $new_progress;
            $progress_notes = $new_notes;
            $progress_date = date('Y-m-d H:i:s');
            $progress_exists = true;
        } else {
            echo "<script>alert('" . translate('Error in saving progress: ') . mysqli_error($con) . "');</script>";
        }
    }
}
?>

<div class="contact" data-aos="fade-up">
    <div class="row">
        <div class="col-md-6">
            <?php if ($progress_exists) { ?>
                <p><strong><?php echo translate('Last Updated'); ?>:</strong> <?php echo $progress_date; ?></p>
            <?php } ?>
        </div>
        <div class="col-md-6">
            <div class="progress mt-3">
                <div class="progress-bar" role="progressbar" style="width: <?php echo $progress_value; ?>%;" 
                     aria-valuenow="<?php echo $progress_value; ?>" aria-valuemin="0" aria-valuemax="100">
                    <?php echo $progress_value; ?> %
                </div>
            </div>
        </div>
    </div>
    <br/>
    
    <form method="post" role="form" class="php-email-form mt-4">
        <div class="form-group mt-3">
            <?php echo translate('Progress Value (0-100)'); ?>
            <input type="number" class="form-control" name="progress_value" min="0" max="100" required 
                   value="<?php echo $progress_value; ?>" <?php if ($progress_value == 100) echo 'disabled'; ?> />
        </div>
        <div class="form-group mt-3">
            <?php echo translate('Notes'); ?>
            <textarea class="form-control" name="notes" rows="5"><?php echo $progress_notes; ?></textarea>
        </div>
        <div class="text-center">
            <center>
                <button type="submit" name="btn-update" <?php if ($progress_value == 100) echo 'disabled'; ?>>
                    <?php echo translate('Update Progress'); ?>
                </button>
            </center>
        </div>
    </form>
</div>

<?php include 'footer.php';?>