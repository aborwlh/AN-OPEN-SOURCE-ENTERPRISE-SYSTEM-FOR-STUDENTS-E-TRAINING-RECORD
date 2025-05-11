<?php include 'config.php'; ?>

<?php
// Get course info for the page title
$course_query = "SELECT name FROM courses WHERE course_id = '$_GET[course_id]'";
$course_result = mysqli_query($con, $course_query) or die('error: ' . mysqli_error($con));
$course_row = mysqli_fetch_array($course_result);
$page_title = translate("Course Events") . " - " . translate($course_row['name']);
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

// Get current date
$current_date = date('Y-m-d');

// Get upcoming events for this course
$upcoming_events_query = "SELECT * FROM course_events 
                         WHERE course_id = '$course_id' AND date >= '$current_date' 
                         ORDER BY date ASC, time ASC";
$upcoming_events_result = mysqli_query($con, $upcoming_events_query) or die('error: ' . mysqli_error($con));

// Get past events for this course
$past_events_query = "SELECT * FROM course_events 
                      WHERE course_id = '$course_id' AND date < '$current_date' 
                      ORDER BY date DESC, time DESC";
$past_events_result = mysqli_query($con, $past_events_query) or die('error: ' . mysqli_error($con));
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h4><?php echo translate($course_row['name']); ?> - <?php echo translate('Course Events'); ?></h4>
            </div>
        </div>
        <div class="card-body">
            <!-- Upcoming Events Section -->
            <h5 class="mb-3"><?php echo translate('Upcoming Events'); ?></h5>
            <?php if (mysqli_num_rows($upcoming_events_result) > 0): ?>
                <div class="list-group mb-4">
                    <?php while ($event = mysqli_fetch_array($upcoming_events_result)): ?>
                        <div class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1"><?php echo translate($event['title']); ?></h5>
                                <small>
                                    <?php 
                                    $event_date = new DateTime($event['date']);
                                    $current = new DateTime();
                                    $interval = $current->diff($event_date);
                                    if ($interval->days == 0) {
                                        echo '<span class="badge bg-warning">' . translate('Today') . '</span>';
                                    } else if ($interval->days == 1) {
                                        echo '<span class="badge bg-info">' . translate('Tomorrow') . '</span>';
                                    } else if ($interval->days <= 7) {
                                        echo '<span class="badge bg-primary">' . translate('In') . ' ' . $interval->days . ' ' . translate('days') . '</span>';
                                    } else {
                                        echo '<span class="badge bg-secondary">' . translate('In') . ' ' . $interval->days . ' ' . translate('days') . '</span>';
                                    }
                                    ?>
                                </small>
                            </div>
                            <p class="mb-1"><?php echo translate($event['description']); ?></p>
                            <div class="d-flex justify-content-between">
                                <small>
                                    <strong><?php echo translate('Date'); ?>:</strong> <?php echo date('F j, Y', strtotime($event['date'])); ?> | 
                                    <strong><?php echo translate('Time'); ?>:</strong> <?php echo date('g:i A', strtotime($event['time'])); ?> | 
                                    <strong><?php echo translate('Location'); ?>:</strong> <?php echo translate($event['location']); ?>
                                </small>
                                <button class="btn btn-sm btn-outline-primary add-to-calendar" 
                                        data-title="<?php echo htmlspecialchars($event['title']); ?>"
                                        data-date="<?php echo $event['date']; ?>"
                                        data-time="<?php echo $event['time']; ?>"
                                        data-location="<?php echo htmlspecialchars($event['location']); ?>"
                                        data-description="<?php echo htmlspecialchars($event['description']); ?>">
                                    <?php echo translate('Add to Calendar'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info mb-4"><?php echo translate('No upcoming events scheduled for this course.'); ?></div>
            <?php endif; ?>
            
            <br/>

            <!-- Past Events Section -->
            <h5 class="mb-3"><?php echo translate('Past Events'); ?></h5>
            <?php if (mysqli_num_rows($past_events_result) > 0): ?>
                <div class="list-group">
                    <?php while ($event = mysqli_fetch_array($past_events_result)): ?>
                        <div class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1"><?php echo translate($event['title']); ?></h5>
                                <small class="text-muted">
                                    <?php 
                                    $event_date = new DateTime($event['date']);
                                    $current = new DateTime();
                                    $interval = $current->diff($event_date);
                                    echo $interval->days . ' ' . translate('days ago');
                                    ?>
                                </small>
                            </div>
                            <p class="mb-1"><?php echo translate($event['description']); ?></p>
                            <small class="text-muted">
                                <strong><?php echo translate('Date'); ?>:</strong> <?php echo date('F j, Y', strtotime($event['date'])); ?> | 
                                <strong><?php echo translate('Time'); ?>:</strong> <?php echo date('g:i A', strtotime($event['time'])); ?> | 
                                <strong><?php echo translate('Location'); ?>:</strong> <?php echo translate($event['location']); ?>
                            </small>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info"><?php echo translate('No past events for this course.'); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Simple function to handle "Add to Calendar" functionality
document.addEventListener('DOMContentLoaded', function() {
    const calendarButtons = document.querySelectorAll('.add-to-calendar');
    
    calendarButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const title = this.getAttribute('data-title');
            const date = this.getAttribute('data-date');
            const time = this.getAttribute('data-time');
            const location = this.getAttribute('data-location');
            const description = this.getAttribute('data-description');
            
            // Format for Google Calendar
            const startDate = date + 'T' + time;
            const endTime = new Date(date + 'T' + time);
            endTime.setHours(endTime.getHours() + 1);
            const endDateFormatted = endTime.toISOString().slice(0, 16).replace('T', 'T');
            
            const googleCalendarUrl = 'https://calendar.google.com/calendar/render?' +
                'action=TEMPLATE' +
                '&text=' + encodeURIComponent(title) +
                '&dates=' + startDate.replace(/[-:]/g, '') + '/' + endDateFormatted.replace(/[-:]/g, '') +
                '&details=' + encodeURIComponent(description) +
                '&location=' + encodeURIComponent(location) +
                '&sprop=&sprop=name:';
            
            window.open(googleCalendarUrl, '_blank');
        });
    });
});
</script>

<?php include 'footer.php'; ?>
