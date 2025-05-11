<?php
include 'config.php';

// Check if user is logged in as student
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != "student") {
    header("Location: login.php");
    exit();
}

// Check if course_id is provided
if (!isset($_GET['course_id'])) {
    header("Location: student_view_my_courses.php");
    exit();
}

$course_id = mysqli_real_escape_string($con, $_GET['course_id']);
$student_id = $_SESSION['user_id'];

// Check if student is enrolled in this course
$enrollment_check = "SELECT * FROM course_enrollments 
                    WHERE student_id = '$student_id' AND course_id = '$course_id'";
$enrollment_result = mysqli_query($con, $enrollment_check);

if (mysqli_num_rows($enrollment_result) == 0) {
    // Student is not enrolled in this course
    echo "<script>alert('".translate('You are not enrolled in this course.')."');</script>";
    echo "<meta http-equiv='Refresh' content='0; url=student_view_my_courses.php'>";
    exit();
}

// Get course details
$course_query = "SELECT c.*, u.name as instructor_name 
                FROM courses c
                JOIN users u ON c.instructor_id = u.user_id
                WHERE c.course_id = '$course_id'";
$course_result = mysqli_query($con, $course_query);
$course = mysqli_fetch_assoc($course_result);

// Get all materials for this course
$materials_query = "SELECT * FROM course_materials 
                   WHERE course_id = '$course_id' 
                   ORDER BY upload_date DESC";
$materials_result = mysqli_query($con, $materials_query);

// Handle URL redirection if material_id is provided
if (isset($_GET['material_id']) && isset($_GET['action']) && $_GET['action'] == 'view') {
    $material_id = mysqli_real_escape_string($con, $_GET['material_id']);
    
    // Get material details
    $material_query = "SELECT * FROM course_materials WHERE material_id = '$material_id' AND course_id = '$course_id'";
    $material_result = mysqli_query($con, $material_query);
    
    if (mysqli_num_rows($material_result) > 0) {
        $material = mysqli_fetch_assoc($material_result);
        
        // Log access (if you have a material_access_log table)
        $check_table = mysqli_query($con, "SHOW TABLES LIKE 'material_access_log'");
        if (mysqli_num_rows($check_table) > 0) {
            $log_query = "INSERT INTO material_access_log (student_id, material_id, access_time) 
                         VALUES ('$student_id', '$material_id', NOW())";
            mysqli_query($con, $log_query);
        }
        
        if ($material['type'] == 'url') {
            // For URL type, redirect to the URL
            $url = $material['content'];
            
            // Debug: Check if URL is empty
            if (empty($url)) {
                echo "<div style='background-color: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border-radius: 5px;'>";
                echo "<h3>".translate('Error: URL is empty')."</h3>";
                echo "<p>".translate('The URL for this material (ID: {$material_id}) is not set in the database.')."</p>";
                echo "<p>".translate('Please contact your instructor or administrator to fix this issue.')."</p>";
                echo "<p><a href='student_view_materials.php?course_id={$course_id}'>".translate('Go back to materials')."</a></p>";
                echo "</div>";
                exit();
            }
            
            // Ensure URL has http:// or https:// prefix
            if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
                $url = "https://" . $url;
            }
            
            // Redirect to the URL
            header("Location: " . $url);
            exit();
        } elseif ($material['type'] == 'file') {
            // For file type, download the file
            $file_path = 'assets/course_materials/' . $material['file_path'];
            
            if (file_exists($file_path)) {
                // Set appropriate headers for file download
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($material['file_path']) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($file_path));
                readfile($file_path);
                exit();
            } else {
                echo "<div style='background-color: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border-radius: 5px;'>";
                echo "<h3>".translate('Error: File not found')."</h3>";
                echo "<p>".translate('The file for this material (ID: {$material_id}) could not be found.')."</p>";
                echo "<p>".translate('Please contact your instructor or administrator to fix this issue.')."</p>";
                echo "<p><a href='student_view_materials.php?course_id={$course_id}'>".translate('Go back to materials')."</a></p>";
                echo "</div>";
                exit();
            }
        } elseif ($material['type'] == 'text') {
            // For text type, display the content
            $page_title = $material['title'];
            include 'header.php';
            ?>
            <style>
            .material-container {
                max-width: 900px;
                margin: 30px auto;
                padding: 20px;
                background-color: white;
                border-radius: 8px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            
            .material-header {
                margin-bottom: 20px;
                padding-bottom: 15px;
                border-bottom: 1px solid #e9ecef;
            }
            
            .material-title {
                font-size: 24px;
                font-weight: bold;
                color: #04639b;
                margin-bottom: 10px;
            }
            
            .material-meta {
                display: flex;
                justify-content: space-between;
                color: #6c757d;
                font-size: 14px;
            }
            
            .material-content {
                line-height: 1.6;
                font-size: 16px;
            }
            
            .material-actions {
                margin-top: 30px;
                display: flex;
                justify-content: space-between;
            }
            
            .btn {
                padding: 8px 16px;
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
            </style>
            
            <div class="material-container">
                <div class="material-header">
                    <div class="material-title"><?php echo htmlspecialchars($material['title']); ?></div>
                    <div class="material-meta">
                        <div><?php echo translate('Added on'); ?> <?php echo date('M d, Y', strtotime($material['upload_date'])); ?></div>
                    </div>
                </div>
                
                <div class="material-content">
                    <?php echo nl2br(htmlspecialchars($material['content'])); ?>
                </div>
                
                <div class="material-actions">
                    <a href="student_view_materials.php?course_id=<?php echo $course_id; ?>" class="btn btn-primary">
                        <?php echo translate('Back to Course Materials'); ?>
                    </a>
                </div>
            </div>
            
            <?php
            include 'footer.php';
            exit();
        }
    } else {
        echo "<div style='background-color: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border-radius: 5px;'>";
        echo "<h3>".translate('Error: Material not found')."</h3>";
        echo "<p>".translate('The requested material could not be found.')."</p>";
        echo "<p><a href='student_view_materials.php?course_id={$course_id}'>".translate('Go back to materials')."</a></p>";
        echo "</div>";
        exit();
    }
}

$page_title = translate("Course Materials");
include 'header.php';
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
}

.course-title {
   font-size: 24px;
   font-weight: bold;
   margin-bottom: 10px;
}

.course-info {
   display: flex;
   justify-content: space-between;
   flex-wrap: wrap;
}

.course-info-item {
   margin-right: 20px;
}

.materials-section {
   background-color: white;
   border-radius: 8px;
   padding: 20px;
   box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.section-title {
   font-size: 20px;
   font-weight: bold;
   margin-bottom: 20px;
   color: #04639b;
   border-bottom: 2px solid #04639b;
   padding-bottom: 10px;
}

.material-filters {
   display: flex;
   justify-content: space-between;
   margin-bottom: 20px;
   flex-wrap: wrap;
}

.search-box {
   flex: 1;
   margin-right: 20px;
   min-width: 200px;
}

.filter-select {
   min-width: 150px;
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

.btn-info {
   background-color: #17a2b8;
   color: white;
}

.btn-info:hover {
   background-color: #138496;
}

.empty-state {
   text-align: center;
   padding: 40px 20px;
   color: #6c757d;
}

.empty-state p {
   margin-bottom: 15px;
   font-size: 16px;
}

@media (max-width: 768px) {
   .material-filters {
       flex-direction: column;
   }
   
   .search-box {
       margin-right: 0;
       margin-bottom: 10px;
   }
}
</style>

<div class="materials-container">
   <div class="course-header">
       <div class="course-title"><?php echo htmlspecialchars($course['name']); ?> - <?php echo translate('Course Materials'); ?></div>
       <div class="course-info">
           <div class="course-info-item">
               <strong><?php echo translate('Instructor'); ?>:</strong> <?php echo htmlspecialchars($course['instructor_name']); ?>
           </div>
           <div class="course-info-item">
               <strong><?php echo translate('Start Date'); ?>:</strong> <?php echo date('M d, Y', strtotime($course['start_date'])); ?>
           </div>
           <div class="course-info-item">
               <a href="student_view_my_courses.php" class="btn btn-primary"><?php echo translate('Back to My Courses'); ?></a>
           </div>
       </div>
   </div>
   
   <div class="materials-section">
       <h2 class="section-title"><?php echo translate('Available Materials'); ?></h2>
       
       <div class="material-filters">
           <div class="search-box">
               <input type="text" id="searchMaterials" class="form-control" placeholder="<?php echo translate('Search materials...'); ?>">
           </div>
           <select id="typeFilter" class="form-control filter-select">
               <option value=""><?php echo translate('All Types'); ?></option>
               <option value="file"><?php echo translate('Files'); ?></option>
               <option value="url"><?php echo translate('URLs'); ?></option>
               <option value="text"><?php echo translate('Text'); ?></option>
           </select>
       </div>
       
       <?php if (mysqli_num_rows($materials_result) > 0): ?>
           <div id="materialsList">
               <?php while ($material = mysqli_fetch_assoc($materials_result)): ?>
                   <div class="material-card" data-type="<?php echo $material['type']; ?>">
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
                       
                       <div class="material-description">
                           <?php echo htmlspecialchars($material['description']); ?>
                       </div>
                       
                       <div class="material-footer">
                           <div class="material-date">
                               <?php echo translate('Added on'); ?> <?php echo date('M d, Y', strtotime($material['upload_date'])); ?>
                           </div>
                           
                           <div class="material-actions">
                               <?php if ($material['type'] == 'file'): ?>
                                   <a href="student_view_materials.php?course_id=<?php echo $course_id; ?>&material_id=<?php echo $material['material_id']; ?>&action=view" 
                                      class="btn btn-primary">
                                       <?php echo translate('Download'); ?>
                                   </a>
                               <?php elseif ($material['type'] == 'url'): ?>
                                   <a href="student_view_materials.php?course_id=<?php echo $course_id; ?>&material_id=<?php echo $material['material_id']; ?>&action=view" 
                                      class="btn btn-success" target="_blank">
                                       <?php echo translate('Visit Link'); ?>
                                   </a>
                               <?php else: ?>
                                   <a href="student_view_materials.php?course_id=<?php echo $course_id; ?>&material_id=<?php echo $material['material_id']; ?>&action=view" 
                                      class="btn btn-info">
                                       <?php echo translate('Read Content'); ?>
                                   </a>
                               <?php endif; ?>
                           </div>
                       </div>
                   </div>
               <?php endwhile; ?>
           </div>
       <?php else: ?>
           <div class="empty-state">
               <p><?php echo translate('No materials have been added to this course yet.'); ?></p>
               <p><?php echo translate('Please check back later or contact your instructor for more information.'); ?></p>
           </div>
       <?php endif; ?>
   </div>
</div>

<script>
// Search and filter functionality
document.addEventListener('DOMContentLoaded', function() {
   const searchInput = document.getElementById('searchMaterials');
   const typeFilter = document.getElementById('typeFilter');
   const materialCards = document.querySelectorAll('.material-card');
   
   function filterMaterials() {
       const searchTerm = searchInput.value.toLowerCase();
       const typeValue = typeFilter.value.toLowerCase();
       
       materialCards.forEach(card => {
           const title = card.querySelector('.material-title').textContent.toLowerCase();
           const description = card.querySelector('.material-description').textContent.toLowerCase();
           const type = card.dataset.type.toLowerCase();
           
           const matchesSearch = title.includes(searchTerm) || description.includes(searchTerm);
           const matchesType = typeValue === '' || type === typeValue;
           
           if (matchesSearch && matchesType) {
               card.style.display = '';
           } else {
               card.style.display = 'none';
           }
       });
       
       // Check if any materials are visible
       const visibleMaterials = document.querySelectorAll('.material-card[style=""]').length;
       const noResultsElement = document.getElementById('noResults');
       
       if (visibleMaterials === 0 && materialCards.length > 0) {
           if (!noResultsElement) {
               const noResults = document.createElement('div');
               noResults.id = 'noResults';
               noResults.className = 'empty-state';
               noResults.innerHTML = '<p><?php echo translate('No materials match your search criteria.'); ?></p>';
               document.getElementById('materialsList').appendChild(noResults);
           }
       } else if (noResultsElement) {
           noResultsElement.remove();
       }
   }
   
   if (searchInput) searchInput.addEventListener('input', filterMaterials);
   if (typeFilter) typeFilter.addEventListener('change', filterMaterials);
});
</script>

<?php include 'footer.php'; ?>
