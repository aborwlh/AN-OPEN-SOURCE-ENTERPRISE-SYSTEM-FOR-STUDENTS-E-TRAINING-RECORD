<?php include 'config.php'; ?>
<?php $page_title = translate("Admin Reports"); ?>
<?php include 'header.php'; ?>

<?php
// if not logged in as admin; redirect to the login page
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

// Default values
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'login_history';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$user_role = isset($_GET['user_role']) ? $_GET['user_role'] : 'all';

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Prepare data for display
$data = [];
$columns = [];
$total_records = 0;

if ($report_type == 'login_history') {
    $columns = [translate('Login ID'), translate('User'), translate('Role'), translate('Login Time'), translate('IP Address')];
    
    // Count total records for pagination
    $count_query = "SELECT COUNT(*) as total 
                   FROM login_history lh
                   JOIN users u ON lh.user_id = u.user_id
                   WHERE lh.login_time BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
    
    if ($user_role != 'all') {
        $count_query .= " AND u.role = '$user_role'";
    }
    
    $count_result = mysqli_query($conn, $count_query);
    $count_row = mysqli_fetch_assoc($count_result);
    $total_records = $count_row['total'];
    
    // Build query for login history with pagination
    $query = "SELECT lh.login_id, lh.user_id, u.name, u.email, u.role, lh.login_time, lh.ip_address 
             FROM login_history lh
             JOIN users u ON lh.user_id = u.user_id
             WHERE lh.login_time BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
    
    if ($user_role != 'all') {
        $query .= " AND u.role = '$user_role'";
    }
    
    $query .= " ORDER BY lh.login_time DESC LIMIT $offset, $records_per_page";
    
} else { // users report
    $columns = [translate('User ID'), translate('Name'), translate('Email'), translate('Mobile'), translate('Role'), translate('Registration Date')];
    
    // Count total records for pagination
    $count_query = "SELECT COUNT(*) as total FROM users";
    
    if ($user_role != 'all') {
        $count_query .= " WHERE role = '$user_role'";
    }
    
    $count_result = mysqli_query($conn, $count_query);
    $count_row = mysqli_fetch_assoc($count_result);
    $total_records = $count_row['total'];
    
    // Build query for users with pagination
    $query = "SELECT user_id, name, email, mobile, role, registration_date 
             FROM users";
    
    if ($user_role != 'all') {
        $query .= " WHERE role = '$user_role'";
    }
    
    $query .= " ORDER BY registration_date DESC LIMIT $offset, $records_per_page";
}

$result = mysqli_query($conn, $query);

// Check if query was successful
if (!$result) {
    echo "<div class='alert alert-danger'>" . translate('Error executing query:') . " " . mysqli_error($conn) . "</div>";
} else {
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
}

// Calculate pagination values
$total_pages = ceil($total_records / $records_per_page);
$prev_page = ($page > 1) ? $page - 1 : 1;
$next_page = ($page < $total_pages) ? $page + 1 : $total_pages;

// Get user roles for filter
$roles_query = "SELECT DISTINCT role FROM users WHERE role != 'admin'";
$roles_result = mysqli_query($conn, $roles_query);
$roles = [];
while ($role = mysqli_fetch_assoc($roles_result)) {
    $roles[] = $role['role'];
}

// Function to generate pagination URL with all current parameters
function getPaginationUrl($page_num) {
    $params = $_GET;
    $params['page'] = $page_num;
    return '?' . http_build_query($params);
}
?>

<style>
.report-container {
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
}

.report-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.report-title {
    font-size: 24px;
    font-weight: bold;
    color: #04639b;
}

.report-section {
    background-color: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.filter-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    margin-bottom: 5px;
    font-weight: bold;
}

.form-control {
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 14px;
}

.form-control:focus {
    outline: none;
    border-color: #04639b;
    box-shadow: 0 0 0 0.2rem rgba(4, 99, 155, 0.25);
}

.btn-group {
    display: flex;
    gap: 10px;
    align-items: flex-end;
}

.btn {
    padding: 8px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: bold;
}

.btn-primary {
    background-color: #04639b;
    color: white;
}

.btn-primary:hover {
    background-color: #034f7d;
}

.btn-success {
    background-color: #28a745;
    color: white;
}

.btn-success:hover {
    background-color: #218838;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background-color: #5a6268;
}

.report-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.report-table th {
    background-color: #f8f9fa;
    padding: 12px;
    text-align: left;
    font-weight: bold;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
}

.report-table td {
    padding: 12px;
    border-bottom: 1px solid #dee2e6;
}

.report-table tr:hover {
    background-color: #f8f9fa;
}

.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
}

.badge-primary {
    background-color: #cfe2ff;
    color: #084298;
}

.badge-success {
    background-color: #d1e7dd;
    color: #0f5132;
}

.badge-warning {
    background-color: #fff3cd;
    color: #664d03;
}

.badge-info {
    background-color: #cff4fc;
    color: #055160;
}

.report-summary {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.report-summary h3 {
    margin-top: 0;
    margin-bottom: 10px;
    font-size: 18px;
    color: #04639b;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.summary-item {
    background-color: white;
    padding: 15px;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.summary-item h4 {
    margin-top: 0;
    margin-bottom: 5px;
    font-size: 14px;
    color: #6c757d;
}

.summary-item p {
    margin: 0;
    font-size: 20px;
    font-weight: bold;
    color: #04639b;
}

.export-options {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
}

.no-data {
    text-align: center;
    padding: 30px;
    color: #6c757d;
}

.pagination {
    display: flex;
    justify-content: center;
    margin-top: 20px;
    gap: 5px;
}

.pagination a {
    padding: 8px 12px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    color: #04639b;
    text-decoration: none;
}

.pagination a:hover {
    background-color: #e9ecef;
}

.pagination a.active {
    background-color: #04639b;
    color: white;
    border-color: #04639b;
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid transparent;
    border-radius: 4px;
}

.alert-danger {
    color: #721c24;
    background-color: #f8d7da;
    border-color: #f5c6cb;
}

.alert-success {
    color: #155724;
    background-color: #d4edda;
    border-color: #c3e6cb;
}

.pagination-info {
    text-align: center;
    margin-top: 10px;
    color: #6c757d;
    font-size: 14px;
}
</style>

<div class="report-container">
    <div class="report-header">
        <h1 class="report-title"><?php echo translate('Admin Reports'); ?></h1>
        <a href="admin_dashboard.php" class="btn btn-secondary"><?php echo translate('Back to Dashboard'); ?></a>
    </div>

    <div class="report-section">
        <h2><?php echo translate('Generate Report'); ?></h2>
        
        <!-- Fixed form with method="GET" and explicit action -->
        <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="filter-form">
                <div class="form-group">
                    <label for="report_type"><?php echo translate('Report Type'); ?></label>
                    <select name="report_type" id="report_type" class="form-control">
                        <option value="login_history" <?php echo $report_type == 'login_history' ? 'selected' : ''; ?>><?php echo translate('Login History'); ?></option>
                        <option value="users" <?php echo $report_type == 'users' ? 'selected' : ''; ?>><?php echo translate('Users'); ?></option>
                    </select>
                </div>
                
                <div class="form-group" id="date_range_container" <?php echo $report_type == 'users' ? 'style="display:none;"' : ''; ?>>
                    <label for="start_date"><?php echo translate('Start Date'); ?></label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo $start_date; ?>">
                </div>
                
                <div class="form-group" id="end_date_container" <?php echo $report_type == 'users' ? 'style="display:none;"' : ''; ?>>
                    <label for="end_date"><?php echo translate('End Date'); ?></label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo $end_date; ?>">
                </div>
                
                <div class="form-group">
                    <label for="user_role"><?php echo translate('User Role'); ?></label>
                    <select name="user_role" id="user_role" class="form-control">
                        <option value="all" <?php echo $user_role == 'all' ? 'selected' : ''; ?>><?php echo translate('All Roles'); ?></option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role; ?>" <?php echo $user_role == $role ? 'selected' : ''; ?>>
                                <?php echo ucfirst(translate($role)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary"><?php echo translate('Generate Report'); ?></button>
                </div>
            </div>
            <!-- Hidden field to maintain page number when changing filters -->
            <input type="hidden" name="page" value="1">
        </form>
    </div>

    <?php if (!empty($data)): ?>
        <div class="report-section">
            <h2><?php echo $report_type == 'login_history' ? translate('Login History Report') : translate('Users Report'); ?></h2>
            
            <div class="report-summary">
                <h3><?php echo translate('Summary'); ?></h3>
                <div class="summary-grid">
                    <div class="summary-item">
                        <h4><?php echo translate('Total Records'); ?></h4>
                        <p><?php echo $total_records; ?></p>
                    </div>
                    
                    <?php if ($report_type == 'login_history'): ?>
                        <div class="summary-item">
                            <h4><?php echo translate('Date Range'); ?></h4>
                            <p><?php echo date('M d, Y', strtotime($start_date)) . ' - ' . date('M d, Y', strtotime($end_date)); ?></p>
                        </div>
                        
                        <?php
                        // Count unique users
                        $unique_users = [];
                        foreach ($data as $row) {
                            $unique_users[$row['user_id']] = true;
                        }
                        $unique_user_count = count($unique_users);
                        ?>
                        
                        <div class="summary-item">
                            <h4><?php echo translate('Unique Users'); ?></h4>
                            <p><?php echo $unique_user_count; ?></p>
                        </div>
                    <?php else: ?>
                        <?php
                        // Count by role
                        $role_counts = [];
                        foreach ($data as $row) {
                            if (!isset($role_counts[$row['role']])) {
                                $role_counts[$row['role']] = 0;
                            }
                            $role_counts[$row['role']]++;
                        }
                        
                        foreach ($role_counts as $role => $count):
                        ?>
                            <div class="summary-item">
                                <h4><?php echo ucfirst(translate($role)); ?>s</h4>
                                <p><?php echo $count; ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="export-options">
                <!-- Direct links to the export script with explicit paths -->
                <a href="export_report.php?report_type=<?php echo urlencode($report_type); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&user_role=<?php echo urlencode($user_role); ?>&export_format=csv" class="btn btn-success">
                    <?php echo translate('Export as CSV'); ?>
                </a>
                <a href="export_report.php?report_type=<?php echo urlencode($report_type); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&user_role=<?php echo urlencode($user_role); ?>&export_format=txt" class="btn btn-secondary">
                    <?php echo translate('Export as TXT'); ?>
                </a>
            </div>
            
            <table class="report-table">
                <thead>
                    <tr>
                        <?php if ($report_type == 'login_history'): ?>
                            <th><?php echo translate('Login ID'); ?></th>
                            <th><?php echo translate('User'); ?></th>
                            <th><?php echo translate('Role'); ?></th>
                            <th><?php echo translate('Login Time'); ?></th>
                            <th><?php echo translate('IP Address'); ?></th>
                        <?php else: ?>
                            <th><?php echo translate('User ID'); ?></th>
                            <th><?php echo translate('Name'); ?></th>
                            <th><?php echo translate('Email'); ?></th>
                            <th><?php echo translate('Mobile'); ?></th>
                            <th><?php echo translate('Role'); ?></th>
                            <th><?php echo translate('Registration Date'); ?></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($report_type == 'login_history'): ?>
                        <?php foreach ($data as $row): ?>
                            <tr>
                                <td><?php echo $row['login_id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($row['name']); ?>
                                    <div style="font-size: 12px; color: #6c757d;"><?php echo htmlspecialchars($row['email']); ?></div>
                                </td>
                                <td>
                                    <span class="badge <?php echo $row['role'] == 'student' ? 'badge-primary' : 'badge-info'; ?>">
                                        <?php echo ucfirst(htmlspecialchars(translate($row['role']))); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y h:i A', strtotime($row['login_time'])); ?></td>
                                <td><?php echo htmlspecialchars($row['ip_address']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php foreach ($data as $row): ?>
                            <tr>
                                <td><?php echo $row['user_id']; ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['mobile']); ?></td>
                                <td>
                                    <span class="badge <?php echo $row['role'] == 'student' ? 'badge-primary' : 'badge-info'; ?>">
                                        <?php echo ucfirst(htmlspecialchars(translate($row['role']))); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($row['registration_date'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- Pagination information -->
            <div class="pagination-info">
                <?php echo translate('Showing'); ?> <?php echo min(($page - 1) * $records_per_page + 1, $total_records); ?> <?php echo translate('to'); ?> 
                <?php echo min($page * $records_per_page, $total_records); ?> <?php echo translate('of'); ?> 
                <?php echo $total_records; ?> <?php echo translate('records'); ?>
            </div>
            
            <!-- Pagination controls with working links -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <a href="<?php echo getPaginationUrl(1); ?>">&laquo;</a>
                    
                    <?php
                    // Determine the range of page numbers to display
                    $range = 2; // Number of pages to show before and after current page
                    $start_page = max(1, $page - $range);
                    $end_page = min($total_pages, $page + $range);
                    
                    // Always show first page
                    if ($start_page > 1) {
                        echo '<a href="' . getPaginationUrl(1) . '">1</a>';
                        if ($start_page > 2) {
                            echo '<span>...</span>';
                        }
                    }
                    
                    // Display page numbers
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        $active_class = ($i == $page) ? 'active' : '';
                        echo '<a href="' . getPaginationUrl($i) . '" class="' . $active_class . '">' . $i . '</a>';
                    }
                    
                    // Always show last page
                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) {
                            echo '<span>...</span>';
                        }
                        echo '<a href="' . getPaginationUrl($total_pages) . '">' . $total_pages . '</a>';
                    }
                    ?>
                    
                    <a href="<?php echo getPaginationUrl($total_pages); ?>">&raquo;</a>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="report-section">
            <div class="no-data">
                <h3><?php echo translate('No data available for the selected criteria'); ?></h3>
                <p><?php echo translate('Try changing your filters or selecting a different report type.'); ?></p>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Toggle date range fields based on report type
document.getElementById('report_type').addEventListener('change', function() {
    const dateRangeContainer = document.getElementById('date_range_container');
    const endDateContainer = document.getElementById('end_date_container');
    
    if (this.value === 'login_history') {
        dateRangeContainer.style.display = 'block';
        endDateContainer.style.display = 'block';
    } else {
        dateRangeContainer.style.display = 'none';
        endDateContainer.style.display = 'none';
    }
});

// Set min/max dates for date inputs
document.addEventListener('DOMContentLoaded', function() {
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    
    // Set max date to today for both inputs
    const today = new Date().toISOString().split('T')[0];
    startDateInput.setAttribute('max', today);
    endDateInput.setAttribute('max', today);
    
    // Update min date of end date based on start date
    startDateInput.addEventListener('change', function() {
        endDateInput.setAttribute('min', this.value);
        
        // If end date is before start date, update it
        if (endDateInput.value < this.value) {
            endDateInput.value = this.value;
        }
    });
    
    // Update max date of start date based on end date
    endDateInput.addEventListener('change', function() {
        startDateInput.setAttribute('max', this.value);
        
        // If start date is after end date, update it
        if (startDateInput.value > this.value) {
            startDateInput.value = this.value;
        }
    });
});
</script>

<?php include 'footer.php'; ?>
