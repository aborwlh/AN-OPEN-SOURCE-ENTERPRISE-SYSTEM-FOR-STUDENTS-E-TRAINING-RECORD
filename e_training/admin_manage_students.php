<?php include 'config.php'; ?>

<?php $page_title = "All Students";?>

<?php include 'header.php';?>

<?php
// if he not logged in ; redirect to the index page
if ($_SESSION ['user_type'] != "admin") {
	header ( "Location: index.php" );
}
?>

<?php
// get all information for the students
$students_query = "SELECT * FROM users WHERE role = 'student'";
$students_result = mysqli_query ($con,  $students_query ) or die ( 'error : ' . mysqli_error ($con) );
?>

<table width="100%" align="center" cellpadding=5 cellspacing=5>
	<tr>
		<th>Name</th>
		<th>Email</th>
		<th>Mobile</th>
		<th></th>
	</tr>
	<?php while ($student_row = mysqli_fetch_array($students_result)) { ?>
	<tr>
		<td><?php echo $student_row['name'];?></td>
		<td><?php echo $student_row['email']?></td>
		<td><?php echo $student_row['mobile']?></td>
		<td>
			<a href="admin_edit_student.php?id=<?php echo $student_row['user_id']?>">Edit</a> | 
			<a href="admin_delete_student.php?id=<?php echo $student_row['user_id']?>">Delete</a>
		</td>
	</tr>
	<?php } ?>
	
	<tr>
		<td colspan="4"><a href="admin_add_student.php">Add new student</a></td>
	</tr>
</table>

<?php include 'footer.php';?>