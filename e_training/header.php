 <?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
   session_start();
}
include 'config.php';
$lang = $_COOKIE['lang'] ?? 'en';
$rtl = ($lang === 'ar') ? 'rtl' : 'ltr';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $rtl; ?>">
<head>
   <meta charset="UTF-8"/>
   <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
   <title><?php echo translate('Ocean App'); ?> - <?php echo $page_title;?></title>
   <link rel="stylesheet" href="assets/style/styles.css" />
   <?php if ($rtl === 'rtl'): ?>
   <link rel="stylesheet" href="assets/style/rtl.css" />
   <?php endif; ?>
   <style>
       body {
           margin: 0;
           padding: 0;
           display: flex;
           flex-direction: column;
           min-height: 100vh;
       }
       .header {
           background-color: #0077b6;
           display: flex;
           justify-content: space-between;
           align-items: center;
           padding: 10px 20px;
           color: white;
           width: 100%;
           box-sizing: border-box;
           position: relative;
           z-index: 100;
       }
       .logo {
           display: flex;
           align-items: center;
       }
       .logo img {
           width: 40px;
           height: 40px;
           margin-right: 10px;
       }
       .logo h2 {
           font-size: 24px;
           margin: 0;
           font-weight: bold;
       }
       .hamburger {
           font-size: 24px;
           background: none;
           border: none;
           color: white;
           cursor: pointer;
       }
       .content {
           flex: 1;
           padding: 20px;
           width: 100%; /* Add this line */
           max-width: none; /* Add this line */
       }
       
       /* Sidebar Styles */
       .sidebar {
           position: fixed;
           top: 0;
           height: 100%;
           width: 250px;
           background-color: #0077b6;
           color: white;
           z-index: 1000;
           overflow-y: auto;
           transition: all 0.3s ease;
           box-shadow: 2px 0 5px rgba(0,0,0,0.2);
       }
       
       /* LTR (Left-to-Right) sidebar position - REVERSED as requested */
       html[dir="ltr"] .sidebar {
           right: -250px; /* Start from right side */
           left: auto;
       }
       
       html[dir="ltr"] .sidebar.active {
           right: 0; /* Open from right side */
           left: auto;
       }
       
       /* RTL (Right-to-Left) sidebar position - REVERSED as requested */
       html[dir="rtl"] .sidebar {
           left: -250px; /* Start from left side */
           right: auto;
       }
       
       html[dir="rtl"] .sidebar.active {
           left: 0; /* Open from left side */
           right: auto;
       }
       
       .sidebar .close-btn {
           position: absolute;
           top: 10px;
           background-color: #e74c3c;
           color: white;
           border: none;
           padding: 5px 10px;
           font-size: 18px;
           cursor: pointer;
           border-radius: 4px;
       }
       
       /* Adjust close button position based on direction */
       html[dir="ltr"] .sidebar .close-btn {
           left: 10px; /* For LTR, close button on left */
           right: auto;
       }
       
       html[dir="rtl"] .sidebar .close-btn {
           right: 10px; /* For RTL, close button on right */
           left: auto;
       }
       
       .sidebar ul {
           list-style: none;
           padding: 0;
           margin-top: 50px;
       }
       
       .sidebar ul li {
           padding: 0;
       }
       
       .sidebar ul li a {
           display: block;
           padding: 15px 20px;
           color: white;
           text-decoration: none;
           transition: background 0.3s;
       }
       
       .sidebar ul li a:hover {
           background-color: rgba(255,255,255,0.1);
       }
       
       /* Language switcher */
       .language-switcher {
           display: flex;
           justify-content: center;
           gap: 10px;
           margin: 20px 0;
           padding: 0 20px;
       }
       
       .language-switcher button {
           background: rgba(255,255,255,0.2);
           border: none;
           color: white;
           padding: 8px 15px;
           border-radius: 4px;
           cursor: pointer;
           transition: background 0.3s;
       }
       
       .language-switcher button:hover {
           background: rgba(255,255,255,0.3);
       }
       
       .language-switcher button.active {
           background: rgba(255,255,255,0.4);
           font-weight: bold;
       }
       
       /* Overlay for mobile */
       .sidebar-overlay {
           position: fixed;
           top: 0;
           left: 0;
           right: 0;
           bottom: 0;
           background-color: rgba(0,0,0,0.5);
           z-index: 999;
           display: none;
       }
       
       .sidebar-overlay.active {
           display: block;
       }
   </style>
   <script>
       // Wait for DOM to be fully loaded
       document.addEventListener('DOMContentLoaded', function() {
           // Function to toggle sidebar
           window.toggleSidebar = function() {
               const sidebar = document.getElementById('sidebar');
               const overlay = document.getElementById('sidebar-overlay');
               
               if (sidebar) {
                   sidebar.classList.toggle('active');
                   
                   if (overlay) {
                       overlay.classList.toggle('active');
                   }
               }
           };
           
           // Function to set language
           window.setLanguage = function(lang) {
               document.cookie = "lang=" + lang + "; path=/; max-age=31536000";
               window.location.reload();
           };
           
           // Close sidebar when clicking outside
           const overlay = document.getElementById('sidebar-overlay');
           if (overlay) {
               overlay.addEventListener('click', function() {
                   toggleSidebar();
               });
           }
       });
   </script>
</head>
<body>
   <!-- Header must be the first element in the body -->
   <header class="header">
       <div class="logo">
           <img src="assets/images/logo.png" alt="OCEAN logo" class="logo-img">
           <h2>OCEAN</h2>
       </div>
       <button class="hamburger" onclick="toggleSidebar()">☰</button>
   </header>
   
   <!-- Sidebar overlay for mobile -->
   <div id="sidebar-overlay" class="sidebar-overlay"></div>
   
   <!-- Sidebar navigation -->
   <nav id="sidebar" class="sidebar">
       <button class="close-btn" onclick="toggleSidebar()">×</button>
       
       <div class="language-switcher">
           <button onclick="setLanguage('en')" class="<?php echo ($lang === 'en') ? 'active' : ''; ?>">English</button>
           <button onclick="setLanguage('ar')" class="<?php echo ($lang === 'ar') ? 'active' : ''; ?>">العربية</button>
       </div>
       
       <ul>
           <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == "admin") { ?>
           <li><a href="admin_dashboard.php"><?php echo translate('Dashboard'); ?></a></li>
           <li><a href="admin_manage_students.php"><?php echo translate('Students'); ?></a></li>
           <li><a href="admin_manage_instructors.php"><?php echo translate('Instructors'); ?></a></li>
           <li><a href="admin_manage_categories.php"><?php echo translate('Categories'); ?></a></li>
           <li><a href="admin_monitor_users.php"><?php echo translate('Monitor Users'); ?></a></li>
           <li><a href="admin_profile.php"><?php echo translate('Profile'); ?></a></li>
           <li><a href="logout.php"><?php echo translate('Logout'); ?></a></li>
           <?php } ?>
           <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == "instructor") { ?>
           <li><a href="instructor_dashboard.php"><?php echo translate('Dashboard'); ?></a></li>
           <li><a href="instructor_manage_courses.php"><?php echo translate('Courses'); ?></a></li>
           <li><a href="manage_notifications.php"><?php echo translate('Notifications'); ?></a></li>
           <li><a href="instructor_profile.php"><?php echo translate('Profile'); ?></a></li>
           <li><a href="logout.php"><?php echo translate('Logout'); ?></a></li>
           <?php } ?>
           <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == "student") { ?>
           <li><a href="student_dashboard.php"><?php echo translate('Dashboard'); ?></a></li>
           <li><a href="student_view_courses.php"><?php echo translate('Courses'); ?></a></li>
           <li><a href="student_view_my_courses.php"><?php echo translate('Your Courses'); ?></a></li>
           <li><a href="manage_notifications.php"><?php echo translate('Notifications'); ?></a></li>
           <li><a href="student_profile.php"><?php echo translate('Profile'); ?></a></li>
           <li><a href="logout.php"><?php echo translate('Logout'); ?></a></li>
           <?php } ?>
       </ul>
   </nav>
   
   <main class="content">

