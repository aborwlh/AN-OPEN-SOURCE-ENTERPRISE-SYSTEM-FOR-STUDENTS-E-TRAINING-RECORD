<?php include 'config.php'; ?>
<?php
// Initialize variables
$student_id = null;
$verification_mode = false;

// Check if we're in verification mode (student_id is provided in URL)
if (isset($_GET['student_id'])) {
    $student_id = mysqli_real_escape_string($con, $_GET['student_id']);
    $verification_mode = true;
} else {
    // Regular mode - check if user is logged in as student
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != "student") {
        header("Location: login.php");
        exit();
    }
    
    // Get student ID from session
    $student_id = $_SESSION['user_id'];
}

// Check if course ID is provided
if (!isset($_GET['course_id'])) {
    if ($verification_mode) {
        header("Location: verify_cert.php");
    } else {
        header("Location: student_view_my_courses.php");
    }
    exit();
}

$course_id = mysqli_real_escape_string($con, $_GET['course_id']);

// Check if student is enrolled in the course and has completed it
$check_query = "SELECT sp.*, c.name AS course_name, c.description AS course_description, 
               cat.name AS category_name, u.name AS student_name, u.email AS student_email,
               ins.name AS instructor_name
               FROM student_progress sp
               JOIN courses c ON sp.course_id = c.course_id
               JOIN category cat ON c.category_id = cat.category_id
               JOIN users u ON sp.student_id = u.user_id
               JOIN users ins ON c.instructor_id = ins.user_id
               WHERE sp.student_id = '$student_id' 
               AND sp.course_id = '$course_id'
               AND sp.value = 100";

$check_result = mysqli_query($con, $check_query);

if (mysqli_num_rows($check_result) === 0) {
    // Student has not completed the course or is not enrolled
    if ($verification_mode) {
        header("Location: verify_cert.php?error=not_completed");
    } else {
        header("Location: student_view_my_courses.php?error=not_completed");
    }
    exit();
}

// Get certificate details
$certificate = mysqli_fetch_assoc($check_result);

// Generate a verification code
// Get enrollment ID for this student and course
$enrollment_query = "SELECT enrollment_id FROM course_enrollments 
                   WHERE student_id = '$student_id' AND course_id = '$course_id'";
$enrollment_result = mysqli_query($con, $enrollment_query);
$enrollment_data = mysqli_fetch_assoc($enrollment_result);
$enrollment_id = $enrollment_data['enrollment_id'];

// Generate a consistent verification code using the enrollment_id
$verification_code = "CERT-{$enrollment_id}-{$course_id}";

// Generate QR code URL for verification
$verification_url = "http://" . $_SERVER['HTTP_HOST'] . "/Home/e_training/verify_cert.php?certificate_number=" . urlencode($verification_code);
$qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($verification_url);

// Format completion date
$completion_date = date('F d, Y', strtotime($certificate['date']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?php echo translate('Course Completion Certificate'); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .controls {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background-color: #04639b;
            color: white;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .certificate-container {
            width: 850px;
            background-color: white;
            border: 20px solid #04639b;
            padding: 50px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        
        .certificate-header {
            display: flex;
            align-items: center;
            margin-bottom: 40px;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            margin-right: 20px;
        }
        
        .certificate-title {
            color: #04639b;
            font-size: 32px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
        }
        
        .certificate-subtitle {
            color: #666;
            font-size: 18px;
            margin-top: 5px;
        }
        
        .certificate-body {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .certificate-text {
            margin: 15px 0;
            font-size: 16px;
            color: #333;
        }
        
        .student-name {
            font-size: 28px;
            font-weight: bold;
            border-bottom: 1px solid #333;
            display: inline-block;
            padding: 0 20px 5px;
            margin: 20px 0;
        }
        
        .course-name {
            font-size: 24px;
            color: #04639b;
            font-weight: bold;
            margin: 20px 0;
        }
        
        .certificate-footer {
            display: flex;
            justify-content: space-between;
            margin-top: 80px;
        }
        
        .signature-section {
            text-align: center;
            width: 45%;
        }
        
        .signature-line {
            width: 100%;
            height: 1px;
            background-color: #333;
            margin-bottom: 10px;
        }
        
        .verification-section {
            display: flex;
            align-items: flex-start;
            margin-top: 40px;
        }
        
        .qr-code {
            width: 150px;
            height: 150px;
        }
        
        .verification-details {
            margin-left: 20px;
            font-size: 14px;
        }
        
        .verification-code {
            font-weight: bold;
            margin-top: 5px;
        }
        
        .verification-url {
            color: #04639b;
            margin-top: 5px;
        }
        
        @media print {
            .controls {
                display: none;
            }
            
            body {
                padding: 0;
                background-color: white;
            }
            
            .certificate-container {
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="controls">
        <button class="btn btn-primary" onclick="window.print()"><?php echo translate('Print Certificate'); ?></button>
        <a href="student_view_my_courses.php" class="btn btn-secondary"><?php echo translate('Back to My Courses'); ?></a>
    </div>
    
    <div class="certificate-container">
        <div class="certificate-header">
            <img src="assets/images/logo.png" alt="<?php echo translate('E-Training Logo'); ?>" class="logo" onerror="this.src='https://via.placeholder.com/80?text=OCEAN'">
            <div>
                <h1 class="certificate-title"><?php echo translate('Certificate of Completion'); ?></h1>
                <div class="certificate-subtitle"><?php echo translate('E-Training Platform'); ?></div>
            </div>
        </div>
        
        <div class="certificate-body">
            <p class="certificate-text"><?php echo translate('This is to certify that'); ?></p>
            <div class="student-name"><?php echo htmlspecialchars($certificate['student_name']); ?></div>
            <p class="certificate-text"><?php echo translate('has successfully completed the course'); ?></p>
            <div class="course-name"><?php echo htmlspecialchars($certificate['course_name']); ?></div>
            <p class="certificate-text"><?php echo translate('with a completion rate of 100%'); ?></p>
        </div>
        
        <div class="verification-section">
            <img src="<?php echo $qr_code_url; ?>" alt="<?php echo translate('Verification QR Code'); ?>" class="qr-code">
            <div class="verification-details">
                <div><?php echo translate('Verification Code:'); ?></div>
                <div class="verification-code"><?php echo $verification_code; ?></div>
                <div style="margin-top: 10px;"><?php echo translate('Verify this certificate at:'); ?></div>
                <div class="verification-url">https://<?php echo $_SERVER['HTTP_HOST']; ?>/Home/e_training/verify_cert.php</div>
                <div style="margin-top: 10px;"><?php echo translate('Or scan the QR code to verify'); ?></div>
            </div>
        </div>
        
        <div class="certificate-footer">
            <div class="signature-section">
                <div class="signature-line"></div>
                <div><?php echo translate('Course Instructor'); ?></div>
            </div>
            
            <div class="signature-section">
                <div class="signature-line"></div>
                <div><?php echo $completion_date; ?></div>
                <div><?php echo translate('Date of Completion'); ?></div>
            </div>
        </div>
    </div>
</body>
</html>
