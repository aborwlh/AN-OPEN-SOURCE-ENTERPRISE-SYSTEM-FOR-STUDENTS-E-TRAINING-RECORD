<?php include 'config.php'; ?>



<?php include 'header.php';?>

<?php
// if not logged in as admin; redirect to the index page
if ($_SESSION['user_type'] != "admin") {
    header("Location: index.php");
    exit();
}

// Initialize message variables
$success_message = "";
$error_message = "";

// Process form submission
if (isset($_POST['btn-submit'])) {
    $name = trim($_POST['name']);
    
    if (empty($name)) {
        $error_message = "Category name cannot be empty";
    } else {
        // Check if category exists
        $check_query = mysqli_query($con, "SELECT * FROM category WHERE name = '$name'") or die('error ' . mysqli_error($con));
        
        if (mysqli_num_rows($check_query) != 0) {
            $error_message = "This category already exists";
        } else {
            // Add new category
            if (mysqli_query($con, "INSERT INTO category (name) VALUES('$name')")) {
                $success_message = "Category added successfully";
                // Clear form input after successful submission
                $name = "";
            } else {
                $error_message = "Error adding category: " . mysqli_error($con);
            }
        }
    }
}
?>

<style>
    .admin-dashboard {
        display: flex;
        min-height: calc(100vh - 60px);
        background-color: #f8f9fa;
    }
    
    .sidebar {
        width: 250px;
        background-color: #343a40;
        color: #fff;
        position: fixed;
        height: 100%;
        overflow-y: auto;
    }
    
    .sidebar-header {
        padding: 20px 15px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    
    .sidebar-menu {
        padding: 0;
        list-style: none;
        margin-top: 20px;
    }
    
    .sidebar-menu li {
        margin-bottom: 5px;
    }
    
    .sidebar-menu li a {
        color: #ced4da;
        padding: 12px 20px;
        display: block;
        transition: all 0.3s;
        text-decoration: none;
    }
    
    .sidebar-menu li a:hover,
    .sidebar-menu li.active a {
        color: #fff;
        background-color: rgba(255,255,255,0.1);
        border-left: 4px solid #007bff;
    }
    
    .sidebar-menu li a i {
        margin-right: 10px;
        width: 20px;
        text-align: center;
    }
    
    .main-content {
        flex: 1;
        margin-left: 250px;
        padding: 30px;
    }
    
    .page-header {
        margin-bottom: 30px;
    }
    
    .page-header h1 {
        margin-bottom: 10px;
        font-size: 28px;
        color: #343a40;
    }
    
    .breadcrumb {
        background-color: transparent;
        padding: 0;
        margin-bottom: 0;
    }
    
    .card {
        border: none;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    
    .card-body {
        padding: 30px;
    }
    
    .form-group {
        margin-bottom: 25px;
    }
    
    .form-group label {
        font-weight: 600;
        margin-bottom: 10px;
        color: #495057;
    }
    
    .input-group-text {
        background-color: #007bff;
        color: #fff;
        border: none;
    }
    
    .form-control {
        height: 45px;
        border-radius: 4px;
        border: 1px solid #ced4da;
    }
    
    .form-control:focus {
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
        border-color: #80bdff;
    }
    
    .form-actions {
        display: flex;
        gap: 15px;
        margin-top: 30px;
    }
    
    .btn {
        padding: 10px 20px;
        font-weight: 500;
        border-radius: 4px;
        display: inline-flex;
        align-items: center;
        transition: all 0.3s;
    }
    
    .btn i {
        margin-right: 8px;
    }
    
    .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
    }
    
    .btn-primary:hover {
        background-color: #0069d9;
        border-color: #0062cc;
    }
    
    .btn-secondary {
        background-color: #6c757d;
        border-color: #6c757d;
        color: #fff;
        text-decoration: none;
    }
    
    .btn-secondary:hover {
        background-color: #5a6268;
        border-color: #545b62;
        color: #fff;
    }
    
    .alert {
        border-radius: 4px;
        padding: 15px 20px;
        margin-bottom: 20px;
    }
    
    .alert i {
        margin-right: 10px;
    }
    
    .close {
        font-size: 1.2rem;
    }
    
    @media (max-width: 991px) {
        .sidebar {
            width: 60px;
            overflow: visible;
            z-index: 999;
        }
        
        .sidebar-header {
            padding: 15px 5px;
            text-align: center;
        }
        
        .sidebar-header h3 {
            display: none;
        }
        
        .sidebar-menu li a span {
            display: none;
        }
        
        .sidebar-menu li a i {
            margin-right: 0;
            font-size: 18px;
        }
        
        .main-content {
            margin-left: 60px;
        }
    }
    
    @media (max-width: 767px) {
        .form-actions {
            flex-direction: column;
        }
        
        .btn {
            width: 100%;
            margin-bottom: 10px;
        }
    }
</style>


<div class="admin-container">
    <div class="card admin-card">
        <div class="card-header">
            <h2><i class="fas fa-folder-plus"></i><?php echo translate('Add New Category'); ?></h2>
        </div>
        
        <div class="card-body">
            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" class="category-form">
                <div class="form-group">
                    <label for="categoryName"><?php echo translate('Category Name'); ?></label>
                    <input type="text" class="form-control" id="categoryName" name="name" placeholder=<?php echo translate('Enter category name'); ?> required value="<?php echo isset($name) ? $name : ''; ?>">
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="btn-submit" class="btn-add-category">
                        <i class="fas fa-plus-circle"></i> <?php echo translate('Add Category'); ?>
                    </button>
                    <a href="admin_manage_categories.php" class="btn-secondary">
                        <i class="fas fa-arrow-left"></i><?php echo translate('Back to Categories'); ?>
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php';?>