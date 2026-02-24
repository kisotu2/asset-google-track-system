<?php
require 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$_POST['email']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($_POST['password'], $user['password'])) {

        $_SESSION['user'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] === 'super_admin' || $user['role'] === 'admin') {
            header("Location: admin.php");
        } else {
            header("Location: index.php");
        }
        exit();

    } else {
        $error = "Invalid email or password!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Login</title>
<style>
body { font-family: Arial; background:#f5f5f5; display:flex; justify-content:center; align-items:center; height:100vh; }
.card { background:white; padding:2rem; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.1); width:300px; }
h2 { color:#b08116; text-align:center; }
input { width:100%; padding:10px; margin:10px 0; }
button { width:100%; padding:10px; background:#99bb4f; color:white; border:none; cursor:pointer; }
.error { color:red; text-align:center; }
</style>
</head>
<body>

<div class="card">
<h2>Asset System Login</h2>

<?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>

<form method="POST">
<input type="email" name="email" placeholder="Email" required>
<input type="password" name="password" placeholder="Password" required>
<button type="submit">Login</button>
</form>
</div>

</body>
</html>