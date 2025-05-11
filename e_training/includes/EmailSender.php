<?php
// Manual include of PHPMailer classes
require_once __DIR__ . '/../vendor/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/src/SMTP.php';

// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailSender {
    // Change the mail property from private to public so we can access it for debugging
    public $mail;
    
    public function __construct() {
        // Create a new PHPMailer instance
        $this->mail = new PHPMailer(true);
        
        // Configure PHPMailer
        $this->mail->isSMTP();                                      // Send using SMTP
        $this->mail->Host       = 'smtp.gmail.com';                 // SMTP server (change to your provider)
        $this->mail->SMTPAuth   = true;                             // Enable SMTP authentication
        $this->mail->Username   = 'oceanproject097@gmail.com';           // SMTP username
        $this->mail->Password   = '';              // SMTP password (app password for Gmail)
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;   // Enable TLS encryption
        $this->mail->Port       = 587;                              // TCP port to connect to

        // In the constructor, add debugging settings
        $this->mail->SMTPDebug = 0;  // Set to 2 for detailed debugging output
        $this->mail->Debugoutput = 'html';
        
        // Set default sender
        $this->mail->setFrom('oceanproject097@gmail.com', 'E-Training System');
        $this->mail->addReplyTo('oceanproject097@gmail.com', 'E-Training System');
        
        // Set default charset and encoding
        $this->mail->CharSet = 'UTF-8';
        $this->mail->Encoding = 'base64';
    }
    
    /**
     * Send password reset email
     * 
     * @param string $email Recipient email
     * @param string $token Reset token
     * @return bool True if email was sent successfully, false otherwise
     */
    public function sendPasswordResetEmail($email, $token) {
        try {
            // Clear all recipients and attachments
            $this->mail->clearAllRecipients();
            $this->mail->clearAttachments();
            
            // Add recipient
            $this->mail->addAddress($email);
            
            // Set email subject
            $this->mail->Subject = 'Password Reset Request';
            
            // Generate reset link
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/Home/e_training/reset_password.php?token=" . $token;
            
            // Set email body (HTML)
            $this->mail->isHTML(true);
            $this->mail->Body = '
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
            
            // Set plain text alternative
            $this->mail->AltBody = "Hello,\n\nYou have requested to reset your password. Please click the link below to reset your password:\n\n" .
                                  $reset_link . "\n\n" .
                                  "This link will expire in 1 hour.\n\n" .
                                  "If you did not request a password reset, please ignore this email.\n\n" .
                                  "Regards,\nE-Training System";
            
            // Send the email
            return $this->mail->send();
        } catch (Exception $e) {
            // Log the error
            error_log('Email sending failed: ' . $this->mail->ErrorInfo);
            return false;
        }
    }
}
