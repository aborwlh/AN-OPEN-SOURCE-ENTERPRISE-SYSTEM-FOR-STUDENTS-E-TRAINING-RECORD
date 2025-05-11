<?php include 'config.php'; ?>

<?php
// Get course info for the page title
$course_query = "SELECT name FROM courses WHERE course_id = '$_GET[course_id]'";
$course_result = mysqli_query($con, $course_query) or die('error: ' . mysqli_error($con));
$course_row = mysqli_fetch_array($course_result);
$page_title = translate("Course Schedule") . " - " . $course_row['name'];
include 'header.php';

// Check if user is logged in as student
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != "student") {
    header("Location: index.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$course_id = $_GET['course_id'];

// Check if student is enrolled in this course or if the course is public
$enrollment_check = "SELECT * FROM course_enrollments 
                    WHERE student_id = '$student_id' AND course_id = '$course_id'";
$enrollment_result = mysqli_query($con, $enrollment_check);

// Get course details
$course_details_query = "SELECT * FROM courses WHERE course_id = '$course_id'";
$course_details_result = mysqli_query($con, $course_details_query);
$course_details = mysqli_fetch_array($course_details_result);

// Check if the user is enrolled or if they're just viewing the schedule from available courses
$is_enrolled = (mysqli_num_rows($enrollment_result) > 0);

// Get weekly schedule for this course
$schedule_query = "SELECT * FROM schedules 
                  WHERE course_id = '$course_id' 
                  ORDER BY FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
$schedule_result = mysqli_query($con, $schedule_query) or die('error: ' . mysqli_error($con));

// Array to store schedule by day
$schedule_by_day = array(
    translate('Monday') => array(),
    translate('Tuesday') => array(),
    translate('Wednesday') => array(),
    translate('Thursday') => array(),
    translate('Friday') => array(),
    translate('Saturday') => array(),
    translate('Sunday') => array()
);

// Populate the schedule by day - Note: We need to use the English day names for the database lookup
$day_mapping = array(
    'Monday' => translate('Monday'),
    'Tuesday' => translate('Tuesday'),
    'Wednesday' => translate('Wednesday'),
    'Thursday' => translate('Thursday'),
    'Friday' => translate('Friday'),
    'Saturday' => translate('Saturday'),
    'Sunday' => translate('Sunday')
);

// Populate the schedule by day
while ($schedule = mysqli_fetch_array($schedule_result)) {
    $translated_day = $day_mapping[$schedule['day']];
    $schedule_by_day[$translated_day][] = $schedule;
}
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h4><?php echo translate($course_row['name']); ?> - <?php echo translate('Course Schedule'); ?></h4>
                
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header"><?php echo translate('Course Information'); ?></div>
                        <div class="card-body">
                            <p><strong><?php echo translate('Course'); ?>:</strong> <?php echo translate($course_details['name']); ?></p>
                            <p><strong><?php echo translate('Start Date'); ?>:</strong> <?php echo date('F j, Y', strtotime($course_details['start_date'])); ?></p>
                            <p><strong><?php echo translate('Prerequisites'); ?>:</strong> <?php echo translate($course_details['prerequisites']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered admin-table">
                    <thead class="table-dark">
                        <tr>
                            <th><?php echo translate('Day'); ?></th>
                            <th><?php echo translate('Time'); ?></th>
                            <th><?php echo translate('Location'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $has_schedule = false;
                        foreach ($schedule_by_day as $day => $sessions) {
                            if (empty($sessions)) {
                                continue;
                            }
                            
                            $has_schedule = true;
                            foreach ($sessions as $index => $session) {
                                echo '<tr>';
                                if ($index === 0) {
                                    echo '<td rowspan="' . count($sessions) . '" class="align-middle font-weight-bold">' . $day . '</td>';
                                }
                                echo '<td>' . date('g:i A', strtotime($session['start_time'])) . ' - ' . date('g:i A', strtotime($session['end_time'])) . '</td>';
                                echo '<td>' . $session['location'] . '</td>';
                                echo '</tr>';
                            }
                        }
                        
                        if (!$has_schedule) {
                            echo '<tr><td colspan="3" class="text-center">' . translate('No schedule has been set for this course yet.') . '</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
