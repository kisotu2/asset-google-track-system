<?php
require 'db.php';
session_start();

if(!isset($_SESSION['role']) || 
   ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'super_admin')){
    header("Location: login.php");
    exit();
}

$message = "";

/* ==========================
   ASSIGN DEVICE
========================== */
if(isset($_POST['assign_device'])){
    $laptop_id = intval($_POST['laptop_id']);
    $user_id   = intval($_POST['user_id']);

    $stmt = $conn->prepare("UPDATE laptops SET assigned_to=? WHERE id=?");
    $stmt->bind_param("ii",$user_id,$laptop_id);
    $stmt->execute();

    $message = "Device assigned successfully!";
}

/* ==========================
   UNASSIGN DEVICE
========================== */
if(isset($_POST['unassign_device'])){
    $laptop_id = intval($_POST['laptop_id']);

    $stmt = $conn->prepare("UPDATE laptops SET assigned_to=NULL WHERE id=?");
    $stmt->bind_param("i",$laptop_id);
    $stmt->execute();

    $message = "Device unassigned successfully!";
}

/* ==========================
   FETCH USERS (INCLUDING ADMINS)
========================== */
$users = [];
$result = $conn->query("
    SELECT id, full_name, role 
    FROM users 
    WHERE status='active'
    ORDER BY full_name
");

while($row = $result->fetch_assoc()){
    $users[] = $row;
}

/* ==========================
   FETCH LAPTOPS
========================== */
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
<title>Assign Devices</title>
<style>
body { font-family: Arial; background:#f4f6f9; padding:2rem; }
h1 { color:#b08116; }
table { width:100%; border-collapse:collapse; margin-top:20px; }
th, td { padding:10px; border:1px solid #ccc; }
th { background:#99bb4f; color:white; }
button { padding:5px 10px; border:none; cursor:pointer; }
.assign { background:#28a745; color:white; }
.unassign { background:#dc3545; color:white; }
.logout { float:right; background:#dc3545; color:white; padding:8px 12px; text-decoration:none; border-radius:5px; }
.message { color:green; font-weight:bold; }
select { padding:5px; }
</style>
</head>
<body>

<h1>Assign Devices
<a href="logout.php" class="logout">Logout</a>
</h1>

<?php if($message): ?>
<p class="message"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

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
<form method="POST">
<input type="hidden" name="laptop_id" value="<?= $lap['id'] ?>">
<select name="user_id" required>
<option value="">Select User</option>
<?php foreach($users as $user): ?>
<option value="<?= $user['id'] ?>">
<?= htmlspecialchars($user['full_name']) ?> (<?= $user['role'] ?>)
</option>
<?php endforeach; ?>
</select>
<button type="submit" name="assign_device" class="assign">Assign</button>
</form>
<?php else: ?>
<form method="POST">
<input type="hidden" name="laptop_id" value="<?= $lap['id'] ?>">
<button type="submit" name="unassign_device" class="unassign">Unassign</button>
</form>
<?php endif; ?>

</td>
</tr>
<?php endforeach; ?>

</table>

</body>
</html>