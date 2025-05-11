<?php include 'config.php'; ?>

<?php $page_title = translate("Update Student");?>

<?php include 'header.php';?>

<?php
// if he not logged in ; redirect to the index page
if ($_SESSION ['user_type'] != "admin") {
	header ( "Location: index.php" );
}

if (isset ( $_POST ['btn-submit'] )) {
	$name = mysqli_real_escape_string($con, $_POST ['name']);
	$email = mysqli_real_escape_string($con, $_POST ['email']);
	$password = mysqli_real_escape_string($con, $_POST ['password']);
	$mobile = mysqli_real_escape_string($con, $_POST ['mobile']);
	
	// Only hash and update password if it's not empty
	if (!empty($password)) {
		$hashed_password = password_hash($password, PASSWORD_DEFAULT);
		$update_query = "UPDATE users SET name = '$name', password = '$hashed_password', email = '$email', mobile = '$mobile' WHERE user_id = $_GET[id]";
	} else {
		// If password field is empty, don't update the password
		$update_query = "UPDATE users SET name = '$name', email = '$email', mobile = '$mobile' WHERE user_id = $_GET[id]";
	}
	
	if (mysqli_query($con, $update_query)) {
		echo "<script>alert('" . translate('Updating successfully') . "');</script>";
	} else {
		echo "<script>alert('" . translate('Error in updating') . "');</script>";
	}
}
?>

<?php
// if the user is loggedin
$query = "SELECT * FROM users WHERE user_id = '" . mysqli_real_escape_string($con, $_GET['id']) . "'";
$user_result = mysqli_query($con, $query) or die("can't run query because " . mysqli_error($con));

$user_row = mysqli_fetch_array($user_result);

if (mysqli_num_rows($user_result) == 1) { ?>
<div class="contact" data-aos="fade-up">
	<form method="post" role="form" class="php-email-form">
		<div class="form-group mt-3">
			<?php echo translate('Name'); ?>
			<input type="text" class="form-control" name="name" required value="<?php echo htmlspecialchars($user_row['name']);?>" />
		</div>
		<div class="form-group mt-3">
			<?php echo translate('Email'); ?>
			<input type="email" class="form-control" name="email" required value="<?php echo htmlspecialchars($user_row['email']);?>"  />
		</div>
		<div class="form-group mt-3">
			<?php echo translate('Password'); ?>
			<input type="password" class="form-control" name="password" placeholder="<?php echo translate('Leave empty to keep current password'); ?>" />
			<small class="text-muted"><?php echo translate('Enter a new password only if you want to change it'); ?></small>
		</div>
		<div class="form-group mt-3">
			<?php echo translate('Mobile'); ?>
			<input type="text" name="mobile" class="form-control" required title="0551234567" value="<?php echo htmlspecialchars($user_row['mobile']);?>" pattern="05[0-9]{8}" maxlength="10"/>
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
