<?php
require 'db.php';
session_start();

// Only allow super admin
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'super_admin'){
    header("Location: login.php");
    exit();
}

$message = "";

// --------------------------
// Handle Adding Admin
// --------------------------
if(isset($_POST['add_admin'])){
    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $super_pass = trim($_POST['super_password']);

    // Fetch super admin hashed password from session user_id
    $stmt = $conn->prepare("SELECT password FROM users WHERE id=?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($super_pass_hash);
    $stmt->fetch();
    $stmt->close();

    // Verify super admin password
    if(password_verify($super_pass, $super_pass_hash)){
        // Check if user already exists
        $check = $conn->prepare("SELECT * FROM users WHERE email=?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();
        if($check->num_rows > 0){
            $message = "Email already exists!";
        } else {
            $default_pass = password_hash("user1234", PASSWORD_DEFAULT); // default password
            $role = "admin";
            $status = "active";

            $insert = $conn->prepare("INSERT INTO users (full_name,email,password,role,status) VALUES (?,?,?,?,?)");
            $insert->bind_param("sssss", $full_name, $email, $default_pass, $role, $status);
            $insert->execute();
            $insert->close();
            $message = "Admin added successfully with default password!";
        }
        $check->close();
    } else {
        $message = "Invalid super admin password!";
    }
}

// --------------------------
// Handle upgrading/downgrading users
// --------------------------
// --------------------------
// Handle upgrading/downgrading users
// --------------------------
if(isset($_POST['change_role'])){
    $target_id = $_POST['user_id'] ?? null;
    $new_role  = $_POST['new_role'] ?? null;
    $super_pass = $_POST['super_password'] ?? null;

    if($target_id && $new_role && $super_pass){
        // Verify super admin password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id=?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $stmt->bind_result($super_pass_hash);
        $stmt->fetch();
        $stmt->close();

        if(password_verify($super_pass, $super_pass_hash)){
            $update = $conn->prepare("UPDATE users SET role=? WHERE id=?");
            $update->bind_param("si", $new_role, $target_id);
            $update->execute();
            $update->close();
            $message = "User role updated successfully!";
        } else {
            $message = "Invalid super admin password!";
        }
    } else {
        $message = "All fields are required to change role!";
    }
}

// --------------------------
// Fetch all users
// --------------------------
$result = $conn->query("SELECT * FROM users ORDER BY role DESC");
$users = [];
if($result){
    while($row = $result->fetch_assoc()){
        $users[] = $row;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Super Admin Dashboard</title>
<style>
body { font-family: Arial, sans-serif; margin:2rem; background:#f5f5f5; }
h1,h2 { color:#b08116; }
table { width:100%; border-collapse:collapse; margin-top:1rem; }
th, td { padding:0.8rem; border:1px solid #ccc; text-align:left; }
th { background:#b08116; color:white; }
tr:nth-child(even) { background:#f0f0f0; }
input, select { padding:0.5rem; margin:0.2rem 0; width:100%; }
button { padding:0.5rem 1rem; background:#99bb4f; color:white; border:none; cursor:pointer; margin-top:0.3rem; }
button:hover { opacity:0.9; }
.message { margin:10px 0; font-weight:bold; }
.success { color:green; }
.error { color:red; }
form { background:white; padding:15px; border-radius:8px; margin-bottom:2rem; box-shadow:0 2px 6px rgba(0,0,0,0.1); }

/* Dropdown Menu */
.dropdown { position: relative; display: inline-block; margin-bottom:1rem; }
.dropdown button { background:#b08116; color:white; padding:10px; border:none; cursor:pointer; border-radius:5px; }
.dropdown-content { display:none; position:absolute; background:#f9f9f9; min-width:200px; box-shadow:0 8px 16px rgba(0,0,0,0.2); z-index:1; }
.dropdown-content div { padding:10px; cursor:pointer; }
.dropdown-content div:hover { background:#f1f1f1; }
.dropdown:hover .dropdown-content { display:block; }
.section { display:none; margin-top:1rem; }
</style>
</head>
<body>

<h1>Super Admin Dashboard</h1>

<?php if($message): ?>
<p class="message <?= strpos($message,'successfully')!==false ? 'success':'error' ?>"><?= $message ?></p>
<?php endif; ?>

<!-- Dropdown to choose section -->
<div class="dropdown">
    <button>Choose Action ▼</button>
    <div class="dropdown-content">
        <div onclick="showSection('addAdmin')">Add Admin</div>
        <div onclick="showSection('viewUsers')">View Users</div>
    </div>
</div>

<!-- Add Admin Section -->
<div class="section" id="addAdmin">
<h2>Add New Admin</h2>
<form method="POST">
<input type="text" name="full_name" placeholder="Full Name" required>
<input type="email" name="email" placeholder="Email" required>
<input type="password" name="super_password" placeholder="Enter your password" required>
<button type="submit" name="add_admin">Add Admin</button>
</form>
</div>

<!-- View Users Section -->
<div class="section" id="viewUsers">
<h2>All Registered Users & Admins</h2>
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
<form method="POST" style="display:inline-block;">
<input type="hidden" name="user_id" value="<?= $u['id'] ?>">
<select name="new_role" required>
<option value="admin" <?= $u['role']=='admin'?'selected':'' ?>>Admin</option>
<option value="user" <?= $u['role']=='user'?'selected':'' ?>>User</option>
</select>
<input type="password" name="super_password" placeholder="Super Admin Password" required>
<button type="submit" name="change_role">Change Role</button>
</form>
<?php else: ?>
N/A
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</table>
</div>

<script>
function showSection(id){
    document.querySelectorAll('.section').forEach(s => s.style.display='none');
    document.getElementById(id).style.display='block';
}
</script>
<div style="text-align:right; margin-bottom:10px;">
    <a href="logout.php" style="background:#dc3545; color:white; padding:8px 12px; border-radius:5px; text-decoration:none;">Logout</a>
</div>
</body>
</html>