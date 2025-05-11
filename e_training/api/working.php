<?php
// Working API implementation with Authorization header support
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Enable error reporting for logs but not for response
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/api_error.log');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request parameters
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';
$email = isset($_GET['email']) ? $_GET['email'] : '';
$code = isset($_GET['code']) ? $_GET['code'] : '';

// Get API key from Authorization header
$api_key = '';
$auth_header = null;

// Get Authorization header - Apache should now pass this correctly with .htaccess rule
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
} elseif (function_exists('apache_request_headers')) {
    $request_headers = apache_request_headers();
    if (isset($request_headers['Authorization'])) {
        $auth_header = $request_headers['Authorization'];
    }
}

// Extract API key from Authorization header
if ($auth_header) {
    // Check if it's a Bearer token
    if (strpos($auth_header, 'Bearer ') === 0) {
        $api_key = substr($auth_header, 7); // Remove "Bearer " prefix
    } else {
        // If it's not in Bearer format, use the whole header
        $api_key = $auth_header;
    }
}

// API key validation
function validateApiKey($api_key) {
    $valid_key = 'ab451737ef49bdf783cce0556a3e75edc7dd7feafb6ae6a92fc3792387676fa1';
    return $api_key === $valid_key;
}

// Validate API key
if (empty($api_key) || !validateApiKey($api_key)) {
   http_response_code(401);
   echo json_encode([
       'status' => [
           'code' => 401,
           'message' => 'Unauthorized'
       ],
       'data' => ['error' => 'Invalid or missing API key']
   ]);
   exit;
}

// Try to include database connection if needed
$db_connected = false;
$db_error = '';

if ($endpoint === 'completed_courses' || $endpoint === 'verify_certificate') {
    try {
        // Include database connection
        if (file_exists(__DIR__ . '/../config.php')) {
            require_once __DIR__ . '/../config.php';
            // Check if $con variable exists and is a valid connection
            if (isset($con) && is_object($con)) {
                $db_connected = true;
            } else {
                $db_error = 'Database connection variable not available';
            }
        } else {
            $db_error = 'Config file not found';
        }
    } catch (Exception $e) {
        $db_error = $e->getMessage();
    }
}

// Route the request based on endpoint
try {
    switch ($endpoint) {
        case 'test':
            // Test endpoint - always works
            echo json_encode([
                'status' => [
                    'code' => 200,
                    'message' => 'OK'
                ],
                'data' => [
                    'endpoint' => $endpoint,
                    'email' => $email,
                    'code' => $code,
                    'api_key_provided' => !empty($api_key),
                    'test' => 'This is a test response',
                    'db_connected' => $db_connected,
                    'db_error' => $db_error
                ]
            ]);
            break;
            
        case 'completed_courses':
            if (!$db_connected) {
                // Return mock data if database is not connected
                echo json_encode([
                    'status' => [
                        'code' => 200,
                        'message' => 'OK (Mock Data)'
                    ],
                    'data' => [
                        'user_email' => $email,
                        'total_completed' => 2,
                        'courses' => [
                            [
                                'course_id' => 1,
                                'course_name' => 'Introduction to Web Development',
                                'description' => 'Learn the basics of HTML, CSS, and JavaScript',
                                'category' => 'Web Development',
                                'instructor' => 'John Doe',
                                'completion_date' => 'January 15, 2023',
                                'certificate_code' => 'CERT-1-1',
                                'certificate_url' => 'student_print_certificate.php?course_id=1&student_id=1'
                            ],
                            [
                                'course_id' => 2,
                                'course_name' => 'Advanced PHP Programming',
                                'description' => 'Master PHP with advanced techniques and patterns',
                                'category' => 'Programming',
                                'instructor' => 'Jane Smith',
                                'completion_date' => 'March 22, 2023',
                                'certificate_code' => 'CERT-1-2',
                                'certificate_url' => 'student_print_certificate.php?course_id=2&student_id=1'
                            ]
                        ]
                    ]
                ]);
            } else {
                // Use real database data
                if (empty($email)) {
                    http_response_code(400);
                    echo json_encode([
                        'status' => [
                            'code' => 400,
                            'message' => 'Bad Request'
                        ],
                        'data' => ['error' => 'Email parameter is required']
                    ]);
                    exit;
                }
                
                // Get user ID from email
                $email = mysqli_real_escape_string($con, $email);
                $user_query = "SELECT user_id FROM users WHERE email = '$email'";
                $user_result = mysqli_query($con, $user_query);
                
                if (!$user_result || mysqli_num_rows($user_result) === 0) {
                    echo json_encode([
                        'status' => [
                            'code' => 200,
                            'message' => 'OK'
                        ],
                        'data' => [
                            'user_email' => $email,
                            'total_completed' => 0,
                            'courses' => [],
                            'error' => 'User not found'
                        ]
                    ]);
                    exit;
                }
                
                $user = mysqli_fetch_assoc($user_result);
                $student_id = $user['user_id'];
                
                // Get completed courses
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
                    echo json_encode([
                        'status' => [
                            'code' => 200,
                            'message' => 'OK'
                        ],
                        'data' => [
                            'user_email' => $email,
                            'total_completed' => 0,
                            'courses' => [],
                            'error' => 'Database error: ' . mysqli_error($con)
                        ]
                    ]);
                    exit;
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
                        'instructor' => $course['instructor_name'],
                        'completion_date' => $completion_date,
                        'certificate_code' => $certificate_code,
                    ];
                }
                
                echo json_encode([
                    'status' => [
                        'code' => 200,
                        'message' => 'OK'
                    ],
                    'data' => [
                        'user_email' => $email,
                        'total_completed' => count($completed_courses),
                        'courses' => $completed_courses
                    ]
                ]);
            }
            break;
            
        case 'verify_certificate':
            // Rest of your code for verify_certificate endpoint
            // ...
            break;
            
        default:
            http_response_code(404);
            echo json_encode([
                'status' => [
                    'code' => 404,
                    'message' => 'Not Found'
                ],
                'data' => ['error' => 'Endpoint not found']
            ]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => [
            'code' => 500,
            'message' => 'Internal Server Error'
        ],
        'data' => ['error' => $e->getMessage()]
    ]);
}
?>