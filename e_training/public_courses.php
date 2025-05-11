<?php 
include 'config.php';

// Fetch all available courses for the public view
$courses_query = "SELECT c.course_id, c.name as course_name, c.description, c.img, 
                 cat.name as category_name, u.name as instructor_name,
                 cat.category_id
                 FROM courses c
                 JOIN category cat ON c.category_id = cat.category_id
                 JOIN users u ON c.instructor_id = u.user_id
                 WHERE c.course_id > 0
                 ORDER BY c.course_id DESC";
$courses_result = mysqli_query($con, $courses_query);

// Fetch course categories for the filter
$categories_query = "SELECT category_id, name FROM category ORDER BY name";
$categories_result = mysqli_query($con, $categories_query);

// Check if language is RTL
$lang = $_COOKIE['lang'] ?? 'en';
$isRTL = isRTLLanguage($lang);
$dirAttribute = $isRTL ? 'dir="rtl"' : '';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" <?php echo $dirAttribute; ?>>
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title><?php echo translate('Available Courses'); ?> - <?php echo translate('E-Training Platform'); ?></title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
   <style>
       :root {
           --primary-color: #04639b;
           --primary-dark: #035483;
           --primary-light: #e6f1f8;
           --secondary-color: #ff7e00;
           --text-dark: #333;
           --text-light: #666;
           --white: #ffffff;
           --light-bg: #f9f9f9;
           --border-color: #eaeaea;
           --shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
           --transition: all 0.3s ease;
       }

       * {
           margin: 0;
           padding: 0;
           box-sizing: border-box;
       }

       body {
           font-family: 'Poppins', sans-serif;
           line-height: 1.6;
           color: var(--text-dark);
           background-color: var(--light-bg);
       }

       .container {
           width: 100%;
           max-width: 1200px;
           margin: 0 auto;
           padding: 0 20px;
       }

       /* Header styles */
       .main-header {
           position: sticky;
           top: 0;
           z-index: 1000;
           background-color: var(--white);
           box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
       }

       .header-container {
           display: flex;
           justify-content: space-between;
           align-items: center;
           padding: 15px 0;
       }

       .logo {
           display: flex;
           align-items: center;
       }

       .logo img {
           height: 40px;
           margin-right: 10px;
       }

       .logo h1 {
           font-size: 1.5rem;
           color: var(--primary-color);
           margin: 0;
           font-weight: 700;
       }

       .nav-links {
           display: flex;
           gap: 25px;
       }

       .nav-links a {
           color: var(--text-dark);
           text-decoration: none;
           font-weight: 500;
           transition: var(--transition);
           position: relative;
       }

       .nav-links a:after {
           content: '';
           position: absolute;
           width: 0;
           height: 2px;
           bottom: -5px;
           left: 0;
           background-color: var(--primary-color);
           transition: var(--transition);
       }

       .nav-links a:hover {
           color: var(--primary-color);
       }

       .nav-links a:hover:after {
           width: 100%;
       }

       .auth-buttons {
           display: flex;
           gap: 15px;
       }

       .auth-btn {
           padding: 10px 20px;
           border-radius: 50px;
           text-decoration: none;
           font-weight: 500;
           transition: var(--transition);
           display: inline-flex;
           align-items: center;
           justify-content: center;
       }

       .login-btn {
           background-color: transparent;
           color: var(--primary-color);
           border: 1px solid var(--primary-color);
       }

       .login-btn:hover {
           background-color: var(--primary-light);
       }

       .register-btn {
           background-color: var(--primary-color);
           color: var(--white);
           border: 1px solid var(--primary-color);
       }

       .register-btn:hover {
           background-color: var(--primary-dark);
           transform: translateY(-2px);
       }

       .auth-btn i {
           margin-right: 8px;
       }

       /* Language switcher */
       .language-switcher {
           display: flex;
           gap: 10px;
           margin-left: 15px;
       }

       .lang-btn {
           padding: 5px 10px;
           border-radius: 4px;
           text-decoration: none;
           font-weight: 500;
           font-size: 0.9rem;
           transition: var(--transition);
           border: 1px solid var(--border-color);
       }

       .lang-btn.active {
           background-color: var(--primary-color);
           color: var(--white);
           border-color: var(--primary-color);
       }

       .lang-btn:not(.active) {
           background-color: var(--white);
           color: var(--text-dark);
       }

       .lang-btn:hover:not(.active) {
           background-color: var(--primary-light);
       }

       /* Mobile menu */
       .mobile-menu-btn {
           display: none;
           background: none;
           border: none;
           font-size: 1.5rem;
           color: var(--primary-color);
           cursor: pointer;
       }

       .mobile-menu {
           display: none;
           position: fixed;
           top: 70px;
           left: 0;
           right: 0;
           background-color: var(--white);
           padding: 20px;
           box-shadow: var(--shadow);
           z-index: 100;
           flex-direction: column;
           gap: 15px;
       }

       .mobile-menu.active {
           display: flex;
       }

       .mobile-menu a {
           color: var(--text-dark);
           text-decoration: none;
           font-weight: 500;
           padding: 10px 0;
           border-bottom: 1px solid var(--border-color);
           transition: var(--transition);
       }

       .mobile-menu a:hover {
           color: var(--primary-color);
       }

       .mobile-auth {
           display: flex;
           flex-direction: column;
           gap: 10px;
           margin-top: 10px;
       }

       .mobile-language {
           display: flex;
           gap: 10px;
           margin-top: 10px;
       }

       /* Page header */
       .page-header {
           background-color: var(--primary-color);
           color: var(--white);
           padding: 60px 0;
           text-align: center;
       }

       .page-title {
           font-size: 2.5rem;
           margin-bottom: 15px;
           font-weight: 700;
       }

       .page-subtitle {
           font-size: 1.1rem;
           max-width: 700px;
           margin: 0 auto;
           opacity: 0.9;
       }

       /* Search and filter section */
       .search-filter-section {
           background-color: var(--white);
           padding: 30px 0;
           box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
       }

       .search-filter-container {
           display: flex;
           flex-wrap: wrap;
           gap: 20px;
           align-items: center;
       }

       .search-box {
           flex: 1;
           min-width: 250px;
           position: relative;
       }

       .search-input {
           width: 100%;
           padding: 12px 20px;
           padding-left: 45px;
           border: 1px solid var(--border-color);
           border-radius: 50px;
           font-size: 1rem;
           transition: var(--transition);
       }

       .search-input:focus {
           outline: none;
           border-color: var(--primary-color);
           box-shadow: 0 0 0 3px rgba(4, 99, 155, 0.1);
       }

       .search-icon {
           position: absolute;
           left: 15px;
           top: 50%;
           transform: translateY(-50%);
           color: var(--text-light);
       }

       .filter-container {
           display: flex;
           gap: 15px;
           flex-wrap: wrap;
       }

       .filter-select {
           padding: 12px 20px;
           border: 1px solid var(--border-color);
           border-radius: 50px;
           font-size: 1rem;
           background-color: var(--white);
           min-width: 180px;
           cursor: pointer;
           transition: var(--transition);
       }

       .filter-select:focus {
           outline: none;
           border-color: var(--primary-color);
           box-shadow: 0 0 0 3px rgba(4, 99, 155, 0.1);
       }

       /* Courses section */
       .courses-section {
           padding: 60px 0;
       }

       .section-title {
           font-size: 2rem;
           margin-bottom: 40px;
           text-align: center;
           color: var(--text-dark);
       }

       .courses-grid {
           display: grid;
           grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
           gap: 30px;
       }

       .course-card {
           background-color: var(--white);
           border-radius: 10px;
           overflow: hidden;
           box-shadow: var(--shadow);
           transition: var(--transition);
           display: flex;
           flex-direction: column;
       }

       .course-card:hover {
           transform: translateY(-10px);
       }

       .course-image-container {
           position: relative;
           overflow: hidden;
           height: 200px;
       }

       .course-image {
           width: 100%;
           height: 100%;
           object-fit: cover;
           transition: transform 0.5s ease;
       }

       .course-card:hover .course-image {
           transform: scale(1.1);
       }

       .course-category {
           position: absolute;
           top: 15px;
           left: 15px;
           background-color: var(--primary-color);
           color: var(--white);
           padding: 5px 15px;
           border-radius: 50px;
           font-size: 0.8rem;
           font-weight: 500;
       }

       .course-content {
           padding: 20px;
           flex-grow: 1;
           display: flex;
           flex-direction: column;
       }

       .course-title {
           font-size: 1.3rem;
           margin-bottom: 10px;
           color: var(--text-dark);
           font-weight: 600;
           line-height: 1.4;
       }

       .course-title a {
           color: inherit;
           text-decoration: none;
           transition: var(--transition);
       }

       .course-title a:hover {
           color: var(--primary-color);
       }

       .course-instructor {
           color: var(--text-light);
           font-size: 0.9rem;
           margin-bottom: 15px;
           display: flex;
           align-items: center;
       }

       .course-instructor i {
           color: var(--primary-color);
           margin-right: 5px;
       }

       .course-description {
           color: var(--text-light);
           margin-bottom: 20px;
           line-height: 1.6;
           flex-grow: 1;
       }

       .course-footer {
           display: flex;
           justify-content: space-between;
           align-items: center;
           padding-top: 15px;
           border-top: 1px solid var(--border-color);
       }

       .course-rating {
           display: flex;
           align-items: center;
           color: var(--text-light);
           font-size: 0.9rem;
       }

       .course-rating i {
           color: #ffc107;
           margin-right: 5px;
       }

       .course-students {
           display: flex;
           align-items: center;
           color: var(--text-light);
           font-size: 0.9rem;
       }

       .course-students i {
           margin-right: 5px;
       }

       .course-btn {
           display: inline-block;
           padding: 10px 20px;
           background-color: var(--primary-color);
           color: var(--white);
           text-decoration: none;
           border-radius: 5px;
           font-weight: 500;
           transition: var(--transition);
           text-align: center;
           margin-top: 15px;
       }

       .course-btn:hover {
           background-color: var(--primary-dark);
       }

       /* Pagination */
       .pagination {
           display: flex;
           justify-content: center;
           margin-top: 50px;
           gap: 10px;
       }

       .pagination-btn {
           display: inline-flex;
           align-items: center;
           justify-content: center;
           width: 40px;
           height: 40px;
           border-radius: 50%;
           background-color: var(--white);
           color: var(--text-dark);
           text-decoration: none;
           font-weight: 500;
           transition: var(--transition);
           border: 1px solid var(--border-color);
       }

       .pagination-btn.active {
           background-color: var(--primary-color);
           color: var(--white);
           border-color: var(--primary-color);
       }

       .pagination-btn:hover:not(.active) {
           background-color: var(--primary-light);
       }

       /* No results */
       .no-results {
           text-align: center;
           padding: 50px 20px;
           background-color: var(--white);
           border-radius: 10px;
           box-shadow: var(--shadow);
       }

       .no-results-icon {
           font-size: 3rem;
           color: var(--text-light);
           margin-bottom: 20px;
       }

       .no-results-title {
           font-size: 1.5rem;
           margin-bottom: 10px;
           color: var(--text-dark);
       }

       .no-results-text {
           color: var(--text-light);
           margin-bottom: 20px;
       }

       .reset-btn {
           display: inline-block;
           padding: 10px 20px;
           background-color: var(--primary-color);
           color: var(--white);
           text-decoration: none;
           border-radius: 5px;
           font-weight: 500;
           transition: var(--transition);
       }

       .reset-btn:hover {
           background-color: var(--primary-dark);
       }

       /* Course placeholder */
       .course-placeholder {
           background-color: #f0f0f0;
           height: 200px;
           width: 100%;
           display: flex;
           align-items: center;
           justify-content: center;
           color: #999;
           font-size: 0.9rem;
       }

       /* Footer */
       .footer {
           background-color: #1a1a1a;
           color: var(--white);
           padding: 60px 0 0;
       }

       .footer-container {
           display: grid;
           grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
           gap: 40px;
       }

       .footer-section h3 {
           color: var(--white);
           margin-bottom: 25px;
           font-size: 1.3rem;
           font-weight: 600;
           position: relative;
           padding-bottom: 10px;
       }

       .footer-section h3::after {
           content: '';
           position: absolute;
           bottom: 0;
           left: 0;
           width: 50px;
           height: 2px;
           background-color: var(--primary-color);
       }

       .footer-about p {
           color: #bbb;
           margin-bottom: 20px;
           line-height: 1.6;
       }

       .social-links {
           display: flex;
           gap: 15px;
       }

       .social-links a {
           display: flex;
           align-items: center;
           justify-content: center;
           width: 40px;
           height: 40px;
           background-color: rgba(255, 255, 255, 0.1);
           color: var(--white);
           border-radius: 50%;
           text-decoration: none;
           transition: var(--transition);
       }

       .social-links a:hover {
           background-color: var(--primary-color);
           transform: translateY(-3px);
       }

       .footer-links {
           list-style: none;
       }

       .footer-links li {
           margin-bottom: 15px;
       }

       .footer-links a {
           color: #bbb;
           text-decoration: none;
           transition: var(--transition);
           display: flex;
           align-items: center;
       }

       .footer-links a i {
           margin-right: 10px;
           color: var(--primary-color);
       }

       .footer-links a:hover {
           color: var(--white);
           padding-left: 5px;
       }

       .footer-contact li {
           display: flex;
           align-items: flex-start;
           margin-bottom: 15px;
           color: #bbb;
       }

       .footer-contact i {
           margin-right: 10px;
           color: var(--primary-color);
           margin-top: 5px;
       }

       .copyright {
           text-align: center;
           padding: 20px 0;
           margin-top: 40px;
           border-top: 1px solid rgba(255, 255, 255, 0.1);
           color: #bbb;
           font-size: 0.9rem;
       }

       /* RTL specific styles */
       html[dir="rtl"] .logo img {
           margin-right: 0;
           margin-left: 10px;
       }

       html[dir="rtl"] .auth-btn i,
       html[dir="rtl"] .footer-links a i,
       html[dir="rtl"] .footer-contact i {
           margin-right: 0;
           margin-left: 8px;
       }

       html[dir="rtl"] .course-instructor i,
       html[dir="rtl"] .course-rating i,
       html[dir="rtl"] .course-students i {
           margin-right: 0;
           margin-left: 5px;
       }

       html[dir="rtl"] .search-icon {
           left: auto;
           right: 15px;
       }

       html[dir="rtl"] .search-input {
           padding-left: 20px;
           padding-right: 45px;
       }

       html[dir="rtl"] .footer-section h3::after {
           left: auto;
           right: 0;
       }

       /* Responsive adjustments */
       @media (max-width: 992px) {
           .page-title {
               font-size: 2rem;
           }
       }

       @media (max-width: 768px) {
           .nav-links, .auth-buttons, .language-switcher {
               display: none;
           }

           .mobile-menu-btn {
               display: block;
           }

           .search-filter-container {
               flex-direction: column;
               align-items: stretch;
           }

           .filter-container {
               flex-direction: column;
           }
       }

       @media (max-width: 576px) {
           .page-title {
               font-size: 1.8rem;
           }

           .section-title {
               font-size: 1.5rem;
           }

           .courses-grid {
               grid-template-columns: 1fr;
           }
       }
   </style>
</head>
<body>
   <!-- Header -->
   <header class="main-header">
       <div class="container">
           <div class="header-container">
               <div class="logo">
                   <img src="assets/images/logo.png" alt="<?php echo translate('E-Training Logo'); ?>">
                   <h1><?php echo translate('E-Training'); ?></h1>
               </div>
               
               <nav class="nav-links">
                   <a href="index.php"><?php echo translate('Home'); ?></a>
                   <a href="public_courses.php" class="active"><?php echo translate('Courses'); ?></a>
                   <a href="#about"><?php echo translate('About Us'); ?></a>
                   <a href="#contact"><?php echo translate('Contact Us'); ?></a>
               </nav>
               
               <div class="auth-buttons">
                   <a href="login.php" class="auth-btn login-btn"><i class="fas fa-sign-in-alt"></i> <?php echo translate('Login'); ?></a>
                   <a href="student_register.php" class="auth-btn register-btn"><i class="fas fa-user-plus"></i> <?php echo translate('Register'); ?></a>
               </div>
               
               <!-- Language Switcher -->
               <div class="language-switcher">
                   <a href="?lang=en" class="lang-btn <?php echo $lang == 'en' ? 'active' : ''; ?>">EN</a>
                   <a href="?lang=ar" class="lang-btn <?php echo $lang == 'ar' ? 'active' : ''; ?>">AR</a>
               </div>
               
               <button class="mobile-menu-btn" id="mobileMenuBtn">
                   <i class="fas fa-bars"></i>
               </button>
           </div>
       </div>
   </header>

   <!-- Mobile Menu -->
   <div class="mobile-menu" id="mobileMenu">
       <a href="index.php"><?php echo translate('Home'); ?></a>
       <a href="public_courses.php"><?php echo translate('Courses'); ?></a>
       <a href="#about"><?php echo translate('About Us'); ?></a>
       <a href="#contact"><?php echo translate('Contact Us'); ?></a>
       <div class="mobile-auth">
           <a href="login.php" class="auth-btn login-btn"><i class="fas fa-sign-in-alt"></i> <?php echo translate('Login'); ?></a>
           <a href="student_register.php" class="auth-btn register-btn"><i class="fas fa-user-plus"></i> <?php echo translate('Register'); ?></a>
       </div>
       <div class="mobile-language">
           <a href="?lang=en" class="lang-btn <?php echo $lang == 'en' ? 'active' : ''; ?>">EN</a>
           <a href="?lang=ar" class="lang-btn <?php echo $lang == 'ar' ? 'active' : ''; ?>">AR</a>
       </div>
   </div>

   <!-- Page Header -->
   <section class="page-header">
       <div class="container">
           <h1 class="page-title"><?php echo translate('Explore Our Courses'); ?></h1>
           <p class="page-subtitle"><?php echo translate('Discover a wide range of courses designed to help you advance your skills and career'); ?></p>
       </div>
   </section>

   <!-- Search and Filter Section -->
   <section class="search-filter-section">
       <div class="container">
           <div class="search-filter-container">
               <div class="search-box">
                   <i class="fas fa-search search-icon"></i>
                   <input type="text" id="courseSearch" class="search-input" placeholder="<?php echo translate('Search for courses...'); ?>">
               </div>
               <div class="filter-container">
                   <select id="categoryFilter" class="filter-select">
                       <option value=""><?php echo translate('All Categories'); ?></option>
                       <?php if ($categories_result && mysqli_num_rows($categories_result) > 0): ?>
                           <?php while ($category = mysqli_fetch_assoc($categories_result)): ?>
                               <option value="<?php echo $category['category_id']; ?>"><?php echo translate($category['name']); ?></option>
                           <?php endwhile; ?>
                       <?php endif; ?>
                   </select>
                   <select id="sortFilter" class="filter-select">
                       <option value="newest"><?php echo translate('Newest First'); ?></option>
                       <option value="oldest"><?php echo translate('Oldest First'); ?></option>
                       <option value="name_asc"><?php echo translate('Name (A-Z)'); ?></option>
                       <option value="name_desc"><?php echo translate('Name (Z-A)'); ?></option>
                   </select>
               </div>
           </div>
       </div>
   </section>

   <!-- Courses Section -->
   <section class="courses-section">
       <div class="container">
           <h2 class="section-title"><?php echo translate('Available Courses'); ?></h2>
           
           <div class="courses-grid" id="coursesGrid">
               <?php if ($courses_result && mysqli_num_rows($courses_result) > 0): ?>
                   <?php while ($course = mysqli_fetch_assoc($courses_result)): 
                       // Get student count for this course
                       $student_count_query = "SELECT COUNT(*) as student_count FROM course_enrollments WHERE course_id = " . $course['course_id'];
                       $student_count_result = mysqli_query($con, $student_count_query);
                       $student_count = ($student_count_result && mysqli_num_rows($student_count_result) > 0) ? mysqli_fetch_assoc($student_count_result)['student_count'] : 0;
                       
                       // Get average rating for this course
                       $rating_query = "SELECT AVG(rating) as avg_rating, COUNT(*) as rating_count FROM course_feedback WHERE course_id = " . $course['course_id'];
                       $rating_result = mysqli_query($con, $rating_query);
                       $rating_data = ($rating_result && mysqli_num_rows($rating_result) > 0) ? mysqli_fetch_assoc($rating_result) : ['avg_rating' => 0, 'rating_count' => 0];
                       $avg_rating = round($rating_data['avg_rating'], 1);
                       $rating_count = $rating_data['rating_count'];
                   ?>
                       <div class="course-card" data-category="<?php echo $course['category_id']; ?>">
                           <div class="course-image-container">
                               <?php if (!empty($course['img'])): ?>
                                   <img src="assets/images/courses/<?php echo $course['img']; ?>" alt="<?php echo translate($course['course_name']); ?>" class="course-image">
                               <?php else: ?>
                                   <div class="course-placeholder">
                                       <i class="fas fa-image"></i> <?php echo translate('No image available'); ?>
                                   </div>
                               <?php endif; ?>
                               <div class="course-category"><?php echo translate($course['category_name']); ?></div>
                           </div>
                           <div class="course-content">
                               <h3 class="course-title">
                                   <a href="course_details.php?id=<?php echo $course['course_id']; ?>"><?php echo translate($course['course_name']); ?></a>
                               </h3>
                               <div class="course-instructor">
                                   <i class="fas fa-chalkboard-teacher"></i> <?php echo $course['instructor_name']; ?>
                               </div>
                               <p class="course-description">
                                   <?php echo translate(substr($course['description'], 0, 100) . (strlen($course['description']) > 100 ? '...' : '')); ?>
                               </p>
                               <div class="course-footer">
                                   <div class="course-rating">
                                       <i class="fas fa-star"></i> <?php echo $avg_rating; ?> (<?php echo $rating_count; ?>)
                                   </div>
                                   <div class="course-students">
                                       <i class="fas fa-user-graduate"></i> <?php echo $student_count; ?> <?php echo translate('students'); ?>
                                   </div>
                               </div>
                               <a href="course_details.php?id=<?php echo $course['course_id']; ?>" class="course-btn"><?php echo translate('View Details'); ?></a>
                           </div>
                       </div>
                   <?php endwhile; ?>
               <?php else: ?>
                   <!-- Display message when no courses are found -->
                   <div class="no-results">
                       <div class="no-results-icon">
                           <i class="fas fa-search"></i>
                       </div>
                       <h3 class="no-results-title"><?php echo translate('No Courses Found'); ?></h3>
                       <p class="no-results-text"><?php echo translate('We couldn\'t find any courses matching your criteria. Please try different search terms or browse all courses.'); ?></p>
                       <a href="public_courses.php" class="reset-btn"><?php echo translate('View All Courses'); ?></a>
                   </div>
               <?php endif; ?>
           </div>
           
           <!-- No Results Message (initially hidden) -->
           <div class="no-results" id="noResults" style="display: none;">
               <div class="no-results-icon">
                   <i class="fas fa-search"></i>
               </div>
               <h3 class="no-results-title"><?php echo translate('No Courses Found'); ?></h3>
               <p class="no-results-text"><?php echo translate('We couldn\'t find any courses matching your search criteria.'); ?></p>
               <button id="resetFiltersBtn" class="reset-btn"><?php echo translate('Reset Filters'); ?></button>
           </div>
           
           <!-- Pagination -->
           <div class="pagination">
               <a href="#" class="pagination-btn active">1</a>
               <a href="#" class="pagination-btn">2</a>
               <a href="#" class="pagination-btn">3</a>
               <a href="#" class="pagination-btn"><i class="fas fa-chevron-right"></i></a>
           </div>
       </div>
   </section>

   <!-- Footer -->
   <footer class="footer">
       <div class="container">
           <div class="footer-container">
               <div class="footer-section footer-about">
                   <h3><?php echo translate('E-Training'); ?></h3>
                   <p><?php echo translate('Providing quality education and professional training to help you achieve your career goals.'); ?></p>
                   <div class="social-links">
                       <a href="#"><i class="fab fa-facebook-f"></i></a>
                       <a href="#"><i class="fab fa-twitter"></i></a>
                       <a href="#"><i class="fab fa-linkedin-in"></i></a>
                       <a href="#"><i class="fab fa-instagram"></i></a>
                   </div>
               </div>
               
               <div class="footer-section">
                   <h3><?php echo translate('Quick Links'); ?></h3>
                   <ul class="footer-links">
                       <li><a href="index.php"><i class="fas fa-chevron-right"></i> <?php echo translate('Home'); ?></a></li>
                       <li><a href="public_courses.php"><i class="fas fa-chevron-right"></i> <?php echo translate('Courses'); ?></a></li>
                       <li><a href="#about"><i class="fas fa-chevron-right"></i> <?php echo translate('About Us'); ?></a></li>
                       <li><a href="#contact"><i class="fas fa-chevron-right"></i> <?php echo translate('Contact Us'); ?></a></li>
                       <li><a href="login.php"><i class="fas fa-chevron-right"></i> <?php echo translate('Login'); ?></a></li>
                       <li><a href="student_register.php"><i class="fas fa-chevron-right"></i> <?php echo translate('Register'); ?></a></li>
                   </ul>
               </div>
               
               <div class="footer-section">
                   <h3><?php echo translate('Contact Us'); ?></h3>
                   <ul class="footer-contact">
                       <li><i class="fas fa-map-marker-alt"></i> <?php echo translate('Aljouf Sakaka, City'); ?></li>
                       <li><i class="fas fa-phone"></i> +966550381788</li>
                       <li><i class="fas fa-envelope"></i> oceanproject097@gmail.com</li>
                   </ul>
               </div>
           </div>
           
           <div class="copyright">
               <p>&copy; <?php echo date('Y'); ?> <?php echo translate('E-Training. All rights reserved.'); ?></p>
           </div>
       </div>
   </footer>

   <script>
       // Mobile menu toggle
       const mobileMenuBtn = document.getElementById('mobileMenuBtn');
       const mobileMenu = document.getElementById('mobileMenu');
       
       mobileMenuBtn.addEventListener('click', function() {
           mobileMenu.classList.toggle('active');
           mobileMenuBtn.innerHTML = mobileMenu.classList.contains('active') ? 
               '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
       });

       // Course filtering
       const courseSearch = document.getElementById('courseSearch');
       const categoryFilter = document.getElementById('categoryFilter');
       const sortFilter = document.getElementById('sortFilter');
       const courseCards = document.querySelectorAll('.course-card');
       const noResults = document.getElementById('noResults');
       const coursesGrid = document.getElementById('coursesGrid');
       const resetFiltersBtn = document.getElementById('resetFiltersBtn');
       
       function filterCourses() {
           const searchTerm = courseSearch.value.toLowerCase();
           const categoryValue = categoryFilter.value;
           
           let visibleCount = 0;
           
           courseCards.forEach(card => {
               const title = card.querySelector('.course-title').textContent.toLowerCase();
               const description = card.querySelector('.course-description').textContent.toLowerCase();
               const category = card.getAttribute('data-category');
               
               // Check if card matches all filters
               const matchesSearch = title.includes(searchTerm) || description.includes(searchTerm);
               const matchesCategory = categoryValue === '' || category === categoryValue;
               
               if (matchesSearch && matchesCategory) {
                   card.style.display = '';
                   visibleCount++;
               } else {
                   card.style.display = 'none';
               }
           });
           
           // Show/hide no results message
           if (visibleCount === 0) {
               noResults.style.display = 'block';
               coursesGrid.style.display = 'none';
           } else {
               noResults.style.display = 'none';
               coursesGrid.style.display = 'grid';
           }
       }
       
       // Sort courses
       function sortCourses() {
           const sortValue = sortFilter.value;
           const coursesArray = Array.from(courseCards);
           
           coursesArray.sort((a, b) => {
               const titleA = a.querySelector('.course-title').textContent;
               const titleB = b.querySelector('.course-title').textContent;
               
               if (sortValue === 'name_asc') {
                   return titleA.localeCompare(titleB);
               } else if (sortValue === 'name_desc') {
                   return titleB.localeCompare(titleA);
               } else if (sortValue === 'newest') {
                   // For demo purposes, we'll just use the current order as "newest"
                   return -1;
               } else if (sortValue === 'oldest') {
                   // For demo purposes, we'll just reverse the current order for "oldest"
                   return 1;
               }
           });
           
           // Remove existing cards
           courseCards.forEach(card => {
               card.remove();
           });
           
           // Append sorted cards
           coursesArray.forEach(card => {
               coursesGrid.appendChild(card);
           });
       }
       
       // Add event listeners
       courseSearch.addEventListener('input', filterCourses);
       categoryFilter.addEventListener('change', filterCourses);
       sortFilter.addEventListener('change', sortCourses);
       
       // Reset filters
       resetFiltersBtn.addEventListener('click', function() {
           courseSearch.value = '';
           categoryFilter.value = '';
           sortFilter.value = 'newest';
           filterCourses();
       });

       // Language switcher - Set cookie and reload page
       document.querySelectorAll('.lang-btn').forEach(btn => {
           btn.addEventListener('click', function(e) {
               e.preventDefault();
               const lang = this.getAttribute('href').split('=')[1];
               document.cookie = `lang=${lang}; path=/; max-age=${60*60*24*30}`; // 30 days
               window.location.reload();
           });
       });
   </script>
</body>
</html>
