<?php
// Email testing script with more options
session_start();

// Only allow access to this page if logged in as admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != "admin") {
    echo "<div style='text-align:center; margin-top:50px;'>";
    echo "<h2>Access Denied</h2>";
    echo "<p>You must be logged in as an administrator to access this page.</p>";
    echo "<p><a href='login.php'>Login</a></p>";
    echo "</div>";
    exit;
}

// Process form submission
$result = '';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_type = $_POST['test_type'] ?? '';
    $to_email = $_POST['to_email'] ?? '';
    $smtp_host = $_POST['smtp_host'] ?? 'smtp.gmail.com';
    $smtp_port = $_POST['smtp_port'] ?? '587';
    $smtp_user = $_POST['smtp_user'] ?? '';
    $smtp_pass = $_POST['smtp_pass'] ?? '';
    $smtp_secure = $_POST['smtp_secure'] ?? 'tls';
    
    if (empty($to_email)) {
        $error = "Please enter a recipient email address";
    } else {
        if ($test_type === 'php_mail') {
            // Test PHP mail() function
            $subject = "Test Email from E-Training System";
            $message = "This is a test email sent using PHP mail() function at " . date('Y-m-d H:i:s');
            $headers = "From: noreply@etraining.com\r\n";
            $headers .= "Reply-To: noreply@etraining.com\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();
            
            if (mail($to_email, $subject, $message, $headers)) {
                $success = "Email sent successfully using PHP mail() function!";
            } else {
                $error = "Failed to send email using PHP mail() function.";
            }
        } else if ($test_type === 'phpmailer') {
            // Test PHPMailer
            if (file_exists('includes/EmailSender.php')) {
                try {
                    // Manual include of PHPMailer classes
                    require_once __DIR__ . '/vendor/phpmailer/src/Exception.php';
                    require_once __DIR__ . '/vendor/phpmailer/src/PHPMailer.php';
                    require_once __DIR__ . '/vendor/phpmailer/src/SMTP.php';
                    
                    // Import PHPMailer classes
                    use PHPMailer\PHPMailer\PHPMailer;
                    use PHPMailer\PHPMailer\SMTP;
                    use PHPMailer\PHPMailer\Exception;
                    
                    // Create a new PHPMailer instance
                    $mail = new PHPMailer(true);
                    
                    // Enable verbose debug output
                    $mail->SMTPDebug = 2; // 0 = off, 1 = client messages, 2 = client and server messages
                    $mail->Debugoutput = function($str, $level) use (&$result) {
                        $result .= htmlspecialchars($str) . "<br>";
                    };
                    
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host = $smtp_host;
                    $mail->SMTPAuth = true;
                    $mail->Username = $smtp_user;
                    $mail->Password = $smtp_pass;
                    $mail->SMTPSecure = $smtp_secure === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port = $smtp_port;
                    
                    // Recipients
                    $mail->setFrom($smtp_user, 'E-Training System');
                    $mail->addAddress($to_email);
                    
                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'PHPMailer Test from E-Training';
                    $mail->Body = 'This is a test email sent using PHPMailer at ' . date('Y-m-d H:i:s');
                    $mail->AltBody = 'This is a test email sent using PHPMailer.';
                    
                    // Send the email
                    if ($mail->send()) {
                        $success = "Email sent successfully using PHPMailer!";
                    } else {
                        $error = "Failed to send email using PHPMailer: " . $mail->ErrorInfo;
                    }
                } catch (Exception $e) {
                    $error = "Error: " . $e->getMessage();
                }
            } else {
                $error = "PHPMailer files not found.";
            }
        } else if ($test_type === 'reset_email') {
            // Test password reset email
            try {
                require_once 'includes/EmailSender.php';
                $emailSender = new EmailSender();
                
                // Override SMTP settings if provided
                if (!empty($smtp_host)) $emailSender->mail->Host = $smtp_host;
                if (!empty($smtp_port)) $emailSender->mail->Port = $smtp_port;
                if (!empty($smtp_user)) $emailSender->mail->Username = $smtp_user;
                if (!empty($smtp_pass)) $emailSender->mail->Password = $smtp_pass;
                if (!empty($smtp_secure)) {
                    $emailSender->mail->SMTPSecure = $smtp_secure === 'tls' ? 
                        PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
                }
                
                // Enable debugging
                $emailSender->mail->SMTPDebug = 2;
                $emailSender->mail->Debugoutput = function($str, $level) use (&$result) {
                    $result .= htmlspecialchars($str) . "<br>";
                };
                
                // Generate a test token
                $token = bin2hex(random_bytes(16));
                
                if ($emailSender->sendPasswordResetEmail($to_email, $token)) {
                    $success = "Password reset email sent successfully!";
                } else {
                    $error = "Failed to send password reset email: " . $emailSender->mail->ErrorInfo;
                }
            } catch (Exception $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Testing Tool</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #04639b;
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn {
            display: inline-block;
            background-color: #04639b;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background-color: #034f7d;
        }
        .error {
            color: #dc3545;
            background-color: #f8d7da;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .success {
            color: #28a745;
            background-color: #d4edda;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .result {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
            overflow-x: auto;
            font-family: monospace;
            font-size: 14px;
        }
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        .tab {
            padding: 10px 15px;
            cursor: pointer;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-bottom: none;
            border-radius: 4px 4px 0 0;
            margin-right: 5px;
        }
        .tab.active {
            background-color: #fff;
            border-bottom: 1px solid #fff;
            margin-bottom: -1px;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Email Testing Tool</h1>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="tabs">
            <div class="tab active" data-tab="php-mail">PHP mail()</div>
            <div class="tab" data-tab="phpmailer">PHPMailer</div>
            <div class="tab" data-tab="reset-email">Reset Email</div>
        </div>
        
        <div class="tab-content active" id="php-mail">
            <form method="post" action="">
                <input type="hidden" name="test_type" value="php_mail">
                
                <div class="form-group">
                    <label for="to_email_php">Recipient Email:</label>
                    <input type="email" id="to_email_php" name="to_email" required>
                </div>
                
                <button type="submit" class="btn">Send Test Email</button>
            </form>
        </div>
        
        <div class="tab-content" id="phpmailer">
            <form method="post" action="">
                <input type="hidden" name="test_type" value="phpmailer">
                
                <div class="form-group">
                    <label for="to_email_phpmailer">Recipient Email:</label>
                    <input type="email" id="to_email_phpmailer" name="to_email" required>
                </div>
                
                <div class="form-group">
                    <label for="smtp_host">SMTP Host:</label>
                    <input type="text" id="smtp_host" name="smtp_host" value="smtp.gmail.com">
                </div>
                
                <div class="form-group">
                    <label for="smtp_port">SMTP Port:</label>
                    <input type="text" id="smtp_port" name="smtp_port" value="587">
                </div>
                
                <div class="form-group">
                    <label for="smtp_user">SMTP Username:</label>
                    <input type="text" id="smtp_user" name="smtp_user" placeholder="your_email@gmail.com">
                </div>
                
                <div class="form-group">
                    <label for="smtp_pass">SMTP Password:</label>
                    <input type="password" id="smtp_pass" name="smtp_pass" placeholder="your_app_password">
                </div>
                
                <div class="form-group">
                    <label for="smtp_secure">SMTP Security:</label>
                    <select id="smtp_secure" name="smtp_secure">
                        <option value="tls">TLS</option>
                        <option value="ssl">SSL</option>
                    </select>
                </div>
                
                <button type="submit" class="btn">Send Test Email</button>
            </form>
        </div>
        
        <div class="tab-content" id="reset-email">
            <form method="post" action="">
                <input type="hidden" name="test_type" value="reset_email">
                
                <div class="form-group">
                    <label for="to_email_reset">Recipient Email:</label>
                    <input type="email" id="to_email_reset" name="to_email" required>
                </div>
                
                <div class="form-group">
                    <label for="smtp_host_reset">SMTP Host:</label>
                    <input type="text" id="smtp_host_reset" name="smtp_host" value="smtp.gmail.com">
                </div>
                
                <div class="form-group">
                    <label for="smtp_port_reset">SMTP Port:</label>
                    <input type="text" id="smtp_port_reset" name="smtp_port" value="587">
                </div>
                
                <div class="form-group">
                    <label for="smtp_user_reset">SMTP Username:</label>
                    <input type="text" id="smtp_user_reset" name="smtp_user" placeholder="your_email@gmail.com">
                </div>
                
                <div class="form-group">
                    <label for="smtp_pass_reset">SMTP Password:</label>
                    <input type="password" id="smtp_pass_reset" name="smtp_pass" placeholder="your_app_password">
                </div>
                
                <div class="form-group">
                    <label for="smtp_secure_reset">SMTP Security:</label>
                    <select id="smtp_secure_reset" name="smtp_secure">
                        <option value="tls">TLS</option>
                        <option value="ssl">SSL</option>
                    </select>
                </div>
                
                <button type="submit" class="btn">Send Reset Email</button>
            </form>
        </div>
        
        <?php if (!empty($result)): ?>
            <div class="result">
                <h3>Debug Output:</h3>
                <?php echo $result; ?>
            </div>
        <?php endif; ?>
        
        <p style="text-align: center; margin-top: 20px;">
            <a href="admin-dashboard.php">Back to Dashboard</a>
        </p>
    </div>
    
    <script>
        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Remove active class from all tabs and contents
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    // Add active class to clicked tab
                    this.classList.add('active');
                    
                    // Show corresponding content
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId).classList.add('active');
                });
            });
        });
    </script>
</body>
</html>
