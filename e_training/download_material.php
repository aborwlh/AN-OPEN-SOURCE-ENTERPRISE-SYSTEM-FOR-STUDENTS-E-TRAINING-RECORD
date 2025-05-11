<?php
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get material ID from URL
$material_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($material_id <= 0) {
    die("Invalid material ID");
}

// Get material details
$material_query = "SELECT cm.*, c.name as course_name 
                  FROM course_materials cm
                  JOIN courses c ON cm.course_id = c.course_id
                  WHERE cm.material_id = '$material_id'";
$material_result = mysqli_query($con, $material_query);

if (mysqli_num_rows($material_result) == 0) {
    die("Material not found");
}

$material = mysqli_fetch_assoc($material_result);
$course_id = $material['course_id'];

// Check if user has access to this material
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Admin and instructors have access to all materials
if ($user_type == 'admin') {
    // Admin has access to all materials
    $has_access = true;
} else if ($user_type == 'instructor') {
    // Check if instructor owns the course
    $instructor_check = "SELECT * FROM courses WHERE course_id = '$course_id' AND instructor_id = '$user_id'";
    $instructor_result = mysqli_query($con, $instructor_check);
    $has_access = (mysqli_num_rows($instructor_result) > 0);
} else {
    // Check if student is enrolled in the course
    $enrollment_check = "SELECT * FROM course_enrollments WHERE student_id = '$user_id' AND course_id = '$course_id'";
    $enrollment_result = mysqli_query($con, $enrollment_check);
    $has_access = (mysqli_num_rows($enrollment_result) > 0);
}

if (!$has_access) {
    die("You do not have permission to access this material");
}

// Get file path
$file_path = 'assets\course_materials/' . $material['file_path'];

// Check if file exists
if (!file_exists($file_path)) {
   die("File not found: " . $file_path);
}




// Get file information
$file_name = basename($material['file_path']);
$file_size = filesize($file_path);
$file_type = mime_content_type($file_path);

// Set appropriate headers for download
header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: private", false);
header("Content-Type: $file_type");
header("Content-Disposition: attachment; filename=\"$file_name\"");
header("Content-Transfer-Encoding: binary");
header("Content-Length: $file_size");

// Output file content
readfile($file_path);
exit;
?>
