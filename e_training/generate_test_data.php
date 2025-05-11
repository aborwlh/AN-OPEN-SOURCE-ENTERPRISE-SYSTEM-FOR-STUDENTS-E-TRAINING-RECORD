<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Generate Test Data for API</h1>";

// Include your config file
require_once 'config.php';

// Check if we should proceed
$confirm = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

if (!$confirm) {
    echo "<p>This script will generate test data for your API. This is intended for testing purposes only.</p>";
    echo "<p><strong>Warning:</strong> This may modify existing data. Make sure you have a backup.</p>";
    echo "<p><a href='?confirm=yes' style='color: red; font-weight: bold;'>Click here to proceed</a></p>";
    exit;
}

// Function to check if a table exists
function tableExists($con, $table) {
    $result = $con->query("SHOW TABLES LIKE '$table'");
    return $result && $result->num_rows > 0;
}

// Function to create a table if it doesn't exist
function createTableIfNotExists($con, $table, $schema) {
    if (!tableExists($con, $table)) {
        if ($con->query($schema)) {
            echo "<p style='color: green;'>✓ Created table: $table</p>";
        } else {
            echo "<p style='color: red;'>✗ Error creating table $table: " . $con->error . "</p>";
        }
    } else {
        echo "<p>Table already exists: $table</p>";
    }
}

// Create necessary tables if they don't exist
if (isset($con)) {
    // Create users table
    $users_schema = "CREATE TABLE IF NOT EXISTS users (
        user_id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        mobile VARCHAR(20),
        role ENUM('admin', 'instructor', 'student') NOT NULL,
        registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login DATETIME NULL
    )";
    createTableIfNotExists($con, 'users', $users_schema);
    
    // Create category table
    $category_schema = "CREATE TABLE IF NOT EXISTS category (
        category_id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL
    )";
    createTableIfNotExists($con, 'category', $category_schema);
    
    // Create courses table
    $courses_schema = "CREATE TABLE IF NOT EXISTS courses (
        course_id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        category_id INT(11),
        instructor_id INT(11),
        start_date DATE,
        img VARCHAR(255),
        prerequisites TEXT,
        FOREIGN KEY (category_id) REFERENCES category(category_id) ON DELETE SET NULL,
        FOREIGN KEY (instructor_id) REFERENCES users(user_id) ON DELETE SET NULL
    )";
    createTableIfNotExists($con, 'courses', $courses_schema);
    
    // Create course_enrollments table
    $enrollments_schema = "CREATE TABLE IF NOT EXISTS course_enrollments (
        enrollment_id INT(11) AUTO_INCREMENT PRIMARY KEY,
        student_id INT(11),
        course_id INT(11),
        enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE
    )";
    createTableIfNotExists($con, 'course_enrollments', $enrollments_schema);
    
    // Create student_progress table
    $progress_schema = "CREATE TABLE IF NOT EXISTS student_progress (
        progress_id INT(11) AUTO_INCREMENT PRIMARY KEY,
        student_id INT(11),
        course_id INT(11),
        value INT(3) DEFAULT 0,
        date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE
    )";
    createTableIfNotExists($con, 'student_progress', $progress_schema);
    
    // Insert test data
    echo "<h2>Inserting test data:</h2>";
    
    // Insert test categories
    $categories = ['Programming', 'Design', 'Business', 'Marketing'];
    foreach ($categories as $category) {
        $cat_name = $con->real_escape_string($category);
        $result = $con->query("SELECT * FROM category WHERE name = '$cat_name'");
        if ($result->num_rows == 0) {
            $con->query("INSERT INTO category (name) VALUES ('$cat_name')");
            echo "<p>Added category: $cat_name</p>";
        } else {
            echo "<p>Category already exists: $cat_name</p>";
        }
    }
    
    // Insert test users
    $password_hash = password_hash('password123', PASSWORD_BCRYPT);
    
    // Add instructor
    $instructor_email = 'instructor@test.com';
    $result = $con->query("SELECT * FROM users WHERE email = '$instructor_email'");
    if ($result->num_rows == 0) {
        $con->query("INSERT INTO users (name, email, password, role) 
                    VALUES ('Test Instructor', '$instructor_email', '$password_hash', 'instructor')");
        echo "<p>Added test instructor: $instructor_email</p>";
    } else {
        echo "<p>Instructor already exists: $instructor_email</p>";
    }
    
    // Add student
    $student_email = 'student1@student1.com';
    $result = $con->query("SELECT * FROM users WHERE email = '$student_email'");
    if ($result->num_rows == 0) {
        $con->query("INSERT INTO users (name, email, password, role) 
                    VALUES ('Test Student', '$student_email', '$password_hash', 'student')");
        echo "<p>Added test student: $student_email</p>";
    } else {
        echo "<p>Student already exists: $student_email</p>";
    }
    
    // Get instructor ID
    $instructor_result = $con->query("SELECT user_id FROM users WHERE email = '$instructor_email'");
    $instructor_id = $instructor_result->fetch_assoc()['user_id'];
    
    // Get category ID
    $category_result = $con->query("SELECT category_id FROM category WHERE name = 'Programming'");
    $category_id = $category_result->fetch_assoc()['category_id'];
    
    // Add test course
    $course_name = 'Test Course';
    $result = $con->query("SELECT * FROM courses WHERE name = '$course_name'");
    if ($result->num_rows == 0) {
        $con->query("INSERT INTO courses (name, description, category_id, instructor_id, start_date) 
                    VALUES ('$course_name', 'This is a test course for API testing', '$category_id', '$instructor_id', NOW())");
        echo "<p>Added test course: $course_name</p>";
    } else {
        echo "<p>Course already exists: $course_name</p>";
    }
    
    // Get course ID
    $course_result = $con->query("SELECT course_id FROM courses WHERE name = '$course_name'");
    $course_id = $course_result->fetch_assoc()['course_id'];
    
    // Get student ID
    $student_result = $con->query("SELECT user_id FROM users WHERE email = '$student_email'");
    $student_id = $student_result->fetch_assoc()['user_id'];
    
    // Add enrollment
    $result = $con->query("SELECT * FROM course_enrollments WHERE student_id = '$student_id' AND course_id = '$course_id'");
    if ($result->num_rows == 0) {
        $con->query("INSERT INTO course_enrollments (student_id, course_id) 
                    VALUES ('$student_id', '$course_id')");
        echo "<p>Added test enrollment for student $student_id in course $course_id</p>";
    } else {
        echo "<p>Enrollment already exists for student $student_id in course $course_id</p>";
    }
    
    // Add progress (100% completion)
    $result = $con->query("SELECT * FROM student_progress WHERE student_id = '$student_id' AND course_id = '$course_id'");
    if ($result->num_rows == 0) {
        $con->query("INSERT INTO student_progress (student_id, course_id, value) 
                    VALUES ('$student_id', '$course_id', 100)");
        echo "<p>Added 100% progress for student $student_id in course $course_id</p>";
    } else {
        echo "<p>Progress already exists for student $student_id in course $course_id</p>";
    }
    
    // Get enrollment ID
    $enrollment_result = $con->query("SELECT enrollment_id FROM course_enrollments WHERE student_id = '$student_id' AND course_id = '$course_id'");
    $enrollment_id = $enrollment_result->fetch_assoc()['enrollment_id'];
    
    echo "<h2>Test data generation complete!</h2>";
    echo "<p>You can now test the API with:</p>";
    echo "<ul>";
    echo "<li>Email: $student_email</li>";
    echo "<li>Certificate Code: CERT-$enrollment_id-$course_id</li>";
    echo "</ul>";
    
    echo "<p><a href='api_test.php'>Go to API Test</a></p>";
    
} else {
    echo "<p style='color: red;'>Database connection variable (\$con) not found in config.php</p>";
}
?>
