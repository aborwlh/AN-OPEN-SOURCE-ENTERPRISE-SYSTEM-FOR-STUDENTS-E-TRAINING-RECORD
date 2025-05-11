<?php include 'config.php'; ?>

<?php 
// Get material info for the page title
$material_query = "SELECT m.*, c.name as course_name, c.instructor_id 
                  FROM course_materials m 
                  JOIN courses c ON m.course_id = c.course_id 
                  WHERE m.material_id = '$_GET[id]'";
$material_result = mysqli_query($con, $material_query) or die('error: ' . mysqli_error($con));

if (mysqli_num_rows($material_result) == 0) {
    header("Location: instructor_manage_courses.php");
    exit;
}

$material = mysqli_fetch_array($material_result);
$page_title = $material['title'];
?>

<?php include 'header.php';?>

<?php
// if user not logged in as instructor; redirect to the index page
if ($_SESSION['user_type'] != "instructor") {
    header("Location: index.php");
}

// Check if the material belongs to this instructor's course
$instructor_id = $_SESSION['user_id'];
if ($material['instructor_id'] != $instructor_id) {
    echo "<script>alert('You do not have permission to access this material');</script>";
    echo "<meta http-equiv='Refresh' content='0; url=instructor_manage_courses.php'>";
    exit;
}
?>

<div class="container mt-4">
    <h2><?php echo $material['title']; ?></h2>
    <p><strong>Course:</strong> <?php echo $material['course_name']; ?></p>
    <p><strong>Description:</strong> <?php echo $material['description']; ?></p>
    <p><strong>Upload Date:</strong> <?php echo date('Y-m-d', strtotime($material['upload_date'])); ?></p>
    
    <div class="content mt-4">
        <?php echo nl2br($material['content']); ?>
    </div>
</div>

<?php include 'footer.php';?>
