<?php include 'config.php'; ?>

<?php $page_title = translate("Add Course");?>

<?php include 'header.php';?>

<?php
// if user not logged in as instructor; redirect to the index page
if ($_SESSION['user_type'] != "instructor") {
    header("Location: index.php");
}
?>

<?php
// Get all categories for the dropdown menu
$categories_query = "SELECT * FROM category ORDER BY name ASC";
$categories_result = mysqli_query($con, $categories_query) or die('error: ' . mysqli_error($con));

if (isset($_POST['btn-submit'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $prerequisites = $_POST['prerequisites'];
    $start_date = $_POST['start_date'];
    $category_id = $_POST['category_id'];
    $instructor_id = $_SESSION['user_id'];
    
    // Handle image upload
    $img = "";
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
    
    // Check if a course with the same name already exists for this instructor
    $check_query = mysqli_query($con, "SELECT * FROM courses WHERE name = '$name' AND instructor_id = '$instructor_id'") 
                  or die('error ' . mysqli_error($con));
    
    if (mysqli_num_rows($check_query) != 0) {
        echo "<h3 style='text-align: center; padding-bottom: 10px; border-bottom: 1px solid #d9db5c'>" . translate('You already have a course with this name') . "</h3>";
    } else {
        $insert_query = "INSERT INTO courses (name, description, img, prerequisites, start_date, instructor_id, category_id) 
                       VALUES ('$name', '$description', '$img', '$prerequisites', '$start_date', '$instructor_id', '$category_id')";
        
        if (mysqli_query($con, $insert_query)) {
            $course_id = mysqli_insert_id($con);
            
            // Get course name for notification
            $course_name = $name;
            
            // Get all enrolled students for this course
            $students_query = "SELECT u.user_id, u.email, u.receive_notification 
                              FROM users u 
                              WHERE u.role = 'student'";
            $students_result = mysqli_query($con, $students_query) or die('error: ' . mysqli_error($con));
            
            // Create notification content
            $notification_content = translate("A new course") . " '$course_name' " . translate("has been added.");
            $notification_content = mysqli_real_escape_string($con, $notification_content);
            
            // Add notification for each student and send email if they have notifications enabled
            while ($student = mysqli_fetch_array($students_result)) {
                // Insert notification into database
                $insert_notification = "INSERT INTO notifications (user_id, content) 
                                      VALUES ('$student[user_id]', '$notification_content')";
                mysqli_query($con, $insert_notification) or die('error: ' . mysqli_error($con));
                
                // Send email if student has notifications enabled
                if ($student['receive_notification'] == 'Yes') {
                    // Include the EmailSender class
                    require_once 'includes/EmailSender.php';
                    $emailSender = new EmailSender();
                    
                    // Send email notification
                    $subject = translate("New Course Added");
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
                                <p>" . translate("You can view this course on the platform.") . "</p>
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
            
            echo "<script>alert('" . translate('Course Added Successfully') . "');</script>";
            echo "<meta http-equiv='Refresh' content='0; url=instructor_manage_courses.php'>";
        } else {
            echo "<script>alert('" . translate('Error in adding course: ') . mysqli_error($con) . "');</script>";
        }
    }
}
?>

<div class="contact" data-aos="fade-up">
    <form method="post" role="form" class="php-email-form" enctype="multipart/form-data">
        <div class="form-group mt-3">
            <?php echo translate('Course Name'); ?> <input type="text" class="form-control" name="name" placeholder="<?php echo translate('Course Name'); ?>" required />
        </div>
        <div class="form-group mt-3">
            <?php echo translate('Description'); ?> <textarea class="form-control" name="description" rows="5" placeholder="<?php echo translate('Course Description'); ?>" required></textarea>
        </div>
        <div class="form-group mt-3">
            <?php echo translate('Prerequisites'); ?> <input type="text" class="form-control" name="prerequisites" placeholder="<?php echo translate('Prerequisites'); ?>" />
        </div>
        <div class="form-group mt-3">
            <?php echo translate('Start Date'); ?> <input type="date" class="form-control" name="start_date" required />
        </div>
        <div class="form-group mt-3">
            <?php echo translate('Category'); ?>
            <select name="category_id" class="form-control" required>
                <option value=""><?php echo translate('Select Category'); ?></option>
                <?php while($category = mysqli_fetch_array($categories_result)) { ?>
                    <option value="<?php echo $category['category_id']; ?>"><?php echo translate($category['name']); ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="form-group mt-3">
            <?php echo translate('Course Image'); ?> <input type="file" class="form-control" name="course_image" accept="image/*" />
        </div>
        <div class="text-center">
            <center><button type="submit" name="btn-submit"><?php echo translate('Add Course'); ?></button></center>
        </div>
    </form>
</div>

<?php include 'footer.php';?>