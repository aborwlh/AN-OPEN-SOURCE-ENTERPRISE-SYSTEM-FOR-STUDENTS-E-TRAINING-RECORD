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
$user_query = "SELECT * FROM users WHERE user_id = '$user_id' AND role = 'instructor'";
$user_result = mysqli_query($con, $user_query);

if (mysqli_num_rows($user_result) == 0) {
    header("Location: admin_dashboard.php?error=user_not_found");
    exit();
}

$user_data = mysqli_fetch_assoc($user_result);
$user_name = $user_data['name'];
$user_email = $user_data['email'];

// Check if a replacement instructor is provided
if (isset($_POST['replacement_instructor']) && $_POST['confirm_delete'] == 'yes') {
    $replacement_id = mysqli_real_escape_string($con, $_POST['replacement_instructor']);
    
    // Store user info in session for success message
    $_SESSION['deleted_instructor'] = [
        'id' => $user_id,
        'name' => $user_name,
        'email' => $user_email
    ];
    
    // Begin transaction to ensure data integrity
    mysqli_begin_transaction($con);
    
    try {
        // 1. Reassign all courses to the replacement instructor
        mysqli_query($con, "UPDATE courses SET instructor_id = '$replacement_id' WHERE instructor_id = '$user_id'");
        
        // 2. Delete notifications related to this instructor
        mysqli_query($con, "DELETE FROM notifications WHERE user_id = '$user_id'");
        
        // 3. Delete login history
        mysqli_query($con, "DELETE FROM login_history WHERE user_id = '$user_id'");
        
        // 4. Delete password reset tokens (if exists)
        $check_table = mysqli_query($con, "SHOW TABLES LIKE 'password_reset_tokens'");
        if (mysqli_num_rows($check_table) > 0) {
            mysqli_query($con, "DELETE FROM password_reset_tokens WHERE email = '$user_email'");
        }
        
        // 5. Finally, delete the instructor
        $delete_query = "DELETE FROM users WHERE user_id = '$user_id' AND role = 'instructor'";
        $result = mysqli_query($con, $delete_query);
        
        if (!$result) {
            throw new Exception(mysqli_error($con));
        }
        
        // If everything is successful, commit the transaction
        mysqli_commit($con);
        
        // Redirect back to the instructors management page with success message
        header("Location: admin_manage_instructors.php?success=deleted");
        exit();
        
    } catch (Exception $e) {
        // If there's an error, roll back the transaction
        mysqli_rollback($con);
        
        // Redirect back with error message
        header("Location: admin_manage_instructors.php?error=delete_failed&message=" . urlencode($e->getMessage()));
        exit();
    }
}

// Check if confirmation is given
if (!isset($_GET['confirm']) || $_GET['confirm'] != 'yes') {
    // Count courses taught by this instructor
    $courses_query = "SELECT COUNT(*) as course_count FROM courses WHERE instructor_id = '$user_id'";
    $courses_result = mysqli_query($con, $courses_query);
    $courses_data = mysqli_fetch_assoc($courses_result);
    $course_count = $courses_data['course_count'];
    
    // Get all other instructors for reassignment
    $instructors_query = "SELECT user_id, name, email FROM users WHERE role = 'instructor' AND user_id != '$user_id' ORDER BY name";
    $instructors_result = mysqli_query($con, $instructors_query);
    
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
                border-left: 4px solid var(--info-color);
            }

            .user-info p {
                margin: 10px 0;
                display: flex;
                align-items: center;
            }

            .user-info .label {
                font-weight: 600;
                color: var(--info-color);
                width: 150px;
                display: inline-block;
            }

            .user-info i {
                margin-right: 10px;
                color: var(--info-color);
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

            .critical-warning {
                background-color: #f8d7da;
                border-left: 4px solid var(--danger-color);
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 20px;
            }

            .critical-warning .warning-title {
                color: #721c24;
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

            .course-count {
                background-color: var(--info-light);
                color: var(--info-dark);
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 20px;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .course-count i {
                font-size: 1.5rem;
            }

            .form-group {
                margin-bottom: 20px;
            }

            .form-group label {
                display: block;
                margin-bottom: 8px;
                font-weight: 500;
                color: var(--dark-color);
            }

            .form-control {
                width: 100%;
                padding: 12px;
                border: 1px solid var(--border-color);
                border-radius: 6px;
                font-size: 1rem;
                transition: all 0.3s ease;
            }

            .form-control:focus {
                outline: none;
                border-color: var(--primary-color);
                box-shadow: 0 0 0 0.2rem rgba(4, 99, 155, 0.25);
            }

            .reassign-section {
                background-color: #e6f1f8;
                border-left: 4px solid var(--primary-color);
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 20px;
            }

            .reassign-title {
                color: var(--primary-dark);
                font-weight: 600;
                margin-bottom: 10px;
                display: flex;
                align-items: center;
            }

            .reassign-title i {
                margin-right: 10px;
                font-size: 1.2rem;
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
                    <i class="fas fa-exclamation-triangle"></i> <?php echo translate('Confirm Instructor Deletion'); ?>
                </div>
                <div class="confirmation-body">
                    <p class="confirmation-message"><?php echo translate('You are about to delete the following instructor:'); ?></p>
                    
                    <div class="user-info">
                        <p><i class="fas fa-id-card"></i> <span class="label"><?php echo translate('ID:'); ?></span> <?php echo $user_id; ?></p>
                        <p><i class="fas fa-user"></i> <span class="label"><?php echo translate('Name:'); ?></span> <?php echo htmlspecialchars($user_name); ?></p>
                        <p><i class="fas fa-envelope"></i> <span class="label"><?php echo translate('Email:'); ?></span> <?php echo htmlspecialchars($user_email); ?></p>
                        <p><i class="fas fa-mobile-alt"></i> <span class="label"><?php echo translate('Mobile:'); ?></span> <?php echo htmlspecialchars($user_data['mobile']); ?></p>
                        <p><i class="fas fa-calendar-alt"></i> <span class="label"><?php echo translate('Registration Date:'); ?></span> <?php echo date('F j, Y', strtotime($user_data['registration_date'])); ?></p>
                    </div>
                    
                    <?php if ($course_count > 0): ?>
                    <div class="course-count">
                        <i class="fas fa-book"></i>
                        <div>
                            <strong><?php echo translate('This instructor teaches'); ?> <?php echo $course_count; ?> <?php echo $course_count == 1 ? translate('course') : translate('courses'); ?></strong>
                        </div>
                    </div>
                    
                    <div class="critical-warning">
                        <div class="warning-title">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo translate('Critical Warning'); ?>
                        </div>
                        <p><?php echo translate('This instructor is currently teaching courses. You must reassign these courses to another instructor before deletion.'); ?></p>
                    </div>
                    
                    <form method="post" action="admin_delete_instructor.php?id=<?php echo $user_id; ?>">
                        <div class="reassign-section">
                            <div class="reassign-title">
                                <i class="fas fa-exchange-alt"></i> <?php echo translate('Reassign Courses'); ?>
                            </div>
                            <p><?php echo translate('Select another instructor to take over the courses:'); ?></p>
                            
                            <div class="form-group">
                                <label for="replacement_instructor"><?php echo translate('Replacement Instructor:'); ?></label>
                                <select name="replacement_instructor" id="replacement_instructor" class="form-control" required>
                                    <option value=""><?php echo translate('-- Select Instructor --'); ?></option>
                                    <?php while ($instructor = mysqli_fetch_assoc($instructors_result)): ?>
                                        <option value="<?php echo $instructor['user_id']; ?>">
                                            <?php echo htmlspecialchars($instructor['name']); ?> (<?php echo htmlspecialchars($instructor['email']); ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    <?php else: ?>
                    <form method="post" action="admin_delete_instructor.php?id=<?php echo $user_id; ?>">
                    <?php endif; ?>
                    
                        <div class="warning-box">
                            <div class="warning-title">
                                <i class="fas fa-exclamation-circle"></i> <?php echo translate('Warning'); ?>
                            </div>
                            <p><?php echo translate('This action cannot be undone. All data related to this instructor will be permanently deleted.'); ?></p>
                        </div>
                        
                        <p><?php echo translate('The following data will be deleted:'); ?></p>
                        <ul class="deletion-list">
                            <li><?php echo translate('Instructor profile information'); ?></li>
                            <li><?php echo translate('Login history'); ?></li>
                            <li><?php echo translate('Notifications'); ?></li>
                            <?php if ($course_count == 0): ?>
                                <li><?php echo translate('Course associations (if any)'); ?></li>
                            <?php endif; ?>
                        </ul>
                        
                        <input type="hidden" name="confirm_delete" value="yes">
                        
                        <div class="buttons">
                            <a href="admin_manage_instructors.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> <?php echo translate('Cancel'); ?>
                            </a>
                            <button type="submit" class="btn btn-danger" <?php echo ($course_count > 0 && mysqli_num_rows($instructors_result) == 0) ? 'disabled' : ''; ?>>
                                <i class="fas fa-trash-alt"></i> <?php echo translate('Delete Instructor'); ?>
                            </button>
                        </div>
                    </form>
                    
                    <?php if ($course_count > 0 && mysqli_num_rows($instructors_result) == 0): ?>
                        <div class="critical-warning" style="margin-top: 20px;">
                            <div class="warning-title">
                                <i class="fas fa-ban"></i> <?php echo translate('Cannot Delete'); ?>
                            </div>
                            <p><?php echo translate('There are no other instructors available to reassign courses to. Please add another instructor first.'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// If we get here without POST data, redirect to confirmation page
header("Location: admin_delete_instructor.php?id=$user_id&confirm=yes");
exit();
?>
