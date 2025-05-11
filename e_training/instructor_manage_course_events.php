<?php include 'config.php'; ?>

<?php 
// Get course info for the page title
$course_query = "SELECT name FROM courses WHERE course_id = '$_GET[id]'";
$course_result = mysqli_query($con, $course_query) or die('error: ' . mysqli_error($con));
$course_row = mysqli_fetch_array($course_result);
$page_title = translate("Events for") . " [ " . $course_row['name'] . " ]";
?>

<?php include 'header.php';?>

<?php
// if user not logged in as instructor; redirect to the index page
if ($_SESSION['user_type'] != "instructor") {
    header("Location: index.php");
}

// Check if the course belongs to this instructor
$instructor_id = $_SESSION['user_id'];
$course_id = mysqli_real_escape_string($con, $_GET['id']);
$course_check_query = "SELECT * FROM courses WHERE course_id = '$course_id' AND instructor_id = '$instructor_id'";
$course_check_result = mysqli_query($con, $course_check_query) or die('error: ' . mysqli_error($con));

if (mysqli_num_rows($course_check_result) == 0) {
    echo "<script>alert('" . translate('You do not have permission to access this course') . "');</script>";
    echo "<meta http-equiv='Refresh' content='0; url=instructor_manage_courses.php'>";
    exit;
}

// Get course name for notifications
$course_name = mysqli_real_escape_string($con, $course_row['name']);

// Handle form submission for adding/updating event
if (isset($_POST['btn-submit'])) {
    $title = mysqli_real_escape_string($con, $_POST['title']);
    $description = mysqli_real_escape_string($con, $_POST['description']);
    $event_date = mysqli_real_escape_string($con, $_POST['event_date']);
    $event_time = mysqli_real_escape_string($con, $_POST['event_time']);
    $location = mysqli_real_escape_string($con, $_POST['location']);
    $event_type = isset($_POST['event_type']) ? mysqli_real_escape_string($con, $_POST['event_type']) : '';
    
    if (isset($_POST['event_id']) && !empty($_POST['event_id'])) {
        // Update existing event
        $event_id = mysqli_real_escape_string($con, $_POST['event_id']);
        $update_query = "UPDATE course_events SET 
                        title = '$title', 
                        description = '$description', 
                        date = '$event_date', 
                        time = '$event_time', 
                        location = '$location'";
        
        // Only add event_type if it exists in the form and table
        if (!empty($event_type)) {
            // Check if event_type column exists
            $check_column_query = "SHOW COLUMNS FROM course_events LIKE 'event_type'";
            $check_column_result = mysqli_query($con, $check_column_query);
            if (mysqli_num_rows($check_column_result) > 0) {
                $update_query .= ", event_type = '$event_type'";
            }
        }
        
        $update_query .= " WHERE event_id = '$event_id'";
        
        if (mysqli_query($con, $update_query)) {
            // Create notification content
            $notification_content = translate("The event") . " '$title' " . translate("for course") . " '$course_name' " . translate("has been updated.");
            $escaped_notification = mysqli_real_escape_string($con, $notification_content);
            
            // Get all enrolled students for this course
            $students_query = "SELECT u.user_id, u.email, u.receive_notification 
                              FROM users u 
                              JOIN course_enrollments ce ON u.user_id = ce.student_id 
                              WHERE ce.course_id = '$course_id'";
            $students_result = mysqli_query($con, $students_query) or die('error: ' . mysqli_error($con));
            
            // Add notification for each enrolled student and send email if they have notifications enabled
            while ($student = mysqli_fetch_array($students_result)) {
                // Insert notification into database
                $insert_notification = "INSERT INTO notifications (user_id, content) 
                                      VALUES ('$student[user_id]', '$escaped_notification')";
                mysqli_query($con, $insert_notification) or die('error: ' . mysqli_error($con));
                
                // Send email if student has notifications enabled
                if (isset($student['receive_notification']) && $student['receive_notification'] == 'Yes') {
                    // Include the EmailSender class
                    if (file_exists('includes/EmailSender.php')) {
                        require_once 'includes/EmailSender.php';
                        $emailSender = new EmailSender();
                        
                        // Send email notification
                        $subject = translate("Course Event Updated");
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
                                    <p>" . translate("Event Details") . ":</p>
                                    <p>" . translate("Title") . ": $title<br>
                                    " . translate("Date") . ": " . date('F j, Y', strtotime($event_date)) . "<br>
                                    " . translate("Time") . ": " . date('h:i A', strtotime($event_time)) . "<br>
                                    " . translate("Location") . ": $location</p>
                                    <p>" . translate("Description") . ": $description</p>
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
            }
            
            echo "<script>alert('" . translate('Event Updated Successfully') . "');</script>";
        } else {
            echo "<script>alert('" . translate('Error in updating event: ') . mysqli_error($con) . "');</script>";
        }
    } else {
        // Add new event
        $insert_query = "INSERT INTO course_events (course_id, title, description, date, time, location";
        
        // Only add event_type if it exists in the form and table
        if (!empty($event_type)) {
            // Check if event_type column exists
            $check_column_query = "SHOW COLUMNS FROM course_events LIKE 'event_type'";
            $check_column_result = mysqli_query($con, $check_column_query);
            if (mysqli_num_rows($check_column_result) > 0) {
                $insert_query .= ", event_type";
            }
        }
        
        $insert_query .= ") VALUES ('$course_id', '$title', '$description', '$event_date', '$event_time', '$location'";
        
        // Only add event_type value if it exists in the form and table
        if (!empty($event_type)) {
            // Check if event_type column exists
            $check_column_query = "SHOW COLUMNS FROM course_events LIKE 'event_type'";
            $check_column_result = mysqli_query($con, $check_column_query);
            if (mysqli_num_rows($check_column_result) > 0) {
                $insert_query .= ", '$event_type'";
            }
        }
        
        $insert_query .= ")";
        
        if (mysqli_query($con, $insert_query)) {
            // Create notification content
            $notification_content = translate("A new event") . " '$title' " . translate("has been added for course") . " '$course_name'.";
            $escaped_notification = mysqli_real_escape_string($con, $notification_content);
            
            // Get all enrolled students for this course
            $students_query = "SELECT u.user_id, u.email, u.receive_notification 
                              FROM users u 
                              JOIN course_enrollments ce ON u.user_id = ce.student_id 
                              WHERE ce.course_id = '$course_id'";
            $students_result = mysqli_query($con, $students_query) or die('error: ' . mysqli_error($con));
            
            // Add notification for each enrolled student and send email if they have notifications enabled
            while ($student = mysqli_fetch_array($students_result)) {
                // Insert notification into database
                $insert_notification = "INSERT INTO notifications (user_id, content) 
                                      VALUES ('$student[user_id]', '$escaped_notification')";
                mysqli_query($con, $insert_notification) or die('error: ' . mysqli_error($con));
                
                // Send email if student has notifications enabled
                if (isset($student['receive_notification']) && $student['receive_notification'] == 'Yes') {
                    // Include the EmailSender class
                    if (file_exists('includes/EmailSender.php')) {
                        require_once 'includes/EmailSender.php';
                        $emailSender = new EmailSender();
                        
                        // Send email notification
                        $subject = translate("New Course Event Added");
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
                                    <p>" . translate("Event Details") . ":</p>
                                    <p>" . translate("Title") . ": $title<br>
                                    " . translate("Date") . ": " . date('F j, Y', strtotime($event_date)) . "<br>
                                    " . translate("Time") . ": " . date('h:i A', strtotime($event_time)) . "<br>
                                    " . translate("Location") . ": $location</p>
                                    <p>" . translate("Description") . ": $description</p>
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
            }
            
            echo "<script>alert('" . translate('Event Added Successfully') . "');</script>";
        } else {
            echo "<script>alert('" . translate('Error in adding event: ') . mysqli_error($con) . "');</script>";
        }
    }
}

// Handle event deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['event_id'])) {
    $event_id = mysqli_real_escape_string($con, $_GET['event_id']);
    
    // Get event details before deleting
    $event_query = "SELECT title FROM course_events WHERE event_id = '$event_id'";
    $event_result = mysqli_query($con, $event_query) or die('error: ' . mysqli_error($con));
    $event = mysqli_fetch_array($event_result);
    $event_title = $event['title'];
    
    $delete_query = "DELETE FROM course_events WHERE event_id = '$event_id'";
    
    if (mysqli_query($con, $delete_query)) {
        // Create notification content
        $notification_content = translate("The event") . " '$event_title' " . translate("for course") . " '$course_name' " . translate("has been cancelled.");
        $escaped_notification = mysqli_real_escape_string($con, $notification_content);
        
        // Get all enrolled students for this course
        $students_query = "SELECT u.user_id, u.email, u.receive_notification 
                          FROM users u 
                          JOIN course_enrollments ce ON u.user_id = ce.student_id 
                          WHERE ce.course_id = '$course_id'";
        $students_result = mysqli_query($con, $students_query) or die('error: ' . mysqli_error($con));
        
        // Add notification for each enrolled student
        while ($student = mysqli_fetch_array($students_result)) {
            // Insert notification into database
            $insert_notification = "INSERT INTO notifications (user_id, content) 
                                  VALUES ('$student[user_id]', '$escaped_notification')";
            mysqli_query($con, $insert_notification) or die('error: ' . mysqli_error($con));
            
            // Send email if student has notifications enabled
            if (isset($student['receive_notification']) && $student['receive_notification'] == 'Yes') {
                // Include the EmailSender class
                if (file_exists('includes/EmailSender.php')) {
                    require_once 'includes/EmailSender.php';
                    $emailSender = new EmailSender();
                    
                    // Send email notification
                    $subject = translate("Course Event Cancelled");
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
        }
        
        echo "<script>alert('" . translate('Event deleted successfully') . "');</script>";
    } else {
        echo "<script>alert('" . translate('Error in deleting event: ') . mysqli_error($con) . "');</script>";
    }
}

// Get all events for this course
$events_query = "SELECT * FROM course_events WHERE course_id = '$course_id' ORDER BY date DESC, time ASC";
$events_result = mysqli_query($con, $events_query) or die('error: ' . mysqli_error($con));

// Handle edit request
$editing = false;
$edit_event = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['event_id'])) {
    $editing = true;
    $event_id = mysqli_real_escape_string($con, $_GET['event_id']);
    $edit_query = "SELECT * FROM course_events WHERE event_id = '$event_id'";
    $edit_result = mysqli_query($con, $edit_query) or die('error: ' . mysqli_error($con));
    $edit_event = mysqli_fetch_array($edit_result);
}

// Check if event_type column exists
$has_event_type = false;
$check_column_query = "SHOW COLUMNS FROM course_events LIKE 'event_type'";
$check_column_result = mysqli_query($con, $check_column_query);
$has_event_type = mysqli_num_rows($check_column_result) > 0;
?>

<div class="contact" data-aos="fade-up">
    <h3><?php echo $editing ? translate('Edit Event') : translate('Add New Event'); ?></h3>
    <form method="post" role="form" class="php-email-form">
        <?php if ($editing && $edit_event) { ?>
            <input type="hidden" name="event_id" value="<?php echo $edit_event['event_id']; ?>" />
        <?php } ?>
        
        <div class="form-group mt-3">
            <?php echo translate('Title'); ?>
            <input type="text" class="form-control" name="title" required 
                value="<?php echo $editing ? htmlspecialchars($edit_event['title']) : ''; ?>" />
        </div>
        <div class="form-group mt-3">
            <?php echo translate('Description'); ?>
            <textarea class="form-control" name="description" rows="5" required><?php echo $editing ? htmlspecialchars($edit_event['description']) : ''; ?></textarea>
        </div>
        <div class="form-group mt-3">
            <?php echo translate('Event Date'); ?>
            <input type="date" class="form-control" name="event_date" required 
                value="<?php echo $editing ? $edit_event['date'] : ''; ?>" />
        </div>
        <div class="form-group mt-3">
            <?php echo translate('Event Time'); ?>
            <input type="time" class="form-control" name="event_time" required 
                value="<?php echo $editing ? $edit_event['time'] : ''; ?>" />
        </div>
        <div class="form-group mt-3">
            <?php echo translate('Location'); ?>
            <input type="text" class="form-control" name="location" required 
                value="<?php echo $editing ? htmlspecialchars($edit_event['location']) : ''; ?>" />
        </div>
        
        <?php if ($has_event_type) { ?>
        <div class="form-group mt-3">
            <?php echo translate('Event Type'); ?>
            <select name="event_type" class="form-control" required>
                <option value="Lecture" <?php if ($editing && isset($edit_event['event_type']) && $edit_event['event_type'] == 'Lecture') echo 'selected'; ?>><?php echo translate('Lecture'); ?></option>
                <option value="Workshop" <?php if ($editing && isset($edit_event['event_type']) && $edit_event['event_type'] == 'Workshop') echo 'selected'; ?>><?php echo translate('Workshop'); ?></option>
                <option value="Exam" <?php if ($editing && isset($edit_event['event_type']) && $edit_event['event_type'] == 'Exam') echo 'selected'; ?>><?php echo translate('Exam'); ?></option>
                <option value="Quiz" <?php if ($editing && isset($edit_event['event_type']) && $edit_event['event_type'] == 'Quiz') echo 'selected'; ?>><?php echo translate('Quiz'); ?></option>
                <option value="Assignment" <?php if ($editing && isset($edit_event['event_type']) && $edit_event['event_type'] == 'Assignment') echo 'selected'; ?>><?php echo translate('Assignment'); ?></option>
                <option value="Other" <?php if ($editing && isset($edit_event['event_type']) && $edit_event['event_type'] == 'Other') echo 'selected'; ?>><?php echo translate('Other'); ?></option>
            </select>
        </div>
        <?php } ?>
        
        <div class="text-center">
            <center><button type="submit" name="btn-submit"><?php echo $editing ? translate('Update Event') : translate('Add Event'); ?></button></center>
        </div>
    </form>
</div>

<div class="mt-4">
    <h3><?php echo translate('Course Events'); ?></h3>
    <table width="100%" align="center" cellpadding=5 cellspacing=5 class="admin-table">
        <tr>
            <th><?php echo translate('Title'); ?></th>
            <th><?php echo translate('Date'); ?></th>
            <th><?php echo translate('Time'); ?></th>
            <th><?php echo translate('Location'); ?></th>
            <?php if ($has_event_type) { ?>
            <th><?php echo translate('Type'); ?></th>
            <?php } ?>
            <th><?php echo translate('Actions'); ?></th>
        </tr>
        <?php if (mysqli_num_rows($events_result) > 0) { ?>
            <?php while ($event = mysqli_fetch_array($events_result)) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($event['title']); ?></td>
                    <td><?php echo date('F j, Y', strtotime($event['date'])); ?></td>
                    <td><?php echo date('h:i A', strtotime($event['time'])); ?></td>
                    <td><?php echo htmlspecialchars($event['location']); ?></td>
                    <?php if ($has_event_type) { ?>
                    <td><?php echo isset($event['event_type']) ? translate($event['event_type']) : ''; ?></td>
                    <?php } ?>
                    <td>
                        <a href="instructor_manage_course_events.php?id=<?php echo $course_id; ?>&action=edit&event_id=<?php echo $event['event_id']; ?>"><?php echo translate('Edit'); ?></a> | 
                        <a href="instructor_manage_course_events.php?id=<?php echo $course_id; ?>&action=delete&event_id=<?php echo $event['event_id']; ?>" 
                           onclick="return confirm('<?php echo translate('Are you sure you want to delete this event?'); ?>');"><?php echo translate('Delete'); ?></a>
                    </td>
                </tr>
            <?php } ?>
        <?php } else { ?>
            <tr>
                <td colspan="<?php echo $has_event_type ? '6' : '5'; ?>" align="center"><?php echo translate('No events found. Please add an event.'); ?></td>
            </tr>
        <?php } ?>
    </table>
</div>

<?php include 'footer.php';?>