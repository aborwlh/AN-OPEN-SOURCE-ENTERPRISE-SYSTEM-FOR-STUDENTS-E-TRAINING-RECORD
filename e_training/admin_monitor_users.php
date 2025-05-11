<?php include 'config.php'; ?>

<?php $page_title = "Monitor Users";?>

<?php include 'header.php';?>

<?php
// if he not logged in ; redirect to the index page
if ($_SESSION ['user_type'] != "admin") {
	header ( "Location: index.php" );
}
?>

<?php
// get all information for the users login history
$historys_query = "SELECT login_history.*, users.name AS user_name FROM login_history LEFT JOIN users ON users.user_id = login_history.user_id";
$historys_result = mysqli_query ($con,  $historys_query ) or die ( 'error : ' . mysqli_error ($con) );
?>

<table width="100%" align="center" cellpadding=5 cellspacing=5>
	<tr>
		<th>User</th>
		<th>Login</th>
		<th>IP</th>
	</tr>
	<?php while ($history_row = mysqli_fetch_array($historys_result)) { ?>
        <tr>
            <td><?php echo $history_row['user_name'];?></td>
            <td><?php echo $history_row['login_time']?></td>
            <td><?php echo $history_row['ip_address']?></td>
        </tr>
	<?php } ?>
</table>

<?php include 'footer.php';?>