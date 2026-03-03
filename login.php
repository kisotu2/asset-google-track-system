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
   STEP 1: HANDLE EMAIL + PASSWORD LOGIN
===================================================== */
if(isset($_POST['login'])){

    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if($user && password_verify($password, $user['password']) && $user['status'] == 'active'){

        // Generate 6-digit OTP
        $otp = rand(100000, 999999);

        // Store OTP and expiry (5 minutes)
        $stmt_update = $conn->prepare("UPDATE users SET otp_code=?, otp_expiry=DATE_ADD(NOW(), INTERVAL 5 MINUTE) WHERE id=?");
        $stmt_update->bind_param("ii", $otp, $user['id']);
        $stmt_update->execute();

        $_SESSION['otp_user_id'] = $user['id'];
        $showOTP = true;

        /* =====================================================
           SEND EMAIL USING PHPMailer
        ===================================================== */
        $mail = new PHPMailer(true);

        try{
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'kisotusamuel2@gmail.com';
            $mail->Password   = 'pgveakwibzlhicqs';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('kisotusamuel2@gmail.com', 'IRA Asset Management System');
            $mail->addAddress($user['email'], $user['full_name']);

            $mail->isHTML(true);
            $mail->Subject = 'IRA Login Verification Code';
            $mail->Body    = "
                <h3>IRA Asset Management System</h3>
                <p>Hello {$user['full_name']},</p>
                <p>Your login verification code is:</p>
                <h2 style='color:#b08116;'>$otp</h2>
                <p>This code will expire in 5 minutes.</p>
                <br>
                <small>If you did not attempt to login, please ignore this email.</small>
            ";

            $mail->send();

        } catch(Exception $e){
            $error = "Failed to send verification email.";
        }

    } else {
        $error = "Invalid email or password.";
    }
}

/* =====================================================
   STEP 2: HANDLE OTP VERIFICATION
===================================================== */
if(isset($_POST['verify_otp'])){

    if(!isset($_SESSION['otp_user_id'])){
        header("Location: login.php");
        exit();
    }

    $user_id = $_SESSION['otp_user_id'];
    $entered_otp = $_POST['otp'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE id=? AND otp_code=? AND otp_expiry > NOW()");
    $stmt->bind_param("ii", $user_id, $entered_otp);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if($user){

        // Clear OTP after successful login
        $stmt_clear = $conn->prepare("UPDATE users SET otp_code=NULL, otp_expiry=NULL WHERE id=?");
        $stmt_clear->bind_param("i", $user['id']);
        $stmt_clear->execute();

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role']    = $user['role'];
        $_SESSION['name']    = $user['full_name'];

        unset($_SESSION['otp_user_id']);

        // Redirect based on role
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
}

if(isset($_SESSION['otp_user_id'])){
    $showOTP = true;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>IRA Asset Management System</title>
<style>
body{
    margin:0;
    font-family:Arial;
    background:linear-gradient(to right,#b08116,#99bb4f);
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}
.card{
    background:white;
    padding:2rem;
    width:350px;
    border-radius:10px;
    box-shadow:0 10px 30px rgba(0,0,0,0.2);
    text-align:center;
}
h2{color:#b08116;}
input{
    width:100%;
    padding:12px;
    margin:10px 0;
    border:1px solid #ccc;
    border-radius:5px;
}
button{
    width:100%;
    padding:12px;
    background:#99bb4f;
    border:none;
    color:white;
    font-weight:bold;
    border-radius:5px;
    cursor:pointer;
}
button:hover{opacity:0.9;}
.error{color:red;margin-bottom:15px;}
</style>
</head>
<body>

<div class="card">

<?php if(!$showOTP): ?>

<h2>Login</h2>
<?php if($error) echo "<p class='error'>$error</p>"; ?>

<form method="POST">
<input type="email" name="email" placeholder="Company Email" required>
<input type="password" name="password" placeholder="Password" required>
<button type="submit" name="login">Login</button>
</form>

<?php else: ?>

<h2>Email Verification</h2>
<?php if($error) echo "<p class='error'>$error</p>"; ?>
<p>A 6-digit verification code has been sent to your email.</p>

<form method="POST">
<input type="text" name="otp" placeholder="Enter 6-digit code" required>
<button type="submit" name="verify_otp">Verify</button>
</form>

<?php endif; ?>

</div>

</body>
</html>