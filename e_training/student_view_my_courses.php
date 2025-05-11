<?php include 'config.php'; ?>

<?php
$page_title = translate("My Enrolled Courses");
include 'header.php';

// Check if user is logged in as student
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != "student") {
    header("Location: index.php");
    exit;
}

$student_id = $_SESSION['user_id'];

// Handle course unenrollment if requested
if (isset($_GET['action']) && $_GET['action'] == 'cancel' && isset($_GET['enrollment_id'])) {
    $enrollment_id = $_GET['enrollment_id'];
    
    // Get the enrollment details before deleting
    $enrollment_query = "SELECT course_id FROM course_enrollments WHERE enrollment_id = '$enrollment_id' AND student_id = '$student_id'";
    $enrollment_result = mysqli_query($con, $enrollment_query) or die('error: ' . mysqli_error($con));
    
    if (mysqli_num_rows($enrollment_result) > 0) {
        $enrollment_data = mysqli_fetch_array($enrollment_result);
        
        // Delete the enrollment
        $delete_query = "DELETE FROM course_enrollments WHERE enrollment_id = '$enrollment_id'";
        
        if (mysqli_query($con, $delete_query)) {
            // Also delete any progress or feedback associated with this enrollment
            $delete_progress_query = "DELETE FROM student_progress WHERE course_id = '{$enrollment_data['course_id']}' AND student_id = '$student_id'";
            mysqli_query($con, $delete_progress_query);
            
            $delete_feedback_query = "DELETE FROM course_feedback WHERE course_id = '{$enrollment_data['course_id']}' AND student_id = '$student_id'";
            mysqli_query($con, $delete_feedback_query);
            
            // Also delete any enrollment requests for this course
            $delete_requests_query = "DELETE FROM enrollment_requests WHERE course_id = '{$enrollment_data['course_id']}' AND student_id = '$student_id'";
            mysqli_query($con, $delete_requests_query);
            
            echo "<script>alert('" . translate('Enrollment canceled successfully') . "');</script>";
        } else {
            echo "<script>alert('" . translate('Error in canceling enrollment: ') . mysqli_error($con) . "');</script>";
        }
    } else {
        echo "<script>alert('" . translate('You do not have permission to cancel this enrollment') . "');</script>";
    }
}

// Get all enrolled courses for this student
$enrolled_courses_query = "SELECT ce.enrollment_id, ce.enrollment_date, c.*, cat.name as category_name, 
                          u.name as instructor_name, COALESCE(sp.value, 0) as progress 
                          FROM course_enrollments ce 
                          JOIN courses c ON ce.course_id = c.course_id
                          JOIN category cat ON c.category_id = cat.category_id
                          JOIN users u ON c.instructor_id = u.user_id
                          LEFT JOIN student_progress sp ON ce.course_id = sp.course_id AND ce.student_id = sp.student_id
                          WHERE ce.student_id = '$student_id' 
                          ORDER BY c.name";
$enrolled_courses_result = mysqli_query($con, $enrolled_courses_query) or die('error: ' . mysqli_error($con));

// Count total enrolled courses
$count_query = "SELECT COUNT(*) as total FROM course_enrollments WHERE student_id = '$student_id'";
$count_result = mysqli_query($con, $count_query) or die('error: ' . mysqli_error($con));
$count_data = mysqli_fetch_array($count_result);
$total_courses = $count_data['total'];

// Count completed courses
$completed_query = "SELECT COUNT(*) as completed FROM student_progress 
                   WHERE student_id = '$student_id' AND value = 100";
$completed_result = mysqli_query($con, $completed_query);
$completed_data = mysqli_fetch_assoc($completed_result);
$completed_courses = $completed_data['completed'];

// Count in-progress courses
$in_progress = $total_courses - $completed_courses;
?>

<style>
.courses-container {
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.page-title {
    font-size: 24px;
    font-weight: bold;
    color: #04639b;
}

.stats-container {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
}

.stat-box {
    flex: 1;
    background-color: white;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.stat-box:hover {
    transform: translateY(-5px);
}

.stat-box.primary {
    border-top: 4px solid #04639b;
}

.stat-box.success {
    border-top: 4px solid #28a745;
}

.stat-box.warning {
    border-top: 4px solid #ffc107;
}

.stat-box h3 {
    font-size: 24px;
    margin: 10px 0;
    color: #04639b;
}

.stat-box p {
    color: #666;
    margin: 0;
}

.search-filter-container {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 20px;
    background-color: white;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.search-box {
    flex: 2;
    min-width: 250px;
}

.filter-options {
    flex: 1;
    display: flex;
    gap: 10px;
    min-width: 250px;
}

.search-input, .filter-select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.search-input:focus, .filter-select:focus {
    outline: none;
    border-color: #04639b;
    box-shadow: 0 0 0 2px rgba(4, 99, 155, 0.2);
}

.filter-select {
    background-color: white;
    cursor: pointer;
}

.course-card {
    background-color: white;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 20px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
}

.course-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.course-header {
    background-color: #04639b;
    color: white;
    padding: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.course-title {
    margin: 0;
    font-size: 18px;
    font-weight: bold;
}

.progress-badge {
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}

.progress-badge.complete {
    background-color: #28a745;
    color: white;
}

.progress-badge.in-progress {
    background-color: #17a2b8;
    color: white;
}

.course-body {
    padding: 15px;
}

.course-content {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
}

.course-image {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: 4px;
}

.course-details {
    flex: 1;
}

.course-details p {
    margin: 5px 0;
    font-size: 14px;
}

.course-details strong {
    color: #333;
}

.progress-container {
    margin-bottom: 15px;
}

.progress-bar-container {
    width: 100%;
    height: 10px;
    background-color: #e9ecef;
    border-radius: 5px;
    overflow: hidden;
}

.progress-bar-fill {
    height: 100%;
    background-color: #17a2b8;
    border-radius: 5px;
    transition: width 0.6s ease;
}

.progress-bar-fill.complete {
    background-color: #28a745;
}

.progress-text {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #6c757d;
    margin-top: 5px;
}

.course-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.btn {
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 14px;
    text-align: center;
    text-decoration: none;
    cursor: pointer;
    border: none;
    flex: 1;
    min-width: 120px;
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

.btn-warning {
    background-color: #ffc107;
    color: #212529;
}

.btn-warning:hover {
    background-color: #e0a800;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background-color: #5a6268;
}

.btn-dark {
    background-color: #343a40;
    color: white;
}

.btn-dark:hover {
    background-color: #23272b;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
}

.btn-danger:hover {
    background-color: #c82333;
}

.course-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(500px, 1fr));
    gap: 20px;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    background-color: #f8f9fa;
    border-radius: 8px;
    margin: 20px 0;
}

.empty-state p {
    color: #6c757d;
    margin-bottom: 15px;
    font-size: 16px;
}

.no-results {
    text-align: center;
    padding: 30px;
    background-color: #f8f9fa;
    border-radius: 8px;
    margin: 20px 0;
    color: #6c757d;
}

/* Confirmation message */
#confirmation-message {
    position: fixed;
    bottom: 20px;
    right: 20px;
    padding: 15px;
    border-radius: 5px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    display: none;
    background-color: #d4edda;
    color: #155724;
    z-index: 1000;
}

/* Responsive adjustments */
@media (max-width: 992px) {
    .course-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .stats-container {
        flex-direction: column;
    }
    
    .search-filter-container {
        flex-direction: column;
    }
    
    .filter-options {
        flex-direction: column;
    }
    
    .course-content {
        flex-direction: column;
    }
    
    .course-image {
        width: 100%;
        height: 200px;
    }
    
    .course-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
}
</style>

<div class="courses-container">
    <div class="page-header">
        <h1 class="page-title"><?php echo translate('My Enrolled Courses'); ?></h1>
       
    </div>

    <!-- Statistics Section -->
    <div class="stats-container">
        <div class="stat-box primary">
            <p><?php echo translate('Total Enrolled'); ?></p>
            <h3><?php echo $total_courses; ?></h3>
            <small><?php echo translate('Courses you\'re taking'); ?></small>
        </div>
        <div class="stat-box success">
            <p><?php echo translate('Completed'); ?></p>
            <h3><?php echo $completed_courses; ?></h3>
            <small><?php echo translate('Courses finished'); ?></small>
        </div>
        <div class="stat-box warning">
            <p><?php echo translate('In Progress'); ?></p>
            <h3><?php echo $in_progress; ?></h3>
            <small><?php echo translate('Courses ongoing'); ?></small>
        </div>
    </div>

    <!-- Search and Filter Section -->
    <div class="search-filter-container">
        <div class="search-box">
            <input type="text" id="courseSearch" class="search-input" placeholder="<?php echo translate('Search your courses...'); ?>">
        </div>
        <div class="filter-options">
            <select id="categoryFilter" class="filter-select">
                <option value=""><?php echo translate('All Categories'); ?></option>
                <?php
                // Reset the result pointer to the beginning
                mysqli_data_seek($enrolled_courses_result, 0);
                
                // Create an array to store unique categories
                $categories = array();
                
                while ($course = mysqli_fetch_array($enrolled_courses_result)) {
                    if (!in_array($course['category_name'], $categories)) {
                        $categories[] = $course['category_name'];
                        echo '<option value="' . htmlspecialchars($course['category_name']) . '">' . htmlspecialchars(translate($course['category_name'])) . '</option>';
                    }
                }
                
                // Reset the result pointer again for the main loop
                mysqli_data_seek($enrolled_courses_result, 0);
                ?>
            </select>
            <select id="progressFilter" class="filter-select">
                <option value=""><?php echo translate('All Progress'); ?></option>
                <option value="completed"><?php echo translate('Completed'); ?></option>
                <option value="in-progress"><?php echo translate('In Progress'); ?></option>
            </select>
        </div>
    </div>

    <?php if (mysqli_num_rows($enrolled_courses_result) > 0): ?>
        <div class="course-grid">
            <?php while ($course = mysqli_fetch_array($enrolled_courses_result)): ?>
                <div class="course-card" data-category="<?php echo htmlspecialchars($course['category_name']); ?>" 
                     data-progress="<?php echo ($course['progress'] == 100) ? 'completed' : 'in-progress'; ?>">
                    <div class="course-header">
                        <h3 class="course-title"><?php echo htmlspecialchars(translate($course['name'])); ?></h3>
                        <span class="progress-badge <?php echo ($course['progress'] == 100) ? 'complete' : 'in-progress'; ?>">
                            <?php echo ($course['progress'] == 100) ? translate('Completed') : translate('In Progress'); ?>
                        </span>
                    </div>
                    <div class="course-body">
                        <div class="course-content">
                            <img src="assets\images\courses/<?php echo $course['img'] ? $course['img'] : 'default-course.jpg'; ?>" 
                                 class="course-image" alt="<?php echo htmlspecialchars(translate($course['name'])); ?>">
                            <div class="course-details">
                                <p><?php echo htmlspecialchars(translate(substr($course['description'], 0, 150)) . (strlen($course['description']) > 150 ? '...' : '')); ?></p>
                                <p><strong><?php echo translate('Category'); ?>:</strong> <?php echo htmlspecialchars(translate($course['category_name'])); ?></p>
                                <p><strong><?php echo translate('Instructor'); ?>:</strong> <?php echo htmlspecialchars($course['instructor_name']); ?></p>
                                <p><strong><?php echo translate('Enrolled'); ?>:</strong> <?php echo date('M d, Y', strtotime($course['enrollment_date'])); ?></p>
                            </div>
                        </div>
                        
                        <div class="progress-container">
                            <div class="progress-bar-container">
                                <div class="progress-bar-fill <?php echo ($course['progress'] == 100) ? 'complete' : ''; ?>" 
                                     style="width: <?php echo (int)$course['progress']; ?>%">
                                </div>
                            </div>
                            <div class="progress-text">
                                <span><?php echo translate('Progress'); ?>: <?php echo (int)$course['progress']; ?>%</span>
                                <span>
                                    <?php 
                                    if ($course['progress'] == 100) {
                                        echo translate('Completed');
                                    } else if ($course['progress'] >= 75) {
                                        echo translate('Almost there!');
                                    } else if ($course['progress'] >= 50) {
                                        echo translate('Halfway through');
                                    } else if ($course['progress'] >= 25) {
                                        echo translate('Getting started');
                                    } else {
                                        echo translate('Just beginning');
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="course-actions">
                            <a href="student_view_progress.php?course_id=<?php echo $course['course_id']; ?>" class="btn btn-primary">
                                <?php echo translate('View Progress'); ?>
                            </a>
                            <a href="student_view_materials.php?course_id=<?php echo $course['course_id']; ?>" class="btn btn-info">
                                <?php echo translate('View Materials'); ?>
                            </a>
                            <a href="student_view_events.php?course_id=<?php echo $course['course_id']; ?>" class="btn btn-warning">
                                <?php echo translate('View Events'); ?>
                            </a>
                            <a href="student_view_schedule.php?course_id=<?php echo $course['course_id']; ?>" class="btn btn-secondary">
                                <?php echo translate('View Schedule'); ?>
                            </a>
                            <a href="student_view_feedback.php?course_id=<?php echo $course['course_id']; ?>" class="btn btn-dark">
                                <?php echo translate('View/Add Feedback'); ?>
                            </a>
                            
                            <?php if ($course['progress'] == 100): ?>
                                <a href="student_print_certificate.php?course_id=<?php echo $course['course_id']; ?>" 
                                   class="btn btn-success" target="_blank">
                                    <?php echo translate('Print Certificate'); ?>
                            </a>
                            <?php endif; ?>
                            
                            <button class="btn btn-danger" 
                                   onclick="confirmCancelEnrollment(<?php echo $course['enrollment_id']; ?>, '<?php echo htmlspecialchars(addslashes(translate($course['name']))); ?>')">
                                <?php echo translate('Cancel Enrollment'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <p><?php echo translate('You are not enrolled in any courses yet.'); ?></p>
            <a href="student_view_courses.php" class="btn btn-primary"><?php echo translate('Browse Available Courses'); ?></a>
        </div>
    <?php endif; ?>
</div>

<!-- Add confirmation message container that's initially hidden -->
<div id="confirmation-message">
    <?php echo translate('Action completed successfully!'); ?>
</div>

<!-- Modal for confirming enrollment cancellation -->
<div id="cancelModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
    <div style="background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
        <h2 style="color: #dc3545;"><?php echo translate('Cancel Enrollment'); ?></h2>
        <p id="cancelMessage"><?php echo translate('Are you sure you want to cancel your enrollment? This will delete all your progress, feedback, and any pending enrollment requests for this course.'); ?></p>
        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
            <button id="cancelNo" style="padding: 8px 16px; background-color: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;"><?php echo translate('No, Keep Enrollment'); ?></button>
            <button id="cancelYes" style="padding: 8px 16px; background-color: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;"><?php echo translate('Yes, Cancel Enrollment'); ?></button>
        </div>
    </div>
</div>

<script>
// Function to show the confirmation message
function showConfirmation(message) {
    const confirmationEl = document.getElementById('confirmation-message');
    confirmationEl.textContent = message;
    confirmationEl.style.display = 'block';
    
    // Hide the message after 3 seconds
    setTimeout(() => {
        confirmationEl.style.display = 'none';
    }, 3000);
}

// Function to confirm enrollment cancellation
function confirmCancelEnrollment(enrollmentId, courseName) {
    const modal = document.getElementById('cancelModal');
    const cancelMessage = document.getElementById('cancelMessage');
    const cancelYes = document.getElementById('cancelYes');
    const cancelNo = document.getElementById('cancelNo');
    
    // Update the message with the course name
    cancelMessage.textContent = `<?php echo translate('Are you sure you want to cancel your enrollment in'); ?> "${courseName}"? <?php echo translate('This will delete all your progress, feedback, and any pending enrollment requests for this course.'); ?>`;
    
    // Show the modal
    modal.style.display = 'block';
    
    // Handle the Yes button click
    cancelYes.onclick = function() {
        window.location.href = `student_view_my_courses.php?action=cancel&enrollment_id=${enrollmentId}`;
    };
    
    // Handle the No button click
    cancelNo.onclick = function() {
        modal.style.display = 'none';
    };
    
    // Close the modal if the user clicks outside of it
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    };
}

// Search and filter functionality
const courseSearch = document.getElementById('courseSearch');
const categoryFilter = document.getElementById('categoryFilter');
const progressFilter = document.getElementById('progressFilter');
const courseCards = document.querySelectorAll('.course-card');

function filterCourses() {
    const searchTerm = courseSearch.value.toLowerCase();
    const categoryValue = categoryFilter.value.toLowerCase();
    const progressValue = progressFilter.value.toLowerCase();
    
    let visibleCount = 0;
    
    courseCards.forEach(card => {
        const title = card.querySelector('.course-title').textContent.toLowerCase();
        const description = card.querySelector('.course-details p:first-child').textContent.toLowerCase();
        const category = card.dataset.category.toLowerCase();
        const progress = card.dataset.progress.toLowerCase();
        
        // Check if card matches all filters
        const matchesSearch = title.includes(searchTerm) || description.includes(searchTerm);
        const matchesCategory = categoryValue === '' || category === categoryValue;
        const matchesProgress = progressValue === '' || progress === progressValue;
        
        if (matchesSearch && matchesCategory && matchesProgress) {
            card.style.display = '';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    // Show "no results" message if no courses match
    const noResultsElement = document.getElementById('noResults');
    if (visibleCount === 0) {
        if (!noResultsElement) {
            const noResults = document.createElement('div');
            noResults.id = 'noResults';
            noResults.className = 'no-results';
            noResults.textContent = '<?php echo translate('No courses match your search criteria.'); ?>';
            document.querySelector('.course-grid').after(noResults);
        }
    } else if (noResultsElement) {
        noResultsElement.remove();
    }
}

// Add event listeners
courseSearch.addEventListener('input', filterCourses);
categoryFilter.addEventListener('change', filterCourses);
progressFilter.addEventListener('change', filterCourses);

// Check if there's a URL parameter indicating a successful action
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success')) {
        showConfirmation('<?php echo translate('Action completed successfully!'); ?>');
    }
});
</script>

<?php include 'footer.php'; ?>
