<?php include 'config.php'; ?>

<?php $page_title = translate("My Courses");?>

<?php include 'header.php';?>

<?php
// if user not logged in as instructor; redirect to the index page
if ($_SESSION['user_type'] != "instructor") {
    header("Location: index.php");
}
?>

<?php
// get all courses for this instructor
$instructor_id = $_SESSION['user_id'];
$courses_query = "SELECT c.*, cat.name as category_name 
                 FROM courses c 
                 JOIN category cat ON c.category_id = cat.category_id 
                 WHERE c.instructor_id = '$instructor_id'";
$courses_result = mysqli_query($con, $courses_query) or die('error: ' . mysqli_error($con));
?>

<table width="100%" align="center" cellpadding=5 cellspacing=5 class="admin-table">
    <tr>
        <th></th>
        <th><?php echo translate('Course Name'); ?></th>
        <th><?php echo translate('Description'); ?></th>
        <th><?php echo translate('Prerequisites'); ?></th>
        <th><?php echo translate('Start Date'); ?></th>
        <th><?php echo translate('Category'); ?></th>
        <th><?php echo translate('Actions'); ?></th>
    </tr>
    <?php while ($course_row = mysqli_fetch_array($courses_result)) { ?>
    <tr>
        <td><img src="assets/images/courses/<?php echo $course_row['img']; ?>" width="100" height="100" /></td>
        <td><?php echo translate($course_row['name']);?></td>
        <td><?php echo translate(substr($course_row['description'], 0, 100) . '...');?></td>
        <td><?php echo translate($course_row['prerequisites']);?></td>
        <td><?php echo $course_row['start_date'];?></td>
        <td><?php echo translate($course_row['category_name']);?></td>
        <td>
            <a href="instructor_edit_course.php?id=<?php echo $course_row['course_id']?>"><?php echo translate('Edit'); ?></a> | 
            <a href="instructor_delete_course.php?id=<?php echo $course_row['course_id']?>"><?php echo translate('Delete'); ?></a> | 
            <a href="instructor_manage_course_schedules.php?id=<?php echo $course_row['course_id']?>"><?php echo translate('Schedules'); ?></a> | 
            <a href="instructor_manage_course_materials.php?id=<?php echo $course_row['course_id']?>"><?php echo translate('Materials'); ?></a> | 
            <a href="instructor_view_course_feedback.php?id=<?php echo $course_row['course_id']?>"><?php echo translate('Feedback'); ?></a> | 
            <a href="instructor_manage_course_events.php?id=<?php echo $course_row['course_id']?>"><?php echo translate('Events'); ?></a> | 
            <a href="instructor_manage_course_enrollment_requests.php?id=<?php echo $course_row['course_id']?>"><?php echo translate('Enrollment Requests'); ?></a> | 
            <a href="instructor_view_course_students.php?id=<?php echo $course_row['course_id']?>"><?php echo translate('Students'); ?></a>
        </td>
    </tr>
    <?php } ?>
    
    <tr>
        <td colspan="7"><a href="instructor_add_course.php"><?php echo translate('Add new course'); ?></a></td>
    </tr>
</table>

<?php include 'footer.php';?>