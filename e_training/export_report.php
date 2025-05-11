<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != "admin") {
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

// Get parameters
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'login_history';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$user_role = isset($_GET['user_role']) ? $_GET['user_role'] : 'all';
$export_format = isset($_GET['export_format']) ? $_GET['export_format'] : 'csv';

// Set headers based on export format
if ($export_format == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="report_' . $report_type . '_' . date('Y-m-d') . '.csv"');
    
    // Disable output buffering
    if (ob_get_level()) ob_end_clean();
} else { // txt format
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="report_' . $report_type . '_' . date('Y-m-d') . '.txt"');
    
    // Disable output buffering
    if (ob_get_level()) ob_end_clean();
}

// Generate report data based on type
if ($report_type == 'login_history') {
    // Build query for login history
    $query = "SELECT lh.login_id, lh.user_id, u.name, u.email, u.role, lh.login_time, lh.ip_address 
             FROM login_history lh
             JOIN users u ON lh.user_id = u.user_id
             WHERE lh.login_time BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
    
    if ($user_role != 'all') {
        $query .= " AND u.role = '$user_role'";
    }
    
    $query .= " ORDER BY lh.login_time DESC";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        die("Query failed: " . mysqli_error($conn));
    }
    
    if ($export_format == 'csv') {
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Write CSV header
        fputcsv($output, ['Login ID', 'User ID', 'Name', 'Email', 'Role', 'Login Time', 'IP Address']);
        
        // Write data rows
        while ($row = mysqli_fetch_assoc($result)) {
            fputcsv($output, $row);
        }
        fclose($output);
    } else { // txt format
        echo "Login History Report (" . $start_date . " to " . $end_date . ")\n";
        echo "=======================================================\n\n";
        echo sprintf("%-8s %-8s %-20s %-30s %-12s %-20s %-15s\n", 
                    "Login ID", "User ID", "Name", "Email", "Role", "Login Time", "IP Address");
        echo str_repeat("-", 120) . "\n";
        
        while ($row = mysqli_fetch_assoc($result)) {
            echo sprintf("%-8s %-8s %-20s %-30s %-12s %-20s %-15s\n", 
                        $row['login_id'], $row['user_id'], substr($row['name'], 0, 18), substr($row['email'], 0, 28), 
                        $row['role'], $row['login_time'], $row['ip_address']);
        }
    }
} else { // users report
    // Build query for users
    $query = "SELECT user_id, name, email, mobile, role, registration_date 
         FROM users";
    
    if ($user_role != 'all') {
        $query .= " WHERE role = '$user_role'";
    }
    
    $query .= " ORDER BY registration_date DESC";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        die("Query failed: " . mysqli_error($conn));
    }
    
    if ($export_format == 'csv') {
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Write CSV header
        fputcsv($output, ['User ID', 'Name', 'Email', 'Mobile', 'Role', 'Registration Date']);
        
        // Write data rows
        while ($row = mysqli_fetch_assoc($result)) {
            fputcsv($output, $row);
        }
        fclose($output);
    } else { // txt format
        echo "Users Report\n";
        echo "=======================================================\n\n";
        echo sprintf("%-8s %-20s %-30s %-15s %-12s %-20s\n", 
                    "User ID", "Name", "Email", "Mobile", "Role", "Registration Date");
        echo str_repeat("-", 120) . "\n";
        
        while ($row = mysqli_fetch_assoc($result)) {
            echo sprintf("%-8s %-20s %-30s %-15s %-12s %-20s\n", 
                        $row['user_id'], substr($row['name'], 0, 18), substr($row['email'], 0, 28), substr($row['mobile'], 0, 13), 
                        $row['role'], $row['registration_date']);
        }
    }
}

// Close database connection
mysqli_close($conn);
exit;
