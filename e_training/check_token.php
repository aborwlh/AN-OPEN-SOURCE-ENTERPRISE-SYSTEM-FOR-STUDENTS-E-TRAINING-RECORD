<?php
include 'config.php';

// This is a simple diagnostic page to check token status
// For security, this should be removed or protected in production

// Get token from URL
$token = isset($_GET['token']) ? $_GET['token'] : '';
$result = [];

if (!empty($token)) {
    // Sanitize the token
    $token = mysqli_real_escape_string($con, $token);
    
    // Check if the token exists
    $token_query = "SELECT * FROM password_reset_tokens WHERE token = '$token'";
    $token_result = mysqli_query($con, $token_query);
    
    if (mysqli_num_rows($token_result) > 0) {
        $token_data = mysqli_fetch_assoc($token_result);
        $result['found'] = true;
        $result['email'] = $token_data['email'];
        $result['created_at'] = $token_data['created_at'];
        $result['expires'] = $token_data['expires'];
        
        // Check if token is expired
        $current_time = date('Y-m-d H:i:s');
        $result['current_time'] = $current_time;
        $result['is_expired'] = ($token_data['expires'] <= $current_time);
        
        // Calculate time difference
        $expires_timestamp = strtotime($token_data['expires']);
        $current_timestamp = strtotime($current_time);
        $time_diff = $expires_timestamp - $current_timestamp;
        
        if ($time_diff > 0) {
            $hours = floor($time_diff / 3600);
            $minutes = floor(($time_diff % 3600) / 60);
            $seconds = $time_diff % 60;
            $result['time_remaining'] = "$hours hours, $minutes minutes, $seconds seconds";
        } else {
            $time_diff = abs($time_diff);
            $hours = floor($time_diff / 3600);
            $minutes = floor(($time_diff % 3600) / 60);
            $seconds = $time_diff % 60;
            $result['time_expired'] = "$hours hours, $minutes minutes, $seconds seconds ago";
        }
    } else {
        $result['found'] = false;
        $result['message'] = "Token not found in database.";
    }
} else {
    $result['error'] = "No token provided.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Token Status Check</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
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
        .result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
        }
        .success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .info {
            background-color: #e2f3f8;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"] {
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
            text-decoration: none;
        }
        .btn:hover {
            background-color: #034f7d;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Password Reset Token Status</h1>
        
        <form method="get" action="">
            <div class="form-group">
                <label for="token">Enter Token:</label>
                <input type="text" id="token" name="token" value="<?php echo htmlspecialchars($token); ?>" required>
            </div>
            
            <button type="submit" class="btn">Check Token</button>
        </form>
        
        <?php if (!empty($result)): ?>
            <div class="result <?php echo isset($result['found']) && $result['found'] ? ($result['is_expired'] ? 'error' : 'success') : 'error'; ?>">
                <?php if (isset($result['error'])): ?>
                    <p><?php echo htmlspecialchars($result['error']); ?></p>
                <?php elseif (isset($result['found']) && !$result['found']): ?>
                    <p><?php echo htmlspecialchars($result['message']); ?></p>
                <?php else: ?>
                    <h3>Token Information</h3>
                    <table>
                        <tr>
                            <th>Email:</th>
                            <td><?php echo htmlspecialchars($result['email']); ?></td>
                        </tr>
                        <tr>
                            <th>Created:</th>
                            <td><?php echo htmlspecialchars($result['created_at']); ?></td>
                        </tr>
                        <tr>
                            <th>Expires:</th>
                            <td><?php echo htmlspecialchars($result['expires']); ?></td>
                        </tr>
                        <tr>
                            <th>Current Time:</th>
                            <td><?php echo htmlspecialchars($result['current_time']); ?></td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <?php if ($result['is_expired']): ?>
                                    <span style="color: #dc3545; font-weight: bold;">EXPIRED</span>
                                    (<?php echo htmlspecialchars($result['time_expired']); ?>)
                                <?php else: ?>
                                    <span style="color: #28a745; font-weight: bold;">VALID</span>
                                    (<?php echo htmlspecialchars($result['time_remaining']); ?> remaining)
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                    
                    <p style="margin-top: 20px;">
                        <a href="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" class="btn">Go to Reset Password Page</a>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <p style="text-align: center; margin-top: 20px;">
            <a href="forget_password.php">Back to Forget Password</a>
        </p>
    </div>
</body>
</html>
