<?php include 'config.php'; ?>

<?php $page_title = translate("Add Student");?>

<?php include 'header.php';?>

<?php
// if he not logged in ; redirect to the index page
if ($_SESSION ['user_type'] != "admin") {
	header ( "Location: index.php" );
	exit;
}?>

<?php
if (isset ( $_POST ['btn-submit'] )) {
	$name = mysqli_real_escape_string($con, $_POST ['name']);
	$email = mysqli_real_escape_string($con, $_POST ['email']);
	$password = mysqli_real_escape_string($con, $_POST ['password']);
	$mobile = mysqli_real_escape_string($con, $_POST ['mobile']);
	
	$check_query = mysqli_query ($con, "SELECT * FROM users WHERE email = '$email' OR mobile = '$mobile'" ) or die ( 'error ' . mysqli_error ($con) );
	
	if (mysqli_num_rows ( $check_query ) != 0) {
		echo "<h3 style='text-align: center; padding-bottom: 10px; border-bottom: 1px solid #d9db5c'>" . translate("This email or mobile is already registered") . "</h3>";
	} else {
		// Hash the password before storing it
		$hashed_password = password_hash($password, PASSWORD_DEFAULT);
		
		if (mysqli_query ($con,  "INSERT INTO users (name, email, password, mobile, role) VALUES('$name', '$email', '$hashed_password', '$mobile', 'student')" )) {
			echo "<script>alert('" . translate('Added Successfully') . "');</script>";
		} else {
			echo "<script>alert('" . translate('Error in add') . "');</script>";
		}
	}
}
?>

<div class="contact" data-aos="fade-up">
	<form method="post" role="form" class="php-email-form">
		<div class="form-group mt-3">
			<?php echo translate('Name'); ?> <input type="text" class="form-control" name="name" placeholder="<?php echo translate('Name'); ?>" required />
		</div>
		<div class="form-group mt-3">
			<?php echo translate('Email'); ?> <input type="email" class="form-control" name="email" placeholder="<?php echo translate('Email'); ?>" required />
		</div>
		<div class="form-group mt-3">
			<?php echo translate('Password'); ?> <input type="password" class="form-control" name="password" placeholder="<?php echo translate('Password'); ?>" required />
		</div>
		<div class="form-group mt-3">
			<?php echo translate('Mobile'); ?> <input type="text" class="form-control" name="mobile" placeholder="<?php echo translate('Mobile'); ?>" title="0551234567" pattern="05[0-9]{8}" required />
		</div>
		<div class="text-center">
			<center><button type="submit" name="btn-submit"><?php echo translate('Add Student'); ?></button></center>
		</div>
	</form>
</div>

<?php include 'footer.php';?>
