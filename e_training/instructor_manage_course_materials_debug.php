<?php
// Start with basic debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
echo "<h2>Debug Information</h2>";
echo "<pre>";

// Check session variables
echo "SESSION Variables:\n";
print_r($_SESSION);
echo "\n\n";

// Check if config.php exists
echo "Config File Check:\n";
if (file_exists('config.php')) {
    echo "config.php exists\n";
    include 'config.php';
} else {
    echo "config.php does not exist!\n";
    die("Cannot continue without config.php");
}

// Check database connection
echo "\nDatabase Connection:\n";
if (isset($con)) {
    echo "Database connection variable exists\n";
    if ($con->connect_error) {
        echo "Connection failed: " . $con->connect_error;
    } else {
        echo "Connection successful\n";
    }
} else {
    echo "Database connection variable does not exist!\n";
}

// Check authentication
echo "\nAuthentication Check:\n";
if (!isset($_SESSION['user_type'])) {
    echo "No user_type in session\n";
} else {
    echo "user_type: " . $_SESSION['user_type'] . "\n";
    
    if ($_SESSION['user_type'] != "instructor") {
        echo "User is not an instructor\n";
    } else {
        echo "User is an instructor\n";
        
        if (!isset($_SESSION['user_id'])) {
            echo "No user_id in session\n";
        } else {
            echo "user_id: " . $_SESSION['user_id'] . "\n";
        }
    }
}

// Check course_id parameter
echo "\nCourse ID Check:\n";
if (!isset($_GET['course_id'])) {
    echo "No course_id provided in URL\n";
    echo "URL should be: instructor_manage_course_materials.php?course_id=X\n";
} else {
    $course_id = $_GET['course_id'];
    echo "course_id: " . $course_id . "\n";
    
    // Check if instructor owns this course
    if (isset($con) && isset($_SESSION['user_id'])) {
        $instructor_id = $_SESSION['user_id'];
        $course_check = "SELECT * FROM courses WHERE course_id = '$course_id' AND instructor_id = '$instructor_id'";
        echo "Query: " . $course_check . "\n";
        
        $course_result = mysqli_query($con, $course_check);
        
        if (!$course_result) {
            echo "Query error: " . mysqli_error($con) . "\n";
        } else {
            echo "Query executed successfully\n";
            echo "Number of rows: " . mysqli_num_rows($course_result) . "\n";
            
            if (mysqli_num_rows($course_result) == 0) {
                echo "Instructor does not own this course\n";
            } else {
                echo "Instructor owns this course\n";
                $course = mysqli_fetch_assoc($course_result);
                echo "Course details:\n";
                print_r($course);
            }
        }
    }
}

echo "</pre>";
echo "<p><a href='instructor_dashboard.php'>Return to Dashboard</a></p>";
?>
