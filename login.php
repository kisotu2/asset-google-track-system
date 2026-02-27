<?php
require 'db.php';
require_once __DIR__ . '/vendor/autoload.php';

use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\BaconQrCodeProvider;

session_start();

// Create QR provider
$qrProvider = new BaconQrCodeProvider();

// Initialize TwoFactorAuth with provider and app name
$tfa = new TwoFactorAuth($qrProvider, 'IRA Asset System');

$show2fa = false; 
$error = "";

// Step 0: Handle re-generate QR request
if(isset($_POST['regenerate_qr'])){
    if(isset($_SESSION['2fa_user'])){
        $user = $_SESSION['2fa_user'];
        $user['google_secret'] = $tfa->createSecret();
        $stmt_update = $conn->prepare("UPDATE users SET google_secret=?, 2fa_confirmed=0 WHERE id=?");
        $stmt_update->bind_param("si", $user['google_secret'], $user['id']);
        $stmt_update->execute();
        $_SESSION['2fa_user'] = $user;
        $error = "New QR code generated. Scan it with Google Authenticator.";
        $show2fa = true;
    }
}

// Step 1: Handle login submission
if(isset($_POST['login'])){
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if($user && password_verify($password, $user['password'])){
        // Create 2FA secret if missing
        if(empty($user['google_secret'])){
            $user['google_secret'] = $tfa->createSecret();
            $stmt_update = $conn->prepare("UPDATE users SET google_secret=? WHERE id=?");
            $stmt_update->bind_param("si", $user['google_secret'], $user['id']);
            $stmt_update->execute();
            $user['qr_required'] = true;
        } else {
            $user['qr_required'] = empty($user['2fa_confirmed']);
        }

        $_SESSION['2fa_user'] = $user;
        $show2fa = true;
    } else {
        $error = "Invalid email or password.";
    }
}

// Step 2: Handle 2FA verification
if(isset($_POST['verify_2fa'])){
    if(!isset($_SESSION['2fa_user'])){
        header("Location: login.php");
        exit();
    }

    $user = $_SESSION['2fa_user'];
    $code = $_POST['code'];

    if($tfa->verifyCode($user['google_secret'], $code)){
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role']    = $user['role'];
        $_SESSION['name']    = $user['full_name'];

        // Mark 2FA as confirmed
        $stmt_update = $conn->prepare("UPDATE users SET 2fa_confirmed=1 WHERE id=?");
        $stmt_update->bind_param("i", $user['id']);
        $stmt_update->execute();

        unset($_SESSION['2fa_user']);

        if($user['role'] === 'super_admin'){
            header("Location: super_dashboard.php");
        } elseif($user['role'] === 'admin'){
            header("Location: admin_dashboard.php");
        } else {
            header("Location: user_index.php");
        }
        exit();
    } else {
        $error = "Invalid authentication code.";
        $show2fa = true;
        $user['qr_required'] = false; 
    }
}

// Show 2FA view if session exists
if(isset($_SESSION['2fa_user'])){
    $show2fa = true;
    $user = $_SESSION['2fa_user'];
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Login - IRA Asset Management</title>
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
h2{color:#b08116;margin-bottom:20px;}
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
.qr img{margin-top:10px;}
.link{margin-top:10px;}
</style>
</head>
<body>

<div class="card">

<?php if(!$show2fa): ?>
<h2>Login</h2>
<?php if($error) echo "<p class='error'>$error</p>"; ?>
<form method="POST">
<input type="email" name="email" placeholder="Company Email" required>
<input type="password" name="password" placeholder="Password" required>
<button type="submit" name="login">Login</button>
</form>
<div class="link">
<a href="register.php">Register as User</a>
</div>

<?php else: ?>
<h2>Two-Factor Authentication</h2>
<?php if($error) echo "<p class='error'>$error</p>"; ?>

<?php if(!empty($user['google_secret']) && $user['qr_required']): ?>
<p>Scan this QR code with Google Authenticator and enter the 6-digit code:</p>
<div class="qr">
<img src="<?php echo $tfa->getQRCodeImageAsDataUri($user['full_name'], $user['google_secret']); ?>" alt="QR Code">
</div>
<form method="POST" style="margin-bottom:10px;">
<button type="submit" name="regenerate_qr">Re-generate QR</button>
</form>
<?php endif; ?>

<form method="POST">
<input type="text" name="code" placeholder="6-digit code" required>
<button type="submit" name="verify_2fa">Verify</button>
</form>
<?php endif; ?>

</div>

</body>
</html>