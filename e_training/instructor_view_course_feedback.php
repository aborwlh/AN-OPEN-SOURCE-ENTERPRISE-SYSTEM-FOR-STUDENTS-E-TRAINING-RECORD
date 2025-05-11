<?php include 'config.php'; ?>

<?php 
// Get course info for the page title
$course_query = "SELECT name FROM courses WHERE course_id = '$_GET[id]'";
$course_result = mysqli_query($con, $course_query) or die('error: ' . mysqli_error($con));
$course_row = mysqli_fetch_array($course_result);
$page_title = translate("Feedback for") . " [ " . $course_row['name'] . " ]";
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

// Get all feedback for this course
$feedback_query = "SELECT cf.*, u.name as student_name 
                  FROM course_feedback cf 
                  JOIN users u ON cf.student_id = u.user_id 
                  WHERE cf.course_id = '$_GET[id]' 
                  ORDER BY cf.date DESC";
$feedback_result = mysqli_query($con, $feedback_query) or die('error: ' . mysqli_error($con));
?>

<br/>

<div class="mt-4">
    
    <?php if (mysqli_num_rows($feedback_result) > 0) { ?>
        <div class="accordion" id="feedbackAccordion">
            <?php $counter = 1; while ($feedback = mysqli_fetch_array($feedback_result)) { ?>
                <div class="card mb-3">
                    <div class="card-header" id="heading<?php echo $counter; ?>">
                        <h4>
                            <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapse<?php echo $counter; ?>">
                                <?php echo translate('Feedback from'); ?> <?php echo $feedback['student_name']; ?> - <?php echo date('Y-m-d', strtotime($feedback['date'])); ?>
                            </button>
                        </h4>
                    </div>
                    
                    <div id="collapse<?php echo $counter; ?>" class="collapse show" aria-labelledby="heading<?php echo $counter; ?>">
                        <div class="card-body">
                            <h5><?php echo translate('Rating'); ?>: <?php echo $feedback['rating']; ?>/5</h5>
                            <h5><?php echo translate('Comment'); ?>:</h5>
                            <p><?php echo nl2br($feedback['comment']); ?></p>
                        </div>
                    </div>
                </div>
                <br/>
            <?php $counter++; } ?>
        </div>
    <?php } else { ?>
        <p><?php echo translate('No feedback received for this course yet.'); ?></p>
    <?php } ?>
</div>

<?php include 'footer.php';?>