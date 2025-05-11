<?php
/**
 * Simple function to send an email
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $message Email message
 * @param string $from Sender email
 * @return bool True if email was sent successfully, false otherwise
 */
function sendEmail($to, $subject, $message, $from = 'noreply@etraining.com') {
    // Headers
    $headers = "From: $from\r\n";
    $headers .= "Reply-To: $from\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    
    // For HTML email
    if (strpos($message, '<html>') !== false) {
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    }
    
    // Send email
    return mail($to, $subject, $message, $headers);
}

/**
 * Send password reset email
 * 
 * @param string $email Recipient email
 * @param string $token Reset token
 * @return bool True if email was sent successfully, false otherwise
 */
function sendPasswordResetEmail($email, $token) {
    // Generate reset link
    $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/e_training/reset_password.php?token=" . $token;
    
    // Email subject
    $subject = "Password Reset Request";
    
    // HTML message
    $html_message = '
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #04639b; color: white; padding: 10px; text-align: center; }
            .content { padding: 20px; background-color: #f9f9f9; }
            .button { display: inline-block; padding: 10px 20px; background-color: #04639b; color: white; 
                      text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { font-size: 12px; color: #777; margin-top: 20px; text-align: center; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>Password Reset Request</h2>
            </div>
            <div class="content">
                <p>Hello,</p>
                <p>You have requested to reset your password. Please click the button below to reset your password:</p>
                <p style="text-align: center;">
                    <a href="' . $reset_link . '" class="button">Reset Password</a>
                </p>
                <p>Alternatively, you can copy and paste the following link into your browser:</p>
                <p>' . $reset_link . '</p>
                <p>This link will expire in 1 hour.</p>
                <p>If you did not request a password reset, please ignore this email.</p>
                <p>Regards,<br>E-Training System</p>
            </div>
            <div class="footer">
                <p>This is an automated email. Please do not reply to this message.</p>
            </div>
        </div>
    </body>
    </html>';
    
    // Send the email
    return sendEmail($email, $subject, $html_message);
}
