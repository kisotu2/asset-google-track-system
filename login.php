<?php
require 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password']) && $user['status'] == 'active') {

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role']    = $user['role'];
        $_SESSION['name']    = $user['full_name'];

        if ($user['role'] === 'super_admin') {
            header("Location: super_dashboard.php");
        } elseif ($user['role'] === 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: user_index.php");
        }
        exit();

    } else {
        $error = "Invalid email or password, or account inactive.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Asset Management System</title>
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
}
h2{text-align:center;color:#b08116;}
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
.error{color:red;text-align:center;}
.link{text-align:center;margin-top:10px;}
</style>
</head>
<body>

<div class="card">
<h2>Login</h2>

<?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>

<form method="POST">
<input type="email" name="email" placeholder="Company Email" required>
<input type="password" name="password" placeholder="Password" required>
<button type="submit">Login</button>
</form>

<div class="link">
<a href="register.php">Register as User</a>
</div>
</div>

</body>
</html>