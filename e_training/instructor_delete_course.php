<?php include 'config.php'; ?>

<?php $page_title = "Delete Course";?>

<?php include 'header.php';?>

<?php
// if user not logged in as instructor; redirect to the index page
if ($_SESSION['user_type'] != "instructor") {
    header("Location: index.php");
}
?>

<?php
// First check if the course belongs to this instructor
$instructor_id = $_SESSION['user_id'];
$course_check_query = "SELECT * FROM courses WHERE course_id = '$_GET[id]' AND instructor_id = '$instructor_id'";
$course_check_result = mysqli_query($con, $course_check_query) or die('error: ' . mysqli_error($con));

if (mysqli_num_rows($course_check_result) == 0) {
    echo "<script>alert('You do not have permission to delete this course');</script>";
    echo "<meta http-equiv='Refresh' content='0; url=instructor_manage_courses.php'>";
    exit;
}

// Get the image filename before deleting the course
$course_data = mysqli_fetch_array($course_check_result);
$img_filename = $course_data['img'];

// Delete the course
mysqli_query($con, "DELETE FROM courses WHERE course_id = '$_GET[id]'") or die('error ' . mysqli_error($con));

// If there is affected rows in the database;
if (mysqli_affected_rows($con) == 1) {
    // Delete the course image if it exists
    if(!empty($img_filename) && file_exists("assets/images/courses/" . $img_filename)) {
        unlink("assets/images/courses/" . $img_filename);
    }
    
    echo "<script>alert('Course deleted successfully');</script>";
    echo "<meta http-equiv='Refresh' content='0; url=instructor_manage_courses.php'>";
} else {
    echo "<script>alert('Error in delete');</script>";
    echo "<meta http-equiv='Refresh' content='0; url=instructor_manage_courses.php'>";
}
?>

<?php include 'footer.php';?>
