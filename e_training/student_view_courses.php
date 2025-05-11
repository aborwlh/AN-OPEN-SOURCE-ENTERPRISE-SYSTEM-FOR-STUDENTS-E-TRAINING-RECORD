<?php include 'config.php'; ?>
<?php include 'header.php'; ?>

<?php
// Check if user is logged in as student
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != "student") {
    header("Location: index.php");
    exit;
}

$student_id = $_SESSION['user_id'];

// Get all courses
$courses_query = "SELECT c.*, cat.name as category_name, u.name as instructor_name 
                  FROM courses c 
                  JOIN category cat ON c.category_id = cat.category_id
                  JOIN users u ON c.instructor_id = u.user_id
                  ORDER BY c.name";
$courses_result = mysqli_query($con, $courses_query) or die('error: ' . mysqli_error($con));

// Check if the form for sending enrollment request was submitted
if (isset($_POST['send_request']) && isset($_POST['course_id'])) {
    $course_id = $_POST['course_id'];
    
    // Check if student is already enrolled
    $check_enrollment = "SELECT * FROM course_enrollments 
                        WHERE student_id = '$student_id' AND course_id = '$course_id'";
    $check_result = mysqli_query($con, $check_enrollment);
    
    // Check if request already exists
    $check_request = "SELECT * FROM enrollment_requests 
                     WHERE student_id = '$student_id' AND course_id = '$course_id'";
    $request_result = mysqli_query($con, $check_request);
    
    if (mysqli_num_rows($check_result) > 0) {
        echo "<script>alert('" . translate('You are already enrolled in this course') . "');</script>";
    } else if (mysqli_num_rows($request_result) > 0) {
        echo "<script>alert('" . translate('You have already sent a request for this course') . "');</script>";
    } else {
        // Insert the enrollment request
        $insert_query = "INSERT INTO enrollment_requests (student_id, course_id, created_at, status) 
                        VALUES ('$student_id', '$course_id', NOW(), 'pending')";
        
        if (mysqli_query($con, $insert_query)) {
            echo "<script>alert('" . translate('Enrollment request sent successfully') . "');</script>";
        } else {
            echo "<script>alert('" . translate('Error sending enrollment request: ') . mysqli_error($con) . "');</script>";
        }
    }
}

// Get all categories for filter
$category_query = "SELECT * FROM category ORDER BY name";
$category_result = mysqli_query($con, $category_query);
?>

<style>
    .course-container {
        padding: 20px;
        max-width: 1400px;
        margin: 0 auto;
    }
    
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        background-color: #fff;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .page-title {
        font-size: 24px;
        font-weight: bold;
        color: #04639b;
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
    
    .course-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }
    
    .course-card {
        height: 100%;
        border: 1px solid #eee;
        border-radius: 8px;
        overflow: hidden;
        transition: transform 0.3s, box-shadow 0.3s;
        background-color: white;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        display: flex;
        flex-direction: column;
    }
    
    .course-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .course-img {
        width: 100%;
        height: 200px;
        object-fit: cover;
    }
    
    .course-body {
        padding: 15px;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }
    
    .course-title {
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 10px;
        color: #04639b;
    }
    
    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
        margin-bottom: 10px;
    }
    
    .badge-success {
        background-color: #d1e7dd;
        color: #0f5132;
    }
    
    .badge-primary {
        background-color: #cfe2ff;
        color: #084298;
    }
    
    .course-description {
        color: #666;
        margin-bottom: 15px;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .course-info {
        margin-bottom: 15px;
        flex-grow: 1;
    }
    
    .course-info p {
        margin: 5px 0;
        font-size: 14px;
    }
    
    .course-info strong {
        color: #333;
    }
    
    .course-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: auto;
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
    
    .btn-warning {
        background-color: #ffc107;
        color: #212529;
    }
    
    .btn-warning:hover {
        background-color: #e0a800;
    }
    
    .btn-info {
        background-color: #17a2b8;
        color: white;
    }
    
    .btn-info:hover {
        background-color: #138496;
    }
    
    .btn-secondary {
        background-color: #6c757d;
        color: white;
    }
    
    .btn-secondary:hover {
        background-color: #5a6268;
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
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .search-filter-container {
            flex-direction: column;
        }
        
        .filter-options {
            flex-direction: column;
        }
        
        .course-grid {
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        }
        
        .course-actions {
            flex-direction: column;
        }
        
        .btn {
            width: 100%;
        }
    }
    
    @media (max-width: 576px) {
        .course-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language'] ?? 'en'; ?>" <?php echo $dirAttribute; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo translate('Available Courses'); ?> - <?php echo translate('E-Training Platform'); ?></title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <?php if ($isRTL): ?>
    <link rel="stylesheet" href="assets/css/rtl.css">
    <?php endif; ?>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-3">
                <?php include 'includes/student_sidebar.php'; ?>
            </div>
            <div class="col-md-9">
                
<div class="course-container">
    <div class="page-header">
        <h1 class="page-title"><?php echo translate('Available Courses'); ?></h1>
    </div>

    <div class="search-filter-container">
        <div class="search-box">
            <input type="text" id="courseSearch" class="search-input" placeholder="<?php echo translate('Search courses...'); ?>">
        </div>
        <div class="filter-options">
            <select id="categoryFilter" class="filter-select">
                <option value=""><?php echo translate('All Categories'); ?></option>
                <?php
                // Reset the result pointer to the beginning
                mysqli_data_seek($category_result, 0);
                while ($category = mysqli_fetch_assoc($category_result)) {
                    echo '<option value="' . $category['name'] . '">' . htmlspecialchars(translate($category['name'])) . '</option>';
                }
                ?>
            </select>
            <select id="statusFilter" class="filter-select">
                <option value=""><?php echo translate('All Statuses'); ?></option>
                <option value="enrolled"><?php echo translate('Enrolled'); ?></option>
                <option value="pending"><?php echo translate('Request Pending'); ?></option>
                <option value="available"><?php echo translate('Available'); ?></option>
            </select>
        </div>
    </div>

    <?php if (mysqli_num_rows($courses_result) > 0): ?>
        <div class="course-grid" id="courseGrid">
            <?php while ($course = mysqli_fetch_array($courses_result)): 
                // Check if student is already enrolled
                $check_enrollment = "SELECT * FROM course_enrollments 
                                    WHERE student_id = '$student_id' AND course_id = '{$course['course_id']}'";
                $check_result = mysqli_query($con, $check_enrollment);
                $is_enrolled = (mysqli_num_rows($check_result) > 0);
                
                // Check if request is pending
                $check_request = "SELECT * FROM enrollment_requests 
                                 WHERE student_id = '$student_id' AND course_id = '{$course['course_id']}' 
                                 AND status = 'pending'";
                $request_result = mysqli_query($con, $check_request);
                $has_pending_request = (mysqli_num_rows($request_result) > 0);
                
                // Determine status for filtering
                $status = $is_enrolled ? 'enrolled' : ($has_pending_request ? 'pending' : 'available');
            ?>
                <div class="course-card" data-category="<?php echo htmlspecialchars($course['category_name']); ?>" data-status="<?php echo $status; ?>">
                    <img src="assets/images/courses/<?php echo $course['img'] ? $course['img'] : 'default-course.jpg'; ?>" 
                         class="course-img" alt="<?php echo htmlspecialchars(translate($course['name'])); ?>">
                    <div class="course-body">
                        <h3 class="course-title"><?php echo htmlspecialchars(translate($course['name'])); ?></h3>
                        
                        <?php if ($is_enrolled): ?>
                            <span class="badge badge-success"><?php echo translate('Enrolled'); ?></span>
                        <?php elseif ($has_pending_request): ?>
                            <span class="badge badge-primary"><?php echo translate('Request Pending'); ?></span>
                        <?php endif; ?>
                        
                        <div class="course-description">
                            <?php echo htmlspecialchars(translate($course['description'])); ?>
                        </div>
                        
                        <div class="course-info">
                            <p><strong><?php echo translate('Category'); ?>:</strong> <?php echo htmlspecialchars(translate($course['category_name'])); ?></p>
                            <p><strong><?php echo translate('Instructor'); ?>:</strong> <?php echo htmlspecialchars($course['instructor_name']); ?></p>
                            <p><strong><?php echo translate('Start Date'); ?>:</strong> <?php echo date('M d, Y', strtotime($course['start_date'])); ?></p>
                            <?php if (!empty($course['prerequisites'])): ?>
                                <p><strong><?php echo translate('Prerequisites'); ?>:</strong> <?php echo htmlspecialchars(translate($course['prerequisites'])); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="course-actions">
                            <?php if ($is_enrolled): ?>
                                <a href="student_view_my_courses.php" class="btn btn-success"><?php echo translate('Go to My Courses'); ?></a>
                            <?php elseif ($has_pending_request): ?>
                                <button class="btn btn-warning" disabled><?php echo translate('Request Pending'); ?></button>
                            <?php else: ?>
                                <form method="post" action="" style="flex: 1;">
                                    <input type="hidden" name="course_id" value="<?php echo $course['course_id']; ?>">
                                    <button type="submit" name="send_request" class="btn btn-primary" style="width: 100%;">
                                        <?php echo translate('Send Enrollment Request'); ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <a href="student_view_schedule.php?course_id=<?php echo $course['course_id']; ?>" class="btn btn-secondary"><?php echo translate('View Schedule'); ?></a>
                            <a href="student_view_feedback.php?course_id=<?php echo $course['course_id']; ?>" class="btn btn-info"><?php echo translate('View Feedback'); ?></a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <!-- No Results Message (initially hidden) -->
        <div class="no-results" id="noResults" style="display: none;">
            <p><?php echo translate('No courses match your search criteria.'); ?></p>
            <button id="resetFiltersBtn" class="btn btn-primary"><?php echo translate('Reset Filters'); ?></button>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <p><?php echo translate('No courses are available at the moment.'); ?></p>
            <p><?php echo translate('Please check back later or contact an administrator for more information.'); ?></p>
        </div>
    <?php endif; ?>
</div>

            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    
<?php include 'footer.php'; ?>

<script>
// Search and filter functionality
document.addEventListener('DOMContentLoaded', function() {
    const courseSearch = document.getElementById('courseSearch');
    const categoryFilter = document.getElementById('categoryFilter');
    const statusFilter = document.getElementById('statusFilter');
    const courseCards = document.querySelectorAll('.course-card');
    const noResults = document.getElementById('noResults');
    const resetFiltersBtn = document.getElementById('resetFiltersBtn');
    
    function filterCourses() {
        const searchTerm = courseSearch.value.toLowerCase();
        const categoryValue = categoryFilter.value.toLowerCase();
        const statusValue = statusFilter.value.toLowerCase();
        
        let visibleCount = 0;
        
        courseCards.forEach(card => {
            const title = card.querySelector('.course-title').textContent.toLowerCase();
            const description = card.querySelector('.course-description').textContent.toLowerCase();
            const category = card.dataset.category.toLowerCase();
            const status = card.dataset.status;
            
            // Check if card matches all filters
            const matchesSearch = title.includes(searchTerm) || description.includes(searchTerm);
            const matchesCategory = categoryValue === '' || category === categoryValue;
            const matchesStatus = statusValue === '' || status === statusValue;
            
            if (matchesSearch && matchesCategory && matchesStatus) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Show/hide no results message
        if (visibleCount === 0) {
            noResults.style.display = 'block';
            document.getElementById('courseGrid').style.display = 'none';
        } else {
            noResults.style.display = 'none';
            document.getElementById('courseGrid').style.display = 'grid';
        }
    }
    
    // Add event listeners
    courseSearch.addEventListener('input', filterCourses);
    categoryFilter.addEventListener('change', filterCourses);
    statusFilter.addEventListener('change', filterCourses);
    
    // Reset filters
    resetFiltersBtn.addEventListener('click', function() {
        courseSearch.value = '';
        categoryFilter.value = '';
        statusFilter.value = '';
        filterCourses();
    });
});
</script>
</body>
</html>
