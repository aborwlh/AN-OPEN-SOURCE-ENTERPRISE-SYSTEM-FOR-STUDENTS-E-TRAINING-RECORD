<?php include 'config.php'; ?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language'] ?? 'en'; ?>" <?php echo $dirAttribute; ?>>
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo translate('Forget Password'); ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- Add Google reCAPTCHA API script -->
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  <style>
    :root {
      --primary-color: #04639b;
      --primary-dark: #035483;
      --primary-light: #e6f1f8;
      --secondary-color: #ff7e00;
      --text-dark: #333;
      --text-light: #666;
      --white: #ffffff;
      --light-bg: #f9f9f9;
      --border-color: #eaeaea;
      --error-color: #e74c3c;
      --success-color: #2ecc71;
      --shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
      --transition: all 0.3s ease;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Poppins', sans-serif;
      line-height: 1.6;
      color: var(--text-dark);
      background-color: var(--light-bg);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .auth-container {
      width: 100%;
      max-width: 450px;
      background-color: var(--white);
      border-radius: 12px;
      box-shadow: var(--shadow);
      padding: 40px;
      text-align: center;
      animation: fadeIn 0.5s ease;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .auth-logo {
      width: 120px;
      margin-bottom: 20px;
      transition: var(--transition);
    }

    .auth-logo:hover {
      transform: scale(1.05);
    }

    h1 {
      font-size: 2rem;
      color: var(--primary-color);
      margin-bottom: 25px;
      font-weight: 600;
    }

    .error-message {
      background-color: rgba(231, 76, 60, 0.1);
      color: var(--error-color);
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-size: 0.9rem;
      border-left: 4px solid var(--error-color);
      text-align: left;
      animation: shake 0.5s ease;
    }

    .success-message {
      background-color: rgba(46, 204, 113, 0.1);
      color: var(--success-color);
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-size: 0.9rem;
      border-left: 4px solid var(--success-color);
      text-align: left;
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
      20%, 40%, 60%, 80% { transform: translateX(5px); }
    }

    .auth-form {
      text-align: left;
    }

    .form-group {
      margin-bottom: 20px;
      position: relative;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: var(--text-dark);
      font-size: 0.95rem;
    }

    .form-group input {
      width: 100%;
      padding: 14px 16px;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      font-size: 1rem;
      font-family: inherit;
      color: var(--text-dark);
      transition: var(--transition);
      background-color: var(--light-bg);
    }

    .form-group input:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(4, 99, 155, 0.1);
      background-color: var(--white);
    }

    .form-group input::placeholder {
      color: #aaa;
    }

    .g-recaptcha {
      margin: 0 auto;
      display: flex;
      justify-content: center;
      margin-bottom: 25px;
      transform: scale(0.95);
      transform-origin: center;
    }

    .btn-primary {
      width: 100%;
      padding: 14px;
      background-color: var(--primary-color);
      color: var(--white);
      border: none;
      border-radius: 8px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .btn-primary:hover {
      background-color: var(--primary-dark);
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(4, 99, 155, 0.2);
    }

    .btn-primary:active {
      transform: translateY(0);
    }

    .auth-links {
      margin-top: 25px;
      font-size: 0.9rem;
      color: var(--text-light);
    }

    .auth-links a {
      color: var(--primary-color);
      text-decoration: none;
      font-weight: 500;
      transition: var(--transition);
    }

    .auth-links a:hover {
      color: var(--primary-dark);
      text-decoration: underline;
    }

    .back-to-home {
      position: absolute;
      top: 20px;
      left: 20px;
      color: var(--primary-color);
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 5px;
      font-weight: 500;
      transition: var(--transition);
    }

    .back-to-home:hover {
      color: var(--primary-dark);
    }

    /* Language switcher styles */
    .language-switcher {
      position: absolute;
      top: 20px;
      right: 20px;
      display: flex;
      gap: 10px;
    }

    .lang-btn {
      padding: 8px 15px;
      border-radius: 50px;
      background-color: var(--white);
      color: var(--primary-color);
      border: 1px solid var(--primary-color);
      font-weight: 500;
      font-size: 0.9rem;
      cursor: pointer;
      transition: var(--transition);
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .lang-btn.active {
      background-color: var(--primary-color);
      color: var(--white);
    }

    .lang-btn:hover:not(.active) {
      background-color: var(--primary-light);
    }

    /* RTL specific styles */
    html[dir="rtl"] .error-message,
    html[dir="rtl"] .success-message {
      border-left: none;
      border-right: 4px solid var(--error-color);
      text-align: right;
    }

    html[dir="rtl"] .success-message {
      border-right-color: var(--success-color);
    }

    html[dir="rtl"] .back-to-home {
      left: auto;
      right: 20px;
    }

    html[dir="rtl"] .language-switcher {
      right: auto;
      left: 20px;
    }

    /* Responsive adjustments */
    @media (max-width: 576px) {
      .auth-container {
        padding: 30px 20px;
        max-width: 100%;
      }

      h1 {
        font-size: 1.8rem;
      }

      .g-recaptcha {
        transform: scale(0.85);
        transform-origin: left;
      }
      
      .language-switcher {
        top: 70px;
        right: 50%;
        transform: translateX(50%);
      }
      
      .back-to-home {
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
      }
      
      html[dir="rtl"] .g-recaptcha {
        transform-origin: right;
      }
      
      html[dir="rtl"] .language-switcher {
        right: 50%;
        left: auto;
      }
      
      html[dir="rtl"] .back-to-home {
        left: 50%;
        right: auto;
      }
    }
  </style>
</head>
<body>
  <a href="index.php" class="back-to-home">
    <i class="fas fa-arrow-left"></i> <?php echo translate('Back to Home'); ?>
  </a>
  
  <!-- Language Switcher -->
  <div class="language-switcher">
    <a href="?lang=en" class="lang-btn <?php echo ($lang == 'en' || !isset($lang)) ? 'active' : ''; ?>">
      <i class="fas fa-globe"></i> English
    </a>
    <a href="?lang=ar" class="lang-btn <?php echo $lang == 'ar' ? 'active' : ''; ?>">
      <i class="fas fa-globe"></i> العربية
    </a>
  </div>

  <div class="auth-container">
    <img src="assets/images/logo.png" alt="<?php echo translate('E-Training Logo'); ?>" class="auth-logo"/>
    <h1><?php echo translate('Forget Password'); ?></h1>
    
    <?php
    // Display error message if any
    if (isset($_GET['error'])) {
        $error_message = '';
        switch ($_GET['error']) {
            case 'email_not_found':
                $error_message = '<i class="fas fa-exclamation-circle"></i> ' . translate('Email not found in our system.');
                break;
            case 'captcha':
                $error_message = '<i class="fas fa-robot"></i> ' . translate('Please verify that you are not a robot.');
                break;
            case 'empty':
                $error_message = '<i class="fas fa-exclamation-triangle"></i> ' . translate('Please enter your email address.');
                break;
            case 'email_send':
                $error_message = '<i class="fas fa-envelope-times"></i> ' . translate('Failed to send password reset email. Please try again later.');
                break;
            default:
                $error_message = '<i class="fas fa-times-circle"></i> ' . translate('An error occurred. Please try again.');
        }
        echo '<div class="error-message">' . $error_message . '</div>';
    }
    
    // Display success message
    if (isset($_GET['success'])) {
        echo '<div class="success-message"><i class="fas fa-check-circle"></i> ' . translate('Password reset instructions have been sent to your email address.') . '</div>';
    }
    ?>

    <form class="auth-form" action="forget_password_check.php" method="post">
      <div class="form-group">
        <label for="email"><?php echo translate('Email'); ?></label>
        <input type="email" id="email" name="email" placeholder="<?php echo translate('Enter your email'); ?>" required />
      </div>
      
      <!-- Add Google reCAPTCHA widget -->
      <div class="form-group">
        <div class="g-recaptcha" data-sitekey="6Lfh0xQrAAAAAN3C-2FSXNNgss80pTyMwLeBjd1H"></div>
      </div>
      
      <button type="submit" class="btn-primary" name="btn-submit">
        <i class="fas fa-key"></i> <?php echo translate('Reset Password'); ?>
      </button>
    </form>

    <p class="auth-links">
      <a href="login.php"><i class="fas fa-sign-in-alt"></i> <?php echo translate('Login'); ?></a> |
      <a href="student_register.php"><i class="fas fa-user-plus"></i> <?php echo translate('Register New Account'); ?></a>
    </p>
  </div>

  <script>
    // Language switcher - Set cookie and reload page
    document.querySelectorAll('.lang-btn').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        const lang = this.getAttribute('href').split('=')[1];
        document.cookie = `lang=${lang}; path=/; max-age=${60*60*24*30}`; // 30 days
        window.location.reload();
      });
    });
  </script>
</body>
</html>
