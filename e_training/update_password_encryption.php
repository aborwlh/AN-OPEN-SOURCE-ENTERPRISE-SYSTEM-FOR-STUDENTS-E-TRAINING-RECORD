<?php
// This script updates existing plain text passwords to bcrypt hashed passwords
// Run this script once after implementing bcrypt to update existing user accounts

include 'config.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != "admin") {
    die("Access denied. Only administrators can run this script.");
}

echo "<h1>Password Encryption Update</h1>";
echo "<p>This script will update all plain text passwords to bcrypt hashed passwords.</p>";

// Get all users
$users_query = "SELECT user_id, password FROM users";
$users_result = mysqli_query($con, $users_query);

if (!$users_result) {
    die("Error fetching users: " . mysqli_error($con));
}

$updated_count = 0;
$error_count = 0;

while ($user = mysqli_fetch_assoc($users_result)) {
    $user_id = $user['user_id'];
    $plain_password = $user['password'];
    
    // Check if password is already hashed (bcrypt hashes start with $2y$)
    if (strpos($plain_password, '$2y$') === 0) {
        echo "<p>User ID {$user_id}: Password already hashed, skipping.</p>";
        continue;
    }
    
    // Hash the password
    $hashed_password = password_hash($plain_password, PASSWORD_BCRYPT);
    
    // Update the user's password
    $update_query = "UPDATE users SET password = '$hashed_password' WHERE user_id = $user_id";
    $update_result = mysqli_query($con, $update_query);
    
    if ($update_result) {
        echo "<p>User ID {$user_id}: Password updated successfully.</p>";
        $updated_count++;
    } else {
        echo "<p>User ID {$user_id}: Error updating password: " . mysqli_error($con) . "</p>";
        $error_count++;
    }
}

echo "<h2>Summary</h2>";
echo "<p>Total users updated: {$updated_count}</p>";
echo "<p>Total errors: {$error_count}</p>";
echo "<p><a href='admin-dashboard.php'>Return to Dashboard</a></p>";
?>
