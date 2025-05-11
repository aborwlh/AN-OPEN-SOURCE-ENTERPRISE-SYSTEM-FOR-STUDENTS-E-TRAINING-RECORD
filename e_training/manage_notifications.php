<?php include 'config.php'; ?>

<?php $page_title = translate("Manage Notifications"); ?>

<?php include 'header.php'; ?>

<?php
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle delete notification
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $notification_id = mysqli_real_escape_string($con, $_GET['id']);
    $delete_query = "DELETE FROM notifications WHERE notification_id = '$notification_id' AND user_id = '$user_id'";
    mysqli_query($con, $delete_query) or die('error: ' . mysqli_error($con));
    echo "<script>alert('" . translate('Notification deleted') . "');</script>";
    echo "<meta http-equiv='Refresh' content='0; url=manage_notifications.php'>";
    exit;
}

// Handle delete all notifications
if (isset($_GET['action']) && $_GET['action'] == 'delete_all') {
    $delete_query = "DELETE FROM notifications WHERE user_id = '$user_id'";
    mysqli_query($con, $delete_query) or die('error: ' . mysqli_error($con));
    echo "<script>alert('" . translate('All notifications deleted') . "');</script>";
    echo "<meta http-equiv='Refresh' content='0; url=manage_notifications.php'>";
    exit;
}

// Handle notification settings update
if (isset($_POST['update_settings'])) {
    $receive_notification = isset($_POST['receive_notification']) ? 'Yes' : 'No';
    $update_query = "UPDATE users SET receive_notification = '$receive_notification' WHERE user_id = '$user_id'";
    mysqli_query($con, $update_query) or die('error: ' . mysqli_error($con));
    echo "<script>alert('" . translate('Notification settings updated') . "');</script>";
}

// Get user's current notification settings
$settings_query = "SELECT receive_notification FROM users WHERE user_id = '$user_id'";
$settings_result = mysqli_query($con, $settings_query) or die('error: ' . mysqli_error($con));
$settings = mysqli_fetch_array($settings_result);

// Get all notifications for the current user
$notifications_query = "SELECT * FROM notifications WHERE user_id = '$user_id' ORDER BY date DESC";
$notifications_result = mysqli_query($con, $notifications_query) or die('error: ' . mysqli_error($con));
?>

<div class="container mt-4" data-aos="fade-up">
    <div class="row">
        <div class="col-md-8">
            <h2><?php echo translate('Your Notifications'); ?></h2>
            
            <?php if (mysqli_num_rows($notifications_result) > 0) { ?>
                <div class="mb-3">
                    <a href="manage_notifications.php?action=delete_all" class="btn btn-sm btn-danger" onclick="return confirm('<?php echo translate('Are you sure you want to delete all notifications?'); ?>');"><?php echo translate('Delete All'); ?></a>
                </div>
                
                <div class="list-group">
                    <?php while ($notification = mysqli_fetch_array($notifications_result)) { ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1"><?php echo translate('Notification'); ?></h5>
                                <small>
                                    <?php echo date('M j, Y g:i A', strtotime($notification['date'])); ?>
                                </small>
                            </div>
                            <p class="mb-1"><?php echo htmlspecialchars(translate($notification['content'])); ?></p>
                            <div class="mt-2">
                                <a href="manage_notifications.php?action=delete&id=<?php echo $notification['notification_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('<?php echo translate('Are you sure you want to delete this notification?'); ?>');"><?php echo translate('Delete'); ?></a>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            <?php } else { ?>
                <div class="alert alert-info">
                    <?php echo translate('You have no notifications.'); ?>
                </div>
            <?php } ?>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h4><?php echo translate('Notification Settings'); ?></h4>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="receive_notification" name="receive_notification" <?php echo ($settings['receive_notification'] == 'Yes') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="receive_notification"><?php echo translate('Receive Email Notifications'); ?></label>
                        </div>
                        <p class="text-muted small">
                            <?php echo translate('When enabled, you will receive email notifications for important updates related to your courses, events, and other activities.'); ?>
                        </p>
                        <button type="submit" name="update_settings" class="btn btn-primary"><?php echo translate('Save Settings'); ?></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Add some basic styling if Bootstrap is not available */
.container {
    max-width: 1140px;
    margin: 0 auto;
    padding: 0 15px;
}
.row {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -15px;
}
.col-md-8 {
    flex: 0 0 66.666667%;
    max-width: 66.666667%;
    padding: 0 15px;
}
.col-md-4 {
    flex: 0 0 33.333333%;
    max-width: 33.333333%;
    padding: 0 15px;
}
.mt-4 {
    margin-top: 1.5rem;
}
.mb-3 {
    margin-bottom: 1rem;
}
.mt-2 {
    margin-top: 0.5rem;
}
.mb-1 {
    margin-bottom: 0.25rem;
}
.card {
    position: relative;
    display: flex;
    flex-direction: column;
    min-width: 0;
    word-wrap: break-word;
    background-color: #fff;
    background-clip: border-box;
    border: 1px solid rgba(0,0,0,.125);
    border-radius: 0.25rem;
}
.card-header {
    padding: 0.75rem 1.25rem;
    margin-bottom: 0;
    background-color: rgba(0,0,0,.03);
    border-bottom: 1px solid rgba(0,0,0,.125);
}
.card-body {
    flex: 1 1 auto;
    padding: 1.25rem;
}
.list-group {
    display: flex;
    flex-direction: column;
    padding-left: 0;
    margin-bottom: 0;
}
.list-group-item {
    position: relative;
    display: block;
    padding: 0.75rem 1.25rem;
    margin-bottom: -1px;
    background-color: #fff;
    border: 1px solid rgba(0,0,0,.125);
}
.d-flex {
    display: flex !important;
}
.justify-content-between {
    justify-content: space-between !important;
}
.btn {
    display: inline-block;
    font-weight: 400;
    text-align: center;
    white-space: nowrap;
    vertical-align: middle;
    user-select: none;
    border: 1px solid transparent;
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    line-height: 1.5;
    border-radius: 0.25rem;
    transition: color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out;
}
.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    line-height: 1.5;
    border-radius: 0.2rem;
}
.btn-primary {
    color: #fff;
    background-color: #007bff;
    border-color: #007bff;
}
.btn-danger {
    color: #fff;
    background-color: #dc3545;
    border-color: #dc3545;
}
.btn-outline-danger {
    color: #dc3545;
    background-color: transparent;
    background-image: none;
    border-color: #dc3545;
}
.alert {
    position: relative;
    padding: 0.75rem 1.25rem;
    margin-bottom: 1rem;
    border: 1px solid transparent;
    border-radius: 0.25rem;
}
.alert-info {
    color: #0c5460;
    background-color: #d1ecf1;
    border-color: #bee5eb;
}
.form-check {
    position: relative;
    display: block;
    padding-left: 1.25rem;
}
.form-check-input {
    position: absolute;
    margin-top: 0.3rem;
    margin-left: -1.25rem;
}
.form-check-label {
    margin-bottom: 0;
}
.text-muted {
    color: #6c757d !important;
}
.small {
    font-size: 80%;
    font-weight: 400;
}
</style>

<?php include 'footer.php'; ?>