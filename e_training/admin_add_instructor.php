<?php include 'config.php'; ?>

<?php $page_title = "Add Instructor";?>

<?php include 'header.php';?>

<?php
// if he not logged in ; redirect to the index page
if ($_SESSION ['user_type'] != "admin") {
	header ( "Location: index.php" );
}?>

<?php
if (isset ( $_POST ['btn-submit'] )) {
	$name = ($_POST ['name']);
	$email = ($_POST ['email']);
	$password = (($_POST ['password']));
	$mobile = (($_POST ['mobile']));
	
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
			<?php echo translate('Name'); ?> <input type="text" class="form-control" name="name" placeholder="Name" required />
		</div>
		<div class="form-group mt-3">
			<?php echo translate('Email'); ?> <input type="email" class="form-control" name="email" placeholder="Email" required />
		</div>
		<div class="form-group mt-3">
			<?php echo translate('Password'); ?> <input type="password" class="form-control" name="password" placeholder="Password" required />
		</div>
		<div class="form-group mt-3">
			<?php echo translate('Mobile'); ?> <input type="text" class="form-control" name="mobile" placeholder="Mobile" title="0551234567" pattern="05[0-9]{8}" required />
		</div>
		<div class="text-center">
			<center><button type="submit" name="btn-submit"><?php echo translate('Add Instructor'); ?></button></center>
		</div>
	</form>
</div>

<?php include 'footer.php';?>