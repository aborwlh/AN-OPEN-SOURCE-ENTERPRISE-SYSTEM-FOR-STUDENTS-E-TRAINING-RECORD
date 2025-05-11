<?php
include 'config.php';

// Check if user is logged in as student
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != "student") {
    header("Location: login.php");
    exit();
}

// Check if material ID is provided
if (!isset($_GET['id'])) {
    header("Location: student_view_my_courses.php");
    exit();
}

$material_id = mysqli_real_escape_string($con, $_GET['id']);
$student_id = $_SESSION['user_id'];

// Get material details
$material_query = "SELECT m.*, c.course_id 
                  FROM course_materials m
                  JOIN courses c ON m.course_id = c.course_id
                  WHERE m.material_id = '$material_id'";
$material_result = mysqli_query($con, $material_query);

if (mysqli_num_rows($material_result) == 0) {
    // Material not found
    echo "<script>alert('" . translate('Material not found') . "');</script>";
    echo "<meta http-equiv='Refresh' content='0; url=student_view_my_courses.php'>";
    exit();
}

$material = mysqli_fetch_assoc($material_result);
$course_id = $material['course_id'];

// Check if student is enrolled in this course
$enrollment_check = "SELECT * FROM course_enrollments 
                    WHERE student_id = '$student_id' AND course_id = '$course_id'";
$enrollment_result = mysqli_query($con, $enrollment_check);

if (mysqli_num_rows($enrollment_result) == 0) {
    // Student is not enrolled in this course
    echo "<script>alert('" . translate('You are not enrolled in this course') . "');</script>";
    echo "<meta http-equiv='Refresh' content='0; url=student_view_my_courses.php'>";
    exit();
}

// Log material access (if you have a material_access_log table)
$check_table = mysqli_query($con, "SHOW TABLES LIKE 'material_access_log'");
if (mysqli_num_rows($check_table) > 0) {
    $log_query = "INSERT INTO material_access_log (student_id, material_id, access_time) 
                 VALUES ('$student_id', '$material_id', NOW())";
    mysqli_query($con, $log_query);
}

// Handle different material types
if ($material['type'] == 'url') {
    // For URL type, redirect to the URL
    $url = $material['content'];
    
    // Ensure URL has http:// or https:// prefix
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        $url = "https://" . $url;
    }
    
    // Redirect to the URL
    header("Location: " . $url);
    exit();
} elseif ($material['type'] == 'text') {
    // For text type, display the content
    $page_title = translate($material['title']);
    include 'header.php';
?>

<style>
.material-container {
    max-width: 900px;
    margin: 30px auto;
    padding: 20px;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.material-header {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e9ecef;
}

.material-title {
    font-size: 24px;
    font-weight: bold;
    color: #04639b;
    margin-bottom: 10px;
}

.material-meta {
    display: flex;
    justify-content: space-between;
    color: #6c757d;
    font-size: 14px;
}

.material-content {
    line-height: 1.6;
    font-size: 16px;
}

.material-actions {
    margin-top: 30px;
    display: flex;
    justify-content: space-between;
}

.btn {
    padding: 8px 16px;
    border-radius: 4px;
    font-size: 14px;
    text-decoration: none;
    cursor: pointer;
    border: none;
}

.btn-primary {
    background-color: #04639b;
    color: white;
}

.btn-primary:hover {
    background-color: #034f7d;
}
</style>

<div class="material-container">
    <div class="material-header">
        <div class="material-title"><?php echo htmlspecialchars(translate($material['title'])); ?></div>
        <div class="material-meta">
            <div><?php echo translate('Added on'); ?> <?php echo date('M d, Y', strtotime($material['upload_date'])); ?></div>
        </div>
    </div>
    
    <div class="material-content">
        <?php echo nl2br(htmlspecialchars(translate($material['content']))); ?>
    </div>
    
    <div class="material-actions">
        <a href="student_view_materials.php?course_id=<?php echo $course_id; ?>" class="btn btn-primary">
            <?php echo translate('Back to Course Materials'); ?>
        </a>
    </div>
</div>

<?php
    include 'footer.php';
} else {
    // For file type, redirect back to materials page
    // (file downloads should be handled directly in student_view_materials.php)
    header("Location: student_view_materials.php?course_id=" . $course_id);
    exit();
}
?>
