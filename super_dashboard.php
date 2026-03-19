<?php
require 'db.php';
session_start();

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'super_admin'){
    header("Location: login.php");
    exit();
}

$message="";

/* ===============================
ADD ADMIN
=================================*/
if(isset($_POST['add_admin'])){
    $full_name=trim($_POST['full_name']);
    $email=trim($_POST['email']);
    $password=password_hash($_POST['password'],PASSWORD_DEFAULT);
    $role="admin";
    $status="active";

    $check=$conn->prepare("SELECT id FROM users WHERE email=?");
    $check->bind_param("s",$email);
    $check->execute();
    $check->store_result();

    if($check->num_rows>0){
        $message="⚠️ Email already exists!";
    }else{
        $stmt=$conn->prepare("INSERT INTO users(full_name,email,password,role,status) VALUES(?,?,?,?,?)");
        $stmt->bind_param("sssss",$full_name,$email,$password,$role,$status);
        $stmt->execute();
        $message="✅ Admin added successfully!";
    }
}

/* ===============================
REGISTER USER
=================================*/
if(isset($_POST['register_user'])){
    $full_name=trim($_POST['full_name']);
    $email=trim($_POST['email']);
    $password=password_hash($_POST['password'],PASSWORD_DEFAULT);
    $role="user";
    $status="active";

    $check=$conn->prepare("SELECT id FROM users WHERE email=?");
    $check->bind_param("s",$email);
    $check->execute();
    $check->store_result();

    if($check->num_rows>0){
        $message="⚠️ Email already exists!";
    }else{
        $stmt=$conn->prepare("INSERT INTO users(full_name,email,password,role,status) VALUES(?,?,?,?,?)");
        $stmt->bind_param("sssss",$full_name,$email,$password,$role,$status);
        $stmt->execute();
        $message="✅ User registered successfully!";
    }
}

/* ===============================
ADD ASSET
=================================*/
if(isset($_POST['add_asset'])){
    $asset_tag=$_POST['asset_tag'];
    $serial=$_POST['serial_number'];
    $brand=$_POST['brand'];
    $model=$_POST['model'];
    $status=$_POST['status'];

    $assigned_to = NULL; // always null on creation

    $stmt=$conn->prepare("
        INSERT INTO laptops(asset_tag,serial_number,brand,model,status,assigned_to)
        VALUES(?,?,?,?,?,?)
    ");
    $stmt->bind_param("sssssi",$asset_tag,$serial,$brand,$model,$status,$assigned_to);
    $stmt->execute();

    if($message): ?>
<p class="message" id="alertMessage"><?= htmlspecialchars($message) ?></p>
<?php endif; 
}

/* ===============================
FETCH DATA
=================================*/
$totalUsers=$conn->query("SELECT COUNT(*) as t FROM users WHERE role='user'")->fetch_assoc()['t'];
$totalAdmins=$conn->query("SELECT COUNT(*) as t FROM users WHERE role='admin'")->fetch_assoc()['t'];
$totalAssets=$conn->query("SELECT COUNT(*) as t FROM laptops")->fetch_assoc()['t'];
$totalSoftware=$conn->query("SELECT COUNT(*) as t FROM softwares")->fetch_assoc()['t'];

$users=$conn->query("SELECT * FROM users WHERE role='user'");
$admins=$conn->query("SELECT * FROM users WHERE role='admin'");
$assets=$conn->query("SELECT * FROM laptops");
$softwares=$conn->query("SELECT * FROM softwares");
?>
<?php if($message): ?>
<p class="message" id="alertMessage"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<!DOCTYPE html>
<html>
<head>
<title>IRA Super Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body{margin:0;font-family:Segoe UI;background:#f4f6f9;}
.sidebar{width:250px;height:100vh;position:fixed;background:linear-gradient(180deg,#99bb4f,#b08116);color:white;}
.sidebar h2{text-align:center;padding:20px;border-bottom:1px solid rgba(255,255,255,0.2);}
.sidebar a{display:block;padding:14px 20px;color:white;text-decoration:none;font-size:15px;}
.sidebar a:hover{background:rgba(255,255,255,0.15);}
.main{margin-left:250px;padding:30px;}
.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;}
.logout{background:#b08116;color:white;padding:8px 15px;text-decoration:none;border-radius:5px;}
.cards{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:25px;}
.card{background:white;padding:25px;border-radius:10px;box-shadow:0 3px 10px rgba(0,0,0,0.1);cursor:pointer;transition:0.3s;text-align:center;}
.card:hover{transform:translateY(-5px);}
.card h3{margin:0;color:#333;}
.card p{font-size:30px;margin-top:10px;color:#99bb4f;}
.section{display:none;margin-top:20px;background:white;padding:20px;border-radius:10px;box-shadow:0 3px 8px rgba(0,0,0,0.1);}
.section table{width:100%;border-collapse:collapse;}
.section th, .section td{padding:10px;border-bottom:1px solid #ddd;}
.section th{background:#b08116;color:white;}
input,select{width:100%;padding:10px;margin:8px 0;border:1px solid #ddd;border-radius:6px;}
button{background:#99bb4f;border:none;padding:10px 15px;color:white;cursor:pointer;border-radius:6px;}
button:hover{background:#7ea93e;}
.message{margin-top:15px;font-weight:bold;}
</style>
<script>
function showSection(sectionId){
    // hide all sections
    let sections=document.querySelectorAll(".section");
    sections.forEach(s=>s.style.display="none");
    // show selected
    document.getElementById(sectionId).style.display="block";
}
</script>
</head>
<body>

<div class="sidebar">
<h2>IRA Asset System</h2>

<a href="#" onclick="showSection('dashboard')">
<i class="fa fa-chart-line"></i> Dashboard
</a>

<a href="#" onclick="showSection('addAdmin')">
<i class="fa fa-user-shield"></i> Add Admin
</a>

<a href="#" onclick="showSection('registerUser')">
<i class="fa fa-user"></i> Register User
</a>

<a href="#" onclick="showSection('addAsset')">
<i class="fa fa-laptop"></i> Add Asset
</a>

<a href="software_dashboard.php">
<i class="fa fa-box"></i> Software Monitoring
</a>

<a href="issue_software.php">
<i class="fa fa-key"></i> Laptop Issue
</a>

<a href="logout.php">
<i class="fa fa-sign-out-alt"></i> Logout
</a>

</div>

<div class="main">
<div class="header">
<h1>Super Admin Control Center</h1>
</div>

<?php if($message){ ?>
<div class="message"><?php echo $message; ?></div>
<?php } ?>

<!-- DASHBOARD CARDS AS FLOATING BUTTONS -->
<div class="cards">
<div class="card" onclick="showSection('adminsList')">
    <h3>Total Admins</h3>
    <p><?php echo $totalAdmins; ?></p>
</div>
<div class="card" onclick="showSection('usersList')">
    <h3>Total Users</h3>
    <p><?php echo $totalUsers; ?></p>
</div>
<div class="card" onclick="showSection('assetsList')">
    <h3>Total Laptops</h3>
    <p><?php echo $totalAssets; ?></p>
</div>
<div class="card" onclick="showSection('softwaresList')">
    <h3>Total Software</h3>
    <p><?php echo $totalSoftware; ?></p>
</div>
</div>

<!-- ADD ADMIN FORM -->
<div class="section" id="addAdmin">
<h2>Add System Admin</h2>
<form method="POST">
<input type="text" name="full_name" placeholder="Full Name" required>
<input type="email" name="email" placeholder="Email" required>
<input type="password" name="password" placeholder="Password" required>
<button name="add_admin">Create Admin</button>
</form>
</div>

<!-- REGISTER USER FORM -->
<div class="section" id="registerUser">
<h2>Register User</h2>
<form method="POST">
<input type="text" name="full_name" placeholder="Full Name" required>
<input type="email" name="email" placeholder="Email" required>
<input type="password" name="password" placeholder="Password" required>
<button name="register_user">Register User</button>
</form>
</div>

<!-- ADD ASSET FORM -->
<div class="section" id="addAsset">
<h2>Add ICT Asset</h2>
<form method="POST">
<input type="text" name="asset_tag" placeholder="Asset Tag">
<input type="text" name="serial_number" placeholder="Serial Number">
<input type="text" name="brand" placeholder="Brand">
<input type="text" name="model" placeholder="Model">
<select name="status">
<option>Active</option>
<option>Faulty</option>
<option>Retired</option>
</select>
<button name="add_asset">Add Asset</button>
</form>
</div>

<!-- ADMIN LIST -->
<div class="section" id="adminsList">
<h2>All Admins</h2>
<table>
<tr><th>Name</th><th>Email</th><th>Status</th></tr>
<?php while($a=$admins->fetch_assoc()){ ?>
<tr>
<td><?php echo $a['full_name']; ?></td>
<td><?php echo $a['email']; ?></td>
<td><?php echo $a['status']; ?></td>
</tr>
<?php } ?>
</table>
</div>

<!-- USER LIST -->
<div class="section" id="usersList">
<h2>All Users</h2>
<table>
<tr><th>Name</th><th>Email</th><th>Status</th></tr>
<?php while($u=$users->fetch_assoc()){ ?>
<tr>
<td><?php echo $u['full_name']; ?></td>
<td><?php echo $u['email']; ?></td>
<td><?php echo $u['status']; ?></td>
</tr>
<?php } ?>
</table>
</div>

<!-- ASSET LIST -->
<div class="section" id="assetsList">
<h2>All Laptops</h2>
<table>
<tr><th>Asset Tag</th><th>Serial</th><th>Brand</th><th>Model</th><th>Status</th><th>State</th></tr>
<?php while($l=$assets->fetch_assoc()){ ?>
<tr>
<td><?php echo $l['asset_tag']; ?></td>
<td><?php echo $l['serial_number']; ?></td>
<td><?php echo $l['brand']; ?></td>
<td><?php echo $l['model']; ?></td>
<td><?php echo $l['status']; ?></td>

<td>
<?php
if(!empty($l['assigned_to'])){
    echo "<span style='color:red;font-weight:bold;'>Issued</span>";
}else{
    echo "<span style='color:green;font-weight:bold;'>Available</span>";
}
?>
</td>
</tr>
<?php } ?>
</table>
</div>

<!-- SOFTWARE LIST -->
<div class="section" id="softwaresList">
<h2>All Software</h2>

<table>
<tr>
<th>Name</th>
<th>Vendor</th>
<th>License</th>
<th>Action</th>
</tr>

<?php while($s=$softwares->fetch_assoc()){ ?>

<tr>
<td><?php echo $s['software_name']; ?></td>
<td><?php echo $s['vendor']; ?></td>
<td><?php echo $s['license_type']; ?></td>

<td>
<a href="software_details.php?id=<?php echo $s['id']; ?>">
<button class="viewBtn">View Details</button>
</a>
</td>

</tr>

<?php } ?>

</table>
</div>

</div>
<script>
window.addEventListener('DOMContentLoaded', (event) => {
    const alertMsg = document.getElementById('alertMessage');
    if(alertMsg){
        setTimeout(() => {
            alertMsg.style.transition = "opacity 0.5s ease";
            alertMsg.style.opacity = 0;
            setTimeout(() => alertMsg.remove(), 500); // remove from DOM
        }, 10000); // 10000ms = 10 seconds
    }
});
</script>
</body>
</html>