<?php include 'config.php'; ?>

<?php 
// Get course info for the page title
$course_query = "SELECT name FROM courses WHERE course_id = '$_GET[id]'";
$course_result = mysqli_query($con, $course_query) or die('error: ' . mysqli_error($con));
$course_row = mysqli_fetch_array($course_result);
$page_title = translate("Enrollment Requests for") . " [ " . $course_row['name'] . " ]";
?>

<?php include 'header.php';?>

<?php
// if user not logged in as instructor; redirect to the index page
if ($_SESSION['user_type'] != "instructor") {
    header("Location: index.php");
}

// Check if the course belongs to this instructor
$instructor_id = $_SESSION['user_id'];
$course_check_query = "SELECT * FROM courses WHERE course_id = '$_GET[id]' AND instructor_id = '$instructor_id'";
$course_check_result = mysqli_query($con, $course_check_query) or die('error: ' . mysqli_error($con));

if (mysqli_num_rows($course_check_result) == 0) {
    echo "<script>alert('" . translate('You do not have permission to access this course') . "');</script>";
    echo "<meta http-equiv='Refresh' content='0; url=instructor_manage_courses.php'>";
    exit;
}
?>

<?php
// Handle approve/reject requests
if (isset($_GET['action']) && isset($_GET['request_id'])) {
    $request_id = $_GET['request_id'];
    $action = $_GET['action'];
    
    if ($action == 'approve') {
        // Get the student_id from the request
        $request_query = "SELECT student_id FROM enrollment_requests WHERE request_id = '$request_id'";
        $request_result = mysqli_query($con, $request_query) or die('error: ' . mysqli_error($con));
        $request_data = mysqli_fetch_array($request_result);
        $student_id = $request_data['student_id'];
        
        // First update the request status
        $update_query = "UPDATE enrollment_requests SET status = 'approved' WHERE request_id = '$request_id'";
        
        if (mysqli_query($con, $update_query)) {
            // Then create an enrollment record
            $enroll_query = "INSERT INTO course_enrollments (course_id, student_id, enrollment_date) 
                           VALUES ('$_GET[id]', '$student_id', NOW())";
            
            if (mysqli_query($con, $enroll_query)) {
                // Then create an progress record
                $progress_query = "INSERT INTO student_progress (course_id, student_id) 
                VALUES ('$_GET[id]', '$student_id')";
                mysqli_query($con, $progress_query);

                echo "<script>alert('" . translate('Request approved and student enrolled successfully') . "');</script>";
            } else {
                echo "<script>alert('" . translate('Error in enrolling student: ') . mysqli_error($con) . "');</script>";
            }
        } else {
            echo "<script>alert('" . translate('Error in approving request: ') . mysqli_error($con) . "');</script>";
        }
    } elseif ($action == 'reject') {
        $update_query = "UPDATE enrollment_requests SET status = 'rejected' WHERE request_id = '$request_id'";
        
        if (mysqli_query($con, $update_query)) {
            echo "<script>alert('" . translate('Request rejected successfully') . "');</script>";
        } else {
            echo "<script>alert('" . translate('Error in rejecting request: ') . mysqli_error($con) . "');</script>";
        }
    }
}

// Get all enrollment requests for this course
$requests_query = "SELECT er.*, u.name as student_name, u.email as student_email 
                  FROM enrollment_requests er 
                  JOIN users u ON er.student_id = u.user_id 
                  WHERE er.course_id = '$_GET[id]' 
                  ORDER BY CASE er.status 
                    WHEN 'pending' THEN 1 
                    WHEN 'approved' THEN 2 
                    WHEN 'rejected' THEN 3 
                  END, er.created_at DESC";
$requests_result = mysqli_query($con, $requests_query) or die('error: ' . mysqli_error($con));
?>

<div class="mt-4">
    <table width="100%" align="center" cellpadding=5 cellspacing=5 class="admin-table">
        <tr>
            <th><?php echo translate('Student'); ?></th>
            <th><?php echo translate('Email'); ?></th>
            <th><?php echo translate('Request Date'); ?></th>
            <th><?php echo translate('Status'); ?></th>
            <th><?php echo translate('Actions'); ?></th>
        </tr>
        <?php if (mysqli_num_rows($requests_result) > 0) { ?>
            <?php while ($request = mysqli_fetch_array($requests_result)) { ?>
                <tr>
                    <td><?php echo $request['student_name']; ?></td>
                    <td><?php echo $request['student_email']; ?></td>
                    <td><?php echo $request['created_at']; ?></td>
                    <td>
                        <span class="status-badge status-<?php echo $request['status']; ?>">
                            <?php echo ucfirst(translate($request['status'])); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($request['status'] == 'pending') { ?>
                            <a href="instructor_manage_course_enrollment_requests.php?id=<?php echo $_GET['id']; ?>&action=approve&request_id=<?php echo $request['request_id']; ?>"
                               onclick="return confirm('<?php echo translate('Are you sure you want to approve this enrollment request?'); ?>');"><?php echo translate('Approve'); ?></a> | 
                            <a href="instructor_manage_course_enrollment_requests.php?id=<?php echo $_GET['id']; ?>&action=reject&request_id=<?php echo $request['request_id']; ?>"
                               onclick="return confirm('<?php echo translate('Are you sure you want to reject this enrollment request?'); ?>');"><?php echo translate('Reject'); ?></a>
                        <?php } else { ?>
                            -
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
        <?php } else { ?>
            <tr>
                <td colspan="6" align="center"><?php echo translate('No enrollment requests found for this course.'); ?></td>
            </tr>
        <?php } ?>
    </table>
</div>

<?php include 'footer.php';?>