<?php include 'config.php'; ?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language'] ?? 'en'; ?>" <?php echo $dirAttribute; ?>>
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo translate('E-Training Student Registration'); ?></title>
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
      --warning-color: #f39c12;
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
      padding: 30px 20px;
    }

    .auth-container {
      width: 100%;
      max-width: 500px;
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

    .success-message a {
      color: var(--primary-color);
      font-weight: 600;
      text-decoration: none;
    }

    .success-message a:hover {
      text-decoration: underline;
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

    .password-toggle {
      position: absolute;
      right: 15px;
      top: 45px;
      cursor: pointer;
      color: var(--text-light);
    }

    .password-requirements {
      display: block;
      font-size: 0.8rem;
      color: var(--text-light);
      margin-top: 8px;
      line-height: 1.4;
    }

    .password-strength-container {
      margin-top: 10px;
      background-color: #eee;
      height: 6px;
      border-radius: 3px;
      overflow: hidden;
    }
    
    .password-strength-meter {
      height: 100%;
      width: 0;
      background-color: var(--error-color);
      transition: width 0.3s, background-color 0.3s;
    }

    .password-feedback {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-top: 10px;
    }

    .requirement {
      font-size: 0.75rem;
      padding: 3px 8px;
      border-radius: 50px;
      background-color: #eee;
      color: var(--text-light);
      display: inline-flex;
      align-items: center;
      gap: 4px;
      transition: var(--transition);
    }

    .requirement.valid {
      background-color: rgba(46, 204, 113, 0.15);
      color: var(--success-color);
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

    .btn-primary:disabled {
      background-color: #ccc;
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
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

    .form-steps {
      display: flex;
      justify-content: space-between;
      margin-bottom: 30px;
    }

    .form-step {
      flex: 1;
      text-align: center;
      position: relative;
    }

    .step-number {
      width: 30px;
      height: 30px;
      border-radius: 50%;
      background-color: var(--light-bg);
      color: var(--text-light);
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 8px;
      font-weight: 600;
      font-size: 0.9rem;
      position: relative;
      z-index: 2;
      transition: var(--transition);
    }

    .step-title {
      font-size: 0.8rem;
      color: var(--text-light);
      transition: var(--transition);
    }

    .form-step.active .step-number {
      background-color: var(--primary-color);
      color: var(--white);
    }

    .form-step.active .step-title {
      color: var(--primary-color);
      font-weight: 500;
    }

    .form-step.completed .step-number {
      background-color: var(--success-color);
      color: var(--white);
    }

    .form-step::after {
      content: '';
      position: absolute;
      top: 15px;
      left: 50%;
      width: 100%;
      height: 2px;
      background-color: var(--light-bg);
      z-index: 1;
    }

    .form-step:last-child::after {
      display: none;
    }

    .form-step.active::after, .form-step.completed::after {
      background-color: var(--primary-color);
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
    html[dir="rtl"] .password-toggle {
      right: auto;
      left: 15px;
    }

    html[dir="rtl"] .back-to-home {
      left: auto;
      right: 20px;
    }

    html[dir="rtl"] .language-switcher {
      right: auto;
      left: 20px;
    }

    html[dir="rtl"] .error-message,
    html[dir="rtl"] .success-message {
      border-left: none;
      border-right: 4px solid var(--error-color);
      text-align: right;
    }

    html[dir="rtl"] .success-message {
      border-right-color: var(--success-color);
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

      .password-feedback {
        gap: 5px;
      }

      .requirement {
        font-size: 0.7rem;
        padding: 2px 6px;
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
    <h1><?php echo translate('Create Your Account'); ?></h1>
    
    <?php
    // Display error message if any
    if (isset($_GET['error'])) {
        $error_message = '';
        switch ($_GET['error']) {
            case 'email_exists':
                $error_message = '<i class="fas fa-exclamation-circle"></i> ' . translate('Email already exists. Please use a different email.');
                break;
            case 'captcha':
                $error_message = '<i class="fas fa-robot"></i> ' . translate('Please verify that you are not a robot.');
                break;
            case 'empty':
                $error_message = '<i class="fas fa-exclamation-triangle"></i> ' . translate('Please fill in all fields.');
                break;
            case 'mobile':
                $error_message = '<i class="fas fa-phone-slash"></i> ' . translate('Please enter a valid mobile number.');
                break;
            case 'password':
                $error_message = '<i class="fas fa-lock"></i> ' . translate('Password must be at least 8 characters and include a capital letter, a number, and a special character.');
                break;
            default:
                $error_message = '<i class="fas fa-times-circle"></i> ' . translate('An error occurred. Please try again.');
        }
        echo '<div class="error-message">' . $error_message . '</div>';
    }
    
    // Display success message
    if (isset($_GET['success'])) {
        echo '<div class="success-message"><i class="fas fa-check-circle"></i> ' . translate('Registration successful! You can now') . ' <a href="login.php">' . translate('login') . '</a> ' . translate('to your account.') . '</div>';
    }
    ?>
    
    <div class="form-steps">
      <div class="form-step active">
        <div class="step-number">1</div>
        <div class="step-title"><?php echo translate('Account Info'); ?></div>
      </div>
      <div class="form-step">
        <div class="step-number">2</div>
        <div class="step-title"><?php echo translate('Verification'); ?></div>
      </div>
      <div class="form-step">
        <div class="step-number">3</div>
        <div class="step-title"><?php echo translate('Complete'); ?></div>
      </div>
    </div>
     
    <form class="auth-form" action="student_register_check.php" method="post" id="registrationForm">
      <div class="form-group">
        <label for="name"><?php echo translate('Full Name'); ?></label>
        <input type="text" id="name" name="name" placeholder="<?php echo translate('Enter your full name'); ?>" required />
      </div>

      <div class="form-group">
        <label for="email"><?php echo translate('Email Address'); ?></label>
        <input type="email" id="email" name="email" placeholder="<?php echo translate('you@example.com'); ?>" required />
      </div>

      <div class="form-group">
        <label for="password"><?php echo translate('Password'); ?></label>
        <input type="password" id="password" name="password" placeholder="<?php echo translate('Create a strong password'); ?>" required />
        <span class="password-toggle" id="passwordToggle">
          <i class="fas fa-eye"></i>
        </span>
        <small class="password-requirements">
          <?php echo translate('Your password must contain at least 8 characters, including uppercase, number, and special character.'); ?>
        </small>
        <div class="password-strength-container">
          <div id="password-strength" class="password-strength-meter"></div>
        </div>
        <div class="password-feedback" id="password-feedback">
          <span class="requirement" id="req-length"><i class="fas fa-times"></i> <?php echo translate('8+ characters'); ?></span>
          <span class="requirement" id="req-uppercase"><i class="fas fa-times"></i> <?php echo translate('Uppercase'); ?></span>
          <span class="requirement" id="req-number"><i class="fas fa-times"></i> <?php echo translate('Number'); ?></span>
          <span class="requirement" id="req-special"><i class="fas fa-times"></i> <?php echo translate('Special'); ?></span>
        </div>
      </div>

      <div class="form-group">
        <label for="mobile"><?php echo translate('Mobile Number'); ?></label>
        <input type="tel" id="mobile" name="mobile" placeholder="<?php echo translate('e.g., 0551234567'); ?>" required />
      </div>
      
      <!-- Add Google reCAPTCHA widget -->
      <div class="form-group">
        <div class="g-recaptcha" data-sitekey="6Lfh0xQrAAAAAN3C-2FSXNNgss80pTyMwLeBjd1H"></div>
      </div>

      <button type="submit" class="btn-primary" name="btn-submit" id="submitBtn">
        <i class="fas fa-user-plus"></i> <?php echo translate('Create Account'); ?>
      </button>
    </form>

    <p class="auth-links">
      <?php echo translate('Already have an account?'); ?> <a href="login.php"><i class="fas fa-sign-in-alt"></i> <?php echo translate('Login here'); ?></a>
    </p>
  </div>

  <script>
    // Toggle password visibility
    const passwordToggle = document.getElementById('passwordToggle');
    const passwordInput = document.getElementById('password');
    
    passwordToggle.addEventListener('click', function() {
      const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      passwordInput.setAttribute('type', type);
      
      // Toggle eye icon
      const icon = passwordToggle.querySelector('i');
      icon.classList.toggle('fa-eye');
      icon.classList.toggle('fa-eye-slash');
    });
    
    // Password validation
    const passwordStrength = document.getElementById('password-strength');
    const form = document.getElementById('registrationForm');
    const submitBtn = document.getElementById('submitBtn');
    
    // Requirement elements
    const reqLength = document.getElementById('req-length');
    const reqUppercase = document.getElementById('req-uppercase');
    const reqNumber = document.getElementById('req-number');
    const reqSpecial = document.getElementById('req-special');
    
    passwordInput.addEventListener('input', function() {
      const password = this.value;
      let strength = 0;
      let validRequirements = 0;
      
      // Check password length
      const hasLength = password.length >= 8;
      if (hasLength) {
        strength += 25;
        validRequirements++;
        reqLength.classList.add('valid');
        reqLength.innerHTML = '<i class="fas fa-check"></i> <?php echo translate('8+ characters'); ?>';
      } else {
        reqLength.classList.remove('valid');
        reqLength.innerHTML = '<i class="fas fa-times"></i> <?php echo translate('8+ characters'); ?>';
      }
      
      // Check for uppercase letter
      const hasUppercase = /[A-Z]/.test(password);
      if (hasUppercase) {
        strength += 25;
        validRequirements++;
        reqUppercase.classList.add('valid');
        reqUppercase.innerHTML = '<i class="fas fa-check"></i> <?php echo translate('Uppercase'); ?>';
      } else {
        reqUppercase.classList.remove('valid');
        reqUppercase.innerHTML = '<i class="fas fa-times"></i> <?php echo translate('Uppercase'); ?>';
      }
      
      // Check for number
      const hasNumber = /[0-9]/.test(password);
      if (hasNumber) {
        strength += 25;
        validRequirements++;
        reqNumber.classList.add('valid');
        reqNumber.innerHTML = '<i class="fas fa-check"></i> <?php echo translate('Number'); ?>';
      } else {
        reqNumber.classList.remove('valid');
        reqNumber.innerHTML = '<i class="fas fa-times"></i> <?php echo translate('Number'); ?>';
      }
      
      // Check for special character
      const hasSpecial = /[^A-Za-z0-9]/.test(password);
      if (hasSpecial) {
        strength += 25;
        validRequirements++;
        reqSpecial.classList.add('valid');
        reqSpecial.innerHTML = '<i class="fas fa-check"></i> <?php echo translate('Special'); ?>';
      } else {
        reqSpecial.classList.remove('valid');
        reqSpecial.innerHTML = '<i class="fas fa-times"></i> <?php echo translate('Special'); ?>';
      }
      
      // Update the strength meter
      passwordStrength.style.width = strength + '%';
      
      // Set the color based on strength
      if (strength < 50) {
        passwordStrength.style.backgroundColor = '#e74c3c'; // Red
      } else if (strength < 100) {
        passwordStrength.style.backgroundColor = '#f39c12'; // Orange
      } else {
        passwordStrength.style.backgroundColor = '#2ecc71'; // Green
      }
      
      // Store the validation result
      passwordStrength.setAttribute('data-valid', validRequirements === 4 ? 'true' : 'false');
      
      // Enable/disable submit button based on password strength
      if (validRequirements === 4) {
        submitBtn.disabled = false;
      } else {
        submitBtn.disabled = password.length > 0 ? true : false;
      }
    });
    
    // Mobile number validation
    const mobileInput = document.getElementById('mobile');
    
    mobileInput.addEventListener('input', function() {
      // Remove non-numeric characters
      this.value = this.value.replace(/[^0-9]/g, '');
      
      // Format the number if needed
      if (this.value.length > 10) {
        this.value = this.value.substring(0, 10);
      }
    });
    
    // Form submission validation
    form.addEventListener('submit', function(event) {
      const isPasswordValid = passwordStrength.getAttribute('data-valid') === 'true';
      
      if (!isPasswordValid) {
        event.preventDefault();
        alert('<?php echo translate('Please ensure your password meets all requirements: at least 8 characters, including a capital letter, a number, and a special character.'); ?>');
      }
      
      // Validate mobile number
      const mobileValue = mobileInput.value.trim();
      if (!/^05\d{8}$/.test(mobileValue)) {
        event.preventDefault();
        alert('<?php echo translate('Please enter a valid Saudi mobile number starting with 05 followed by 8 digits.'); ?>');
      }
    });
    
    // Form steps animation (for visual purposes only in this version)
    document.addEventListener('DOMContentLoaded', function() {
      const formSteps = document.querySelectorAll('.form-step');
      const firstStep = formSteps[0];
      
      // Mark first step as active
      firstStep.classList.add('active');
      
      // Simulate progress when form is filled correctly
      form.addEventListener('input', function() {
        const allFilled = Array.from(form.elements)
          .filter(el => el.type !== 'submit' && el.type !== 'hidden')
          .every(el => el.value.trim() !== '');
          
        if (allFilled && passwordStrength.getAttribute('data-valid') === 'true') {
          formSteps[0].classList.add('completed');
          formSteps[1].classList.add('active');
        } else {
          formSteps[0].classList.remove('completed');
          formSteps[1].classList.remove('active');
        }
      });
    });
    
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
