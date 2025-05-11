<?php
// Include the config file which already has session_start()
include 'config.php';

// Function to debug file paths
function debugFilePath($file_path) {
    $debug_info = [];
    $debug_info['original_path'] = $file_path;
    $debug_info['file_exists'] = file_exists($file_path) ? 'Yes' : 'No';
    $debug_info['is_readable'] = is_readable($file_path) ? 'Yes' : 'No';
    $debug_info['absolute_path'] = realpath($file_path) ?: 'Not found';
    $debug_info['current_dir'] = getcwd();
    
    return $debug_info;
}

// Check if user is logged in as instructor
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != "instructor") {
    header("Location: login.php");
    exit();
}

// Check if course_id is provided
if (!isset($_GET['id'])) {
   echo "<div style='background-color: #f8d7da; color: #721c24; padding: 20px; margin: 20px; border-radius: 5px;'>";
   echo "<h3>" . translate('Missing Course ID') . "</h3>";
   echo "<p>" . translate('No course ID was provided in the URL.') . "</p>";
   echo "<p>" . translate('The URL should be: instructor_manage_course_materials.php?id=X') . "</p>";
   echo "<p><a href='instructor_dashboard.php' style='color: #721c24; font-weight: bold;'>" . translate('Return to Dashboard') . "</a></p>";
   echo "</div>";
   exit();
}

$course_id = mysqli_real_escape_string($con, $_GET['id']);

$instructor_id = $_SESSION['user_id'];

// Check if the course belongs to this instructor
$course_query = "SELECT * FROM courses WHERE course_id = '$course_id' AND instructor_id = '$instructor_id'";
$course_result = mysqli_query($con, $course_query);

if (mysqli_num_rows($course_result) == 0) {
    echo "<script>alert('" . translate('You do not have permission to access this course') . "');</script>";
    echo "<meta http-equiv='Refresh' content='0; url=instructor-dashboard.php'>";
    exit;
}

$course = mysqli_fetch_assoc($course_result);
$page_title = translate("Materials for") . " " . translate($course['name']);
include 'header.php';

// Handle form submission for adding new material
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_material'])) {
    $title = mysqli_real_escape_string($con, $_POST['title']);
    $type = mysqli_real_escape_string($con, $_POST['type']);
    $description = mysqli_real_escape_string($con, $_POST['description']);
    $content = '';
    $file_path = '';
    
    // Handle different material types
    if ($type == 'file') {
        // Handle file upload
        if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
            // Define multiple possible upload directories
            $possible_dirs = [
                "assets/course_materials/",
                "../assets/course_materials/",
                "e_training/assets/course_materials/"
            ];
            
            $upload_success = false;
            $file_name = time() . '_' . basename($_FILES['file']['name']);
            
            foreach ($possible_dirs as $target_dir) {
                // Try to create directory if it doesn't exist
                if (!file_exists($target_dir)) {
                    @mkdir($target_dir, 0777, true);
                }
                
                // Check if directory exists and is writable
                if (is_dir($target_dir) && is_writable($target_dir)) {
                    $target_file = $target_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
                        $file_path = $file_name; // Store just the filename in the database
                        $upload_success = true;
                        break;
                    }
                }
            }
            
            if (!$upload_success) {
                $error_message = translate("Failed to upload file. Please check directory permissions.");
            }
        } else {
            $error_message = translate("Please select a valid file to upload.");
        }
    } elseif ($type == 'url') {
        // Handle URL
        $url = mysqli_real_escape_string($con, $_POST['url_content']);
        
        // Validate URL is not empty
        if (empty($url)) {
            $error_message = translate("Please enter a valid URL.");
        } else {
            $content = $url;
        }
    } elseif ($type == 'text') {
        $content = mysqli_real_escape_string($con, $_POST['content']);
    }
    
    if (!isset($error_message)) {
        // Insert the new material
        $insert_query = "INSERT INTO course_materials (course_id, title, type, description, content, file_path, upload_date) 
                        VALUES ('$course_id', '$title', '$type', '$description', '$content', '$file_path', NOW())";
        
        if (mysqli_query($con, $insert_query)) {
            $success_message = translate("Material added successfully!");
        } else {
            $error_message = translate("Error:") . " " . mysqli_error($con);
        }
    }
}

// Handle material deletion
if (isset($_GET['delete'])) {
    $material_id = mysqli_real_escape_string($con, $_GET['delete']);
    
    // Check if material belongs to this course
    $check_query = "SELECT * FROM course_materials WHERE material_id = '$material_id' AND course_id = '$course_id'";
    $check_result = mysqli_query($con, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $material = mysqli_fetch_assoc($check_result);
        
        // Delete file if it's a file type material
        if ($material['type'] == 'file' && !empty($material['file_path'])) {
            $file_path = "assets/course_materials/" . $material['file_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        // Delete the material from database
        $delete_query = "DELETE FROM course_materials WHERE material_id = '$material_id'";
        if (mysqli_query($con, $delete_query)) {
            $success_message = translate("Material deleted successfully!");
        } else {
            $error_message = translate("Error deleting material:") . " " . mysqli_error($con);
        }
    } else {
        $error_message = translate("Material not found or you don't have permission to delete it.");
    }
}

// Get course materials
$materials_query = "SELECT * FROM course_materials WHERE course_id = '$course_id' ORDER BY upload_date DESC";
$materials_result = mysqli_query($con, $materials_query);
?>

<style>
.materials-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.course-header {
    background-color: #04639b;
    color: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.course-title {
    font-size: 24px;
    font-weight: bold;
}

.back-button {
    background-color: rgba(255, 255, 255, 0.2);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 14px;
}

.back-button:hover {
    background-color: rgba(255, 255, 255, 0.3);
    color: white;
}

.materials-section {
    background-color: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.section-title {
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 20px;
    color: #04639b;
    border-bottom: 2px solid #04639b;
    padding-bottom: 10px;
}

.material-card {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.material-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.material-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}

.material-title {
    font-size: 18px;
    font-weight: bold;
    color: #04639b;
}

.material-type {
    background-color: #e9ecef;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
}

.material-type.file {
    background-color: #cfe2ff;
    color: #084298;
}

.material-type.url {
    background-color: #d1e7dd;
    color: #0f5132;
}

.material-type.text {
    background-color: #fff3cd;
    color: #664d03;
}

.material-description {
    margin-bottom: 15px;
    color: #6c757d;
}

.material-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.material-date {
    font-size: 12px;
    color: #6c757d;
}

.material-actions {
    display: flex;
    gap: 10px;
}

.btn {
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 14px;
    text-decoration: none;
    cursor: pointer;
    border: none;
}

.btn-primary {
    background-color: #04639b;
    color: white;
}

.btn-primary:hover {
    background-color: #034f7d;
}

.btn-success {
    background-color: #28a745;
    color: white;
}

.btn-success:hover {
    background-color: #218838;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
}

.btn-danger:hover {
    background-color: #c82333;
}

.add-form {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-control {
    width: 100%;
    padding: 8px;
    border: 1px solid #ced4da;
    border-radius: 4px;
}

.form-control:focus {
    border-color: #04639b;
    outline: none;
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
}

.type-toggle {
    margin-top: 10px;
}

.type-content {
    margin-top: 10px;
}
</style>

<div class="materials-container">
    <div class="course-header">
        <div class="course-title"><?php echo htmlspecialchars(translate($course['name'])); ?> - <?php echo translate('Course Materials'); ?></div>
        <a href="instructor_dashboard.php" class="back-button"><?php echo translate('Back to Dashboard'); ?></a>
    </div>
    
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <div class="materials-section">
        <h2 class="section-title"><?php echo translate('Add New Material'); ?></h2>
        
        <form class="add-form" method="post" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title"><?php echo translate('Title'); ?></label>
                <input type="text" id="title" name="title" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="description"><?php echo translate('Description (Optional)'); ?></label>
                <textarea id="description" name="description" class="form-control" rows="2"></textarea>
            </div>
            
            <div class="form-group">
                <label for="type"><?php echo translate('Material Type'); ?></label>
                <select id="type" name="type" class="form-control" required onchange="toggleTypeFields()">
                    <option value="file"><?php echo translate('File Upload'); ?></option>
                    <option value="url"><?php echo translate('URL Link'); ?></option>
                    <option value="text"><?php echo translate('Text Content'); ?></option>
                </select>
            </div>
            
            <div id="file-content" class="type-content">
                <div class="form-group">
                    <label for="file"><?php echo translate('Upload File'); ?></label>
                    <input type="file" id="file" name="file" class="form-control">
                </div>
            </div>
            
            <div id="url-content" class="type-content" style="display: none;">
                <div class="form-group">
                    <label for="url-input"><?php echo translate('URL'); ?></label>
                    <input type="url" id="url-input" name="url_content" class="form-control" placeholder="https://example.com">
                </div>
            </div>
            
            <div id="text-content" class="type-content" style="display: none;">
                <div class="form-group">
                    <label for="text-input"><?php echo translate('Content'); ?></label>
                    <textarea id="text-input" name="content" class="form-control" rows="5"></textarea>
                </div>
            </div>
            
            <button type="submit" name="add_material" class="btn btn-primary"><?php echo translate('Add Material'); ?></button>
        </form>
    </div>
    
    <div class="materials-section">
        <h2 class="section-title"><?php echo translate('Existing Materials'); ?></h2>
        
        <?php if (mysqli_num_rows($materials_result) > 0): ?>
            <?php while ($material = mysqli_fetch_assoc($materials_result)): ?>
                <div class="material-card">
                    <div class="material-header">
                        <div class="material-title"><?php echo htmlspecialchars($material['title']); ?></div>
                        <div class="material-type <?php echo $material['type']; ?>">
                            <?php 
                            if ($material['type'] == 'file') {
                                echo translate("File");
                            } elseif ($material['type'] == 'url') {
                                echo translate("URL");
                            } else {
                                echo translate("Text");
                            }
                            ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($material['description'])): ?>
                        <div class="material-description">
                            <?php echo htmlspecialchars($material['description']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="material-footer">
                        <div class="material-date">
                            <?php echo translate('Added on'); ?> <?php echo date('M d, Y', strtotime($material['upload_date'])); ?>
                        </div>
                        
                        <div class="material-actions">
                            <?php 
                            // Check if the file exists in different possible locations
                            $file_locations = [
                                "assets/course_materials/" . $material['file_path'],
                                "../assets/course_materials/" . $material['file_path'],
                                "e_training/assets/course_materials/" . $material['file_path']
                            ];
                            
                            $file_exists = false;
                            $file_path = "";
                            
                            foreach ($file_locations as $location) {
                                if (file_exists($location)) {
                                    $file_exists = true;
                                    $file_path = $location;
                                    break;
                                }
                            }
                            
                            // If file not found in any location, use the default path
                            if (!$file_exists) {
                                $file_path = "assets/course_materials/" . $material['file_path'];
                            }
                            ?>
                            
                            <?php if ($material['type'] == 'file'): ?>
                                <a href="<?php echo $file_path; ?>" class="btn btn-primary" target="_blank">
                                    <?php echo translate('View File'); ?>
                                </a>
                                <a href="<?php echo $file_path; ?>" class="btn btn-success" download>
                                    <?php echo translate('Download'); ?>
                                </a>
                            <?php elseif ($material['type'] == 'url'): ?>
                                <?php
                                // Make sure the URL has a protocol prefix
                                $url = $material['content'];
                                if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
                                    $url = "https://" . $url;
                                }
                                ?>
                                <a href="<?php echo $url; ?>" class="btn btn-success" target="_blank">
                                    <?php echo translate('Visit Link'); ?>
                                </a>
                            <?php else: ?>
                                <a href="#" class="btn btn-primary" onclick="showTextContent('<?php echo htmlspecialchars(addslashes($material['content'])); ?>')">
                                    <?php echo translate('View Content'); ?>
                                </a>
                            <?php endif; ?>
                            
                            <a href="?id=<?php echo $course_id; ?>&delete=<?php echo $material['material_id']; ?>" 
                               class="btn btn-danger" onclick="return confirm('<?php echo translate('Are you sure you want to delete this material?'); ?>')">
                                <?php echo translate('Delete'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <p><?php echo translate('No materials found for this course.'); ?></p>
                <p><?php echo translate('Use the form above to add your first material.'); ?></p>
            </div>
        <?php endif; ?>
    </div>

<?php if (isset($_GET['debug']) && $_GET['debug'] == 1): ?>
    <div class="materials-section">
        <h2 class="section-title"><?php echo translate('Debug Information'); ?></h2>
        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; font-family: monospace;">
            <h3><?php echo translate('Server Information'); ?></h3>
            <p><?php echo translate('Current Directory'); ?>: <?php echo getcwd(); ?></p>
            <p><?php echo translate('Script Path'); ?>: <?php echo __FILE__; ?></p>
            
            <h3><?php echo translate('File Paths'); ?></h3>
            <?php
            $materials_debug = mysqli_query($con, "SELECT * FROM course_materials WHERE course_id = '$course_id' AND type = 'file' LIMIT 5");
            if (mysqli_num_rows($materials_debug) > 0):
                while ($material_debug = mysqli_fetch_assoc($materials_debug)):
                    $file_path = "assets/course_materials/" . $material_debug['file_path'];
                    $debug_info = debugFilePath($file_path);
            ?>
                <div style="margin-bottom: 15px; padding: 10px; border: 1px solid #ddd;">
                    <p><strong><?php echo translate('Material ID'); ?>:</strong> <?php echo $material_debug['material_id']; ?></p>
                    <p><strong><?php echo translate('File Path'); ?>:</strong> <?php echo $debug_info['original_path']; ?></p>
                    <p><strong><?php echo translate('File Exists'); ?>:</strong> <?php echo $debug_info['file_exists']; ?></p>
                    <p><strong><?php echo translate('Is Readable'); ?>:</strong> <?php echo $debug_info['is_readable']; ?></p>
                    <p><strong><?php echo translate('Absolute Path'); ?>:</strong> <?php echo $debug_info['absolute_path']; ?></p>
                    
                    <p><strong><?php echo translate('Alternative Paths'); ?>:</strong></p>
                    <ul>
                        <?php
                        $alt_paths = [
                            "../assets/course_materials/" . $material_debug['file_path'],
                            "../../assets/course_materials/" . $material_debug['file_path'],
                            "/assets/course_materials/" . $material_debug['file_path']
                        ];
                        
                        foreach ($alt_paths as $alt_path):
                            $alt_debug = debugFilePath($alt_path);
                        ?>
                            <li>
                                <?php echo $alt_path; ?> - 
                                <?php echo translate('Exists'); ?>: <?php echo $alt_debug['file_exists']; ?>, 
                                <?php echo translate('Readable'); ?>: <?php echo $alt_debug['is_readable']; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php
                endwhile;
            else:
                echo "<p>" . translate('No file materials found for debugging.') . "</p>";
            endif;
            ?>
        </div>
    </div>
<?php endif; ?>
</div>

<!-- Modal for displaying text content -->
<div id="textModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
    <div style="background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 800px; border-radius: 8px;">
        <span style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;" onclick="closeTextModal()">&times;</span>
        <h2 id="modalTitle"><?php echo translate('Content'); ?></h2>
        <div id="modalContent" style="margin-top: 15px; white-space: pre-wrap;"></div>
    </div>
</div>

<script>
function toggleTypeFields() {
    const type = document.getElementById('type').value;
    
    // Hide all content divs
    document.getElementById('file-content').style.display = 'none';
    document.getElementById('url-content').style.display = 'none';
    document.getElementById('text-content').style.display = 'none';
    
    // Show the selected content div
    document.getElementById(type + '-content').style.display = 'block';
}

function showTextContent(content) {
    document.getElementById('modalContent').textContent = content;
    document.getElementById('textModal').style.display = 'block';
}

function closeTextModal() {
    document.getElementById('textModal').style.display = 'none';
}

// Close modal when clicking outside of it
window.onclick = function(event) {
    const modal = document.getElementById('textModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

// Initialize the form
document.addEventListener('DOMContentLoaded', function() {
    toggleTypeFields();
});
</script>

<?php include 'footer.php'; ?>