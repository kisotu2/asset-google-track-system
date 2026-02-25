<?php
require 'db.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = $_POST['full_name'];
    $email     = $_POST['email'];
    $password  = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if super admin already exists
    $check = $conn->query("SELECT * FROM users WHERE role='super_admin'");

    if ($check->num_rows > 0) {
        $message = "Super Admin already exists!";
    } else {

        $stmt = $conn->prepare("INSERT INTO users (full_name,email,password,role,status) VALUES (?,?,?,?,?)");
        $role = "super_admin";
        $status = "active";

        $stmt->bind_param("sssss", $full_name, $email, $password, $role, $status);

        if ($stmt->execute()) {
            $message = "Super Admin created successfully!";
        } else {
            $message = "Error creating Super Admin.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Create Super Admin</title>
<style>
body{
    font-family: Arial;
    background: linear-gradient(to right,#b08116,#99bb4f);
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}
.card{
    background:white;
    padding:30px;
    width:350px;
    border-radius:8px;
    box-shadow:0 8px 20px rgba(0,0,0,0.2);
}
h2{text-align:center;color:#b08116;}
input{
    width:100%;
    padding:10px;
    margin:10px 0;
    border:1px solid #ccc;
    border-radius:5px;
}
button{
    width:100%;
    padding:10px;
    background:#99bb4f;
    border:none;
    color:white;
    font-weight:bold;
    border-radius:5px;
    cursor:pointer;
}
.message{
    text-align:center;
    font-weight:bold;
    margin-bottom:10px;
}
.success{color:green;}
.error{color:red;}
</style>
</head>
<body>

<div class="card">
<h2>Setup Super Admin</h2>

<?php if($message): ?>
    <p class="message"><?= $message ?></p>
<?php endif; ?>

<form method="POST">
<input type="text" name="full_name" placeholder="Full Name" required>
<input type="email" name="email" placeholder="Email" required>
<input type="password" name="password" placeholder="Password" required>
<button type="submit">Create Super Admin</button>
</form>

</div>

</body>
</html>