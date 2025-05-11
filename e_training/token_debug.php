<?php
// Token debugging tool
session_start();

// Only allow access to this page if logged in as admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != "admin") {
    echo "<div style='text-align:center; margin-top:50px;'>";
    echo "<h2>Access Denied</h2>";
    echo "<p>You must be logged in as an administrator to access this page.</p>";
    echo "<p><a href='login.php'>Login</a></p>";
    echo "</div>";
    exit;
}

include 'config.php';

// Process token check if submitted
$token_info = [];
$token_valid = false;

if (isset($_POST['check_token'])) {
    $token = mysqli_real_escape_string($con, $_POST['token']);
    
    // Get token information
    $token_query = "SELECT * FROM password_reset_tokens WHERE token = '$token'";
    $token_result = mysqli_query($con, $token_query);
    
    if (mysqli_num_rows($token_result) > 0) {
        $token_info = mysqli_fetch_assoc($token_result);
        
        // Check if token is expired
        $current_time = date('Y-m-d H:i:s');
        $token_valid = ($token_info['expires'] > $current_time);
        
        // Calculate time difference
        $expires_timestamp = strtotime($token_info['expires']);
        $current_timestamp = strtotime($current_time);
        $time_diff = $expires_timestamp - $current_timestamp;
        
        $token_info['time_diff_seconds'] = $time_diff;
        $token_info['time_diff_formatted'] = formatTimeDiff($time_diff);
        $token_info['current_time'] = $current_time;
        $token_info['is_valid'] = $token_valid;
    }
}

// Process token creation if submitted
$new_token = '';
$new_token_link = '';

if (isset($_POST['create_token'])) {
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $hours = (int)$_POST['hours'];
    
    // Check if email exists
    $check_query = "SELECT * FROM users WHERE email = '$email'";
    $check_result = mysqli_query($con, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        // Generate a unique reset token
        $new_token = bin2hex(random_bytes(16));
        
        // Set expiration time
        $expires = date('Y-m-d H:i:s', strtotime("+$hours hours"));
        
        // Delete any existing tokens for this email
        $delete_query = "DELETE FROM password_reset_tokens WHERE email = '$email'";
        mysqli_query($con, $delete_query);
        
        // Insert the new token
        $insert_query = "INSERT INTO password_reset_tokens (email, token, expires) VALUES ('$email', '$new_token', '$expires')";
        $insert_result = mysqli_query($con, $insert_query);
        
        if ($insert_result) {
            $new_token_link = "http://" . $_SERVER['HTTP_HOST'] . "/e_training/reset_password.php?token=" . $new_token;
        }
    }
}

// Process token deletion if submitted
if (isset($_POST['delete_token'])) {
    $token_id = (int)$_POST['token_id'];
    
    $delete_query = "DELETE FROM password_reset_tokens WHERE id = $token_id";
    mysqli_query($con, $delete_query);
}

// Get all tokens
$all_tokens_query = "SELECT * FROM password_reset_tokens ORDER BY expires DESC";
$all_tokens_result = mysqli_query($con, $all_tokens_query);
$all_tokens = [];

while ($row = mysqli_fetch_assoc($all_tokens_result)) {
    // Check if token is expired
    $current_time = date('Y-m-d H:i:s');
    $row['is_valid'] = ($row['expires'] > $current_time);
    
    // Calculate time difference
    $expires_timestamp = strtotime($row['expires']);
    $current_timestamp = strtotime($current_time);
    $time_diff = $expires_timestamp - $current_timestamp;
    
    $row['time_diff_seconds'] = $time_diff;
    $row['time_diff_formatted'] = formatTimeDiff($time_diff);
    
    $all_tokens[] = $row;
}

// Helper function to format time difference
function formatTimeDiff($seconds) {
    if ($seconds < 0) {
        return "Expired " . formatTimeDiff(abs($seconds)) . " ago";
    }
    
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    
    $result = "";
    if ($hours > 0) {
        $result .= "$hours hour" . ($hours > 1 ? "s" : "") . " ";
    }
    if ($minutes > 0) {
        $result .= "$minutes minute" . ($minutes > 1 ? "s" : "") . " ";
    }
    if ($secs > 0 || ($hours == 0 && $minutes == 0)) {
        $result .= "$secs second" . ($secs != 1 ? "s" : "");
    }
    
    return trim($result);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Token Debugging Tool</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #04639b;
            text-align: center;
        }
        .section {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        input[type="number"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn {
            display: inline-block;
            background-color: #04639b;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background-color: #034f7d;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .token-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
            border: 1px solid #ddd;
        }
        .token-info h3 {
            margin-top: 0;
            color: #04639b;
        }
        .token-info p {
            margin: 5px 0;
        }
        .valid {
            color: #28a745;
            font-weight: bold;
        }
        .invalid {
            color: #dc3545;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .token-link {
            word-break: break-all;
        }
        .copy-btn {
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
            font-size: 12px;
            margin-left: 5px;
        }
        .copy-btn:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Token Debugging Tool</h1>
        
        <div class="section">
            <h2>Check Token Validity</h2>
            <form method="post" action="">
                <div class="form-group">
                    <label for="token">Token:</label>
                    <input type="text" id="token" name="token" required>
                </div>
                
                <button type="submit" name="check_token" class="btn">Check Token</button>
            </form>
            
            <?php if (!empty($token_info)): ?>
                <div class="token-info">
                    <h3>Token Information</h3>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($token_info['email']); ?></p>
                    <p><strong>Created:</strong> <?php echo htmlspecialchars($token_info['created_at']); ?></p>
                    <p><strong>Expires:</strong> <?php echo htmlspecialchars($token_info['expires']); ?></p>
                    <p><strong>Current Time:</strong> <?php echo htmlspecialchars($token_info['current_time']); ?></p>
                    <p><strong>Time Remaining:</strong> <?php echo htmlspecialchars($token_info['time_diff_formatted']); ?></p>
                    <p><strong>Status:</strong> 
                        <span class="<?php echo $token_info['is_valid'] ? 'valid' : 'invalid'; ?>">
                            <?php echo $token_info['is_valid'] ? 'Valid' : 'Expired'; ?>
                        </span>
                    </p>
                    <p><strong>Reset Link:</strong> 
                        <span class="token-link">
                            <?php 
                            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/e_training/reset_password.php?token=" . $token;
                            echo htmlspecialchars($reset_link); 
                            ?>
                            <button class="copy-btn" onclick="copyToClipboard('<?php echo $reset_link; ?>')">Copy</button>
                        </span>
                    </p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h2>Create New Token</h2>
            <form method="post" action="">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="hours">Expiration (hours):</label>
                    <input type="number" id="hours" name="hours" value="1" min="1" max="72" required>
                </div>
                
                <button type="submit" name="create_token" class="btn">Create Token</button>
            </form>
            
            <?php if (!empty($new_token)): ?>
                <div class="token-info">
                    <h3>New Token Created</h3>
                    <p><strong>Token:</strong> <?php echo htmlspecialchars($new_token); ?></p>
                    <p><strong>Reset Link:</strong> 
                        <span class="token-link">
                            <?php echo htmlspecialchars($new_token_link); ?>
                            <button class="copy-btn" onclick="copyToClipboard('<?php echo $new_token_link; ?>')">Copy</button>
                        </span>
                    </p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h2>All Tokens</h2>
            
            <?php if (count($all_tokens) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Token</th>
                            <th>Created</th>
                            <th>Expires</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_tokens as $token): ?>
                            <tr>
                                <td><?php echo $token['id']; ?></td>
                                <td><?php echo htmlspecialchars($token['email']); ?></td>
                                <td><?php echo substr(htmlspecialchars($token['token']), 0, 10) . '...'; ?></td>
                                <td><?php echo htmlspecialchars($token['created_at']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($token['expires']); ?>
                                    <br>
                                    <small><?php echo htmlspecialchars($token['time_diff_formatted']); ?></small>
                                </td>
                                <td>
                                    <span class="<?php echo $token['is_valid'] ? 'valid' : 'invalid'; ?>">
                                        <?php echo $token['is_valid'] ? 'Valid' : 'Expired'; ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="post" action="" style="display: inline;">
                                        <input type="hidden" name="token_id" value="<?php echo $token['id']; ?>">
                                        <button type="submit" name="delete_token" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this token?')">Delete</button>
                                    </form>
                                    
                                    <form method="post" action="" style="display: inline;">
                                        <input type="hidden" name="token" value="<?php echo $token['token']; ?>">
                                        <button type="submit" name="check_token" class="btn">Check</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No tokens found in the database.</p>
            <?php endif; ?>
        </div>
        
        <p style="text-align: center; margin-top: 20px;">
            <a href="admin-dashboard.php">Back to Dashboard</a>
        </p>
    </div>
    
    <script>
        function copyToClipboard(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            alert('Copied to clipboard!');
        }
    </script>
</body>
</html>
