<?php
require 'db.php';
require_once __DIR__ . '/vendor/autoload.php';

use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\BaconQrCodeProvider;

session_start();

if(!isset($_SESSION['2fa_user'])){
    header("Location: login.php");
    exit();
}

$qrProvider = new BaconQrCodeProvider();
$tfa = new TwoFactorAuth($qrProvider, 'IRA Asset System');

$user = $_SESSION['2fa_user'];

if(isset($_POST['verify'])){
    $code = $_POST['code'];

    if(empty($user['google_secret'])){
        $error = "Two-Factor Authentication is not set up for your account.";
    } elseif($tfa->verifyCode($user['google_secret'], $code)){
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role']    = $user['role'];
        $_SESSION['name']    = $user['full_name'];

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
    }
}

// Generate QR code for Google Authenticator
$qrCodeUrl = $tfa->getQRText($user['full_name'], $user['google_secret']);
?>

<!DOCTYPE html>
<html>
<head>
<title>Verify Authentication</title>
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
.error{color:red;margin-bottom:10px;}
.qr img{margin-top:10px;}
</style>
</head>
<body>

<div class="card">
<h2>Two-Factor Authentication</h2>

<?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>

<?php if(!empty($user['google_secret'])): ?>
<p>Scan this QR code with Google Authenticator and enter the 6-digit code:</p>
<div class="qr">
<img src="<?php echo $qrProvider->getDataUri($user['full_name'], $user['google_secret']); ?>" alt="QR Code">
</div>
<?php endif; ?>

<form method="POST">
<input type="text" name="code" placeholder="6-digit code" required>
<button type="submit" name="verify">Verify</button>
</form>
</div>

</body>
</html>