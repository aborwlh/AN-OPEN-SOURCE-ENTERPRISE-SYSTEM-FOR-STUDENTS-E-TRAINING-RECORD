<?php include 'config.php'; ?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language'] ?? 'en'; ?>" <?php echo $dirAttribute; ?>>
<head>
 <meta charset="UTF-8"/>
 <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
 <title><?php echo translate('Reset Password'); ?></title>
 <link rel="stylesheet" href="assets/style/styles.css"/>
 <style>
   .password-requirements {
     display: block;
     font-size: 12px;
     color: #666;
     margin-top: 5px;
   }
   
   .password-strength-meter {
     height: 5px;
     width: 0;
     background-color: #dc3545;
     margin-top: 5px;
     transition: width 0.3s, background-color 0.3s;
   }
 </style>
</head>
<body>

 <div class="auth-container">
   <img src="assets/images/logo.png" alt="<?php echo translate('System Logo'); ?>" class="auth-logo"/>
   <h1><?php echo translate('Reset Password'); ?></h1>
   
   <?php
  // Check if success parameter is set first
  if (isset($_GET['success'])) {
      echo '<div class="success-message">' . translate('Your password has been reset successfully. You can now') . ' <a href="login.php">' . translate('login') . '</a> ' . translate('with your new password.') . '</div>';
  } 
  // Only check token if success is not set
  else {
      // Check if token is valid
      $token_valid = false;
      $email = '';
      $debug_info = '';
      
      if (isset($_GET['token'])) {
          $token = mysqli_real_escape_string($con, $_GET['token']);
          
          // Check if the token exists and is not expired
          $token_query = "SELECT * FROM password_reset_tokens WHERE token = '$token'";
          $token_result = mysqli_query($con, $token_query);
          
          if (mysqli_num_rows($token_result) > 0) {
              $token_data = mysqli_fetch_assoc($token_result);
              $email = $token_data['email'];
              
              // Debug information
              $debug_info .= translate('Token found in database.') . "<br>";
              $debug_info .= translate('Token expires:') . " " . $token_data['expires'] . "<br>";
              $debug_info .= translate('Current time:') . " " . date('Y-m-d H:i:s') . "<br>";
              
              // Check if token is expired
              $current_time = date('Y-m-d H:i:s');
              if ($token_data['expires'] > $current_time) {
                  $token_valid = true;
                  $debug_info .= translate('Token is valid and not expired.') . "<br>";
              } else {
                  $debug_info .= translate('Token has expired.') . "<br>";
              }
          } else {
              $debug_info .= translate('Token not found in database.') . "<br>";
          }
      } else {
          $debug_info .= translate('No token provided in URL.') . "<br>";
      }
      
      // Display error message if any
      if (isset($_GET['error'])) {
          $error_message = '';
          switch ($_GET['error']) {
              case 'password':
                  $error_message = translate('Password must be at least 8 characters and include a capital letter, a number, and a special character.');
                  break;
              case 'match':
                  $error_message = translate('Passwords do not match.');
                  break;
              case 'empty':
                  $error_message = translate('Please fill in all fields.');
                  break;
              case 'db_error':
                  $error_message = translate('Database error occurred. Please try again.');
                  break;
              default:
                  $error_message = translate('An error occurred. Please try again.');
          }
          echo '<div class="error-message">' . $error_message . '</div>';
      }
      
      if ($token_valid) {
          // Show the password reset form
          ?>
          <form class="auth-form" action="reset_password_check.php" method="post">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
            
            <div class="form-group">
              <label for="password"><?php echo translate('New Password'); ?></label>
              <input type="password" id="password" name="password" placeholder="<?php echo translate('Enter new password'); ?>" required />
              <small class="password-requirements">
                <?php echo translate('Password must be at least 8 characters and include a capital letter, a number, and a special character.'); ?>
              </small>
              <div id="password-strength" class="password-strength-meter"></div>
            </div>
            
            <div class="form-group">
              <label for="confirm_password"><?php echo translate('Confirm Password'); ?></label>
              <input type="password" id="confirm_password" name="confirm_password" placeholder="<?php echo translate('Confirm new password'); ?>" required />
            </div>
            
            <button type="submit" class="btn-primary" name="btn-submit"><?php echo translate('Reset Password'); ?></button>
          </form>
          <?php
      } else {
          // Show error message for invalid token
          ?>
          <div class="error-message">
            <?php echo translate('Invalid or expired password reset link. Please request a new password reset link from the'); ?> <a href="forget_password.php"><?php echo translate('forget password'); ?></a> <?php echo translate('page.'); ?>
          </div>
          <?php
          // Only show debug info if we're in development mode
          $show_debug = false; // Set to true for debugging
          if ($show_debug) {
              echo '<div style="margin-top: 20px; padding: 10px; background-color: #f8f9fa; border: 1px solid #ddd; font-family: monospace; font-size: 12px;">';
              echo '<h3>' . translate('Debug Information:') . '</h3>';
              echo $debug_info;
              echo '</div>';
          }
      }
  }
  ?>

  <p class="auth-links">
    <a href="login.php"><?php echo translate('Back to Login'); ?></a>
  </p>
</div>

 <script>
   // Password validation
   const passwordInput = document.getElementById('password');
   const confirmPasswordInput = document.getElementById('confirm_password');
   const passwordStrength = document.getElementById('password-strength');
   const form = document.querySelector('.auth-form');
   
   passwordInput.addEventListener('input', function() {
     const password = this.value;
     let strength = 0;
     let feedback = '';
     
     // Check password length
     if (password.length >= 8) {
       strength += 25;
     } else {
       feedback += '<?php echo translate('Password must be at least 8 characters.'); ?> ';
     }
     
     // Check for uppercase letter
     if (/[A-Z]/.test(password)) {
       strength += 25;
     } else {
       feedback += '<?php echo translate('Add a capital letter.'); ?> ';
     }
     
     // Check for number
     if (/[0-9]/.test(password)) {
       strength += 25;
     } else {
       feedback += '<?php echo translate('Add a number.'); ?> ';
     }
     
     // Check for special character
     if (/[^A-Za-z0-9]/.test(password)) {
       strength += 25;
     } else {
       feedback += '<?php echo translate('Add a special character.'); ?> ';
     }
     
     // Update the strength meter
     passwordStrength.style.width = strength + '%';
     
     // Set the color based on strength
     if (strength < 50) {
       passwordStrength.style.backgroundColor = '#dc3545'; // Red
     } else if (strength < 100) {
       passwordStrength.style.backgroundColor = '#ffc107'; // Yellow
     } else {
       passwordStrength.style.backgroundColor = '#28a745'; // Green
     }
     
     // Store the validation result
     passwordStrength.setAttribute('data-valid', strength === 100 ? 'true' : 'false');
     
     // Show feedback
     if (password.length > 0) {
       passwordStrength.setAttribute('title', feedback);
     }
   });
   
   // Form submission validation
   form.addEventListener('submit', function(event) {
     const isPasswordValid = passwordStrength.getAttribute('data-valid') === 'true';
     const password = passwordInput.value;
     const confirmPassword = confirmPasswordInput.value;
     
     if (!isPasswordValid) {
       event.preventDefault();
       alert('<?php echo translate('Please ensure your password meets all requirements: at least 8 characters, including a capital letter, a number, and a special character.'); ?>');
       return;
     }
     
     if (password !== confirmPassword) {
       event.preventDefault();
       alert('<?php echo translate('Passwords do not match.'); ?>');
       return;
     }
   });
 </script>

</body>
</html>
