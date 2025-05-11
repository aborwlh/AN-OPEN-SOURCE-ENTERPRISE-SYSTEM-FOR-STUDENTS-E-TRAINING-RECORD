<?php include 'config.php'; ?>

<?php $page_title = "Update Instructor";?>

<?php include 'header.php';?>

<?php
// if he not logged in ; redirect to the index page
if ($_SESSION ['user_type'] != "admin") {
	header ( "Location: index.php" );
}

if (isset ( $_POST ['btn-submit'] )) {
	$name = ($_POST ['name']);
	$email = ($_POST ['email']);
	$password = ($_POST ['password']);
	$mobile = (($_POST ['mobile']));
	
	if (mysqli_query ($con,  "UPDATE users SET name = '$name', password = '$password', email = '$email', mobile = '$mobile' WHERE user_id = $_GET[id]" )) {
		echo "<script>alert('Updating successfully');</script>";
	} else {
		echo "<script>alert('Error in updating');</script>";
	}
}
?>

<?php
// if the user is loggedin
$query = "SELECT * FROM users WHERE user_id = '$_GET[id]'";
$user_result = mysqli_query ($con,  $query ) or die ( "can't run query because " . mysqli_error ($con) );

$user_row = mysqli_fetch_array ( $user_result );

if (mysqli_num_rows ( $user_result ) == 1) { ?>
<div class="contact" data-aos="fade-up">
	<form method="post" role="form" class="php-email-form">
		<div class="form-group mt-3">
			<?php echo translate('Name'); ?>
			<input type="text" class="form-control" name="name" required value="<?php echo $user_row['name'];?>" />
		</div>
		<div class="form-group mt-3">
			<?php echo translate('Email'); ?> 
			<input type="email" class="form-control" name="email" required value="<?php echo $user_row['email'];?>"  />
		</div>
		<div class="form-group mt-3">
			<?php echo translate('Password'); ?> 
			<input type="password" class="form-control" name="password" required value=""  />
		</div>
		<div class="form-group mt-3">
			<?php echo translate('Mobile'); ?>
			<input type="text" name="mobile" class="form-control" required title="0551234567" value="<?php echo $user_row['mobile'];?>" pattern="05[0-9]{8}" maxlength="10"/>
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