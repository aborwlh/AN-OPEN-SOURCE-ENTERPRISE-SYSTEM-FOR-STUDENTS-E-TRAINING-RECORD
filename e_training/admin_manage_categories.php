<?php include 'config.php'; ?>

<?php $page_title = translate("All Categories");?>

<?php include 'header.php';?>

<?php
// if he not logged in ; redirect to the index page
if ($_SESSION ['user_type'] != "admin") {
	header ( "Location: index.php" );
}
?>

<?php
// get all information for the categories
$categories_query = "SELECT * FROM category";
$categories_result = mysqli_query ($con,  $categories_query ) or die ( 'error : ' . mysqli_error ($con) );
?>

<div class="admin-container">
    <div class="page-header">
        <h1><?php echo translate('Category Management'); ?></h1>
        <p><?php echo translate('View and manage product categories'); ?></p>
    </div>

    <?php if(isset($_GET['success'])): ?>
    <div class="success-message">
        <?php echo translate($_GET['success']); ?>
    </div>
    <?php endif; ?>

    <?php if(isset($_GET['error'])): ?>
    <div class="error-message">
        <?php echo translate($_GET['error']); ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h5><?php echo translate('All Categories'); ?></h5>
        </div>
        <div class="card-body">
            <?php if (mysqli_num_rows($categories_result) > 0): ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th><?php echo translate('Name'); ?></th>
                            <th><?php echo translate('Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($category_row = mysqli_fetch_array($categories_result)) { ?>
                        <tr>
                            <td><?php echo translate($category_row['name']);?></td>
                            <td>
                                <a href="admin_edit_category.php?id=<?php echo $category_row['category_id']?>" class="btn-edit"><?php echo translate('Edit'); ?></a>
                                <a href="admin_delete_category.php?id=<?php echo $category_row['category_id']?>" class="btn-delete" onclick="return confirm('<?php echo translate('Are you sure you want to delete this category?'); ?>')"><?php echo translate('Delete'); ?></a>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="alert-info">
                <?php echo translate('No categories found. Please add a new category.'); ?>
            </div>
            <?php endif; ?>
            
            <a href="admin_add_category.php" class="new-course-btn"><?php echo translate('Add New Category'); ?></a>
        </div>
    </div>
</div>

<?php include 'footer.php';?>