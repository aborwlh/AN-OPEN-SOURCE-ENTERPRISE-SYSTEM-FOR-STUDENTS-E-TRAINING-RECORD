<?php include 'config.php'; ?>

<?php session_destroy (); ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Ocean App - Log Out</title>
  <link rel="stylesheet" href="assets/style/styles.css"/>
</head>
<body>
  <main class="logout-screen">
    <h1>You have been logged out</h1>
    <p>Thank you for using Ocean App.</p>
    <p><a href="login.php">Return to login page</a></p>
  </main>
</body>
</html>
