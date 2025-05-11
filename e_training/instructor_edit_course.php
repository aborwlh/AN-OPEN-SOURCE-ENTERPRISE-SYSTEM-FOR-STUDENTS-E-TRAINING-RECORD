<?php include 'config.php'; ?>

<?php $page_title = translate("Update Course");?>

<?php include 'header.php';?>

<?php
// if user not logged in as instructor; redirect to the index page
if ($_SESSION['user_type'] != "instructor") {
    header("Location: index.php");
}

// Get all categories for the dropdown menu
$categories_query = "SELECT * FROM category ORDER BY name ASC";
$categories_result = mysqli_query($con, $categories_query) or die('error: ' . mysqli_error($con));

if (isset($_POST['btn-submit'])) {
    // Escape user input to prevent SQL injection
    $name = mysqli_real_escape_string($con, $_POST['name']);
    $description = mysqli_real_escape_string($con, $_POST['description']);
    $prerequisites = mysqli_real_escape_string($con, $_POST['prerequisites']);
    $start_date = mysqli_real_escape_string($con, $_POST['start_date']);
    $category_id = mysqli_real_escape_string($con, $_POST['category_id']);
    $course_id = mysqli_real_escape_string($con, $_GET['id']);
    
    // Get current course info for image handling
    $current_course_query = "SELECT img, name FROM courses WHERE course_id = '$course_id'";
    $current_course_result = mysqli_query($con, $current_course_query) or die('error: ' . mysqli_error($con));
    $current_course = mysqli_fetch_array($current_course_result);
    $img = $current_course['img'];
    $old_course_name = $current_course['name'];
    
    // Handle image upload if a new image is provided
    if(isset($_FILES['course_image']) && $_FILES['course_image']['error'] == 0) {
        $allowed = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png");
        $filename = $_FILES["course_image"]["name"];
        $filetype = $_FILES["course_image"]["type"];
        $filesize = $_FILES["course_image"]["size"];
        
        // Verify file extension
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if(!array_key_exists($ext, $allowed)) {
            echo "<script>alert('" . translate('Error: Please select a valid file format.') . "');</script>";
        } else {
            // Verify file size - 5MB maximum
            $maxsize = 5 * 1024 * 1024;
            if($filesize > $maxsize) {
                echo "<script>alert('" . translate('Error: File size is larger than the allowed limit.') . "');</script>";
            } else {
                // Delete the old image if it exists
                if(!empty($current_course['img']) && file_exists("assets/images/courses/" . $current_course['img'])) {
                    unlink("assets/images/courses/" . $current_course['img']);
                }
                
                // Rename the file to avoid conflicts
                $new_filename = uniqid() . '.' . $ext;
                $destination = "assets/images/courses/" . $new_filename;
                
                if(move_uploaded_file($_FILES["course_image"]["tmp_name"], $destination)) {
                    $img = $new_filename;
                } else {
                    echo "<script>alert('" . translate('Error: There was a problem uploading your file. Please try again.') . "');</script>";
                }
            }
        }
    }
    
    $update_query = "UPDATE courses SET 
                    name = '$name', 
                    description = '$description', 
                    img = '$img', 
                    prerequisites = '$prerequisites', 
                    start_date = '$start_date', 
                    category_id = '$category_id' 
                    WHERE course_id = '$course_id'";
    
    if (mysqli_query($con, $update_query)) {
        // Create notification for enrolled students
        $notification_content = translate("The course") . " '$name' " . translate("has been updated.");
        $escaped_notification = mysqli_real_escape_string($con, $notification_content);
        
        // Get all enrolled students
        $students_query = "SELECT u.user_id, u.email, u.receive_notification 
                          FROM users u 
                          JOIN course_enrollments ce ON u.user_id = ce.student_id 
                          WHERE ce.course_id = '$course_id'";
        $students_result = mysqli_query($con, $students_query) or die('error: ' . mysqli_error($con));
        
        // Add notification for each enrolled student
        while ($student = mysqli_fetch_array($students_result)) {
            $insert_notification = "INSERT INTO notifications (user_id, content) 
                                  VALUES ('$student[user_id]', '$escaped_notification')";
            mysqli_query($con, $insert_notification) or die('error: ' . mysqli_error($con));
            
            // Send email if student has notifications enabled
            if (isset($student['receive_notification']) && $student['receive_notification'] == 'Yes') {
                // Include the EmailSender class if it exists
                if (file_exists('includes/EmailSender.php')) {
                    require_once 'includes/EmailSender.php';
                    $emailSender = new EmailSender();
                    
                    // Send email notification
                    $subject = translate("Course Updated");
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
                                <p>" . translate("Please log in to your account to view the updated course details.") . "</p>
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
        
        echo "<script>alert('" . translate('Course Updated Successfully') . "');</script>";
        echo "<meta http-equiv='Refresh' content='0; url=instructor_manage_courses.php'>";
    } else {
        echo "<script>alert('" . translate('Error in updating course: ') . mysqli_error($con) . "');</script>";
    }
}
?>

<?php
// Get the course details
$course_id = mysqli_real_escape_string($con, $_GET['id']);
$query = "SELECT * FROM courses WHERE course_id = '$course_id'";
$course_result = mysqli_query($con, $query) or die("can't run query because " . mysqli_error($con));

$course_row = mysqli_fetch_array($course_result);

// Ensure the course belongs to this instructor
if ($course_row['instructor_id'] != $_SESSION['user_id']) {
    echo "<script>alert('" . translate('You do not have permission to edit this course') . "');</script>";
    echo "<meta http-equiv='Refresh' content='0; url=instructor_manage_courses.php'>";
    exit;
}

if (mysqli_num_rows($course_result) == 1) { ?>
<div class="contact" data-aos="fade-up">
    <form method="post" role="form" class="php-email-form" enctype="multipart/form-data">
        <div class="form-group mt-3">
            <?php echo translate('Course Name'); ?> 
            <input type="text" class="form-control" name="name" required value="<?php echo htmlspecialchars($course_row['name']);?>" />
        </div>
        <div class="form-group mt-3">
            <?php echo translate('Description'); ?> 
            <textarea class="form-control" name="description" rows="5" required><?php echo htmlspecialchars($course_row['description']);?></textarea>
        </div>
        <div class="form-group mt-3">
            <?php echo translate('Prerequisites'); ?> 
            <input type="text" class="form-control" name="prerequisites" value="<?php echo htmlspecialchars($course_row['prerequisites']);?>" />
        </div>
        <div class="form-group mt-3">
            <?php echo translate('Start Date'); ?> 
            <input type="date" class="form-control" name="start_date" required value="<?php echo $course_row['start_date'];?>" />
        </div>
        <div class="form-group mt-3">
            <?php echo translate('Category'); ?>
            <select name="category_id" class="form-control" required>
                <?php 
                mysqli_data_seek($categories_result, 0); // Reset the result pointer
                while($category = mysqli_fetch_array($categories_result)) { ?>
                    <option value="<?php echo $category['category_id']; ?>" <?php if($category['category_id'] == $course_row['category_id']) echo "selected"; ?>>
                        <?php echo translate(htmlspecialchars($category['name'])); ?>
                    </option>
                <?php } ?>
            </select>
        </div>
        <div class="form-group mt-3">
            <?php echo translate('Course Image'); ?> 
            <?php if(!empty($course_row['img'])) { ?>
                <p><?php echo translate('Current Image'); ?>: <img src="assets/images/courses/<?php echo htmlspecialchars($course_row['img']); ?>" width="100" /></p>
            <?php } ?>
            <input type="file" class="form-control" name="course_image" />
            <small><?php echo translate('Leave empty to keep current image'); ?></small>
        </div>
        <div class="text-center">
            <center><button type="submit" name="btn-submit"><?php echo translate('Update Course'); ?></button></center>
        </div>
    </form>
</div>
<?php
} else {
    echo "<script>alert('" . translate('Course not found') . "');</script>";
    echo "<meta http-equiv='Refresh' content='0; url=instructor_manage_courses.php'>";
}
?>

<?php include 'footer.php'; ?>