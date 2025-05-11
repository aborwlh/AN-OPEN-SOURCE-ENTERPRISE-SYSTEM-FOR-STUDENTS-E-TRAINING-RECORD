<?php include 'config.php'; ?>

<?php $page_title = translate("Add New Course"); ?>

<?php include 'header.php'; ?>

<?php
// if not logged in as admin; redirect to the login page
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != "admin") {
    header("Location: login.php");
    exit();
}

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
        $img_name = '';
        if ($_FILES['img']['size'] > 0) {
            $img_tmp = $_FILES['img']['tmp_name'];
            $img_ext = strtolower(pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
            
            if (in_array($img_ext, $allowed_extensions)) {
                $img_name = 'course_' . time() . '.' . $img_ext;
                $upload_path = 'assets/images/courses/' . $img_name;
                
                if (!move_uploaded_file($img_tmp, $upload_path)) {
                    $error_message = translate('Failed to upload image');
                    $img_name = '';
                }
            } else {
                $error_message = translate('Invalid image format. Allowed formats: JPG, JPEG, PNG, GIF');
            }
        }
        
        if (!isset($error_message)) {
            // Insert course into database
            $insert_query = "INSERT INTO courses (name, description, start_date, prerequisites, category_id, instructor_id, img) 
                           VALUES ('$name', '$description', '$start_date', '$prerequisites', '$category_id', '$instructor_id', '$img_name')";
            
            if (mysqli_query($con, $insert_query)) {
                $course_id = mysqli_insert_id($con);
                $success_message = translate('Course added successfully');
                
                // Clear form data after successful submission
                unset($name, $description, $start_date, $prerequisites, $category_id, $instructor_id);
            } else {
                $error_message = translate('Error adding course') . ': ' . mysqli_error($con);
            }
        }
    }
}

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
                <h4><i class="fas fa-plus-circle"></i> <?php echo translate('Add New Course'); ?></h4>
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
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="description" class="form-label"><?php echo translate('Description'); ?> <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="5" required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="start_date" class="form-label"><?php echo translate('Start Date'); ?> <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo isset($start_date) ? $start_date : date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="prerequisites" class="form-label"><?php echo translate('Prerequisites'); ?></label>
                            <textarea class="form-control" id="prerequisites" name="prerequisites" rows="3"><?php echo isset($prerequisites) ? htmlspecialchars($prerequisites) : ''; ?></textarea>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="category_id" class="form-label"><?php echo translate('Category'); ?> <span class="text-danger">*</span></label>
                            <select class="form-control" id="category_id" name="category_id" required>
                                <option value=""><?php echo translate('Select Category'); ?></option>
                                <?php while ($category = mysqli_fetch_assoc($categories_result)): ?>
                                    <option value="<?php echo $category['category_id']; ?>" <?php echo (isset($category_id) && $category_id == $category['category_id']) ? 'selected' : ''; ?>>
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
                                    <option value="<?php echo $instructor['user_id']; ?>" <?php echo (isset($instructor_id) && $instructor_id == $instructor['user_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($instructor['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="img" class="form-label"><?php echo translate('Course Image'); ?></label>
                            <input type="file" class="form-control" id="img" name="img" accept="image/*">
                            <small class="text-muted"><?php echo translate('Recommended size: 800x600 pixels'); ?></small>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 text-center">
                    <button type="submit" name="btn-submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo translate('Add Course'); ?>
                    </button>
                    <button type="reset" class="btn btn-secondary ms-2">
                        <i class="fas fa-undo"></i> <?php echo translate('Reset'); ?>
                    </button>
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
