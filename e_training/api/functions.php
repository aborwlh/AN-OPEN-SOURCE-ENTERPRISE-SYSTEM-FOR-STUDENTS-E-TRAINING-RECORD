<?php
/**
 * API Functions
 */

/**
 * Validate API key
 * 
 * @param string $api_key The API key to validate
 * @return bool True if valid, false otherwise
 */
function validateApiKey($api_key) {
    // Use the API key defined in config.php
    $valid_key = defined('API_KEY') ? API_KEY : 'ab451737ef49bdf783cce0556a3e75edc7dd7feafb6ae6a92fc3792387676fa1';
    
    // Check if the API key is in the format "Bearer {key}"
    if (preg_match('/^Bearer\s+(.+)$/i', $api_key, $matches)) {
        return $matches[1] === $valid_key;
    }
    
    // Also accept the raw key for flexibility
    return $api_key === $valid_key;
}

/**
 * Get completed courses for a user by email
 * 
 * @param string $email User email
 * @return array Completed courses data
 */
function getCompletedCourses($email) {
    global $con;
    
    // First, get the user ID from email
    $user_query = "SELECT user_id FROM users WHERE email = '$email'";
    $user_result = mysqli_query($con, $user_query);
    
    if (!$user_result || mysqli_num_rows($user_result) === 0) {
        return ['error' => 'User not found'];
    }
    
    $user = mysqli_fetch_assoc($user_result);
    $student_id = $user['user_id'];
    
    // Get all completed courses (progress = 100%)
    $courses_query = "SELECT c.course_id, c.name as course_name, c.description, 
                     cat.name as category_name, u.name as instructor_name,
                     sp.date as completion_date, ce.enrollment_id
                     FROM student_progress sp
                     JOIN courses c ON sp.course_id = c.course_id
                     JOIN category cat ON c.category_id = cat.category_id
                     JOIN users u ON c.instructor_id = u.user_id
                     JOIN course_enrollments ce ON sp.course_id = ce.course_id AND sp.student_id = ce.student_id
                     WHERE sp.student_id = '$student_id' AND sp.value = 100
                     ORDER BY sp.date DESC";
    
    $courses_result = mysqli_query($con, $courses_query);
    
    if (!$courses_result) {
        error_log("Database error in getCompletedCourses: " . mysqli_error($con));
        return ['error' => 'Database error: ' . mysqli_error($con)];
    }
    
    $completed_courses = [];
    
    while ($course = mysqli_fetch_assoc($courses_result)) {
        // Generate certificate code
        $certificate_code = "CERT-{$course['enrollment_id']}-{$course['course_id']}";
        
        // Format completion date
        $completion_date = date('F j, Y', strtotime($course['completion_date']));
        
        $completed_courses[] = [
            'course_id' => $course['course_id'],
            'course_name' => $course['course_name'],
            'description' => $course['description'],
            'category' => $course['category_name'],
            'instructor' => $course['instructor_name'],
            'completion_date' => $completion_date,
            'certificate_code' => $certificate_code,
            'certificate_url' => "student_print_certificate.php?course_id={$course['course_id']}&student_id={$student_id}"
        ];
    }
    
    return [
        'user_email' => $email,
        'total_completed' => count($completed_courses),
        'courses' => $completed_courses
    ];
}

/**
 * Verify a certificate by its code
 * 
 * @param string $code Certificate verification code
 * @return array Certificate verification result
 */
function verifyCertificate($code) {
    global $con;
    
    // Parse the certificate code format: CERT-{enrollment_id}-{course_id}
    if (!preg_match('/^CERT-(\d+)-(\d+)(?:-(\d+))?$/', $code, $matches)) {
        return ['verified' => false, 'message' => 'Invalid certificate format'];
    }
    
    $enrollment_id = $matches[1];
    $course_id = $matches[2];
    $student_id = isset($matches[3]) ? $matches[3] : null;
    
    // If student_id is not in the certificate code, we need to find it
    if (!$student_id) {
        // Get student ID from enrollment
        $student_query = "SELECT student_id FROM course_enrollments WHERE enrollment_id = '$enrollment_id' AND course_id = '$course_id'";
        $student_result = mysqli_query($con, $student_query);
        
        if ($student_result && mysqli_num_rows($student_result) > 0) {
            $student_data = mysqli_fetch_assoc($student_result);
            $student_id = $student_data['student_id'];
        } else {
            return ['verified' => false, 'message' => 'Certificate not found'];
        }
    }
    
    // Verify the certificate
    $certificate_query = "SELECT sp.*, c.name as course_name, c.description, 
                         cat.name as category_name, u.name as instructor_name, 
                         s.name as student_name, s.email as student_email
                         FROM student_progress sp
                         JOIN courses c ON sp.course_id = c.course_id
                         JOIN category cat ON c.category_id = cat.category_id
                         JOIN users u ON c.instructor_id = u.user_id
                         JOIN users s ON sp.student_id = s.user_id
                         JOIN course_enrollments ce ON sp.course_id = ce.course_id AND sp.student_id = ce.student_id
                         WHERE sp.student_id = '$student_id' 
                         AND sp.course_id = '$course_id'
                         AND ce.enrollment_id = '$enrollment_id'
                         AND sp.value = 100";
    
    $certificate_result = mysqli_query($con, $certificate_query);
    
    if (!$certificate_result) {
        error_log("Database error in verifyCertificate: " . mysqli_error($con));
        return ['verified' => false, 'message' => 'Database error: ' . mysqli_error($con)];
    }
    
    if (mysqli_num_rows($certificate_result) === 0) {
        return ['verified' => false, 'message' => 'Certificate not found or course not completed'];
    }
    
    $certificate = mysqli_fetch_assoc($certificate_result);
    
    // Format completion date
    $completion_date = date('F j, Y', strtotime($certificate['date']));
    
    return [
        'verified' => true,
        'certificate_code' => $code,
        'student' => [
            'name' => $certificate['student_name'],
            'email' => $certificate['student_email']
        ],
        'course' => [
            'id' => $course_id,
            'name' => $certificate['course_name'],
            'description' => $certificate['description'],
            'category' => $certificate['category_name'],
            'instructor' => $certificate['instructor_name']
        ],
        'completion_date' => $completion_date,
        'certificate_url' => "student_print_certificate.php?course_id={$course_id}&student_id={$student_id}"
    ];
}
