<?php include 'config.php'; ?>

<?php 
// Get course info for the page title
$course_query = "SELECT name FROM courses WHERE course_id = '$_GET[id]'";
$course_result = mysqli_query($con, $course_query) or die('error: ' . mysqli_error($con));
$course_row = mysqli_fetch_array($course_result);
$page_title = translate("Schedules for") . " [ " . $course_row['name'] . " ]";
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
// Handle form submission for adding/updating schedule
if (isset($_POST['btn-submit'])) {
    $course_id = $_GET['id'];
    $day = $_POST['day'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $location = $_POST['location'];
    
    // Get course name for notification
    $course_query = "SELECT name FROM courses WHERE course_id = '$course_id'";
    $course_result = mysqli_query($con, $course_query) or die('error: ' . mysqli_error($con));
    $course_row = mysqli_fetch_array($course_result);
    $course_name = $course_row['name'];
    $escaped_course_name = mysqli_real_escape_string($con, $course_name);
    
    if (isset($_POST['schedule_id']) && !empty($_POST['schedule_id'])) {
        // Update existing schedule
        $schedule_id = $_POST['schedule_id'];
        $update_query = "UPDATE schedules SET day = '$day', start_time = '$start_time', end_time = '$end_time', location = '$location' 
                        WHERE schedule_id = '$schedule_id'";
        
        if (mysqli_query($con, $update_query)) {
            // Create notification content
            $notification_content = translate("The schedule for course") . " '$escaped_course_name' " . translate("on") . " $day " . translate("has been updated.");
            
            // Get all enrolled students for this course
            $students_query = "SELECT u.user_id, u.email, u.receive_notification 
                              FROM users u 
                              JOIN course_enrollments ce ON u.user_id = ce.student_id 
                              WHERE ce.course_id = '$course_id'";
            $students_result = mysqli_query($con, $students_query) or die('error: ' . mysqli_error($con));
            
            // Add notification for each enrolled student and send email if they have notifications enabled
            while ($student = mysqli_fetch_array($students_result)) {
                // Insert notification into database
                $escaped_notification = mysqli_real_escape_string($con, $notification_content);
                $insert_notification = "INSERT INTO notifications (user_id, content) 
                                      VALUES ('$student[user_id]', '$escaped_notification')";
                mysqli_query($con, $insert_notification) or die('error: ' . mysqli_error($con));
                
                // Send email if student has notifications enabled
                if ($student['receive_notification'] == 'Yes') {
                    // Include the EmailSender class
                    require_once 'includes/EmailSender.php';
                    $emailSender = new EmailSender();
                    
                    // Send email notification
                    $subject = translate("Course Schedule Updated");
                    $message = "
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                            .header { background-color: #04639b; color: white; padding: 10px; text-align: center; }
                            .content { padding: 20px; background-color: #f9f9f9; }
                            .footer { font-size: 12px; color: #777; margin-top: 20px; text-align: center; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h2>" . translate("E-Training System Notification") . "</h2>
                            </div>
                            <div class='content'>
                                <p>" . translate("Hello") . ",</p>
                                <p>$notification_content</p>
                                <p>" . translate("New Schedule Details") . ":</p>
                                <p>" . translate("Day") . ": $day<br>
                                " . translate("Time") . ": " . date('h:i A', strtotime($start_time)) . " - " . date('h:i A', strtotime($end_time)) . "<br>
                                " . translate("Location") . ": $location</p>
                                <p>" . translate("Regards") . ",<br>" . translate("E-Training System") . "</p>
                            </div>
                            <div class='footer'>
                                <p>" . translate("This is an automated email. Please do not reply to this message.") . "</p>
                            </div>
                        </div>
                    </body>
                    </html>";
                    
                    $emailSender->mail->clearAllRecipients();
                    $emailSender->mail->addAddress($student['email']);
                    $emailSender->mail->Subject = $subject;
                    $emailSender->mail->isHTML(true);
                    $emailSender->mail->Body = $message;
                    $emailSender->mail->AltBody = strip_tags($notification_content);
                    
                    try {
                        $emailSender->mail->send();
                    } catch (Exception $e) {
                        // Log the error but continue with the process
                        error_log('Email sending failed: ' . $emailSender->mail->ErrorInfo);
                    }
                }
            }
            
            echo "<script>alert('" . translate('Schedule Updated Successfully') . "');</script>";
        } else {
            echo "<script>alert('" . translate('Error in updating schedule: ') . mysqli_error($con) . "');</script>";
        }
    } else {
        // Add new schedule
        $insert_query = "INSERT INTO schedules (course_id, day, start_time, end_time, location) 
                       VALUES ('$course_id', '$day', '$start_time', '$end_time', '$location')";
        
        if (mysqli_query($con, $insert_query)) {
            // Create notification content
            $notification_content = translate("A new schedule for course") . " '$escaped_course_name' " . translate("has been added on") . " $day.";
            
            // Get all enrolled students for this course
            $students_query = "SELECT u.user_id, u.email, u.receive_notification 
                              FROM users u 
                              JOIN course_enrollments ce ON u.user_id = ce.student_id 
                              WHERE ce.course_id = '$course_id'";
            $students_result = mysqli_query($con, $students_query) or die('error: ' . mysqli_error($con));
            
            // Add notification for each enrolled student and send email if they have notifications enabled
            while ($student = mysqli_fetch_array($students_result)) {
                // Insert notification into database
                $escaped_notification = mysqli_real_escape_string($con, $notification_content);
                $insert_notification = "INSERT INTO notifications (user_id, content) 
                                      VALUES ('$student[user_id]', '$escaped_notification')";
                mysqli_query($con, $insert_notification) or die('error: ' . mysqli_error($con));
                
                // Send email if student has notifications enabled
                if ($student['receive_notification'] == 'Yes') {
                    // Include the EmailSender class
                    require_once 'includes/EmailSender.php';
                    $emailSender = new EmailSender();
                    
                    // Send email notification
                    $subject = translate("New Course Schedule Added");
                    $message = "
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                            .header { background-color: #04639b; color: white; padding: 10px; text-align: center; }
                            .content { padding: 20px; background-color: #f9f9f9; }
                            .footer { font-size: 12px; color: #777; margin-top: 20px; text-align: center; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h2>" . translate("E-Training System Notification") . "</h2>
                            </div>
                            <div class='content'>
                                <p>" . translate("Hello") . ",</p>
                                <p>$notification_content</p>
                                <p>" . translate("Schedule Details") . ":</p>
                                <p>" . translate("Day") . ": $day<br>
                                " . translate("Time") . ": " . date('h:i A', strtotime($start_time)) . " - " . date('h:i A', strtotime($end_time)) . "<br>
                                " . translate("Location") . ": $location</p>
                                <p>" . translate("Regards") . ",<br>" . translate("E-Training System") . "</p>
                            </div>
                            <div class='footer'>
                                <p>" . translate("This is an automated email. Please do not reply to this message.") . "</p>
                            </div>
                        </div>
                    </body>
                    </html>";
                    
                    $emailSender->mail->clearAllRecipients();
                    $emailSender->mail->addAddress($student['email']);
                    $emailSender->mail->Subject = $subject;
                    $emailSender->mail->isHTML(true);
                    $emailSender->mail->Body = $message;
                    $emailSender->mail->AltBody = strip_tags($notification_content);
                    
                    try {
                        $emailSender->mail->send();
                    } catch (Exception $e) {
                        // Log the error but continue with the process
                        error_log('Email sending failed: ' . $emailSender->mail->ErrorInfo);
                    }
                }
            }
            
            echo "<script>alert('" . translate('Schedule Added Successfully') . "');</script>";
        } else {
            echo "<script>alert('" . translate('Error in adding schedule: ') . mysqli_error($con) . "');</script>";
        }
    }
}

// Handle schedule deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['schedule_id'])) {
    $schedule_id = $_GET['schedule_id'];
    $delete_query = "DELETE FROM schedules WHERE schedule_id = '$schedule_id'";
    
    if (mysqli_query($con, $delete_query)) {
        echo "<script>alert('" . translate('Schedule deleted successfully') . "');</script>";
    } else {
        echo "<script>alert('" . translate('Error in deleting schedule: ') . mysqli_error($con) . "');</script>";
    }
}

// Get all schedules for this course
$schedules_query = "SELECT * FROM schedules WHERE course_id = '$_GET[id]' ORDER BY FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), start_time";
$schedules_result = mysqli_query($con, $schedules_query) or die('error: ' . mysqli_error($con));

// Handle edit request
$editing = false;
$edit_schedule = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['schedule_id'])) {
    $editing = true;
    $schedule_id = $_GET['schedule_id'];
    $edit_query = "SELECT * FROM schedules WHERE schedule_id = '$schedule_id'";
    $edit_result = mysqli_query($con, $edit_query) or die('error: ' . mysqli_error($con));
    $edit_schedule = mysqli_fetch_array($edit_result);
}
?>

<div class="contact" data-aos="fade-up">
    <h3><?php echo translate('Add New Schedule'); ?></h3>
    <form method="post" role="form" class="php-email-form">
        <?php if ($editing && $edit_schedule) { ?>
            <input type="hidden" name="schedule_id" value="<?php echo $edit_schedule['schedule_id']; ?>" />
        <?php } ?>
        
        <div class="form-group mt-3">
            <?php echo translate('Day'); ?>
            <select name="day" class="form-control" required>
                <option value="Saturday" <?php if ($editing && $edit_schedule['day'] == 'Saturday') echo 'selected'; ?>><?php echo translate('Saturday'); ?></option>
                <option value="Sunday" <?php if ($editing && $edit_schedule['day'] == 'Sunday') echo 'selected'; ?>><?php echo translate('Sunday'); ?></option>
                <option value="Monday" <?php if ($editing && $edit_schedule['day'] == 'Monday') echo 'selected'; ?>><?php echo translate('Monday'); ?></option>
                <option value="Tuesday" <?php if ($editing && $edit_schedule['day'] == 'Tuesday') echo 'selected'; ?>><?php echo translate('Tuesday'); ?></option>
                <option value="Wednesday" <?php if ($editing && $edit_schedule['day'] == 'Wednesday') echo 'selected'; ?>><?php echo translate('Wednesday'); ?></option>
                <option value="Thursday" <?php if ($editing && $edit_schedule['day'] == 'Thursday') echo 'selected'; ?>><?php echo translate('Thursday'); ?></option>
                <option value="Friday" <?php if ($editing && $edit_schedule['day'] == 'Friday') echo 'selected'; ?>><?php echo translate('Friday'); ?></option>
            </select>
        </div>
        <div class="form-group mt-3">
            <?php echo translate('Start Time'); ?>
            <input type="time" class="form-control" name="start_time" required 
                value="<?php echo $editing ? $edit_schedule['start_time'] : ''; ?>" />
        </div>
        <div class="form-group mt-3">
            <?php echo translate('End Time'); ?>
            <input type="time" class="form-control" name="end_time" required 
                value="<?php echo $editing ? $edit_schedule['end_time'] : ''; ?>" />
        </div>
        <div class="form-group mt-3">
            <?php echo translate('Location'); ?>
            <input type="text" class="form-control" name="location" required 
                value="<?php echo $editing ? $edit_schedule['location'] : ''; ?>" />
        </div>
        <div class="text-center">
            <center><button type="submit" name="btn-submit"><?php echo $editing ? translate('Update Schedule') : translate('Add Schedule'); ?></button></center>
        </div>
    </form>
</div>

<div class="mt-4">
    <h3><?php echo translate('Course Schedules'); ?></h3>
    <table width="100%" align="center" cellpadding=5 cellspacing=5 class="admin-table">
        <tr>
            <th><?php echo translate('Day'); ?></th>
            <th><?php echo translate('Start Time'); ?></th>
            <th><?php echo translate('End Time'); ?></th>
            <th><?php echo translate('Location'); ?></th>
            <th><?php echo translate('Actions'); ?></th>
        </tr>
        <?php if (mysqli_num_rows($schedules_result) > 0) { ?>
            <?php while ($schedule = mysqli_fetch_array($schedules_result)) { ?>
                <tr>
                    <td><?php echo translate($schedule['day']); ?></td>
                    <td><?php echo date('h:i A', strtotime($schedule['start_time'])); ?></td>
                    <td><?php echo date('h:i A', strtotime($schedule['end_time'])); ?></td>
                    <td><?php echo $schedule['location']; ?></td>
                    <td>
                        <a href="instructor_manage_course_schedules.php?id=<?php echo $_GET['id']; ?>&action=edit&schedule_id=<?php echo $schedule['schedule_id']; ?>"><?php echo translate('Edit'); ?></a> | 
                        <a href="instructor_manage_course_schedules.php?id=<?php echo $_GET['id']; ?>&action=delete&schedule_id=<?php echo $schedule['schedule_id']; ?>" 
                           onclick="return confirm('<?php echo translate('Are you sure you want to delete this schedule?'); ?>');"><?php echo translate('Delete'); ?></a>
                    </td>
                </tr>
            <?php } ?>
        <?php } else { ?>
            <tr>
                <td colspan="5" align="center"><?php echo translate('No schedules found. Please add a schedule.'); ?></td>
            </tr>
        <?php } ?>
    </table>
</div>

<?php include 'footer.php';?>