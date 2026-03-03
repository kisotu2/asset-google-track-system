<?php
require 'db.php';

// PHPMailer files
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
require 'PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

$error = "";
$showOTP = false;

/* =====================================================
   STEP 1: EMAIL + PASSWORD
===================================================== */
if(isset($_POST['login'])){

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows === 1){

        $user = $result->fetch_assoc();

        if(password_verify($password, $user['password']) && $user['status'] === 'active'){

            // Generate OTP
            $otp = rand(100000, 999999);

            // Save OTP + expiry
            $update = $conn->prepare("UPDATE users SET otp_code=?, otp_expiry=DATE_ADD(NOW(), INTERVAL 5 MINUTE) WHERE id=?");
            $update->bind_param("ii", $otp, $user['id']);
            $update->execute();
            $update->close();

            $_SESSION['otp_user_id'] = $user['id'];
            $showOTP = true;

            // Send Email
            $mail = new PHPMailer(true);

            try{
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'kisotusamuel2@gmail.com';   // CHANGE
                $mail->Password   = 'pgveakwibzlhicqs';      // CHANGE
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;

                $mail->setFrom('kisotusamuel2@gmail.com', 'IRA Asset Management System');
                $mail->addAddress($user['email'], $user['full_name']);

                $mail->isHTML(true);
                $mail->Subject = 'IRA Login Verification Code';
                $mail->Body = "
                    <h3>IRA Asset Management System</h3>
                    <p>Hello {$user['full_name']},</p>
                    <p>Your verification code is:</p>
                    <h2 style='color:#b08116;'>$otp</h2>
                    <p>This code expires in 5 minutes.</p>
                ";

                $mail->send();

            } catch(Exception $e){
                $error = "Failed to send verification email.";
            }

        } else {
            $error = "Invalid email, password, or inactive account.";
        }

    } else {
        $error = "Invalid email or password.";
    }

    $stmt->close();
}


/* =====================================================
   STEP 2: OTP VERIFICATION
===================================================== */
if(isset($_POST['verify_otp'])){

    if(!isset($_SESSION['otp_user_id'])){
        header("Location: login.php");
        exit();
    }

    $user_id = $_SESSION['otp_user_id'];
    $entered_otp = trim($_POST['otp']);

    $stmt = $conn->prepare("SELECT * FROM users WHERE id=? AND otp_code=? AND otp_expiry > NOW()");
    $stmt->bind_param("ii", $user_id, $entered_otp);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows === 1){

        $user = $result->fetch_assoc();

        // Clear OTP
        $clear = $conn->prepare("UPDATE users SET otp_code=NULL, otp_expiry=NULL WHERE id=?");
        $clear->bind_param("i", $user['id']);
        $clear->execute();
        $clear->close();

        // Login session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role']    = $user['role'];
        $_SESSION['name']    = $user['full_name'];

        unset($_SESSION['otp_user_id']);

        // Redirect
        if($user['role'] === 'super_admin'){
            header("Location: super_dashboard.php");
        } elseif($user['role'] === 'admin'){
            header("Location: admin_dashboard.php");
        } else {
            header("Location: user_index.php");
        }
        exit();

    } else {
        $error = "Invalid or expired verification code.";
        $showOTP = true;
    }

    $stmt->close();
}

if(isset($_SESSION['otp_user_id'])){
    $showOTP = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>IRA Asset Management System</title>

<style>

:root {
    --primary: #003366;
    --secondary: #C8102E;
    --accent: #F2A900;
    --light-bg: #eef2f7;
    --text-dark: #1f2d3d;
    --text-light: #6c7a89;
}

/* Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', sans-serif;
}

/* Animated Background */
body {
    height: 100vh;
    background:linear-gradient(to right,#b08116,#99bb4f);
    background-size: 400% 400%;
    animation: gradientMove 12s ease infinite;
    display: flex;
    border-radius: 0 0 20px 20px;
}

@keyframes gradientMove {
    0% {background-position: 0% 50%;}
    50% {background-position: 100% 50%;}
    100% {background-position: 0% 50%;}
}

/* Layout */
.container {
    display: flex;
    width: 100%;
}

/* LEFT PANEL */
.left {
    flex: 1;
    padding: 60px;
    color: white;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.ira-logo {
    width: 170px;
    margin-bottom: 25px;
}

.system-title {
    font-size: 30px;
    font-weight: 600;
    margin-bottom: 10px;
}

.motto {
    color: var(--accent);
    font-style: italic;
    margin-bottom: 30px;
}

.description {
    max-width: 400px;
    color: #d6e2f0;
}

/* RIGHT PANEL */
.right {
    flex: 1;
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(10px);
    padding: 80px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    position: relative;
}

/* Login Card */
.login-card {
    background: white;
    padding: 45px;
    border-radius: 18px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.15);
}

h2 {
    color: var(--primary);
    margin-bottom: 25px;
}

/* Inputs */
input {
    width: 100%;
    padding: 14px;
    margin: 10px 0 20px;
    border-radius: 10px;
    border: 1px solid #dce3ea;
    transition: 0.3s;
}

input:focus {
    border-color: var(--secondary);
    box-shadow: 0 0 0 3px rgba(200,16,46,0.1);
}

/* Password Wrapper */
.password-wrapper {
    position: relative;
}

.toggle-password {
    position: absolute;
    right: 15px;
    top: 18px;
    cursor: pointer;
    font-size: 14px;
    color: var(--text-light);
}

/* Remember Me */
.remember {
    display: flex;
    align-items: center;
    font-size: 14px;
    margin-bottom: 20px;
}

.remember input {
    width: auto;
    margin-right: 8px;
}

/* Button */
.btn-login {
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: 30px;
    background:linear-gradient(to right,#b08116,#99bb4f);
    color: white;
    font-size: 15px;
    cursor: pointer;
    transition: 0.3s;
}

.btn-login:hover {
    transform: translateY(-2px);
    opacity: 0.95;
}

/* Footer */
.system-footer {
    position: absolute;
    bottom: 20px;
    right: 60px;
    font-size: 12px;
    color: #555;
}

/* Responsive */
@media(max-width:900px){
    .container {
        flex-direction: column;
    }
    .right {
        padding: 40px;
    }
}

</style>
</head>
<body>

<div class="container">

    <!-- LEFT PANEL -->
    <div class="left">
        <img src="IRA.png" class="ira-logo">
        <div class="system-title">Asset Management System</div>
        <div class="motto">Promoting insurance. Protecting the insured.</div>
        <div class="description">
            Securely monitor and manage ICT assets across departments with transparency and accountability.
        </div>
    </div>

    <!-- RIGHT PANEL -->
    <div class="right">

        <?php if(!$showOTP): ?>

<form method="POST">

    <label>Email Address</label>
    <input type="email" name="email" placeholder="name@ira.go.ke" required>

    <label>Password</label>
    <div class="password-wrapper">
        <input type="password" name="password" id="password" placeholder="************" required>
        <span class="toggle-password" onclick="togglePassword()">Show</span>
    </div>

    <button type="submit" name="login" class="btn-login">Login</button>
</form>

<?php else: ?>

<form method="POST">

    <label>Enter 6-Digit Verification Code</label>
    <input type="text" name="otp" maxlength="6" placeholder="123456" required>

    <button type="submit" name="verify_otp" class="btn-login">Verify Code</button>
</form>

<?php endif; ?>

        <div class="system-footer">
            IRA Asset Management System v1.0
        </div>

    </div>

</div>

<script>
function togglePassword() {
    const password = document.getElementById("password");
    const toggle = document.querySelector(".toggle-password");
    
    if (password.type === "password") {
        password.type = "text";
        toggle.textContent = "Hide";
    } else {
        password.type = "password";
        toggle.textContent = "Show";
    }
}
</script>

</body>
</html>