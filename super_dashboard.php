<?php
require 'db.php';
session_start();

// Only super admin
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'super_admin'){
    header("Location: login.php");
    exit();
}

// Messages
$message = "";

// --- Handle Add Admin ---
if(isset($_POST['add_admin'])){
    $full_name = $_POST['full_name'];
    $email     = $_POST['email'];
    $password  = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role      = "admin";
    $status    = "active";
    $super_pass = $_POST['super_password'] ?? '';

    // Validate super admin password
    $stmt = $conn->prepare("SELECT password FROM users WHERE role='super_admin' AND email=?");
    $stmt->bind_param("s", $_SESSION['user_email']);
    $stmt->execute();
    $stmt->bind_result($hashed_super_pass);
    $stmt->fetch();
    $stmt->close();

    if(!password_verify($super_pass, $hashed_super_pass)){
        $message = "Invalid super admin password!";
    } else {
        $check = $conn->prepare("SELECT * FROM users WHERE email=?");
        $check->bind_param("s",$email);
        $check->execute();
        $check->store_result();
        if($check->num_rows>0){
            $message = "Email already exists!";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (full_name,email,password,role,status) VALUES (?,?,?,?,?)");
            $stmt->bind_param("sssss",$full_name,$email,$password,$role,$status);
            $stmt->execute();
            $message = "Admin added successfully!";
        }
    }
}

// --- Handle Asset Registration ---
if(isset($_POST['add_asset'])){

    if(!empty($_POST['asset_tag']) && !empty($_POST['serial_number'])){

        $asset_tag      = $_POST['asset_tag'];
        $serial_number  = $_POST['serial_number'];
        $brand          = $_POST['brand'];
        $model          = $_POST['model'];
        $department     = "ICT";
        $assigned_to    = NULL;
        $status         = $_POST['status'];
        $purchase_date  = $_POST['purchase_date'] ?: NULL;
        $warranty_expiry= $_POST['warranty_expiry'] ?: NULL;

        $stmt = $conn->prepare("
            INSERT INTO laptops
            (asset_tag,serial_number,brand,model,department,assigned_to,status,purchase_date,warranty_expiry)
            VALUES (?,?,?,?,?,?,?,?,?)
        ");

        $stmt->bind_param(
            "sssssisss",
            $asset_tag,
            $serial_number,
            $brand,
            $model,
            $department,
            $assigned_to,
            $status,
            $purchase_date,
            $warranty_expiry
        );

        $stmt->execute();
        $message = "Asset added successfully!";

    } else {
        $message = "Asset Tag and Serial Number required!";
    }
}

// --- Handle Change Role ---
if(isset($_POST['change_role'])){

    $user_id = $_POST['change_role'];
    $new_role = $_POST['new_role'];
    $super_pass = $_POST['password'];

    // Verify super admin password
    $stmt = $conn->prepare("SELECT password FROM users WHERE role='super_admin' AND email=?");
    $stmt->bind_param("s", $_SESSION['user_email']);
    $stmt->execute();
    $stmt->bind_result($hashed_super_pass);
    $stmt->fetch();
    $stmt->close();

    if(!password_verify($super_pass, $hashed_super_pass)){
        $message = "Invalid super admin password!";
    } else {

        $update = $conn->prepare("UPDATE users SET role=? WHERE id=?");
        $update->bind_param("si", $new_role, $user_id);
        $update->execute();

        $message = "Role updated successfully!";
    }
}


// --- Fetch users
$users = [];
$result = $conn->query("SELECT * FROM users ORDER BY role DESC");
while($row = $result->fetch_assoc()){
    $users[] = $row;
}

// --- Fetch ICT assets
$assets = [];
$result2 = $conn->query("SELECT laptops.*, users.full_name 
    FROM laptops
    LEFT JOIN users ON laptops.assigned_to = users.id
    WHERE laptops.department='ICT'
    ORDER BY laptops.created_at DESC");
while($row = $result2->fetch_assoc()){
    $assets[] = $row;
}

// --- Handle Register User ---
if(isset($_POST['register_user'])){

    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $password  = trim($_POST['password']);
    $role      = "user";
    $status    = "active";

    if(!empty($full_name) && !empty($email) && !empty($password)){

        $check = $conn->prepare("SELECT id FROM users WHERE email=?");
        $check->bind_param("s",$email);
        $check->execute();
        $check->store_result();

        if($check->num_rows > 0){
            $message = "Email already exists!";
        } else {

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
                INSERT INTO users (full_name,email,password,role,status) 
                VALUES (?,?,?,?,?)
            ");
            $stmt->bind_param("sssss",$full_name,$email,$hashedPassword,$role,$status);
            $stmt->execute();

            $message = "User registered successfully!";
        }

        $check->close();

    } else {
        $message = "All fields are required!";
    }
}
?>



<!DOCTYPE html>
<html>
<head>
<title>Super Admin Dashboard</title>
<style>
body { font-family: Arial; margin:2rem; background:#f5f5f5; }
h1, h2 { color:#b08116; }
.message { font-weight:bold; margin:10px 0; }
.success { color:green; }
.error { color:red; }
form { background:white; padding:15px; border-radius:8px; margin-bottom:2rem; box-shadow:0 2px 6px rgba(0,0,0,0.1); max-width:500px; }
input, select { padding:0.5rem; margin:0.2rem 0; width:100%; }
button { padding:0.5rem 1rem; background:#99bb4f; color:white; border:none; cursor:pointer; margin-top:0.3rem; }
button:hover { opacity:0.9; }
table { width:100%; border-collapse:collapse; margin-top:1rem; }
th, td { padding:0.8rem; border:1px solid #ccc; text-align:left; }
th { background:#b08116; color:white; }
tr:nth-child(even) { background:#f0f0f0; }

/* Dropdown menu */
.nav { margin-bottom:20px; }
.dropdown { position: relative; display: inline-block; }
.dropdown-content { display: none; position: absolute; background:#f9f9f9; min-width:200px; box-shadow:0px 8px 16px rgba(0,0,0,0.2); z-index:1; }
.dropdown-content a { color:black; padding:10px 12px; text-decoration:none; display:block; }
.dropdown-content a:hover { background-color:#f1f1f1; }
.dropdown:hover .dropdown-content { display:block; }

a.logout { float:right; background:linear-gradient(to right,#b08116,#99bb4f); color:white; padding:1px 2px; border-radius:5px; text-decoration:none; font-size: 25px;}
.logout:hover { opacity:0.9; }

/* Sections hidden by default */
.section { display:none; margin-top:20px; }
</style>
<script>
function showSection(id){
    // Hide all sections
    let sections = document.querySelectorAll(".section");
    sections.forEach(s => s.style.display="none");

    // Show selected
    document.getElementById(id).style.display = "block";
}
</script>
</head>
<body>

<h1>Super Admin Dashboard
    <a href="logout.php" class="logout">Logout</a>
</h1>

<?php if($message): ?>
<p class="message <?= strpos($message,'successfully')!==false ? 'success':'error' ?>"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<div class="nav">
<div class="dropdown">
<button>Actions ▼</button>
<div class="dropdown-content">
<a href="javascript:void(0)" onclick="showSection('add-admin')">Add Admin</a>
<a href="javascript:void(0)" onclick="showSection('register-user')">Register User</a>
<a href="javascript:void(0)" onclick="showSection('view-users')">View Users</a>
<a href="javascript:void(0)" onclick="showSection('add-asset')">Add ICT Asset</a>
<a href="javascript:void(0)" onclick="showSection('view-assets')">View ICT Assets</a>

</div>
</div>
</div>

<!-- ADD ADMIN -->
<div id="add-admin" class="section">
<h2>Add New Admin</h2>
<form method="POST">
<input type="text" name="full_name" placeholder="Full Name" required>
<input type="email" name="email" placeholder="Email" required>
<input type="password" name="password" placeholder="Password" required>
<button type="submit" name="add_admin">Add Admin</button>
</form>
</div>

<!-- REGISTER USER -->
<div id="register-user" class="section">
<h2>Register New User</h2>
<form method="POST">
<input type="text" name="full_name" placeholder="Full Name" required>
<input type="email" name="email" placeholder="Email" required>
<input type="password" name="password" placeholder="Password" required>
<button type="submit" name="register_user">Register User</button>
</form>
</div>

<!-- VIEW USERS -->
<div id="view-users" class="section">
<h2>Registered Users & Admins</h2>
<table>
<tr>
<th>Name</th>
<th>Email</th>
<th>Role</th>
<th>Status</th>
<th>Actions</th>
</tr>
<?php foreach($users as $u): ?>
<tr>
<td><?= htmlspecialchars($u['full_name']) ?></td>
<td><?= htmlspecialchars($u['email']) ?></td>
<td><?= htmlspecialchars($u['role']) ?></td>
<td><?= htmlspecialchars($u['status']) ?></td>
<td>
<?php if($u['role'] != 'super_admin'): ?>
<form method="POST" action="">
<select name="new_role">
    <option value="user" <?= $u['role']=='user'?'selected':'' ?>>User</option>
    <option value="admin" <?= $u['role']=='admin'?'selected':'' ?>>Admin</option>
</select>
<input type="password" name="super_password" placeholder="Enter Your Super Admin Password" required>
<button type="submit" name="change_role" value="<?= $u['id'] ?>">Update Role</button>
</form>
<?php else: ?>
N/A
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</table>
</div>

<!-- ADD ICT ASSET -->
<div id="add-asset" class="section">
<h2>Register ICT Asset</h2>
<form method="POST">
<input type="text" name="asset_tag" placeholder="Asset Tag" required>
<input type="text" name="serial_number" placeholder="Serial Number" required>
<input type="text" name="brand" placeholder="Brand">
<input type="text" name="model" placeholder="Model">
<select name="status">
<option value="Active">Active</option>
<option value="Faulty">Faulty</option>
<option value="Retired">Retired</option>
</select>
<label>Purchase Date</label>
<input type="date" name="purchase_date">
<label>Warranty Expiry</label>
<input type="date" name="warranty_expiry">
<button type="submit" name="add_asset">Register Asset</button>
</form>
</div>

<!-- VIEW ICT ASSETS -->
<div id="view-assets" class="section">
<h2>ICT Registered Assets</h2>
<table>
<tr>
<th>Asset Tag</th><th>Serial Number</th><th>Brand</th><th>Model</th><th>Assigned To</th><th>Status</th><th>Purchase Date</th><th>Warranty Expiry</th>
</tr>
<?php foreach($assets as $a): ?>
<tr>
<td><?= htmlspecialchars($a['asset_tag']) ?></td>
<td><?= htmlspecialchars($a['serial_number']) ?></td>
<td><?= htmlspecialchars($a['brand']) ?></td>
<td><?= htmlspecialchars($a['model']) ?></td>
<td>
<?= $a['full_name'] ? htmlspecialchars($a['full_name']) : 'Unassigned' ?>
</td><td><?= htmlspecialchars($a['status']) ?></td>
<td><?= htmlspecialchars($a['purchase_date']) ?></td>
<td><?= htmlspecialchars($a['warranty_expiry']) ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>



</body>
</html>