<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8"/>
   <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
   <title><?php echo translate('Certificate Verification'); ?></title>
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="assets/style/styles.css"/>
   <style>
       body {
           background-color: #f9f9f9;
           color: #333;
           font-family: 'Georgia', serif;
       }
       
       .verification-container {
           max-width: 800px;
           margin: 50px auto;
           padding: 30px;
           background-color: white;
           border-radius: 8px;
           box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
       }
       
       .verification-header {
           text-align: center;
           margin-bottom: 30px;
           border-bottom: 2px solid #083d77;
           padding-bottom: 20px;
       }
       
       .verification-header h1 {
           color: #083d77;
           font-size: 32px;
           font-weight: bold;
           margin-bottom: 10px;
       }
       
       .verification-form {
           background-color: #f8f9fa;
           padding: 20px;
           border-radius: 8px;
           margin-bottom: 30px;
       }
       
       .verification-result {
           padding: 20px;
           border-radius: 8px;
           margin-top: 30px;
       }
       
       .verification-success {
           background-color: #d1e7dd;
           border: 1px solid #badbcc;
           color: #0f5132;
       }
       
       .verification-error {
           background-color: #f8d7da;
           border: 1px solid #f5c2c7;
           color: #842029;
       }
       
       .certificate-details {
           background-color: white;
           padding: 20px;
           border-radius: 8px;
           box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
           margin-top: 20px;
           margin-bottom: 20px;
       }
       
       .certificate-details h3 {
           color: #083d77;
           border-bottom: 1px solid #dee2e6;
           padding-bottom: 10px;
           margin-bottom: 20px;
       }
       
       .detail-row {
           display: flex;
           margin-bottom: 10px;
           padding-bottom: 10px;
           border-bottom: 1px solid #f0f0f0;
       }
       
       .detail-row:last-child {
           border-bottom: none;
       }
       
       .detail-label {
           font-weight: bold;
           width: 200px;
           color: #555;
       }
       
       .detail-value {
           flex: 1;
       }
       
       .certificate-seal {
           width: 120px;
           height: 120px;
           border: 2px solid #083d77;
           border-radius: 50%;
           display: flex;
           align-items: center;
           justify-content: center;
           color: #083d77;
           font-size: 14px;
           text-align: center;
           margin: 20px auto;
           transform: rotate(-15deg);
           opacity: 0.8;
       }
       
       .nav-tabs .nav-link {
           color: #6c757d;
       }
       
       .nav-tabs .nav-link.active {
           color: #083d77;
           font-weight: bold;
       }
       
       .tab-content {
           padding: 20px;
           background-color: white;
           border: 1px solid #dee2e6;
           border-top: none;
           border-radius: 0 0 8px 8px;
       }
       
       .certificate-card {
           border: 1px solid #dee2e6;
           border-radius: 8px;
           margin-bottom: 20px;
           overflow: hidden;
       }
       
       .certificate-card-header {
           background-color: #083d77;
           color: white;
           padding: 15px;
           font-weight: bold;
       }
       
       .certificate-card-body {
           padding: 15px;
       }
       
       .certificate-card-footer {
           background-color: #f8f9fa;
           padding: 10px 15px;
           border-top: 1px solid #dee2e6;
           text-align: right;
       }
   </style>
</head>
<body>
   <div class="verification-container">
       <div class="verification-header">
           <h1><?php echo translate('Certificate Verification'); ?></h1>
           <p><?php echo translate('Verify the authenticity of an E-Training certificate'); ?></p>
       </div>
       
       <?php
       // Initialize variables
       $verification_result = '';
       $certificate_data = null;
       $certificates_list = null;
       
       // Check if certificate number is provided in URL (for QR code scanning)
       if (isset($_GET['certificate_number']) && !empty($_GET['certificate_number'])) {
           $certificate_number = mysqli_real_escape_string($con, $_GET['certificate_number']);
           
           // Parse the certificate number format: CERT-{enrollment_id}-{course_id}-{student_id}
           if (preg_match('/^CERT-(\d+)-(\d+)(?:-(\d+))?$/', $certificate_number, $matches)) {
               $enrollment_id = $matches[1];
               $course_id = $matches[2];
               $student_id = isset($matches[3]) ? $matches[3] : null;
               
               // If student_id is not in the certificate number, we need to find it
               if (!$student_id) {
                   // Get student ID from enrollment
                   $student_query = "SELECT student_id FROM course_enrollments WHERE enrollment_id = '$enrollment_id' AND course_id = '$course_id'";
                   $student_result = mysqli_query($con, $student_query);
                   
                   if ($student_result && mysqli_num_rows($student_result) > 0) {
                       $student_data = mysqli_fetch_assoc($student_result);
                       $student_id = $student_data['student_id'];
                   }
               }
               
               if ($student_id) {
                   // Verify the certificate
                   $certificate_data = verifyCertificate($con, $student_id, $course_id, $enrollment_id);
                   
                   if ($certificate_data) {
                       $verification_result = 'success';
                   } else {
                       $verification_result = 'error';
                   }
               } else {
                   $verification_result = 'error';
               }
           } else {
               $verification_result = 'error';
           }
       }
       // Process verification request from form
       else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
           // Check which verification method was used
           if (isset($_POST['certificate_number']) && !empty($_POST['certificate_number'])) {
               // Verification by certificate number
               $certificate_number = mysqli_real_escape_string($con, $_POST['certificate_number']);
               
               // Parse the certificate number format: CERT-{enrollment_id}-{course_id}-{student_id}
               if (preg_match('/^CERT-(\d+)-(\d+)(?:-(\d+))?$/', $certificate_number, $matches)) {
                   $enrollment_id = $matches[1];
                   $course_id = $matches[2];
                   $student_id = isset($matches[3]) ? $matches[3] : null;
                   
                   // If student_id is not in the certificate number, we need to find it
                   if (!$student_id) {
                       // Get student ID from enrollment
                       $student_query = "SELECT student_id FROM course_enrollments WHERE enrollment_id = '$enrollment_id' AND course_id = '$course_id'";
                       $student_result = mysqli_query($con, $student_query);
                       
                       if ($student_result && mysqli_num_rows($student_result) > 0) {
                           $student_data = mysqli_fetch_assoc($student_result);
                           $student_id = $student_data['student_id'];
                       }
                   }
                   
                   if ($student_id) {
                       // Verify the certificate
                       $certificate_data = verifyCertificate($con, $student_id, $course_id, $enrollment_id);
                       
                       if ($certificate_data) {
                           $verification_result = 'success';
                       } else {
                           $verification_result = 'error';
                       }
                   } else {
                       $verification_result = 'error';
                   }
               } else {
                   $verification_result = 'error';
               }
           } elseif (isset($_POST['student_id']) && isset($_POST['course_id'])) {
               // Verification by student and course IDs
               $student_id = mysqli_real_escape_string($con, $_POST['student_id']);
               $course_id = mysqli_real_escape_string($con, $_POST['course_id']);
               
               // Verify the certificate
               $certificate_data = verifyCertificate($con, $student_id, $course_id);
               
               if ($certificate_data) {
                   $verification_result = 'success';
               } else {
                   $verification_result = 'error';
               }
           } elseif (isset($_POST['email']) && !empty($_POST['email'])) {
               // Verification by email
               $email = mysqli_real_escape_string($con, $_POST['email']);
               
               // Get all certificates for this email
               $certificates_list = getCertificatesByEmail($con, $email);
               
               if ($certificates_list && count($certificates_list) > 0) {
                   $verification_result = 'success_multiple';
               } else {
                   $verification_result = 'error';
               }
           }
       }
       
       /**
        * Verify if a student has completed a course and is eligible for a certificate
        * 
        * @param mysqli $con Database connection
        * @param int $student_id Student ID
        * @param int $course_id Course ID
        * @param int $enrollment_id Optional enrollment ID for more specific verification
        * @return array|null Certificate details if verified, null otherwise
        */
       function verifyCertificate($con, $student_id, $course_id, $enrollment_id = null) {
           // Build the enrollment check query
           $enrollment_check = "SELECT ce.enrollment_id, ce.enrollment_date, 
                              c.name as course_name, c.description, c.start_date, c.prerequisites,
                              cat.name as category_name,
                              u.name as instructor_name, 
                              s.name as student_name, s.email as student_email, s.user_id,
                              COALESCE(sp.value, 0) as progress,
                              sp.date as completion_date
                              FROM course_enrollments ce
                              JOIN courses c ON ce.course_id = c.course_id
                              JOIN category cat ON c.category_id = cat.category_id
                              JOIN users u ON c.instructor_id = u.user_id
                              JOIN users s ON ce.student_id = s.user_id
                              LEFT JOIN student_progress sp ON ce.course_id = sp.course_id AND ce.student_id = sp.student_id
                              WHERE ce.student_id = '$student_id' AND ce.course_id = '$course_id'";
           
           // Add enrollment ID check if provided
           if ($enrollment_id) {
               $enrollment_check .= " AND ce.enrollment_id = '$enrollment_id'";
           }
           
           $enrollment_result = mysqli_query($con, $enrollment_check);
           
           if (!$enrollment_result || mysqli_num_rows($enrollment_result) === 0) {
               return null; // Student is not enrolled in the course
           }
           
           $certificate_data = mysqli_fetch_assoc($enrollment_result);
           
           // Check if the student has completed the course (progress = 100%)
           if ($certificate_data['progress'] < 100) {
               return null; // Student has not completed the course
           }
           
           // Get materials count for additional info
           $materials_query = "SELECT COUNT(*) as total_materials FROM course_materials WHERE course_id = '$course_id'";
           $materials_result = mysqli_query($con, $materials_query);
           $materials_data = mysqli_fetch_assoc($materials_result);
           $certificate_data['total_materials'] = $materials_data['total_materials'];
           
           // Get events count for additional info
           $events_query = "SELECT COUNT(*) as total_events FROM course_events WHERE course_id = '$course_id'";
           $events_result = mysqli_query($con, $events_query);
           $events_data = mysqli_fetch_assoc($events_result);
           $certificate_data['total_events'] = $events_data['total_events'];
           
           // Calculate course duration (in weeks or months)
           $start_date = new DateTime($certificate_data['start_date']);
           $completion_date = new DateTime($certificate_data['completion_date']); 
           $interval = $start_date->diff($completion_date);
           
           if ($interval->m > 0) {
               $certificate_data['duration'] = $interval->m . " " . translate("month") . ($interval->m > 1 ? translate("s") : "");
           } else {
               $weeks = ceil($interval->days / 7);
               $certificate_data['duration'] = $weeks . " " . translate("week") . ($weeks > 1 ? translate("s") : "");
           }
           
           // Generate certificate number
           $certificate_data['certificate_number'] = 'CERT-' . $certificate_data['enrollment_id'] . '-' . $course_id . '-' . $student_id;
           
           // Format dates for display
           $certificate_data['issue_date'] = date('F j, Y', strtotime($certificate_data['completion_date']));
           $certificate_data['enrollment_date_formatted'] = date('F j, Y', strtotime($certificate_data['enrollment_date']));
           
           return $certificate_data;
       }
       
       /**
        * Get all certificates for a user by email
        * 
        * @param mysqli $con Database connection
        * @param string $email User email
        * @return array|null List of certificates if found, null otherwise
        */
       function getCertificatesByEmail($con, $email) {
           // First get the user ID from email
           $user_query = "SELECT user_id FROM users WHERE email = '$email'";
           $user_result = mysqli_query($con, $user_query);
           
           if (!$user_result || mysqli_num_rows($user_result) === 0) {
               return null; // User not found
           }
           
           $user_data = mysqli_fetch_assoc($user_result);
           $student_id = $user_data['user_id'];
           
           // Get all courses where the student has 100% progress
           $courses_query = "SELECT ce.course_id, ce.enrollment_id 
                            FROM course_enrollments ce
                            JOIN student_progress sp ON ce.course_id = sp.course_id AND ce.student_id = sp.student_id
                            WHERE ce.student_id = '$student_id' AND sp.value = 100";
           $courses_result = mysqli_query($con, $courses_query);
           
           if (!$courses_result || mysqli_num_rows($courses_result) === 0) {
               return null; // No completed courses
           }
           
           $certificates = array();
           
           // Get certificate details for each completed course
           while ($course = mysqli_fetch_assoc($courses_result)) {
               $certificate = verifyCertificate($con, $student_id, $course['course_id'], $course['enrollment_id']);
               if ($certificate) {
                   $certificates[] = $certificate;
               }
           }
           
           return $certificates;
       }
       ?>
       
       <?php if ($verification_result === 'success'): ?>
           <div class="verification-result verification-success">
               <h2 class="mb-3">✓ <?php echo translate('Certificate Verified'); ?></h2>
               <p><?php echo translate('This certificate is valid and has been issued by E-Training.'); ?></p>
               
               <div class="certificate-seal">
                   <?php echo translate('OFFICIAL'); ?><br><?php echo translate('COURSE'); ?><br><?php echo translate('CERTIFICATE'); ?>
               </div>
               
               <div class="certificate-details">
                   <h3><?php echo translate('Certificate Details'); ?></h3>
                   
                   <div class="detail-row">
                       <div class="detail-label"><?php echo translate('Certificate Number:'); ?></div>
                       <div class="detail-value"><?php echo htmlspecialchars($certificate_data['certificate_number']); ?></div>
                   </div>
                   
                   <div class="detail-row">
                       <div class="detail-label"><?php echo translate('Student Name:'); ?></div>
                       <div class="detail-value"><?php echo htmlspecialchars($certificate_data['student_name']); ?></div>
                   </div>
                   
                   <div class="detail-row">
                       <div class="detail-label"><?php echo translate('Student ID:'); ?></div>
                       <div class="detail-value"><?php echo htmlspecialchars($certificate_data['user_id']); ?></div>
                   </div>
                   
                   <div class="detail-row">
                       <div class="detail-label"><?php echo translate('Course:'); ?></div>
                       <div class="detail-value"><?php echo htmlspecialchars($certificate_data['course_name']); ?></div>
                   </div>
                   
                   <div class="detail-row">
                       <div class="detail-label"><?php echo translate('Course Description:'); ?></div>
                       <div class="detail-value"><?php echo htmlspecialchars($certificate_data['description']); ?></div>
                   </div>
                   
                   <div class="detail-row">
                       <div class="detail-label"><?php echo translate('Category:'); ?></div>
                       <div class="detail-value"><?php echo htmlspecialchars($certificate_data['category_name']); ?></div>
                   </div>
                   
                   <div class="detail-row">
                       <div class="detail-label"><?php echo translate('Instructor:'); ?></div>
                       <div class="detail-value"><?php echo htmlspecialchars($certificate_data['instructor_name']); ?></div>
                   </div>
                   
                   <div class="detail-row">
                       <div class="detail-label"><?php echo translate('Course Duration:'); ?></div>
                       <div class="detail-value"><?php echo htmlspecialchars($certificate_data['duration']); ?></div>
                   </div>
                   
                   <div class="detail-row">
                       <div class="detail-label"><?php echo translate('Enrollment Date:'); ?></div>
                       <div class="detail-value"><?php echo htmlspecialchars($certificate_data['enrollment_date_formatted']); ?></div>
                   </div>
                   
                   <div class="detail-row">
                       <div class="detail-label"><?php echo translate('Completion Date:'); ?></div>
                       <div class="detail-value"><?php echo htmlspecialchars($certificate_data['issue_date']); ?></div>
                   </div>
                   
                   <div class="detail-row">
                       <div class="detail-label"><?php echo translate('Materials Completed:'); ?></div>
                       <div class="detail-value"><?php echo htmlspecialchars($certificate_data['total_materials']); ?></div>
                   </div>
                   
                   <div class="detail-row">
                       <div class="detail-label"><?php echo translate('Events Attended:'); ?></div>
                       <div class="detail-value"><?php echo htmlspecialchars($certificate_data['total_events']); ?></div>
                   </div>
               </div>
               
               
           </div>
       <?php elseif ($verification_result === 'success_multiple'): ?>
           <div class="verification-result verification-success">
               <h2 class="mb-3">✓ <?php echo translate('Certificates Found'); ?></h2>
               <p><?php echo translate('The following certificates have been issued to'); ?> <?php echo htmlspecialchars($certificates_list[0]['student_name']); ?> (<?php echo htmlspecialchars($certificates_list[0]['student_email']); ?>):</p>
               
               <div class="certificate-seal">
                   <?php echo translate('OFFICIAL'); ?><br><?php echo translate('COURSE'); ?><br><?php echo translate('CERTIFICATES'); ?>
               </div>
               
               <?php foreach ($certificates_list as $cert): ?>
                   <div class="certificate-card">
                       <div class="certificate-card-header">
                           <?php echo htmlspecialchars($cert['course_name']); ?>
                       </div>
                       <div class="certificate-card-body">
                           <div class="row">
                               <div class="col-md-6">
                                   <p><strong><?php echo translate('Certificate Number:'); ?></strong> <?php echo htmlspecialchars($cert['certificate_number']); ?></p>
                                   <p><strong><?php echo translate('Category:'); ?></strong> <?php echo htmlspecialchars($cert['category_name']); ?></p>
                                   <p><strong><?php echo translate('Instructor:'); ?></strong> <?php echo htmlspecialchars($cert['instructor_name']); ?></p>
                               </div>
                               <div class="col-md-6">
                                   <p><strong><?php echo translate('Completion Date:'); ?></strong> <?php echo htmlspecialchars($cert['issue_date']); ?></p>
                                   <p><strong><?php echo translate('Course Duration:'); ?></strong> <?php echo htmlspecialchars($cert['duration']); ?></p>
                                   <p><strong><?php echo translate('Materials Completed:'); ?></strong> <?php echo htmlspecialchars($cert['total_materials']); ?></p>
                               </div>
                           </div>
                       </div>
                       <div class="certificate-card-footer">
                           
                       </div>
                   </div>
               <?php endforeach; ?>
           </div>
       <?php elseif ($verification_result === 'error'): ?>
           <div class="verification-result verification-error">
               <h2 class="mb-3">✗ <?php echo translate('Certificate Not Verified'); ?></h2>
               <p><?php echo translate('We could not verify this certificate. This may be because:'); ?></p>
               <ul>
                   <li><?php echo translate('The certificate number, email, or details entered are incorrect'); ?></li>
                   <li><?php echo translate('The student has not completed the course'); ?></li>
                   <li><?php echo translate('The student is not enrolled in the course'); ?></li>
                   <li><?php echo translate('The certificate has been revoked'); ?></li>
                   <li><?php echo translate('No certificates have been issued to this email address'); ?></li>
               </ul>
               <p><?php echo translate('Please check the information and try again, or contact support for assistance.'); ?></p>
           </div>
       <?php else: ?>
           <!-- Verification Form Tabs -->
           <ul class="nav nav-tabs" id="verificationTabs" role="tablist">
               <li class="nav-item" role="presentation">
                   <button class="nav-link active" id="certificate-tab" data-bs-toggle="tab" data-bs-target="#certificate" 
                           type="button" role="tab" aria-controls="certificate" aria-selected="true">
                       <?php echo translate('Verify by Certificate Number'); ?>
                   </button>
               </li>
               <li class="nav-item" role="presentation">
                   <button class="nav-link" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" 
                           type="button" role="tab" aria-controls="details" aria-selected="false">
                       <?php echo translate('Verify by Student/Course ID'); ?>
                   </button>
               </li>
               <li class="nav-item" role="presentation">
                   <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" 
                           type="button" role="tab" aria-controls="email" aria-selected="false">
                       <?php echo translate('Verify by Email'); ?>
                   </button>
               </li>
           </ul>
           
           <div class="tab-content" id="verificationTabsContent">
               <!-- Certificate Number Verification Form -->
               <div class="tab-pane fade show active" id="certificate" role="tabpanel" aria-labelledby="certificate-tab">
                   <form class="verification-form" method="post" action="">
                       <div class="mb-3">
                           <label for="certificate_number" class="form-label"><?php echo translate('Certificate Number'); ?></label>
                           <input type="text" class="form-control" id="certificate_number" name="certificate_number" 
                                  placeholder="<?php echo translate('Enter certificate number (e.g., CERT-123-456-789)'); ?>" required
                                  value="<?php echo isset($_GET['certificate_number']) ? htmlspecialchars($_GET['certificate_number']) : ''; ?>">
                           <div class="form-text"><?php echo translate('The certificate number is printed on the certificate (format: CERT-XXX-YYY-ZZZ)'); ?></div>
                       </div>
                       <button type="submit" class="btn btn-primary"><?php echo translate('Verify Certificate'); ?></button>
                   </form>
               </div>
               
               <!-- Student/Course ID Verification Form -->
               <div class="tab-pane fade" id="details" role="tabpanel" aria-labelledby="details-tab">
                   <form class="verification-form" method="post" action="">
                       <div class="mb-3">
                           <label for="student_id" class="form-label"><?php echo translate('Student ID'); ?></label>
                           <input type="number" class="form-control" id="student_id" name="student_id" 
                                  placeholder="<?php echo translate('Enter student ID'); ?>" required>
                       </div>
                       <div class="mb-3">
                           <label for="course_id" class="form-label"><?php echo translate('Course ID'); ?></label>
                           <input type="number" class="form-control" id="course_id" name="course_id" 
                                  placeholder="<?php echo translate('Enter course ID'); ?>" required>
                       </div>
                       <button type="submit" class="btn btn-primary"><?php echo translate('Verify Certificate'); ?></button>
                   </form>
               </div>
               
               <!-- Email Verification Form -->
               <div class="tab-pane fade" id="email" role="tabpanel" aria-labelledby="email-tab">
                   <form class="verification-form" method="post" action="">
                       <div class="mb-3">
                           <label for="email" class="form-label"><?php echo translate('Email Address'); ?></label>
                           <input type="email" class="form-control" id="email" name="email" 
                                  placeholder="<?php echo translate('Enter email address'); ?>" required>
                           <div class="form-text"><?php echo translate('Enter the email address associated with the certificates'); ?></div>
                       </div>
                       <button type="submit" class="btn btn-primary"><?php echo translate('Find Certificates'); ?></button>
                   </form>
               </div>
           </div>
       <?php endif; ?>
       
       <div class="mt-4 text-center">
           <a href="index.php" class="btn btn-secondary"><?php echo translate('Back to Home'); ?></a>
       </div>
   </div>
   
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
   
   <?php if (isset($_GET['certificate_number']) && empty($verification_result)): ?>
   <!-- Auto-submit the form if certificate number is in URL but verification hasn't happened yet -->
   <script>
       document.addEventListener('DOMContentLoaded', function() {
           document.querySelector('form').submit();
       });
   </script>
   <?php endif; ?>
</body>
</html>
