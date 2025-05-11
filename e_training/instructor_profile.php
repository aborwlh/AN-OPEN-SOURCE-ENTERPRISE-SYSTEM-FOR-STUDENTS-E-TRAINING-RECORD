<?php include 'config.php'; ?>

<?php $page_title = "Update Profile";?>

<?php include 'header.php';?>

<?php
// if he not logged in ; redirect to the index page
if ($_SESSION ['user_type'] != "instructor") {
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
	
    // Hash the password using bcrypt
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    
	if (mysqli_query ( $con, "UPDATE users SET name = '$name', email = '$email', password = '$hashed_password', mobile = '$mobile', receive_notification = '$receive_notification' WHERE user_id = '$_SESSION[user_id]'" )) {
		echo "<script>alert('Updated Successfully');</script>";
	} else {
		echo "<script>alert('Error in update');</script>";
	}
}
?>

<?php
// if the instructor is loggedin
$query = "SELECT * FROM users WHERE user_id = $_SESSION[user_id]";
$instructor_result = mysqli_query ( $con, $query ) or die ( "can't run query because " . mysql_error () );

$instructor_row = mysqli_fetch_array ( $instructor_result );

if (mysqli_num_rows ( $instructor_result ) == 1) { ?>
	<div class="contact" data-aos="fade-up" id="contact">
		<div class="row justify-content-center" data-aos="fade-up" data-aos-delay="200">
			<div class="col-xl-9 col-lg-12 mt-4">
				<form method="post" role="form" class="php-email-form">
					<div class="form-group mt-3">
						<?php echo translate('Name'); ?> <input type="text" name="name" placeholder="Name" required value="<?php echo $instructor_row['name'];?>" class="form-control" />
					</div>
					<div class="form-group mt-3">
						<?php echo translate('Email'); ?> <input type="email" name="email" placeholder="Email" required value="<?php echo $instructor_row['email'];?>" class="form-control" />
					</div>
					<div class="form-group mt-3">
						<?php echo translate('Password'); ?> <input type="password" name="password" placeholder="New Password" required value="" class="form-control" />
					</div>
					<div class="form-group mt-3">
						<?php echo translate('Mobile'); ?> <input type="text" name="mobile" placeholder="Mobile" required value="<?php echo $instructor_row['mobile'];?>" class="form-control" pattern="05[0-9]{8}" />
					</div>
					<div class="form-group mt-3">
						<?php echo translate('Receive Notification'); ?> 
						<select name="receive_notification" required class="form-control">
							<option value="Yes" <?php if($instructor_row['receive_notification'] == "Yes") echo "selected";?>>Yes</option>
							<option value="No" <?php if($instructor_row['receive_notification'] == "No") echo "selected";?>>No</option>
						</select>
					</div>
					
					<br/>
					<div class="text-center">
						<center><button type="submit" name="btn-submit"><?php echo translate('Update profile'); ?> </button></center>
					</div>
				</form>
			</div>
		</div>
	</div>
<?php
} // end of else; the instructor didn't loggedin
?>

<?php include 'footer.php'; ?>