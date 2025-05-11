<?php
include 'config.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != "admin") {
    header("Location: login.php");
    exit();
}

// Check if user_id is provided
if (!isset($_GET['id'])) {
    header("Location: admin_dashboard.php?error=no_id");
    exit();
}

$user_id = mysqli_real_escape_string($con, $_GET['id']);

// Get user information before deletion for the success message
$user_query = "SELECT * FROM users WHERE user_id = '$user_id' AND role = 'student'";
$user_result = mysqli_query($con, $user_query);

if (mysqli_num_rows($user_result) == 0) {
    header("Location: admin_dashboard.php?error=user_not_found");
    exit();
}

$user_data = mysqli_fetch_assoc($user_result);
$user_name = $user_data['name'];
$user_email = $user_data['email'];

// Check if confirmation is given
if (!isset($_GET['confirm']) || $_GET['confirm'] != 'yes') {
    // Show confirmation dialog and exit
    ?>
    <!DOCTYPE html>
    <html lang="<?php echo $_SESSION['language'] ?? 'en'; ?>" <?php echo $dirAttribute; ?>>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo translate('Confirm Deletion'); ?> - <?php echo translate('E-Training Platform'); ?></title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            :root {
                --primary-color: #04639b;
                --primary-dark: #035483;
                --primary-light: #e6f1f8;
                --secondary-color: #ff7e00;
                --success-color: #28a745;
                --danger-color: #dc3545;
                --warning-color: #ffc107;
                --info-color: #17a2b8;
                --light-color: #f8f9fa;
                --dark-color: #343a40;
                --gray-color: #6c757d;
                --white-color: #ffffff;
                --border-color: #dee2e6;
                --shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            }

            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
                background-color: #f5f7fa;
                color: #333;
                line-height: 1.6;
                padding: 20px;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
            }

            .confirmation-container {
                width: 100%;
                max-width: 600px;
                margin: 0 auto;
            }

            .confirmation-box {
                background-color: var(--white-color);
                border-radius: 8px;
                box-shadow: var(--shadow);
                overflow: hidden;
                animation: fadeIn 0.3s ease-in-out;
            }

            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }

            .confirmation-header {
                background-color: var(--danger-color);
                color: var(--white-color);
                padding: 20px;
                text-align: center;
                font-size: 1.5rem;
                font-weight: 600;
            }

            .confirmation-body {
                padding: 25px;
            }

            .confirmation-message {
                margin-bottom: 20px;
                font-size: 1rem;
                color: #333;
            }

            .user-info {
                background-color: var(--light-color);
                border-radius: 8px;
                padding: 20px;
                margin-bottom: 20px;
                border-left: 4px solid var(--primary-color);
            }

            .user-info p {
                margin: 10px 0;
                display: flex;
                align-items: center;
            }

            .user-info .label {
                font-weight: 600;
                color: var(--primary-color);
                width: 150px;
                display: inline-block;
            }

            .user-info i {
                margin-right: 10px;
                color: var(--primary-color);
                width: 20px;
                text-align: center;
            }

            .warning-box {
                background-color: #fff3cd;
                border-left: 4px solid var(--warning-color);
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 20px;
            }

            .warning-title {
                color: #856404;
                font-weight: 600;
                margin-bottom: 10px;
                display: flex;
                align-items: center;
            }

            .warning-title i {
                margin-right: 10px;
                font-size: 1.2rem;
            }

            .deletion-list {
                background-color: #f8f9fa;
                border-radius: 8px;
                padding: 15px 15px 15px 40px;
                margin-bottom: 20px;
            }

            .deletion-list li {
                margin-bottom: 8px;
                color: #495057;
            }

            .buttons {
                display: flex;
                justify-content: flex-end;
                gap: 15px;
                margin-top: 30px;
            }

            .btn {
                padding: 12px 20px;
                border-radius: 6px;
                cursor: pointer;
                font-weight: 500;
                text-decoration: none;
                text-align: center;
                transition: all 0.3s ease;
                border: none;
                font-size: 0.95rem;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
            }

            .btn-danger {
                background-color: var(--danger-color);
                color: var(--white-color);
            }

            .btn-danger:hover {
                background-color: #c82333;
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(220, 53, 69, 0.2);
            }

            .btn-secondary {
                background-color: var(--gray-color);
                color: var(--white-color);
            }

            .btn-secondary:hover {
                background-color: #5a6268;
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(108, 117, 125, 0.2);
            }

            /* Responsive adjustments */
            @media (max-width: 768px) {
                .confirmation-container {
                    padding: 0 15px;
                }
                
                .user-info p {
                    flex-direction: column;
                    align-items: flex-start;
                }
                
                .user-info .label {
                    width: 100%;
                    margin-bottom: 5px;
                }
                
                .buttons {
                    flex-direction: column;
                    gap: 10px;
                }
                
                .btn {
                    width: 100%;
                }
            }
        </style>
    </head>
    <body>
        <div class="confirmation-container">
            <div class="confirmation-box">
                <div class="confirmation-header">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo translate('Confirm Student Deletion'); ?>
                </div>
                <div class="confirmation-body">
                    <p class="confirmation-message"><?php echo translate('You are about to delete the following student:'); ?></p>
                    
                    <div class="user-info">
                        <p><i class="fas fa-id-card"></i> <span class="label"><?php echo translate('ID:'); ?></span> <?php echo $user_id; ?></p>
                        <p><i class="fas fa-user"></i> <span class="label"><?php echo translate('Name:'); ?></span> <?php echo htmlspecialchars($user_name); ?></p>
                        <p><i class="fas fa-envelope"></i> <span class="label"><?php echo translate('Email:'); ?></span> <?php echo htmlspecialchars($user_email); ?></p>
                        <p><i class="fas fa-mobile-alt"></i> <span class="label"><?php echo translate('Mobile:'); ?></span> <?php echo htmlspecialchars($user_data['mobile']); ?></p>
                        <p><i class="fas fa-calendar-alt"></i> <span class="label"><?php echo translate('Registration Date:'); ?></span> <?php echo date('F j, Y', strtotime($user_data['registration_date'])); ?></p>
                    </div>
                    
                    <div class="warning-box">
                        <div class="warning-title">
                            <i class="fas fa-exclamation-circle"></i> <?php echo translate('Warning'); ?>
                        </div>
                        <p><?php echo translate('This action cannot be undone. All data related to this student will be permanently deleted.'); ?></p>
                    </div>
                    
                    <p><?php echo translate('The following data will be deleted:'); ?></p>
                    <ul class="deletion-list">
                        <li><?php echo translate('Course enrollments'); ?></li>
                        <li><?php echo translate('Progress records'); ?></li>
                        <li><?php echo translate('Feedback submissions'); ?></li>
                        <li><?php echo translate('Enrollment requests'); ?></li>
                        <li><?php echo translate('Notifications'); ?></li>
                        <li><?php echo translate('Login history'); ?></li>
                    </ul>
                    
                    <div class="buttons">
                        <a href="admin_dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> <?php echo translate('Cancel'); ?>
                        </a>
                        <a href="admin_delete_student.php?id=<?php echo $user_id; ?>&confirm=yes" class="btn btn-danger">
                            <i class="fas fa-trash-alt"></i> <?php echo translate('Delete Student'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Store user info in session for success message
$_SESSION['deleted_user'] = [
    'id' => $user_id,
    'name' => $user_name,
    'email' => $user_email
];

// Begin transaction to ensure data integrity
mysqli_begin_transaction($con);

try {
    // Delete related records in the following order:
    
    // 1. Delete notifications related to this user
    mysqli_query($con, "DELETE FROM notifications WHERE user_id = '$user_id'");
    
    // 2. Delete enrollment requests
    mysqli_query($con, "DELETE FROM enrollment_requests WHERE student_id = '$user_id'");
    
    // 3. Delete course feedback
    mysqli_query($con, "DELETE FROM course_feedback WHERE student_id = '$user_id'");
    
    // 4. Delete student progress
    mysqli_query($con, "DELETE FROM student_progress WHERE student_id = '$user_id'");
    
    // 5. Delete material access logs if the table exists
    $check_material_logs = mysqli_query($con, "SHOW TABLES LIKE 'material_access_log'");
    if (mysqli_num_rows($check_material_logs) > 0) {
        mysqli_query($con, "DELETE FROM material_access_log WHERE student_id = '$user_id'");
    }
    
    // 6. Delete course enrollments
    mysqli_query($con, "DELETE FROM course_enrollments WHERE student_id = '$user_id'");
    
    // 7. Delete login history
    mysqli_query($con, "DELETE FROM login_history WHERE user_id = '$user_id'");
    
    // 8. Delete password reset tokens (if exists)
    $check_table = mysqli_query($con, "SHOW TABLES LIKE 'password_reset_tokens'");
    if (mysqli_num_rows($check_table) > 0) {
        mysqli_query($con, "DELETE FROM password_reset_tokens WHERE email = '$user_email'");
    }
    
    // 9. Finally, delete the user
    $delete_query = "DELETE FROM users WHERE user_id = '$user_id' AND role = 'student'";
    $result = mysqli_query($con, $delete_query);
    
    if (!$result) {
        throw new Exception(mysqli_error($con));
    }
    
    // If everything is successful, commit the transaction
    mysqli_commit($con);
    
    // Redirect back to the admin dashboard with success message
    header("Location: admin_dashboard.php?success=deleted");
    exit();
    
} catch (Exception $e) {
    // If there's an error, roll back the transaction
    mysqli_rollback($con);
    
    // Redirect back with error message
    header("Location: admin_dashboard.php?error=delete_failed&message=" . urlencode($e->getMessage()));
    exit();
}
?>