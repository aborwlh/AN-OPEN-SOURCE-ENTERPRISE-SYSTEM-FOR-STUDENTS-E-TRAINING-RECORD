<?php include 'config.php'; ?>

<?php $page_title = translate("Update Profile");?>

<?php include 'header.php';?>

<?php
// if he not logged in ; redirect to the index page
if ($_SESSION ['user_type'] != "student") {
	header ( "Location: login.php" );
}
?>

<?php
if (isset ( $_POST ['email'] )) {
	$name = ($_POST ['name']);
	$email = ($_POST ['email']);
	$password = ($_POST ['password']);
	$mobile = ($_POST ['mobile']);
	$receive_notification = ($_POST ['receive_notification']);
	
	if (mysqli_query ( $con, "UPDATE users SET name = '$name', email = '$email', password = '$password', mobile = '$mobile', receive_notification = '$receive_notification' WHERE user_id = '$_SESSION[user_id]'" )) {
		echo "<script>alert('" . translate('Updated Successfully') . "');</script>";
	} else {
		echo "<script>alert('" . translate('Error in update') . "');</script>";
	}
}
?>

<?php
// if the student is loggedin
$query = "SELECT * FROM users WHERE user_id = $_SESSION[user_id]";
$student_result = mysqli_query ( $con, $query ) or die ( "can't run query because " . mysql_error () );

$student_row = mysqli_fetch_array ( $student_result );

if (mysqli_num_rows ( $student_result ) == 1) { ?>
	<div class="contact" data-aos="fade-up" id="contact">
		<div class="row justify-content-center" data-aos="fade-up" data-aos-delay="200">
			<div class="col-xl-9 col-lg-12 mt-4">
				<form method="post" role="form" class="php-email-form">
					<div class="form-group mt-3">
						<?php echo translate('Name'); ?> <input type="text" name="name" placeholder="<?php echo translate('Name'); ?>" required value="<?php echo $student_row['name'];?>" class="form-control" />
					</div>
					<div class="form-group mt-3">
						<?php echo translate('Email'); ?> <input type="email" name="email" placeholder="<?php echo translate('Email'); ?>" required value="<?php echo $student_row['email'];?>" class="form-control" />
					</div>
					<div class="form-group mt-3">
						<?php echo translate('Password'); ?> <input type="password" name="password" placeholder="<?php echo translate('New Password'); ?>" required value="" class="form-control" />
					</div>
					<div class="form-group mt-3">
						<?php echo translate('Mobile'); ?> <input type="text" name="mobile" placeholder="<?php echo translate('Mobile'); ?>" required value="<?php echo $student_row['mobile'];?>" class="form-control" pattern="05[0-9]{8}" />
					</div>
					<div class="form-group mt-3">
						<?php echo translate('Receive Notification'); ?>  
						<select name="receive_notification" required class="form-control">
							<option value="Yes" <?php if($student_row['receive_notification'] == "Yes") echo "selected";?>><?php echo translate('Yes'); ?></option>
							<option value="No" <?php if($student_row['receive_notification'] == "No") echo "selected";?>><?php echo translate('No'); ?></option>
						</select>
					</div>
					
					<br/>
					<div class="text-center">
						<center><button type="submit" name="btn-submit"><?php echo translate('Update profile'); ?></button></center>
					</div>
				</form>
			</div>
		</div>
	</div>
<?php
} // end of else; the student didn't loggedin
?>

<?php include 'footer.php'; ?>
