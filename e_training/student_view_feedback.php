<?php include 'config.php'; ?>

<?php
// Get course info for the page title
$course_query = "SELECT name FROM courses WHERE course_id = '$_GET[course_id]'";
$course_result = mysqli_query($con, $course_query) or die('error: ' . mysqli_error($con));
$course_row = mysqli_fetch_array($course_result);
$page_title = translate("Course Feedback") . " - " . translate($course_row['name']);
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


// Process feedback submission
if (isset($_POST['submit_feedback'])) {
    $rating = $_POST['rating'];
    $comment = mysqli_real_escape_string($con, $_POST['comment']);
    
    // Check if student has already submitted feedback for this course
    $check_feedback = "SELECT * FROM course_feedback 
                      WHERE student_id = '$student_id' AND course_id = '$course_id'";
    $feedback_result = mysqli_query($con, $check_feedback);
    
    if (mysqli_num_rows($feedback_result) > 0) {
        // Update existing feedback
        $update_query = "UPDATE course_feedback 
                        SET rating = '$rating', comment = '$comment', date = CURRENT_TIMESTAMP 
                        WHERE student_id = '$student_id' AND course_id = '$course_id'";
        mysqli_query($con, $update_query) or die('error: ' . mysqli_error($con));
        $success_message = translate("Your feedback has been updated successfully!");
    } else {
        // Insert new feedback
        $insert_query = "INSERT INTO course_feedback (course_id, student_id, rating, comment) 
                        VALUES ('$course_id', '$student_id', '$rating', '$comment')";
        mysqli_query($con, $insert_query) or die('error: ' . mysqli_error($con));
        $success_message = translate("Your feedback has been submitted successfully!");
    }
}

// Get all feedback for this course
$feedback_query = "SELECT cf.*, s.name
                  FROM course_feedback cf
                  JOIN users s ON cf.student_id = s.user_id
                  WHERE cf.course_id = '$course_id' 
                  ORDER BY cf.date DESC";
$feedback_result = mysqli_query($con, $feedback_query) or die('error: ' . mysqli_error($con));

// Get student's current feedback for this course (if exists)
$student_feedback_query = "SELECT * FROM course_feedback 
                         WHERE student_id = '$student_id' AND course_id = '$course_id'";
$student_feedback_result = mysqli_query($con, $student_feedback_query);
$student_feedback = mysqli_fetch_array($student_feedback_result);

// Calculate average rating
$avg_rating_query = "SELECT AVG(rating) as average_rating FROM course_feedback 
                    WHERE course_id = '$course_id'";
$avg_rating_result = mysqli_query($con, $avg_rating_query);
$avg_rating_row = mysqli_fetch_array($avg_rating_result);
$average_rating = round($avg_rating_row['average_rating'], 1);
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h4><?php echo translate($course_row['name']); ?> - <?php echo translate('Course Feedback'); ?></h4>
            </div>
        </div>
        <div class="card-body">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (mysqli_num_rows($enrollment_result) != 0) { ?>
                <!-- Submit Feedback Section -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><?php echo translate('Your Feedback'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="contact" data-aos="fade-up" id="contact">
                            <div class="row justify-content-center" data-aos="fade-up" data-aos-delay="200">
                                <div class="col-xl-9 col-lg-12 mt-4">
                                    <form method="post" role="form" class="php-email-form" action="">
                                        <div class="form-group mt-3">
                                            <label for="rating" class="form-label"><?php echo translate('Rating (1-5)'); ?></label>
                                            <select class="form-control" id="rating" name="rating" required>
                                                <option value=""><?php echo translate('Select Rating'); ?></option>
                                                <option value="5" <?php if(isset($student_feedback) && $student_feedback['rating'] == 5) echo 'selected'; ?>><?php echo translate('5 - Excellent'); ?></option>
                                                <option value="4" <?php if(isset($student_feedback) && $student_feedback['rating'] == 4) echo 'selected'; ?>><?php echo translate('4 - Very Good'); ?></option>
                                                <option value="3" <?php if(isset($student_feedback) && $student_feedback['rating'] == 3) echo 'selected'; ?>><?php echo translate('3 - Good'); ?></option>
                                                <option value="2" <?php if(isset($student_feedback) && $student_feedback['rating'] == 2) echo 'selected'; ?>><?php echo translate('2 - Fair'); ?></option>
                                                <option value="1" <?php if(isset($student_feedback) && $student_feedback['rating'] == 1) echo 'selected'; ?>><?php echo translate('1 - Poor'); ?></option>
                                            </select>
                                        </div>
                                        <div class="form-group mt-3">
                                            <label for="comment" class="form-label"><?php echo translate('Comment'); ?></label>
                                            <textarea class="form-control" id="comment" name="comment" rows="3" required><?php if(isset($student_feedback)) echo $student_feedback['comment']; ?></textarea>
                                        </div>
                                        <button type="submit" name="submit_feedback" class="btn btn-primary">
                                            <?php echo isset($student_feedback) ? translate('Update Feedback') : translate('Submit Feedback'); ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
            
            <!-- Course Rating Summary -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><?php echo translate('Course Rating'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <h1 class="display-4 me-3"><?php echo $average_rating; ?></h1>
                        <div>
                            <div class="stars">
                                <?php
                                // Display stars based on average rating
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $average_rating) {
                                        echo '<span class="text-warning">★</span>';
                                    } else if ($i - 0.5 <= $average_rating) {
                                        echo '<span class="text-warning">★</span>';
                                    } else {
                                        echo '<span class="text-secondary">☆</span>';
                                    }
                                }
                                ?>
                            </div>
                            <p class="mb-0"><?php echo translate('Based on'); ?> <?php echo mysqli_num_rows($feedback_result); ?> <?php echo translate('feedback submissions'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- All Feedback Section -->
            <h5 class="mb-3"><?php echo translate('All Feedback'); ?></h5>
            <?php if (mysqli_num_rows($feedback_result) > 0): ?>
                <div class="list-group">
                    <?php while ($feedback = mysqli_fetch_array($feedback_result)): ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <div>
                                    <h5 class="mb-1">
                                        <?php 
                                        // Show full name only for the current student's feedback
                                        if ($feedback['student_id'] == $student_id) {
                                            echo $feedback['name'] . ' ' . translate('(You)');
                                        } else {
                                            echo translate('Student') . ' ' . substr($feedback['name'], 0, 1) . '.';
                                        }
                                        ?>
                                    </h5>
                                    <div class="mb-2">
                                        <?php
                                        // Display rating as stars
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $feedback['rating']) {
                                                echo '<span class="text-warning">★</span>';
                                            } else {
                                                echo '<span class="text-secondary">☆</span>';
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                                <small class="text-muted">
                                    <?php echo date('M j, Y', strtotime($feedback['date'])); ?>
                                </small>
                            </div>
                            <p class="mb-1"><?php echo nl2br(translate($feedback['comment'])); ?></p>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info"><?php echo translate('No feedback has been submitted for this course yet.'); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
