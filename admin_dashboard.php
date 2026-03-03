<?php
require 'db.php';
session_start();

/* ===============================
   ACCESS CONTROL
=================================*/
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit();
}

$message = "";

/* ===============================
   ASSIGN LAPTOP
=================================*/
if(isset($_POST['assign_laptop'])){
    $laptop_id = intval($_POST['laptop_id']);
    $user_id   = intval($_POST['user_id']);

    $stmt = $conn->prepare("UPDATE laptops SET assigned_to=? WHERE id=? AND assigned_to IS NULL");
    $stmt->bind_param("ii",$user_id,$laptop_id);
    $stmt->execute();

    $message = "Laptop assigned successfully!";
}

/* ===============================
   UNASSIGN LAPTOP
=================================*/
if(isset($_POST['unassign_laptop'])){
    $laptop_id = intval($_POST['laptop_id']);

    $stmt = $conn->prepare("UPDATE laptops SET assigned_to=NULL WHERE id=?");
    $stmt->bind_param("i",$laptop_id);
    $stmt->execute();

    $message = "Laptop unassigned successfully!";
}

/* ===============================
   FETCH STATS
=================================*/
$total_assets = $conn->query("SELECT COUNT(*) as total FROM laptops")->fetch_assoc()['total'];
$assigned_assets = $conn->query("SELECT COUNT(*) as total FROM laptops WHERE assigned_to IS NOT NULL")->fetch_assoc()['total'];
$unassigned_assets = $conn->query("SELECT COUNT(*) as total FROM laptops WHERE assigned_to IS NULL")->fetch_assoc()['total'];

/* ===============================
   FETCH USERS WITHOUT LAPTOP
=================================*/
$available_users = [];
$result = $conn->query("
    SELECT u.id, u.full_name 
    FROM users u
    LEFT JOIN laptops l ON u.id = l.assigned_to
    WHERE l.assigned_to IS NULL
    AND u.role='user'
    AND u.status='active'
");

while($row = $result->fetch_assoc()){
    $available_users[] = $row;
}

/* ===============================
   FETCH LAPTOPS
=================================*/
$laptops = [];
$result2 = $conn->query("
    SELECT l.*, u.full_name 
    FROM laptops l
    LEFT JOIN users u ON l.assigned_to = u.id
    ORDER BY l.created_at DESC
");

while($row = $result2->fetch_assoc()){
    $laptops[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard</title>
<style>
body { font-family: Arial; background:#f4f6f9; margin:2rem; }
h1 { color:#b08116; }
.card-container { display:flex; gap:20px; margin-bottom:2rem; }
.card {
    background:white;
    padding:20px;
    border-radius:8px;
    box-shadow:0 2px 6px rgba(0,0,0,0.1);
    flex:1;
    text-align:center;
}
.card h2 { margin:0; }
table { width:100%; border-collapse:collapse; margin-top:1rem; }
th, td { padding:0.8rem; border:1px solid #ccc; }
th { background:#99bb4f; color:white; }
button { padding:5px 10px; border:none; cursor:pointer; }
.assign { background:#28a745; color:white; }
.unassign { background:#dc3545; color:white; }
.logout { float:right; background:linear-gradient(to right,#b08116,#99bb4f); color:white; padding:1px 2px; border-radius:5px; text-decoration:none; font-size: 25px;}
.message { font-weight:bold; margin:10px 0; color:green; }
select { padding:5px; }
</style>
</head>
<body>

<h1>Admin Dashboard
<a href="logout.php" class="logout">Logout</a>
</h1>

<?php if($message): ?>
<p class="message"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<!-- STATISTICS -->
<div class="card-container">
    <div class="card">
        <h2><?= $total_assets ?></h2>
        <p>Total Laptops</p>
    </div>
    <div class="card">
        <h2><?= $assigned_assets ?></h2>
        <p>Assigned</p>
    </div>
    <div class="card">
        <h2><?= $unassigned_assets ?></h2>
        <p>Available</p>
    </div>
</div>

<!-- LAPTOP TABLE -->
<h2>ICT Assets</h2>
<table>
<tr>
<th>Asset Tag</th>
<th>Serial</th>
<th>Brand</th>
<th>Model</th>
<th>Status</th>
<th>Assigned To</th>
<th>Action</th>
</tr>

<?php foreach($laptops as $lap): ?>
<tr>
<td><?= htmlspecialchars($lap['asset_tag']) ?></td>
<td><?= htmlspecialchars($lap['serial_number']) ?></td>
<td><?= htmlspecialchars($lap['brand']) ?></td>
<td><?= htmlspecialchars($lap['model']) ?></td>
<td><?= htmlspecialchars($lap['status']) ?></td>
<td>
<?= $lap['full_name'] ? htmlspecialchars($lap['full_name']) : 'Unassigned' ?>
</td>
<td>

<?php if(!$lap['assigned_to']): ?>
<form method="POST" style="display:inline;">
<input type="hidden" name="laptop_id" value="<?= $lap['id'] ?>">
<select name="user_id" required>
<option value="">Select User</option>
<?php foreach($available_users as $user): ?>
<option value="<?= $user['id'] ?>">
<?= htmlspecialchars($user['full_name']) ?>
</option>
<?php endforeach; ?>
</select>
<button type="submit" name="assign_laptop" class="assign">Assign</button>
</form>
<?php else: ?>
<form method="POST" style="display:inline;">
<input type="hidden" name="laptop_id" value="<?= $lap['id'] ?>">
<button type="submit" name="unassign_laptop" class="unassign">Unassign</button>
</form>
<?php endif; ?>

</td>
</tr>
<?php endforeach; ?>
</table>

</body>
</html>