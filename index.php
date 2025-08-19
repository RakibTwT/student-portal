<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Student Portal</title>
<link rel="stylesheet" href="assets/css/style.css">
<script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body>

<div class="login-container">
    <h2>Login</h2>

    <?php if ($error) echo "<p class='error'>$error</p>"; ?>

    <form id="loginForm" method="POST" action="login_process.php">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>

    <p>Or login with Google:</p>

    <div id="g_id_onload"
         data-client_id="685696352202-m46agnnlidcchatdtihob3lrqv68d4is.apps.googleusercontent.com"
         data-callback="handleCredentialResponse">
    </div>

    <div class="g_id_signin"
         data-type="standard"
         data-size="large"
         data-theme="outline"
         data-text="sign_in_with"
         data-shape="rectangular"
         data-logo_alignment="left">
    </div>

    <p>Don't have an account? <a href="register.php">Register here</a></p>
</div>

<script>
function handleCredentialResponse(response) {
    fetch('google_login.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id_token: response.credential})
    })
    .then(res => res.json())
    .then(data => {
        if(data.success){
            window.location.href = 'dashboard.php';
        } else {
            alert('Google login failed: ' + data.message);
        }
    })
    .catch(() => alert('Login failed. Please try again.'));
}
</script>

</body>
</html>
