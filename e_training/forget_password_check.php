<?php
include 'config.php';

// Check if form is submitted
if (isset($_POST['btn-submit'])) {
    // Get form data
    $email = mysqli_real_escape_string($con, $_POST['email']);
    
    // Validate form data
    if (empty($email)) {
        header("Location: forget_password.php?error=empty");
        exit();
    }
    
    // Verify reCAPTCHA
    $recaptcha_secret = "6Lfh0xQrAAAAANmWG6Fe6962SbDRYsrBeIhw1Nkf";
    $recaptcha_response = isset($_POST['g-recaptcha-response']) ? $_POST['g-recaptcha-response'] : '';
    
    // Skip reCAPTCHA verification during development/testing
    // Remove or comment this block in production
    $skip_captcha = false; // Set to false in production
    if (!$skip_captcha) {
        // Make request to Google to verify captcha
        $verify_response = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$recaptcha_secret.'&response='.$recaptcha_response);
        $response_data = json_decode($verify_response);
        
        // Check if reCAPTCHA verification was successful
        if (!$response_data->success) {
            header("Location: forget_password.php?error=captcha");
            exit();
        }
    }
    
    // Check if email exists in the database
    $check_query = "SELECT * FROM users WHERE email = '$email'";
    $check_result = mysqli_query($con, $check_query);
    
    if (mysqli_num_rows($check_result) == 0) {
        header("Location: forget_password.php?error=email_not_found");
        exit();
    }
    
    // Generate a unique reset token
    $token = bin2hex(random_bytes(32));
    
    // Set expiration time to 1 hour from now
    // Use the correct date format for MySQL DATETIME
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Debug: Check the expiration time
    error_log("Token created at: " . date('Y-m-d H:i:s'));
    error_log("Token expires at: " . $expires);
    
    // Store the token in the database
    // Check if the table exists
    $table_check = mysqli_query($con, "SHOW TABLES LIKE 'password_reset_tokens'");
    if (mysqli_num_rows($table_check) == 0) {
        // Create the table if it doesn't exist
        $create_table = "CREATE TABLE password_reset_tokens (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            token VARCHAR(255) NOT NULL,
            expires DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        mysqli_query($con, $create_table);
    }
    
    // Delete any existing tokens for this email
    $delete_query = "DELETE FROM password_reset_tokens WHERE email = '$email'";
    mysqli_query($con, $delete_query);
    
    // Insert the new token
    $insert_query = "INSERT INTO password_reset_tokens (email, token, expires) VALUES ('$email', '$token', '$expires')";
    $insert_result = mysqli_query($con, $insert_query);
    
    if (!$insert_result) {
        error_log("Failed to insert token: " . mysqli_error($con));
        header("Location: forget_password.php?error=db_error");
        exit();
    }
    
    // Verify the token was inserted correctly
    $verify_query = "SELECT * FROM password_reset_tokens WHERE email = '$email' AND token = '$token'";
    $verify_result = mysqli_query($con, $verify_query);
    
    if (mysqli_num_rows($verify_result) > 0) {
        $token_data = mysqli_fetch_assoc($verify_result);
        error_log("Token verified in database. Expires: " . $token_data['expires']);
    } else {
        error_log("Token not found in database after insertion!");
    }
    
    if ($insert_result) {
        $email_sent = false;
        $error_message = "";
        
        // Option 1: Use PHPMailer (if you've set it up)
        if (file_exists('includes/EmailSender.php')) {
            require_once 'includes/EmailSender.php';
            try {
                $emailSender = new EmailSender();
                if ($emailSender->sendPasswordResetEmail($email, $token)) {
                    $email_sent = true;
                    header("Location: forget_password.php?success=1");
                    exit();
                } else {
                    $error_message = "PHPMailer failed: " . (isset($emailSender->mail->ErrorInfo) ? $emailSender->mail->ErrorInfo : "Unknown error");
                }
            } catch (Exception $e) {
                // Log the error
                $error_message = "PHPMailer exception: " . $e->getMessage();
                error_log('Password reset email failed: ' . $e->getMessage());
                // Continue to Option 2
            }
        }
        
        // Option 2: Use PHP's mail() function as fallback if PHPMailer failed
        if (!$email_sent) {
            // Use the exact URL format specified by the user
            $reset_link = "https://oceanlearn.ct.ws/Home/e_training/reset_password.php?token=" . $token;
            
            // HTML email content
            $html_message = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Password Reset Request</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; }
        .container { padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .header { background-color: #04639b; color: white; padding: 15px; text-align: center; }
        .button { display: inline-block; padding: 10px 20px; background-color: #04639b; color: white; text-decoration: none; border-radius: 5px; margin: 15px 0; }
        .footer { margin-top: 20px; font-size: 12px; color: #777; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>Password Reset Request</h2>
        </div>
        <p>Hello,</p>
        <p>You have requested to reset your password. Please click the button below to reset your password:</p>
        <p style='text-align: center;'><a href='{$reset_link}' class='button'>Reset Password</a></p>
        <p>Alternatively, you can copy and paste the following link into your browser:</p>
        <p>{$reset_link}</p>
        <p>This link will expire in 1 hour.</p>
        <p>If you did not request a password reset, please ignore this email.</p>
        <div class='footer'>
            <p>Regards,<br>E-Training System</p>
        </div>
    </div>
</body>
</html>
";
            
            // Plain text version
            $text_message = "Hello1,\n\n";
            $text_message .= "You have requested to reset your password. Please click the link below to reset your password:\n\n";
            $text_message .= $reset_link . "\n\n";
            $text_message .= "This link will expire in 1 hour.\n\n";
            $text_message .= "If you did not request a password reset, please ignore this email.\n\n";
            $text_message .= "Regards,\nE-Training System";
            
            $subject = "Password Reset Request";
            
            // Set up email headers for HTML email
            $headers = "From: noreply@etraining.com\r\n";
            $headers .= "Reply-To: noreply@etraining.com\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: multipart/alternative; boundary=\"boundary\"\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();
            
            // Create the email body with both text and HTML versions
            $body = "--boundary\r\n";
            $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $body .= $text_message . "\r\n\r\n";
            $body .= "--boundary\r\n";
            $body .= "Content-Type: text/html; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $body .= $html_message . "\r\n\r\n";
            $body .= "--boundary--";
            
            if (mail($email, $subject, $body, $headers)) {
                $email_sent = true;
                header("Location: forget_password.php?success=1");
                exit();
            } else {
                $error_message .= " PHP mail() also failed.";
            }
        }
        
        // If both methods failed, show a message with the reset link and error details
        if (!$email_sent) {
            echo "<div style='max-width: 600px; margin: 50px auto; padding: 20px; background-color: #f9f9f9; border: 1px solid #ddd; border-radius: 5px;'>";
            echo "<h2>Password Reset Link</h2>";
            echo "<p style='color: red;'>Email sending failed: " . htmlspecialchars($error_message) . "</p>";
            echo "<p>Here is your password reset link:</p>";
            echo "<p><a href='" . htmlspecialchars($reset_link) . "'>" . htmlspecialchars($reset_link) . "</a></p>";
            echo "<p>This link will expire in 1 hour.</p>";
            echo "<p><a href='login.php'>Back to Login</a></p>";
            echo "</div>";
            exit();
        }
    } else {
        header("Location: forget_password.php?error=db_error");
        exit();
    }
} else {
    // If the form was not submitted, redirect to forget password page
    header("Location: forget_password.php");
    exit();
}
?>
