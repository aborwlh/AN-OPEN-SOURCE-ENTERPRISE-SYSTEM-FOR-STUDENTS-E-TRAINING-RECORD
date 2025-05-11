<?php include 'config.php'; ?>

<?php $page_title = "All Instructors";?>

<?php include 'header.php';?>

<?php
// if he not logged in ; redirect to the index page
if ($_SESSION ['user_type'] != "admin") {
	header ( "Location: index.php" );
}
?>

<?php
// get all information for the instructors
$instructors_query = "SELECT * FROM users WHERE role = 'instructor'";
$instructors_result = mysqli_query ($con,  $instructors_query ) or die ( 'error : ' . mysqli_error ($con) );
?>

<table width="100%" align="center" cellpadding=5 cellspacing=5>
	<tr>
		<th>Name</th>
		<th>Email</th>
		<th>Mobile</th>
		<th></th>
	</tr>
	<?php while ($instructor_row = mysqli_fetch_array($instructors_result)) { ?>
	<tr>
		<td><?php echo $instructor_row['name'];?></td>
		<td><?php echo $instructor_row['email']?></td>
		<td><?php echo $instructor_row['mobile']?></td>
		<td>
			<a href="admin_edit_instructor.php?id=<?php echo $instructor_row['user_id']?>">Edit</a> | 
			<a href="admin_delete_instructor.php?id=<?php echo $instructor_row['user_id']?>">Delete</a>
		</td>
	</tr>
	<?php } ?>
	
	<tr>
		<td colspan="4"><a href="admin_add_instructor.php">Add new instructor</a></td>
	</tr>
</table>

<?php include 'footer.php';?>