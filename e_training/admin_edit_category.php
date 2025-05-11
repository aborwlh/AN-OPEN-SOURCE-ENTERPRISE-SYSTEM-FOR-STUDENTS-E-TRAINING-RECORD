<?php include 'config.php'; ?>

<?php $page_title = "Update Category";?>

<?php include 'header.php';?>

<?php
// if he not logged in ; redirect to the index page
if ($_SESSION ['user_type'] != "admin") {
	header ( "Location: index.php" );
}

if (isset ( $_POST ['btn-submit'] )) {
	$name = ($_POST ['name']);
	
	if (mysqli_query ($con,  "UPDATE category SET name = '$name' WHERE category_id = $_GET[id]" )) {
		echo "<script>alert('Updating successfully');</script>";
	} else {
		echo "<script>alert('Error in updating');</script>";
	}
}
?>

<?php
// if the category is loggedin
$query = "SELECT * FROM category WHERE category_id = '$_GET[id]'";
$category_result = mysqli_query ($con,  $query ) or die ( "can't run query because " . mysqli_error ($con) );

$category_row = mysqli_fetch_array ( $category_result );

if (mysqli_num_rows ( $category_result ) == 1) { ?>
<div class="contact" data-aos="fade-up">
	<form method="post" role="form" class="php-email-form">
		<div class="form-group mt-3">
			<?php echo translate('Name'); ?>
			<input type="text" class="form-control" name="name" required value="<?php echo $category_row['name'];?>" />
		</div>
		<div class="text-center">
			<center><button type="submit" name="btn-submit"><?php echo translate('Update'); ?></button></center>
		</div>
	</form>
</div>
<?php
} // end of else; 
?>

<?php include 'footer.php'; ?>