<?php
include 'config.php';

// Check if form is submitted
if (isset($_POST['btn-submit'])) {
    // Get form data
    $token = mysqli_real_escape_string($con, $_POST['token']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $password = mysqli_real_escape_string($con, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($con, $_POST['confirm_password']);
    
    // Enable error logging for debugging
    error_log("Reset password attempt for email: $email with token: $token");
    
    // Validate form data
    if (empty($token) || empty($email) || empty($password) || empty($confirm_password)) {
        error_log("Empty fields in reset password form");
        header("Location: reset_password.php?token=$token&error=empty");
        exit();
    }
    
    // Validate password strength
    $password_pattern = '/^(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}$/';
    if (!preg_match($password_pattern, $password)) {
        error_log("Password does not meet requirements");
        header("Location: reset_password.php?token=$token&error=password");
        exit();
    }
    
    // Check if passwords match
    if ($password !== $confirm_password) {
        error_log("Passwords do not match");
        header("Location: reset_password.php?token=$token&error=match");
        exit();
    }
    
    // Check if token is valid
    $token_query = "SELECT * FROM password_reset_tokens WHERE token = '$token' AND email = '$email'";
    $token_result = mysqli_query($con, $token_query);
    
    if (mysqli_num_rows($token_result) == 0) {
        error_log("Token not found in database: $token for email: $email");
        header("Location: forget_password.php?error=invalid_token");
        exit();
    }
    
    $token_data = mysqli_fetch_assoc($token_result);
    
    // Check if token is expired
    $current_time = date('Y-m-d H:i:s');
    if ($token_data['expires'] <= $current_time) {
        error_log("Token expired. Expiry: " . $token_data['expires'] . ", Current: $current_time");
        header("Location: forget_password.php?error=expired_token");
        exit();
    }
    
    // Hash the password using bcrypt
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    
    // Update the user's password
    $update_query = "UPDATE users SET password = '$hashed_password' WHERE email = '$email'";
    $update_result = mysqli_query($con, $update_query);
    
    if ($update_result) {
        error_log("Password updated successfully for email: $email");
        
        // Delete the used token
        $delete_query = "DELETE FROM password_reset_tokens WHERE token = '$token'";
        mysqli_query($con, $delete_query);
        
        // Redirect to success page
        header("Location: reset_password.php?success=1");
        exit();
    } else {
        error_log("Failed to update password: " . mysqli_error($con));
        header("Location: reset_password.php?token=$token&error=db_error");
        exit();
    }
} else {
    // If the form was not submitted, redirect to forget password page
    header("Location: forget_password.php");
    exit();
}
?>
