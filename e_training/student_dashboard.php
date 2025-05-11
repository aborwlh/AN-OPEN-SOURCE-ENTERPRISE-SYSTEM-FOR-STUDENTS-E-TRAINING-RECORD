<?php include 'config.php'; ?>

<?php include 'header.php'; ?>

<?php
// if not logged in as student; redirect to the login page
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != "student") {
  header("Location: login.php");
  exit();
}


// Check if database connection exists
if (!isset($conn) || $conn === null) {
  // Try to establish connection if it doesn't exist
  $servername = "sql300.infinityfree.com";
  $username = "if0_38712527";
  $password = "dlm8tS7wRoN";
  $dbname = "if0_38712527_e_training";
  
  $conn = mysqli_connect($servername, $username, $password, $dbname);
  
  if (!$conn) {
      die("Connection failed: " . mysqli_connect_error());
  }
}

// Get student information
$student_id = $_SESSION['user_id'];
$student_name = "Student"; // Default name in case query fails

try {
  $student_query = "SELECT * FROM users WHERE user_id = $student_id";
  $student_result = mysqli_query($conn, $student_query);
  
  if ($student_result && mysqli_num_rows($student_result) > 0) {
      $student = mysqli_fetch_assoc($student_result);
      $student_name = $student['name'];
  }
} catch (Exception $e) {
  // Silently handle the error, we'll use the default name
}

// Get enrolled courses with progress
$courses_query = "SELECT c.*, ce.enrollment_date, sp.value as progress 
               FROM courses c 
               JOIN course_enrollments ce ON c.course_id = ce.course_id 
               LEFT JOIN student_progress sp ON c.course_id = sp.course_id AND sp.student_id = $student_id
               WHERE ce.student_id = $student_id 
               ORDER BY ce.enrollment_date DESC";
$courses_result = mysqli_query($conn, $courses_query);

// Count total enrolled courses
$total_courses = mysqli_num_rows($courses_result);

// Get completed courses count
$completed_query = "SELECT COUNT(*) as completed FROM student_progress 
                 WHERE student_id = $student_id AND value = 100";
$completed_result = mysqli_query($conn, $completed_query);
$completed_data = mysqli_fetch_assoc($completed_result);
$completed_courses = $completed_data['completed'];

// Get in-progress courses count
$in_progress = $total_courses - $completed_courses;

// Get upcoming events/deadlines
$events_query = "SELECT ce.*, c.name as course_name 
              FROM course_events ce
              JOIN courses c ON ce.course_id = c.course_id
              JOIN course_enrollments e ON c.course_id = e.course_id
              WHERE e.student_id = $student_id 
              AND ce.date >= CURDATE()
              ORDER BY ce.date ASC, ce.time ASC
              LIMIT 5";
$events_result = mysqli_query($conn, $events_query);

// Get course materials
$materials_query = "SELECT cm.*, c.name as course_name
                 FROM course_materials cm
                 JOIN courses c ON cm.course_id = c.course_id
                 JOIN course_enrollments e ON c.course_id = e.course_id
                 WHERE e.student_id = $student_id
                 ORDER BY cm.upload_date DESC
                 LIMIT 5";
$materials_result = mysqli_query($conn, $materials_query);

// Get course schedules
$schedules_query = "SELECT s.*, c.name as course_name
                 FROM schedules s
                 JOIN courses c ON s.course_id = c.course_id
                 JOIN course_enrollments e ON c.course_id = e.course_id
                 WHERE e.student_id = $student_id
                 ORDER BY FIELD(s.day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')";
$schedules_result = mysqli_query($conn, $schedules_query);

// Get recommended courses (not enrolled)
$recommended_query = "SELECT c.*, u.name as instructor_name, cat.name as category_name
                   FROM courses c
                   JOIN users u ON c.instructor_id = u.user_id
                   JOIN category cat ON c.category_id = cat.category_id
                   WHERE c.course_id NOT IN (
                       SELECT course_id FROM course_enrollments WHERE student_id = $student_id
                   )
                   LIMIT 2";
$recommended_result = mysqli_query($conn, $recommended_query);


$base_url = ""; 
?>

<style>
:root {
  --primary: #04639b;
  --primary-dark: #034f7d;
  --primary-light: #e7f3fb;
  --secondary: #ff7e00;
  --success: #28a745;
  --info: #17a2b8;
  --warning: #ffc107;
  --danger: #dc3545;
  --light: #f8f9fa;
  --dark: #343a40;
  --gray: #6c757d;
  --gray-light: #e9ecef;
  --white: #ffffff;
  --shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  --border-radius: 8px;
  --transition: all 0.3s ease;
}

/* Dashboard Layout */
.dashboard-container {
  max-width: none;
  margin: 0 auto;
  padding: 20px;
}

/* Welcome Banner */
.welcome-banner {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: var(--white);
  border-radius: var(--border-radius);
  padding: 25px;
  margin-bottom: 30px;
  box-shadow: var(--shadow);
  position: relative;
  overflow: hidden;
}

.welcome-banner::before {
  content: '';
  position: absolute;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  background-image: url('assets/images/pattern.png');
  opacity: 0.3;
}

.welcome-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: relative;
  z-index: 1;
}

.welcome-text h2 {
  margin: 0;
  font-size: 1.8rem;
  font-weight: 700;
}

.welcome-text p {
  margin: 8px 0 0;
  opacity: 0.9;
  font-size: 1rem;
}

.resume-btn {
  background-color: var(--white);
  color: var(--primary);
  border: none;
  border-radius: 50px;
  padding: 10px 20px;
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
  display: flex;
  align-items: center;
  gap: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.resume-btn:hover {
  background-color: var(--light);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.resume-btn i {
  font-size: 1rem;
}

/* Stats Cards */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  background-color: var(--white);
  border-radius: var(--border-radius);
  padding: 20px;
  text-align: center;
  box-shadow: var(--shadow);
  transition: var(--transition);
  position: relative;
  overflow: hidden;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 5px;
}

.stat-card.primary::before {
  background-color: var(--primary);
}

.stat-card.success::before {
  background-color: var(--success);
}

.stat-card.info::before {
  background-color: var(--info);
}

.stat-card.warning::before {
  background-color: var(--warning);
}

.stat-icon {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 15px;
  font-size: 1.5rem;
}

.stat-card.primary .stat-icon {
  background-color: var(--primary-light);
  color: var(--primary);
}

.stat-card.success .stat-icon {
  background-color: rgba(40, 167, 69, 0.1);
  color: var(--success);
}

.stat-card.info .stat-icon {
  background-color: rgba(23, 162, 184, 0.1);
  color: var(--info);
}

.stat-card.warning .stat-icon {
  background-color: rgba(255, 193, 7, 0.1);
  color: var(--warning);
}

.stat-number {
  font-size: 2rem;
  font-weight: 700;
  margin: 0;
  line-height: 1;
}

.stat-label {
  color: var(--gray);
  margin: 8px 0 0;
  font-size: 0.9rem;
}

/* Section Styles */
.dashboard-section {
  background-color: var(--white);
  border-radius: var(--border-radius);
  padding: 25px;
  margin-bottom: 30px;
  box-shadow: var(--shadow);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 1px solid var(--gray-light);
}

.section-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--dark);
  margin: 0;
  display: flex;
  align-items: center;
  gap: 10px;
}

.section-title i {
  color: var(--primary);
}

.section-action {
  color: var(--primary);
  text-decoration: none;
  font-weight: 500;
  font-size: 0.9rem;
  transition: var(--transition);
  display: flex;
  align-items: center;
  gap: 5px;
}

.section-action:hover {
  color: var(--primary-dark);
}

/* Course Cards */
.courses-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 25px;
}

.course-card {
  background-color: var(--white);
  border-radius: var(--border-radius);
  overflow: hidden;
  box-shadow: var(--shadow);
  transition: var(--transition);
  border: 1px solid var(--gray-light);
  height: 100%;
  display: flex;
  flex-direction: column;
}

.course-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

.course-header {
  position: relative;
  overflow: hidden;
}

.course-img {
  width: 100%;
  height: 180px;
  object-fit: cover;
  transition: transform 0.5s ease;
}

.course-card:hover .course-img {
  transform: scale(1.05);
}

.course-badge {
  position: absolute;
  top: 15px;
  right: 15px;
  background-color: var(--primary);
  color: var(--white);
  padding: 5px 10px;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  z-index: 1;
}

.course-content {
  padding: 20px;
  flex-grow: 1;
  display: flex;
  flex-direction: column;
}

.course-title {
  font-size: 1.1rem;
  font-weight: 600;
  margin: 0 0 15px;
  color: var(--dark);
}

.course-progress {
  margin-bottom: 15px;
}

.progress-bar {
  height: 8px;
  background-color: var(--gray-light);
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 8px;
}

.progress-fill {
  height: 100%;
  background-color: var(--primary);
  border-radius: 4px;
  transition: width 0.5s ease;
}

.progress-fill.complete {
  background-color: var(--success);
}

.progress-text {
  display: flex;
  justify-content: space-between;
  font-size: 0.85rem;
  color: var(--gray);
}

.course-info {
  margin-bottom: 15px;
  flex-grow: 1;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
  font-size: 0.9rem;
}

.info-item i {
  color: var(--primary);
  width: 16px;
}

.course-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: auto;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: 6px;
  font-size: 0.85rem;
  font-weight: 500;
  text-decoration: none;
  transition: var(--transition);
  border: none;
  cursor: pointer;
}

.btn-primary {
  background-color: var(--primary);
  color: var(--white);
}

.btn-primary:hover {
  background-color: var(--primary-dark);
  color: var(--white);
}

.btn-success {
  background-color: var(--success);
  color: var(--white);
}

.btn-success:hover {
  background-color: #218838;
  color: var(--white);
}

.btn-info {
  background-color: var(--info);
  color: var(--white);
}

.btn-info:hover {
  background-color: #138496;
  color: var(--white);
}

.btn-outline {
  background-color: transparent;
  color: var(--primary);
  border: 1px solid var(--primary);
}

.btn-outline:hover {
  background-color: var(--primary-light);
}

/* Tables */
.dashboard-table {
  width: 100%;
  border-collapse: collapse;
}

.dashboard-table th {
  background-color: var(--primary-light);
  color: var(--primary-dark);
  padding: 12px 15px;
  text-align: left;
  font-weight: 600;
  font-size: 0.9rem;
}

.dashboard-table td {
  padding: 12px 15px;
  border-bottom: 1px solid var(--gray-light);
  font-size: 0.9rem;
}

.dashboard-table tr:last-child td {
  border-bottom: none;
}

.dashboard-table tr:hover td {
  background-color: rgba(4, 99, 155, 0.03);
}

.table-badge {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
}

.badge-soon {
  background-color: rgba(220, 53, 69, 0.1);
  color: var(--danger);
}

.badge-upcoming {
  background-color: rgba(255, 193, 7, 0.1);
  color: #856404;
}

.badge-file {
  background-color: rgba(23, 162, 184, 0.1);
  color: var(--info);
}

.badge-url {
  background-color: rgba(40, 167, 69, 0.1);
  color: var(--success);
}

.badge-text {
  background-color: rgba(108, 117, 125, 0.1);
  color: var(--gray);
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 30px 20px;
  color: var(--gray);
}

.empty-state i {
  font-size: 3rem;
  margin-bottom: 15px;
  color: var(--gray-light);
}

.empty-state p {
  margin-bottom: 20px;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
  .welcome-content {
      flex-direction: column;
      align-items: flex-start;
      gap: 15px;
  }
  
  .resume-btn {
      align-self: flex-start;
  }
  
  .stats-grid {
      grid-template-columns: repeat(2, 1fr);
  }
  
  .dashboard-table {
      display: block;
      overflow-x: auto;
  }
}

@media (max-width: 576px) {
  .stats-grid {
      grid-template-columns: 1fr;
  }
  
  .courses-grid {
      grid-template-columns: 1fr;
  }
  
  .dashboard-section {
      padding: 20px 15px;
  }
}

/* Toast Notification */
.toast-notification {
  position: fixed;
  bottom: 20px;
  right: 20px;
  padding: 15px 20px;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  display: flex;
  align-items: center;
  gap: 12px;
  z-index: 1000;
  transform: translateY(100px);
  opacity: 0;
  transition: all 0.3s ease;
}

.toast-notification.success {
  background-color: var(--success);
  color: white;
}

.toast-notification.error {
  background-color: var(--danger);
  color: white;
}

.toast-notification.show {
  transform: translateY(0);
  opacity: 1;
}

.toast-notification i {
  font-size: 1.2rem;
}

.toast-message {
  flex-grow: 1;
  font-weight: 500;
}

.toast-close {
  background: none;
  border: none;
  color: white;
  cursor: pointer;
  font-size: 1.2rem;
  opacity: 0.8;
  transition: opacity 0.2s;
}

.toast-close:hover {
  opacity: 1;
}

/* Animations */
@keyframes fadeIn {
  from {
      opacity: 0;
      transform: translateY(20px);
  }
  to {
      opacity: 1;
      transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fadeIn 0.5s ease forwards;
}

.delay-1 {
  animation-delay: 0.1s;
}

.delay-2 {
  animation-delay: 0.2s;
}

.delay-3 {
  animation-delay: 0.3s;
}

.delay-4 {
  animation-delay: 0.4s;
}
</style>

<div class="dashboard-container">
  <!-- Welcome Banner -->
  <div class="welcome-banner animate-fade-in">
      <div class="welcome-content">
          <div class="welcome-text">
              <h2><?php echo translate('Welcome back'); ?>, <?php echo htmlspecialchars($student_name); ?>!</h2>
              <p><?php echo translate('Continue your learning journey today and achieve your goals.'); ?></p>
          </div>
          <button id="resumeBtn" class="resume-btn">
              <i class="fas fa-play-circle"></i> <?php echo translate('Resume Learning'); ?>
          </button>
      </div>
  </div>

  <!-- Stats Cards -->
  <div class="stats-grid">
      <div class="stat-card primary animate-fade-in delay-1">
          <div class="stat-icon">
              <i class="fas fa-book-open"></i>
          </div>
          <h3 class="stat-number"><?php echo $total_courses; ?></h3>
          <p class="stat-label"><?php echo translate('Enrolled Courses'); ?></p>
      </div>
      
      <div class="stat-card success animate-fade-in delay-2">
          <div class="stat-icon">
              <i class="fas fa-check-circle"></i>
          </div>
          <h3 class="stat-number"><?php echo $completed_courses; ?></h3>
          <p class="stat-label"><?php echo translate('Completed'); ?></p>
      </div>
      
      <div class="stat-card info animate-fade-in delay-3">
          <div class="stat-icon">
              <i class="fas fa-spinner"></i>
          </div>
          <h3 class="stat-number"><?php echo $in_progress; ?></h3>
          <p class="stat-label"><?php echo translate('In Progress'); ?></p>
      </div>
      
      <div class="stat-card warning animate-fade-in delay-4">
          <div class="stat-icon">
              <i class="fas fa-calendar-alt"></i>
          </div>
          <h3 class="stat-number"><?php echo mysqli_num_rows($events_result); ?></h3>
          <p class="stat-label"><?php echo translate('Upcoming Events'); ?></p>
      </div>
  </div>

  <!-- My Courses Section -->
  <div class="dashboard-section animate-fade-in">
      <div class="section-header">
          <h2 class="section-title">
              <i class="fas fa-graduation-cap"></i> <?php echo translate('My Courses'); ?>
          </h2>
          <a href="<?php echo $base_url; ?>student_view_my_courses.php" class="section-action">
              <?php echo translate('View All'); ?> <i class="fas fa-arrow-right"></i>
          </a>
      </div>
      
      <?php if ($total_courses > 0): ?>
          <div class="courses-grid">
              <?php 
              // Reset the result pointer to the beginning
              mysqli_data_seek($courses_result, 0);
              
              while ($course = mysqli_fetch_assoc($courses_result)): 
                  // Set default progress to 0 if null
                  $progress = isset($course['progress']) ? $course['progress'] : 0;
                  $course_id = $course['course_id'];
                  
                  // Get instructor name
                  $instructor_query = "SELECT name FROM users WHERE user_id = " . $course['instructor_id'];
                  $instructor_result = mysqli_query($conn, $instructor_query);
                  $instructor_name = "Unknown";
                  
                  if ($instructor_result && mysqli_num_rows($instructor_result) > 0) {
                      $instructor = mysqli_fetch_assoc($instructor_result);
                      $instructor_name = $instructor['name'];
                  }
              ?>
                  <div class="course-card">
                      <div class="course-header">
                          <img src="<?php echo $base_url; ?>assets/images/courses/<?php echo htmlspecialchars($course['img']); ?>" 
                               class="course-img" alt="<?php echo htmlspecialchars($course['name']); ?>">
                          <span class="course-badge">
                              <?php echo ($progress == 100) ? translate('Completed') : $progress . '% ' . translate('Complete'); ?>
                          </span>
                      </div>
                      <div class="course-content">
                          <h3 class="course-title"><?php echo htmlspecialchars($course['name']); ?></h3>
                          
                          <div class="course-progress">
                              <div class="progress-bar">
                                  <div class="progress-fill <?php echo ($progress == 100) ? 'complete' : ''; ?>" 
                                       style="width: <?php echo (int)$progress; ?>%">
                                  </div>
                              </div>
                              <div class="progress-text">
                                  <span><?php echo (int)$progress; ?>% <?php echo translate('Complete'); ?></span>
                                  <?php if ($progress == 100): ?>
                                      <span><i class="fas fa-check-circle" style="color: var(--success);"></i> <?php echo translate('Finished'); ?></span>
                                  <?php endif; ?>
                              </div>
                          </div>
                          
                          <div class="course-info">
                              <div class="info-item">
                                  <i class="fas fa-chalkboard-teacher"></i>
                                  <span><?php echo htmlspecialchars($instructor_name); ?></span>
                              </div>
                              <div class="info-item">
                                  <i class="fas fa-calendar"></i>
                                  <span><?php echo translate('Started'); ?>: <?php echo date('M d, Y', strtotime($course['start_date'])); ?></span>
                              </div>
                              <?php if (!empty($course['prerequisites'])): ?>
                              <div class="info-item">
                                  <i class="fas fa-list-ul"></i>
                                  <span><?php echo translate('Prerequisites'); ?>: <?php echo htmlspecialchars($course['prerequisites']); ?></span>
                              </div>
                              <?php endif; ?>
                          </div>
                          
                          <div class="course-actions">
                              <a href="<?php echo $base_url; ?>student_view_progress.php?course_id=<?php echo $course['course_id']; ?>" class="btn btn-primary">
                                  <i class="fas fa-<?php echo ($progress > 0) ? 'play-circle' : 'book-open'; ?>"></i>
                                  <?php echo ($progress > 0) ? translate('Continue') : translate('Start Course'); ?>
                              </a>
                              
                              <?php if ($progress == 100): ?>
                                  <a href="<?php echo $base_url; ?>student_print_certificate.php?course_id=<?php echo (int)$course_id; ?>" class="btn btn-success">
                                      <i class="fas fa-certificate"></i> <?php echo translate('Certificate'); ?>
                                  </a>
                              <?php endif; ?>
                              
                              <a href="<?php echo $base_url; ?>student_view_materials.php?course_id=<?php echo (int)$course_id; ?>" class="btn btn-info">
                                  <i class="fas fa-file-alt"></i> <?php echo translate('Materials'); ?>
                              </a>
                          </div>
                      </div>
                  </div>
              <?php endwhile; ?>
          </div>
      <?php else: ?>
          <div class="empty-state">
              <i class="fas fa-book"></i>
              <p><?php echo translate('You are not enrolled in any courses yet. Explore our course catalog to get started!'); ?></p>
              <a href="<?php echo $base_url; ?>student_view_courses.php" class="btn btn-primary"><?php echo translate('Browse Courses'); ?></a>
          </div>
      <?php endif; ?>
  </div>

  <!-- My Schedule Section -->
  <div class="dashboard-section animate-fade-in">
      <div class="section-header">
          <h2 class="section-title">
              <i class="fas fa-calendar-week"></i> <?php echo translate('My Schedule'); ?>
          </h2>
          
      </div>
      
      <?php if (mysqli_num_rows($schedules_result) > 0): ?>
          <div class="table-responsive">
              <table class="dashboard-table">
                  <thead>
                      <tr>
                          <th><i class="fas fa-calendar-day"></i> <?php echo translate('Day'); ?></th>
                          <th><i class="fas fa-book"></i> <?php echo translate('Course'); ?></th>
                          <th><i class="fas fa-clock"></i> <?php echo translate('Time'); ?></th>
                          <th><i class="fas fa-map-marker-alt"></i> <?php echo translate('Location'); ?></th>
                      </tr>
                  </thead>
                  <tbody>
                      <?php while ($schedule = mysqli_fetch_assoc($schedules_result)): ?>
                          <tr>
                              <td><?php echo translate($schedule['day']); ?></td>
                              <td><?php echo htmlspecialchars($schedule['course_name']); ?></td>
                              <td>
                                  <?php 
                                      echo date('h:i A', strtotime($schedule['start_time'])) . ' - ' . 
                                           date('h:i A', strtotime($schedule['end_time']));
                                  ?>
                              </td>
                              <td><?php echo htmlspecialchars($schedule['location']); ?></td>
                          </tr>
                      <?php endwhile; ?>
                  </tbody>
              </table>
          </div>
      <?php else: ?>
          <div class="empty-state">
              <i class="fas fa-calendar-times"></i>
              <p><?php echo translate('You don\'t have any scheduled classes yet.'); ?></p>
          </div>
      <?php endif; ?>
  </div>

  <!-- Upcoming Events Section -->
  <div class="dashboard-section animate-fade-in">
      <div class="section-header">
          <h2 class="section-title">
              <i class="fas fa-bell"></i> <?php echo translate('Upcoming Events'); ?>
          </h2>
          
      </div>
      
      <?php if (mysqli_num_rows($events_result) > 0): ?>
          <div class="table-responsive">
              <table class="dashboard-table">
                  <thead>
                      <tr>
                          <th><i class="fas fa-calendar-day"></i> <?php echo translate('Date'); ?> & <?php echo translate('Time'); ?></th>
                          <th><i class="fas fa-bookmark"></i> <?php echo translate('Event'); ?></th>
                          <th><i class="fas fa-book"></i> <?php echo translate('Course'); ?></th>
                          <th><i class="fas fa-map-marker-alt"></i> <?php echo translate('Location'); ?></th>
                      </tr>
                  </thead>
                  <tbody>
                      <?php while ($event = mysqli_fetch_assoc($events_result)): ?>
                          <tr>
                              <td>
                                  <?php 
                                      $event_date = new DateTime($event['date']);
                                      $today = new DateTime();
                                      $diff = $today->diff($event_date)->days;
                                      
                                      echo date('M d, Y', strtotime($event['date'])) . ' at ' . 
                                           date('h:i A', strtotime($event['time']));
                                      
                                      if ($diff <= 3 && $diff >= 0) {
                                          echo ' <span class="table-badge badge-soon">'.translate('Soon!').'</span>';
                                      } else if ($diff <= 7) {
                                          echo ' <span class="table-badge badge-upcoming">'.translate('Upcoming').'</span>';
                                      }
                                  ?>
                              </td>
                              <td><?php echo htmlspecialchars($event['title']); ?></td>
                              <td><?php echo htmlspecialchars($event['course_name']); ?></td>
                              <td><?php echo htmlspecialchars($event['location']); ?></td>
                          </tr>
                      <?php endwhile; ?>
                  </tbody>
              </table>
          </div>
      <?php else: ?>
          <div class="empty-state">
              <i class="fas fa-calendar-times"></i>
              <p><?php echo translate('You don\'t have any upcoming events.'); ?></p>
          </div>
      <?php endif; ?>
  </div>
</div>

<!-- Toast Notification -->
<div id="toast-notification" class="toast-notification">
  <i class="fas fa-check-circle"></i>
  <div class="toast-message" id="toast-message"><?php echo translate('Action completed successfully!'); ?></div>
  <button class="toast-close" onclick="hideToast()">×</button>
</div>

<script>
// Function to show toast notification
function showToast(message, type = 'success') {
  const toast = document.getElementById('toast-notification');
  const toastMessage = document.getElementById('toast-message');
  
  // Set message
  toastMessage.textContent = message;
  
  // Set type (success or error)
  toast.className = 'toast-notification ' + type;
  
  // Show the toast
  toast.classList.add('show');
  
  // Hide after 3 seconds
  setTimeout(hideToast, 3000);
}

// Function to hide toast notification
function hideToast() {
  const toast = document.getElementById('toast-notification');
  toast.classList.remove('show');
}

// Resume Learning button functionality
document.getElementById('resumeBtn').addEventListener('click', function() {
  // Find the first in-progress course
  const courseButtons = document.querySelectorAll('.btn-primary');
  
  if (courseButtons.length > 0) {
      // Navigate to the first course
      window.location.href = courseButtons[0].href;
  } else {
      showToast('<?php echo translate('No courses available to resume'); ?>', 'error');
  }
});

// Add animation to elements when they come into view
document.addEventListener('DOMContentLoaded', function() {
  const animatedElements = document.querySelectorAll('.animate-fade-in');
  
  const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
          if (entry.isIntersecting) {
              entry.target.style.opacity = 1;
              entry.target.style.transform = 'translateY(0)';
              observer.unobserve(entry.target);
          }
      });
  }, {
      threshold: 0.1
  });
  
  animatedElements.forEach(element => {
      element.style.opacity = 0;
      element.style.transform = 'translateY(20px)';
      element.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
      observer.observe(element);
  });
});
</script>

<?php include 'footer.php'; ?>
