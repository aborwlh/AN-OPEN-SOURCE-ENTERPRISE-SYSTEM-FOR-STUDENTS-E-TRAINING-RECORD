<?php include 'config.php'; ?>

<?php $page_title = "Delete Category";?>

<?php include 'header.php';?>

<?php
// if he not logged in ; redirect to the index page
if ($_SESSION ['user_type'] != "admin") {
	header ( "Location: index.php" );
}
?>

<?php
// delete the user
mysqli_query ($con,  "DELETE FROM category WHERE category_id = '$_GET[id]'" ) or die ( 'error ' . mysqli_error ($con) );

// if there is affected rows in the database;
if (mysqli_affected_rows ( $con ) == 1) {
	echo "<script>alert('Deleted successfully');</script>";
	
	echo "<meta http-equiv='Refresh' content='0; url=admin_manage_categories.php'>";
} else {
	echo "<script>alert('Error in delete');</script>";
	echo "<meta http-equiv='Refresh' content='0; url=admin_manage_categories.php'>";
}
?>

<?php include 'footer.php';?>