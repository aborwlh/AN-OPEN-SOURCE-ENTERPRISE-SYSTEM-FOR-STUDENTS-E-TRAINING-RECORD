<?php
include 'config.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != "admin") {
    header("Location: login.php");
    exit();
}

// Check if course_id is provided
if (!isset($_GET['id'])) {
    header("Location: admin_manage_courses.php?error=no_id");
    exit();
}

$course_id = mysqli_real_escape_string($con, $_GET['id']);

// Get course information before deletion for the success message
$course_query = "SELECT c.*, cat.name as category_name, u.name as instructor_name 
                FROM courses c
                LEFT JOIN category cat ON c.category_id = cat.category_id
                LEFT JOIN users u ON c.instructor_id = u.user_id
                WHERE c.course_id = '$course_id'";
$course_result = mysqli_query($con, $course_query);

if (mysqli_num_rows($course_result) == 0) {
    header("Location: admin_manage_courses.php?error=course_not_found");
    exit();
}

$course_data = mysqli_fetch_assoc($course_result);
$course_name = $course_data['name'];
$category_name = $course_data['category_name'];
$instructor_name = $course_data['instructor_name'];

// Check if confirmation is given
if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] == 'yes') {
    // Store course info in session for success message
    $_SESSION['deleted_course'] = [
        'id' => $course_id,
        'name' => $course_name,
        'category' => $category_name,
        'instructor' => $instructor_name
    ];
    
    // Check if there are enrollments for this course
    $check_enrollments = "SELECT COUNT(*) as count FROM course_enrollments WHERE course_id = '$course_id'";
    $enrollments_result = mysqli_query($con, $check_enrollments);
    $enrollments_count = mysqli_fetch_assoc($enrollments_result)['count'];
    
    // If there are enrollments and the unenroll checkbox wasn't checked
    if ($enrollments_count > 0 && (!isset($_POST['unenroll_students']) || $_POST['unenroll_students'] != 'yes')) {
        header("Location: admin_manage_courses.php?error=has_enrollments");
        exit();
    }
    
    // Begin transaction to ensure data integrity
    mysqli_begin_transaction($con);
    
    try {
        // If there are enrollments and the unenroll checkbox was checked, unenroll all students
        if ($enrollments_count > 0 && isset($_POST['unenroll_students']) && $_POST['unenroll_students'] == 'yes') {
            // Delete all enrollments for this course
            mysqli_query($con, "DELETE FROM course_enrollments WHERE course_id = '$course_id'");
            
            // Store the number of unenrolled students in session for the success message
            $_SESSION['unenrolled_students_count'] = $enrollments_count;
        }
        
        // 1. Delete course materials
        mysqli_query($con, "DELETE FROM course_materials WHERE course_id = '$course_id'");
        
        // 2. Delete course events
        mysqli_query($con, "DELETE FROM course_events WHERE course_id = '$course_id'");
        
        // 3. Delete course schedules
        mysqli_query($con, "DELETE FROM schedules WHERE course_id = '$course_id'");
        
        // 4. Delete enrollment requests
        mysqli_query($con, "DELETE FROM enrollment_requests WHERE course_id = '$course_id'");
        
        // 5. Delete course feedback
        mysqli_query($con, "DELETE FROM course_feedback WHERE course_id = '$course_id'");
        
        // 6. Delete student progress
        mysqli_query($con, "DELETE FROM student_progress WHERE course_id = '$course_id'");
        
        // 7. Finally delete the course
        $delete_query = "DELETE FROM courses WHERE course_id = '$course_id'";
        $result = mysqli_query($con, $delete_query);
        
        if (!$result) {
            throw new Exception(mysqli_error($con));
        }
        
        // If everything is successful, commit the transaction
        mysqli_commit($con);
        
        // Redirect back to the courses management page with success message
        header("Location: admin_manage_courses.php?success=deleted");
        exit();
        
    } catch (Exception $e) {
        // If there's an error, roll back the transaction
        mysqli_rollback($con);
        
        // Redirect back with error message
        header("Location: admin_manage_courses.php?error=delete_failed&message=" . urlencode($e->getMessage()));
        exit();
    }
}

// If we get here, show confirmation dialog
if (!isset($_GET['confirm']) || $_GET['confirm'] != 'yes') {
    // Count related data
    $materials_query = "SELECT COUNT(*) as count FROM course_materials WHERE course_id = '$course_id'";
    $materials_result = mysqli_query($con, $materials_query);
    $materials_count = mysqli_fetch_assoc($materials_result)['count'];
    
    $events_query = "SELECT COUNT(*) as count FROM course_events WHERE course_id = '$course_id'";
    $events_result = mysqli_query($con, $events_query);
    $events_count = mysqli_fetch_assoc($events_result)['count'];
    
    $schedules_query = "SELECT COUNT(*) as count FROM schedules WHERE course_id = '$course_id'";
    $schedules_result = mysqli_query($con, $schedules_query);
    $schedules_count = mysqli_fetch_assoc($schedules_result)['count'];
    
    $requests_query = "SELECT COUNT(*) as count FROM enrollment_requests WHERE course_id = '$course_id'";
    $requests_result = mysqli_query($con, $requests_query);
    $requests_count = mysqli_fetch_assoc($requests_result)['count'];
    
    $feedback_query = "SELECT COUNT(*) as count FROM course_feedback WHERE course_id = '$course_id'";
    $feedback_result = mysqli_query($con, $feedback_query);
    $feedback_count = mysqli_fetch_assoc($feedback_result)['count'];
    
    // Check if there are enrollments for this course
    $check_enrollments = "SELECT COUNT(*) as count FROM course_enrollments WHERE course_id = '$course_id'";
    $enrollments_result = mysqli_query($con, $check_enrollments);
    $enrollments_count = mysqli_fetch_assoc($enrollments_result)['count'];
    
    // Show confirmation dialog and exit
    ?>
    <!DOCTYPE html>
<html lang="<?php echo $_SESSION['language'] ?? 'en'; ?>" <?php echo $dirAttribute; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo translate('Confirm Course Deletion'); ?> - <?php echo translate('E-Training Platform'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #17a2b8;
            --primary-dark: #138496;
            --danger-color: #dc3545;
            --danger-light: #f8d7da;
            --danger-dark: #721c24;
            --white: #ffffff;
            --light-bg: #f8f9fa;
            --border-color: #dee2e6;
            --text-dark: #343a40;
            --text-muted: #6c757d;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: var(--text-dark);
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .confirmation-container {
            width: 100%;
            max-width: 550px;
            margin: 0 auto;
        }

        .confirmation-box {
            background-color: var(--white);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .confirmation-header {
            background-color: var(--danger-color);
            color: var(--white);
            padding: 20px;
            text-align: center;
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .confirmation-body {
            padding: 25px;
        }

        .confirmation-message {
            margin-bottom: 20px;
            font-size: 1rem;
            color: var(--text-dark);
        }

        .course-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary-color);
        }

        .course-info p {
            margin: 10px 0;
            display: flex;
            align-items: center;
        }

        .course-info .label {
            font-weight: 600;
            color: var(--primary-color);
            width: 120px;
            display: inline-block;
        }

        .course-info i {
            margin-right: 10px;
            color: var(--primary-color);
            width: 20px;
            text-align: center;
        }

        .warning-box {
            background-color: var(--danger-light);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .warning-title {
            color: var(--danger-dark);
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .unenroll-checkbox {
            margin-top: 15px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .unenroll-checkbox input[type="checkbox"] {
            margin-top: 3px;
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        .unenroll-checkbox label {
            cursor: pointer;
            font-weight: 500;
            color: var(--danger-dark);
        }

        .hidden-buttons {
            display: none;
        }

        .btn {
            padding: 12px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            text-align: center;
            transition: all 0.3s ease;
            border: none;
            font-size: 0.95rem;
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: var(--white);
        }

        .btn-secondary {
            background-color: var(--text-muted);
            color: var(--white);
        }

        @media (max-width: 576px) {
            .confirmation-container {
                padding: 0 10px;
            }
            
            .course-info .label {
                width: 100px;
            }
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <div class="confirmation-box">
            <div class="confirmation-header">
                <i class="fas fa-exclamation-triangle"></i> <?php echo translate('Confirm Course Deletion'); ?>
            </div>
            <div class="confirmation-body">
                <p class="confirmation-message"><?php echo translate('You are about to delete the following course:'); ?></p>
                
                <div class="course-info">
                    <p><i class="fas fa-id-card"></i> <span class="label"><?php echo translate('ID:'); ?></span> <?php echo $course_id; ?></p>
                    <p><i class="fas fa-book"></i> <span class="label"><?php echo translate('Name:'); ?></span> <?php echo htmlspecialchars($course_name); ?></p>
                    <p><i class="fas fa-tag"></i> <span class="label"><?php echo translate('Category:'); ?></span> <?php echo htmlspecialchars($category_name); ?></p>
                    <p><i class="fas fa-chalkboard-teacher"></i> <span class="label"><?php echo translate('Instructor:'); ?></span> <?php echo htmlspecialchars($instructor_name); ?></p>
                    <p><i class="fas fa-calendar-alt"></i> <span class="label"><?php echo translate('Start Date:'); ?></span> <?php echo date('F j, Y', strtotime($course_data['start_date'])); ?></p>
                </div>
                
                <?php if ($enrollments_count > 0): ?>
                <div class="warning-box">
                    <div class="warning-title">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo translate('Course Has Enrolled Students'); ?>
                    </div>
                    <p><?php echo translate('This course has'); ?> <strong><?php echo $enrollments_count; ?></strong> <?php echo translate('enrolled students.'); ?></p>
                    <p><?php echo translate('You must confirm that you want to unenroll all students before deleting this course.'); ?></p>
                    
                    <form method="post" action="admin_delete_course.php?id=<?php echo $course_id; ?>" id="deleteForm">
                        <input type="hidden" name="confirm_delete" value="yes">
                        
                        <div class="unenroll-checkbox">
                            <input type="checkbox" name="unenroll_students" value="yes" id="unenroll_checkbox">
                            <label for="unenroll_checkbox"><?php echo translate('Unenroll all students'); ?></label>
                        </div>
                        
                        <div class="hidden-buttons">
                            <button type="submit" id="delete_button" class="btn btn-danger">
                                <i class="fas fa-trash-alt"></i> <?php echo translate('Delete Course'); ?>
                            </button>
                            <a href="admin_manage_courses.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> <?php echo translate('Cancel'); ?>
                            </a>
                        </div>
                    </form>
                </div>
                <?php else: ?>
                <form method="post" action="admin_delete_course.php?id=<?php echo $course_id; ?>" id="deleteForm">
                    <input type="hidden" name="confirm_delete" value="yes">
                    <div class="hidden-buttons">
                        <button type="submit" id="delete_button" class="btn btn-danger">
                            <i class="fas fa-trash-alt"></i> <?php echo translate('Delete Course'); ?>
                        </button>
                        <a href="admin_manage_courses.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> <?php echo translate('Cancel'); ?>
                        </a>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-submit form when checkbox is checked
        document.getElementById('unenroll_checkbox').addEventListener('change', function() {
            if (this.checked) {
                // Add a small delay to allow the user to see the checkbox being checked
                setTimeout(function() {
                    document.getElementById('deleteForm').submit();
                }, 300);
            }
        });
    </script>
</body>
</html>
    <?php
    exit();
}

// If we get here without POST data, redirect to confirmation page
header("Location: admin_delete_course.php?id=$course_id&confirm=yes");
exit();
?>
