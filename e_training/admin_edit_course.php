<?php include 'config.php'; ?>

<?php $page_title = translate("Edit Course"); ?>

<?php include 'header.php'; ?>

<?php
// if not logged in as admin; redirect to the login page
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != "admin") {
    header("Location: login.php");
    exit();
}

// Check if course ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('" . translate('Course ID is required') . "'); window.location.href='admin_manage_courses.php';</script>";
    exit();
}

$course_id = mysqli_real_escape_string($con, $_GET['id']);

// Process form submission
if (isset($_POST['btn-submit'])) {
    // Get form data
    $name = mysqli_real_escape_string($con, $_POST['name']);
    $description = mysqli_real_escape_string($con, $_POST['description']);
    $start_date = mysqli_real_escape_string($con, $_POST['start_date']);
    $prerequisites = mysqli_real_escape_string($con, $_POST['prerequisites']);
    $category_id = mysqli_real_escape_string($con, $_POST['category_id']);
    $instructor_id = mysqli_real_escape_string($con, $_POST['instructor_id']);
    
    // Validate form data
    if (empty($name) || empty($description) || empty($start_date) || empty($category_id) || empty($instructor_id)) {
        $error_message = translate('Please fill in all required fields');
    } else {
        // Handle image upload
        $img_update = "";
        if ($_FILES['img']['size'] > 0) {
            $img_name = $_FILES['img']['name'];
            $img_tmp = $_FILES['img']['tmp_name'];
            $img_ext = strtolower(pathinfo($img_name, PATHINFO_EXTENSION));
            $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
            
            if (in_array($img_ext, $allowed_extensions)) {
                $new_img_name = 'course_' . time() . '.' . $img_ext;
                $upload_path = 'assets/images/courses/' . $new_img_name;
                
                if (move_uploaded_file($img_tmp, $upload_path)) {
                    $img_update = ", img = '$new_img_name'";
                } else {
                    $error_message = translate('Failed to upload image');
                }
            } else {
                $error_message = translate('Invalid image format. Allowed formats: JPG, JPEG, PNG, GIF');
            }
        }
        
        if (!isset($error_message)) {
            // Update course in database
            $update_query = "UPDATE courses SET 
                            name = '$name', 
                            description = '$description', 
                            start_date = '$start_date', 
                            prerequisites = '$prerequisites', 
                            category_id = '$category_id', 
                            instructor_id = '$instructor_id'
                            $img_update
                            WHERE course_id = '$course_id'";
            
            if (mysqli_query($con, $update_query)) {
                $success_message = translate('Course updated successfully');
            } else {
                $error_message = translate('Error updating course') . ': ' . mysqli_error($con);
            }
        }
    }
}

// Get course details
$course_query = "SELECT * FROM courses WHERE course_id = '$course_id'";
$course_result = mysqli_query($con, $course_query);

if (mysqli_num_rows($course_result) == 0) {
    echo "<script>alert('" . translate('Course not found') . "'); window.location.href='admin_manage_courses.php';</script>";
    exit();
}

$course = mysqli_fetch_assoc($course_result);

// Get categories for dropdown
$categories_query = "SELECT * FROM category ORDER BY name";
$categories_result = mysqli_query($con, $categories_query);

// Get instructors for dropdown
$instructors_query = "SELECT user_id, name FROM users WHERE role = 'instructor' ORDER BY name";
$instructors_result = mysqli_query($con, $instructors_query);
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-edit"></i> <?php echo translate('Edit Course'); ?></h4>
                <a href="admin_manage_courses.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> <?php echo translate('Back to Courses'); ?>
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="name" class="form-label"><?php echo translate('Course Name'); ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($course['name']); ?>" required>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="description" class="form-label"><?php echo translate('Description'); ?> <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="5" required><?php echo htmlspecialchars($course['description']); ?></textarea>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="start_date" class="form-label"><?php echo translate('Start Date'); ?> <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $course['start_date']; ?>" required>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="prerequisites" class="form-label"><?php echo translate('Prerequisites'); ?></label>
                            <textarea class="form-control" id="prerequisites" name="prerequisites" rows="3"><?php echo htmlspecialchars($course['prerequisites']); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="category_id" class="form-label"><?php echo translate('Category'); ?> <span class="text-danger">*</span></label>
                            <select class="form-control" id="category_id" name="category_id" required>
                                <option value=""><?php echo translate('Select Category'); ?></option>
                                <?php while ($category = mysqli_fetch_assoc($categories_result)): ?>
                                    <option value="<?php echo $category['category_id']; ?>" <?php echo ($category['category_id'] == $course['category_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="instructor_id" class="form-label"><?php echo translate('Instructor'); ?> <span class="text-danger">*</span></label>
                            <select class="form-control" id="instructor_id" name="instructor_id" required>
                                <option value=""><?php echo translate('Select Instructor'); ?></option>
                                <?php while ($instructor = mysqli_fetch_assoc($instructors_result)): ?>
                                    <option value="<?php echo $instructor['user_id']; ?>" <?php echo ($instructor['user_id'] == $course['instructor_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($instructor['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="img" class="form-label"><?php echo translate('Course Image'); ?></label>
                            <?php if (!empty($course['img'])): ?>
                                <div class="mb-2">
                                    <img src="assets/images/courses/<?php echo $course['img']; ?>" alt="<?php echo htmlspecialchars($course['name']); ?>" class="img-thumbnail" style="max-height: 150px;">
                                    <p class="text-muted small"><?php echo translate('Current image'); ?>: <?php echo $course['img']; ?></p>
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="img" name="img" accept="image/*">
                            <small class="text-muted"><?php echo translate('Leave empty to keep current image'); ?></small>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 text-center">
                    <button type="submit" name="btn-submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo translate('Update Course'); ?>
                    </button>
                    <a href="admin_manage_courses.php" class="btn btn-secondary ms-2">
                        <i class="fas fa-times"></i> <?php echo translate('Cancel'); ?>
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Add form validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    
    form.addEventListener('submit', function(event) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('is-invalid');
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        if (!isValid) {
            event.preventDefault();
            alert('<?php echo translate('Please fill in all required fields'); ?>');
        }
    });
});
</script>

<?php include 'footer.php'; ?>
