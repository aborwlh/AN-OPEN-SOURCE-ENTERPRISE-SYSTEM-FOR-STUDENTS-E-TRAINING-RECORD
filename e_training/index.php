<?php 
include 'config.php';

// Fetch featured courses for the homepage
$featured_courses_query = "SELECT c.course_id, c.name as course_name, c.description, c.img, 
                          cat.name as category_name, u.name as instructor_name,
                          cat.category_id
                          FROM courses c
                          JOIN category cat ON c.category_id = cat.category_id
                          JOIN users u ON c.instructor_id = u.user_id
                          WHERE c.course_id > 0
                          ORDER BY c.course_id DESC
                          LIMIT 6";
$featured_courses_result = mysqli_query($con, $featured_courses_query);

// Fetch course categories for the filter
$categories_query = "SELECT category_id, name FROM category ORDER BY name";
$categories_result = mysqli_query($con, $categories_query);

// Get statistics for the platform
// Total students (unique students in course_enrollments)
$students_query = "SELECT COUNT(DISTINCT student_id) as total_students FROM course_enrollments";
$students_result = mysqli_query($con, $students_query);
$students_count = ($students_result && mysqli_num_rows($students_result) > 0) ? mysqli_fetch_assoc($students_result)['total_students'] : 0;

// Total courses
$courses_query = "SELECT COUNT(*) as total_courses FROM courses";
$courses_result = mysqli_query($con, $courses_query);
$courses_count = ($courses_result && mysqli_num_rows($courses_result) > 0) ? mysqli_fetch_assoc($courses_result)['total_courses'] : 0;

// Total instructors
$instructors_query = "SELECT COUNT(*) as total_instructors FROM users WHERE role = 'instructor'";
$instructors_result = mysqli_query($con, $instructors_query);
$instructors_count = ($instructors_result && mysqli_num_rows($instructors_result) > 0) ? mysqli_fetch_assoc($instructors_result)['total_instructors'] : 0;

// Success rate (can be calculated based on student progress)
$success_query = "SELECT AVG(value) as avg_progress FROM student_progress";
$success_result = mysqli_query($con, $success_query);
$success_rate = ($success_result && mysqli_num_rows($success_result) > 0) ? round(mysqli_fetch_assoc($success_result)['avg_progress']) : 0;

// Fetch testimonials from course_feedback
$testimonials_query = "SELECT cf.rating, cf.comment, cf.date, u.name, c.name as course_name 
                      FROM course_feedback cf
                      JOIN users u ON cf.student_id = u.user_id
                      JOIN courses c ON cf.course_id = c.course_id
                      ORDER BY cf.date DESC
                      LIMIT 3";
$testimonials_result = mysqli_query($con, $testimonials_query);

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
    <title><?php echo translate('E-Training Platform - Learn and Grow'); ?></title>
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
            background-color: var(--white);
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

        /* Hero section styles */
        .hero {
            background: linear-gradient(rgba(4, 99, 155, 0.9), rgba(4, 99, 155, 0.8)), url('assets/images/hero-bg.jpg');
            background-size: cover;
            background-position: center;
            color: var(--white);
            padding: 100px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('assets/images/pattern.png');
            opacity: 0.1;
        }

        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 800px;
            margin: 0 auto;
        }

        .hero h1 {
            font-size: 3rem;
            margin-bottom: 20px;
            font-weight: 700;
            line-height: 1.2;
        }

        .hero p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            line-height: 1.6;
            opacity: 0.9;
        }

        .hero-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .btn-hero {
            display: inline-flex;
            align-items: center;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
        }

        .btn-primary {
            background-color: var(--white);
            color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--primary-light);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .btn-secondary {
            background-color: transparent;
            color: var(--white);
            border: 2px solid var(--white);
        }

        .btn-secondary:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .btn-hero i {
            margin-right: 10px;
        }

        /* Stats section */
        .stats {
            background-color: var(--white);
            padding: 30px 0;
            box-shadow: var(--shadow);
            margin-top: -50px;
            position: relative;
            z-index: 2;
            border-radius: 10px;
            max-width: 1000px;
            margin-left: auto;
            margin-right: auto;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .stat-item {
            text-align: center;
            padding: 20px;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        /* Features section */
        .features {
            padding: 80px 0;
            background-color: var(--light-bg);
        }

        .section-title {
            text-align: center;
            font-size: 2.2rem;
            margin-bottom: 20px;
            color: var(--primary-color);
            font-weight: 700;
        }

        .section-subtitle {
            text-align: center;
            font-size: 1.1rem;
            color: var(--text-light);
            max-width: 700px;
            margin: 0 auto 50px;
        }

        .features-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .feature-card {
            background-color: var(--white);
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border-bottom: 4px solid transparent;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            border-bottom: 4px solid var(--primary-color);
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            background-color: var(--primary-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: var(--primary-color);
            font-size: 1.8rem;
            transition: var(--transition);
        }

        .feature-card:hover .feature-icon {
            background-color: var(--primary-color);
            color: var(--white);
        }

        .feature-title {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: var(--text-dark);
            font-weight: 600;
        }

        .feature-description {
            color: var(--text-light);
            line-height: 1.6;
        }

        /* Courses section */
        .courses-section {
            padding: 80px 0;
        }

        .courses-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .courses-title-container {
            max-width: 600px;
        }

        .view-all-btn {
            display: inline-flex;
            align-items: center;
            padding: 10px 20px;
            background-color: var(--primary-light);
            color: var(--primary-color);
            text-decoration: none;
            border-radius: 50px;
            font-weight: 500;
            transition: var(--transition);
        }

        .view-all-btn:hover {
            background-color: var(--primary-color);
            color: var(--white);
        }

        .view-all-btn i {
            margin-left: 8px;
        }

        .courses-filter {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 20px;
            background-color: var(--white);
            border: 1px solid var(--border-color);
            border-radius: 50px;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
        }

        .filter-btn.active, .filter-btn:hover {
            background-color: var(--primary-color);
            color: var(--white);
            border-color: var(--primary-color);
        }

        .courses-container {
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

        /* Testimonials section */
        .testimonials {
            padding: 80px 0;
            background-color: var(--light-bg);
            position: relative;
            overflow: hidden;
        }

        .testimonials::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('assets/images/pattern.png');
            opacity: 0.05;
        }

        .testimonials-container {
            position: relative;
            z-index: 1;
            max-width: 1000px;
            margin: 0 auto;
        }

        .testimonial-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .testimonial {
            background-color: var(--white);
            border-radius: 10px;
            padding: 30px;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .testimonial:hover {
            transform: translateY(-10px);
        }

        .testimonial-content {
            position: relative;
            color: var(--text-light);
            line-height: 1.6;
            margin-bottom: 20px;
            font-style: italic;
        }

        .testimonial-content::before {
            content: '"';
            font-size: 4rem;
            color: var(--primary-color);
            opacity: 0.2;
            position: absolute;
            top: -20px;
            left: -15px;
            font-family: serif;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
        }

        .author-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 3px solid var(--primary-light);
        }

        .author-info h4 {
            margin: 0;
            color: var(--text-dark);
            font-weight: 600;
        }

        .author-info p {
            margin: 5px 0 0;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        /* Call to action section */
        .cta {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 80px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('assets/images/pattern.png');
            opacity: 0.1;
        }

        .cta-content {
            position: relative;
            z-index: 1;
            max-width: 800px;
            margin: 0 auto;
        }

        .cta h2 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .cta p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            line-height: 1.6;
            opacity: 0.9;
        }

        .cta-btn {
            display: inline-flex;
            align-items: center;
            padding: 15px 30px;
            background-color: var(--white);
            color: var(--primary-color);
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: var(--transition);
        }

        .cta-btn:hover {
            background-color: var(--primary-light);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .cta-btn i {
            margin-right: 10px;
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

        .newsletter-form {
            display: flex;
            margin-top: 20px;
        }

        .newsletter-input {
            flex-grow: 1;
            padding: 12px 15px;
            border: none;
            border-radius: 5px 0 0 5px;
            font-family: inherit;
        }

        .newsletter-btn {
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            padding: 0 20px;
            border-radius: 0 5px 5px 0;
            cursor: pointer;
            transition: var(--transition);
        }

        .newsletter-btn:hover {
            background-color: var(--primary-dark);
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
        html[dir="rtl"] .btn-hero i,
        html[dir="rtl"] .cta-btn i,
        html[dir="rtl"] .footer-links a i,
        html[dir="rtl"] .footer-contact i {
            margin-right: 0;
            margin-left: 8px;
        }

        html[dir="rtl"] .view-all-btn i {
            margin-left: 0;
            margin-right: 8px;
        }

        html[dir="rtl"] .course-instructor i,
        html[dir="rtl"] .course-rating i,
        html[dir="rtl"] .course-students i {
            margin-right: 0;
            margin-left: 5px;
        }

        html[dir="rtl"] .author-avatar {
            margin-right: 0;
            margin-left: 15px;
        }

        html[dir="rtl"] .footer-section h3::after {
            left: auto;
            right: 0;
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .nav-links, .auth-buttons, .language-switcher {
                display: none;
            }

            .mobile-menu-btn {
                display: block;
            }

            .hero h1 {
                font-size: 2.2rem;
            }

            .hero p {
                font-size: 1rem;
            }

            .section-title {
                font-size: 1.8rem;
            }

            .cta h2 {
                font-size: 2rem;
            }

            .cta p {
                font-size: 1rem;
            }
        }

        @media (max-width: 576px) {
            .stats-container {
                grid-template-columns: 1fr;
            }

            .hero-buttons {
                flex-direction: column;
                gap: 15px;
            }

            .btn-hero {
                width: 100%;
            }

            .courses-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        /* Animation */
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

        .animate {
            animation: fadeIn 0.8s ease forwards;
        }

        .delay-1 {
            animation-delay: 0.2s;
        }

        .delay-2 {
            animation-delay: 0.4s;
        }

        .delay-3 {
            animation-delay: 0.6s;
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
                    <a href="#features"><?php echo translate('Features'); ?></a>
                    <a href="#courses"><?php echo translate('Courses'); ?></a>
                    <a href="#testimonials"><?php echo translate('Testimonials'); ?></a>
                    <a href="#contact"><?php echo translate('Contact'); ?></a>
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
        <a href="#features"><?php echo translate('Features'); ?></a>
        <a href="#courses"><?php echo translate('Courses'); ?></a>
        <a href="#testimonials"><?php echo translate('Testimonials'); ?></a>
        <a href="#contact"><?php echo translate('Contact'); ?></a>
        <div class="mobile-auth">
            <a href="login.php" class="auth-btn login-btn"><i class="fas fa-sign-in-alt"></i> <?php echo translate('Login'); ?></a>
            <a href="student_register.php" class="auth-btn register-btn"><i class="fas fa-user-plus"></i> <?php echo translate('Register'); ?></a>
        </div>
        <div class="mobile-language">
            <a href="?lang=en" class="lang-btn <?php echo $lang == 'en' ? 'active' : ''; ?>">EN</a>
            <a href="?lang=ar" class="lang-btn <?php echo $lang == 'ar' ? 'active' : ''; ?>">AR</a>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content animate">
                <h1><?php echo translate('Advance Your Career with E-Training'); ?></h1>
                <p><?php echo translate('Access high-quality courses taught by industry experts. Learn at your own pace and achieve your professional goals.'); ?></p>
                <div class="hero-buttons">
                    <a href="student_register.php" class="btn-hero btn-primary"><i class="fas fa-rocket"></i> <?php echo translate('Get Started'); ?></a>
                    <a href="public_courses.php" class="btn-hero btn-secondary"><i class="fas fa-book"></i> <?php echo translate('Explore Courses'); ?></a>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <div class="stats-container">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $students_count; ?>+</div>
                    <div class="stat-label"><?php echo translate('Students'); ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $courses_count; ?>+</div>
                    <div class="stat-label"><?php echo translate('Courses'); ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $instructors_count; ?>+</div>
                    <div class="stat-label"><?php echo translate('Expert Instructors'); ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $success_rate; ?>%</div>
                    <div class="stat-label"><?php echo translate('Success Rate'); ?></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <h2 class="section-title animate"><?php echo translate('Why Choose E-Training?'); ?></h2>
            <p class="section-subtitle animate delay-1"><?php echo translate('Our platform offers everything you need to enhance your skills and advance your career'); ?></p>
            <div class="features-container">
                <div class="feature-card animate delay-1">
                    <div class="feature-icon">
                        <i class="fas fa-laptop"></i>
                    </div>
                    <h3 class="feature-title"><?php echo translate('Learn Anywhere'); ?></h3>
                    <p class="feature-description"><?php echo translate('Access your courses from any device, anytime. Our platform is fully responsive and designed for learning on the go.'); ?></p>
                </div>
                
                <div class="feature-card animate delay-2">
                    <div class="feature-icon">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <h3 class="feature-title"><?php echo translate('Earn Certificates'); ?></h3>
                    <p class="feature-description"><?php echo translate('Receive recognized certificates upon course completion to showcase your new skills to employers.'); ?></p>
                </div>
                
                <div class="feature-card animate delay-3">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="feature-title"><?php echo translate('Expert Instructors'); ?></h3>
                    <p class="feature-description"><?php echo translate('Learn from industry professionals with years of experience and proven expertise in their fields.'); ?></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Courses Section -->
    <section class="courses-section" id="courses">
        <div class="container">
            <div class="courses-header">
                <div class="courses-title-container">
                    <h2 class="section-title"><?php echo translate('Featured Courses'); ?></h2>
                    <p class="section-subtitle"><?php echo translate('Explore our most popular courses and start learning today'); ?></p>
                </div>
                <a href="public_courses.php" class="view-all-btn"><?php echo translate('View All Courses'); ?> <i class="fas fa-arrow-right"></i></a>
            </div>
            
            <div class="courses-filter">
                <button class="filter-btn active" data-filter="all"><?php echo translate('All Categories'); ?></button>
                <?php if ($categories_result && mysqli_num_rows($categories_result) > 0): ?>
                    <?php while ($category = mysqli_fetch_assoc($categories_result)): ?>
                        <button class="filter-btn" data-filter="<?php echo $category['category_id']; ?>"><?php echo translate($category['name']); ?></button>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
            
            <div class="courses-container">
                <?php if ($featured_courses_result && mysqli_num_rows($featured_courses_result) > 0): ?>
                    <?php while ($course = mysqli_fetch_assoc($featured_courses_result)): 
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
                                <a href="course_details.php?id=<?php echo $course['course_id']; ?>" class="course-btn"><?php echo translate('View Course'); ?></a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <!-- Display placeholder courses if no courses are found -->
                    <div class="course-card">
                        <div class="course-image-container">
                            <div class="course-placeholder">
                                <i class="fas fa-image"></i> <?php echo translate('No courses available'); ?>
                            </div>
                            <div class="course-category"><?php echo translate('N/A'); ?></div>
                        </div>
                        <div class="course-content">
                            <h3 class="course-title">
                                <a href="#"><?php echo translate('No Courses Available'); ?></a>
                            </h3>
                            <div class="course-instructor">
                                <i class="fas fa-chalkboard-teacher"></i> <?php echo translate('N/A'); ?>
                            </div>
                            <p class="course-description">
                                <?php echo translate('There are currently no courses available. Please check back later.'); ?>
                            </p>
                            <div class="course-footer">
                                <div class="course-rating">
                                    <i class="fas fa-star"></i> 0 (0)
                                </div>
                                <div class="course-students">
                                    <i class="fas fa-user-graduate"></i> 0 <?php echo translate('students'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials" id="testimonials">
        <div class="container">
            <h2 class="section-title"><?php echo translate('What Our Students Say'); ?></h2>
            <p class="section-subtitle"><?php echo translate('Hear from our students about their learning experience'); ?></p>
            <div class="testimonial-grid">
                <?php if ($testimonials_result && mysqli_num_rows($testimonials_result) > 0): ?>
                    <?php while ($testimonial = mysqli_fetch_assoc($testimonials_result)): ?>
                        <div class="testimonial animate">
                            <div class="testimonial-content">
                                <?php echo translate($testimonial['comment'] ? $testimonial['comment'] : 'The courses on E-Training have been instrumental in advancing my career. The instructors are knowledgeable, and the content is up-to-date with industry standards.'); ?>
                            </div>
                            <div class="testimonial-author">
                                <img src="assets/images/defult_icon.png" alt="<?php echo $testimonial['name']; ?>" class="author-avatar">
                                <div class="author-info">
                                    <h4><?php echo $testimonial['name']; ?></h4>
                                    <p><?php echo translate($testimonial['course_name']); ?> <?php echo translate('Student'); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <!-- Default testimonials if none are found in the database -->
                    <div class="testimonial animate">
                        <div class="testimonial-content">
                            <?php echo translate('The courses on E-Training have been instrumental in advancing my career. The instructors are knowledgeable, and the content is up-to-date with industry standards. I\'ve already recommended it to several colleagues!'); ?>
                        </div>
                        <div class="testimonial-author">
                            <img src="assets/images/testimonial1.jpg" alt="Ahmed Ali" class="author-avatar">
                            <div class="author-info">
                                <h4><?php echo translate('Ahmed Ali'); ?></h4>
                                <p><?php echo translate('Software Developer'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="testimonial animate delay-1">
                        <div class="testimonial-content">
                            <?php echo translate('As someone transitioning to a new career, E-Training provided me with the perfect foundation to build my skills. The flexible learning schedule allowed me to study while working full-time. Highly recommended!'); ?>
                        </div>
                        <div class="testimonial-author">
                            <img src="assets/images/testimonial2.jpg" alt="Fatima Hassan" class="author-avatar">
                            <div class="author-info">
                                <h4><?php echo translate('Fatima Hassan'); ?></h4>
                                <p><?php echo translate('Marketing Specialist'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="testimonial animate delay-2">
                        <div class="testimonial-content">
                            <?php echo translate('The certificate I earned through E-Training helped me secure a promotion at work. The practical assignments and projects gave me real-world experience that I could immediately apply to my job.'); ?>
                        </div>
                        <div class="testimonial-author">
                            <img src="assets/images/testimonial3.jpg" alt="Mohammed Khalid" class="author-avatar">
                            <div class="author-info">
                                <h4><?php echo translate('Mohammed Khalid'); ?></h4>
                                <p><?php echo translate('Project Manager'); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Call to Action Section -->
    <section class="cta">
        <div class="container">
            <div class="cta-content">
                <h2><?php echo translate('Ready to Start Your Learning Journey?'); ?></h2>
                <p><?php echo translate('Join thousands of students who are already advancing their careers with E-Training.'); ?></p>
                <a href="student_register.php" class="cta-btn"><i class="fas fa-user-plus"></i> <?php echo translate('Register Now'); ?></a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" id="contact">
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
                        <li><a href="#features"><i class="fas fa-chevron-right"></i> <?php echo translate('Features'); ?></a></li>
                        <li><a href="#courses"><i class="fas fa-chevron-right"></i> <?php echo translate('Courses'); ?></a></li>
                        <li><a href="#testimonials"><i class="fas fa-chevron-right"></i> <?php echo translate('Testimonials'); ?></a></li>
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
        const filterButtons = document.querySelectorAll('.filter-btn');
        const courseCards = document.querySelectorAll('.course-card');
        
        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                // Remove active class from all buttons
                filterButtons.forEach(btn => btn.classList.remove('active'));
                
                // Add active class to clicked button
                button.classList.add('active');
                
                // Get filter value
                const filterValue = button.getAttribute('data-filter');
                
                // Filter courses
                if (filterValue === 'all') {
                    courseCards.forEach(card => {
                        card.style.display = 'block';
                    });
                } else {
                    courseCards.forEach(card => {
                        const categoryId = card.getAttribute('data-category');
                        if (categoryId === filterValue) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                }
            });
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 70,
                        behavior: 'smooth'
                    });
                    
                    // Close mobile menu if open
                    mobileMenu.classList.remove('active');
                    mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
                }
            });
        });

        // Animation on scroll
        const animateElements = document.querySelectorAll('.animate');
        
        function checkIfInView() {
            const windowHeight = window.innerHeight;
            const windowTopPosition = window.scrollY;
            const windowBottomPosition = windowTopPosition + windowHeight;
            
            animateElements.forEach(element => {
                const elementHeight = element.offsetHeight;
                const elementTopPosition = element.offsetTop;
                const elementBottomPosition = elementTopPosition + elementHeight;
                
                // Check if element is in view
                if (
                    (elementBottomPosition >= windowTopPosition) &&
                    (elementTopPosition <= windowBottomPosition)
                ) {
                    element.classList.add('show');
                }
            });
        }
        
        window.addEventListener('scroll', checkIfInView);
        window.addEventListener('load', checkIfInView);

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
