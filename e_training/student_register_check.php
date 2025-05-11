<?php
include 'config.php';

// Check if form is submitted
if (isset($_POST['btn-submit'])) {
    // Get form data
    $name = mysqli_real_escape_string($con, $_POST['name']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $password = mysqli_real_escape_string($con, $_POST['password']);
    $mobile = mysqli_real_escape_string($con, $_POST['mobile']);
    
    // Validate form data
    if (empty($name) || empty($email) || empty($password) || empty($mobile)) {
        header("Location: student_register.php?error=empty");
        exit();
    }
    
    // Validate password strength
    $password_pattern = '/^(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}$/';
    if (!preg_match($password_pattern, $password)) {
        header("Location: student_register.php?error=password");
        exit();
    }
    
    // Validate mobile number (10 digits starting with 05)
    if (!preg_match('/^05\d{8}$/', $mobile)) {
        header("Location: student_register.php?error=mobile");
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
            header("Location: student_register.php?error=captcha");
            exit();
        }
    }
    
    // Check if email already exists
    $check_query = "SELECT * FROM users WHERE email = '$email'";
    $check_result = mysqli_query($con, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        header("Location: student_register.php?error=email_exists");
        exit();
    }
    
      // Hash the password using bcrypt
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Insert new student
    $role = "student"; // Set role to student
    $insert_query = "INSERT INTO users (name, email, password, mobile, role) VALUES ('$name', '$email', '$hashed_password', '$mobile', '$role')";
    
    if (mysqli_query($con, $insert_query)) {
        // Registration successful
        header("Location: student_register.php?success=1");
        exit();
    } else {
        // Registration failed
        header("Location: student_register.php?error=db_error");
        exit();
    }
} else {
    // If the form was not submitted, redirect to registration page
    header("Location: student_register.php");
    exit();
}
?>
