<?php
include 'config.php';

// Check if form is submitted
if (isset($_POST['btn-submit'])) {
    // Get form data
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $password = mysqli_real_escape_string($con, $_POST['password']);
    
    // Validate form data
    if (empty($email) || empty($password)) {
        header("Location: login.php?error=empty");
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
            header("Location: login.php?error=captcha");
            exit();
        }
    }
    
    // Check user credentials
    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($con, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        
        // Verify password using password_verify() for bcrypt
        if (password_verify($password, $user['password'])) {
            // Password is correct, start a new session
            session_start();
            
            // Store user data in session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_type'] = $user['role'];
            
            // Log the login activity
            $user_id = $user['user_id'];
            $ip_address = $_SERVER['REMOTE_ADDR'];
            
            try {
                $log_query = "INSERT INTO login_history (user_id, ip_address) VALUES ('$user_id', '$ip_address')";
                mysqli_query($con, $log_query);
            } catch (Exception $e) {
                // Silently handle the error - login history is not critical
            }
            
            // Redirect based on user role
            switch ($user['role']) {
                case 'admin':
                    header("Location: admin_dashboard.php");
                    break;
                case 'instructor':
                    header("Location: instructor_dashboard.php");
                    break;
                case 'student':
                    header("Location: student_dashboard.php");
                    break;
                default:
                    header("Location: index.php");
            }
            exit();
        } else {
            // Password is incorrect
            header("Location: login.php?error=invalid");
            exit();
        }
    } else {
        // User not found
        header("Location: login.php?error=invalid");
        exit();
    }
} else {
    // If the form was not submitted, redirect to login page
    header("Location: login.php");
    exit();
}
?>
