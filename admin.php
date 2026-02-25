<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
?>

<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user']) || 
   ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin')) {
    header("Location: login.php");
    exit();
}
$laptops = getLaptops();
?>
<?php if($_SESSION['role'] === 'super_admin'): ?>
<a href="register_admin.php" style="color:blue;">Manage Admins</a>
<?php endif; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laptop Administration</title>

<style>
body { font-family: Arial,sans-serif; margin:2rem; background:#f5f5f5; }
h1 { color:#b08116; }

nav { margin-bottom:20px; }
nav a { margin-right:15px; color:#b08116; text-decoration:none; font-weight:bold; }

select.process-select {
    width:250px;
    padding:8px;
    margin:15px 0;
    font-size:16px;
}

input, select, button {
    width:100%;
    padding:0.5rem;
    margin:0.2rem 0;
}

button {
    background:#99bb4f;
    color:white;
    border:none;
    cursor:pointer;
}

button:hover { opacity:0.9; }

table { width:100%; border-collapse:collapse; margin-top:1rem; }
th, td { padding:0.8rem; border:1px solid #ccc; text-align:left; }
th { background:#b08116; color:white; }
tr:nth-child(even) { background:#f0f0f0; }

.flash { padding:12px; border-radius:6px; font-weight:bold; color:white; margin-bottom:10px; }
.flash-success { background-color:#28a745; }
.flash-error { background-color:#dc3545; }

.section { display:none; margin-top:20px; }
.section.active { display:block; }

.card {
    background:white;
    padding:1rem;
    border-radius:8px;
    box-shadow:0 2px 6px rgba(0,0,0,0.1);
}
</style>
</head>
<body>

<h1>Laptop Administration</h1>

<nav>
<a href="index.php">Home</a>
</nav>

<!-- Flash Messages -->
<?php if(isset($_SESSION['flash'])): ?>
<div class="flash <?php echo $_SESSION['flash_type']; ?>">
<?php 
echo $_SESSION['flash'];
unset($_SESSION['flash'], $_SESSION['flash_type']);
?>
</div>
<?php endif; ?>

<!-- PROCESS DROPDOWN -->
<label><strong>Select Process:</strong></label>
<select class="process-select" onchange="showSection(this.value)">
    <option value="">-- Choose Process --</option>
    <option value="add">Add Asset</option>
    <option value="edit">Edit Asset</option>
    <option value="delete">Delete Asset</option>
</select>

<!-- ================= ADD SECTION ================= -->
<div id="add" class="section card">
<h2>Add Laptop</h2>
<form method="POST" action="add_laptop.php">
<input name="asset_tag" placeholder="Asset Tag" required>
<input name="serial_number" placeholder="Serial Number" required>
<input name="brand" placeholder="Brand">
<input name="model" placeholder="Model">
<input name="department" placeholder="Department">
<input name="assigned_to" placeholder="Assigned To">
<select name="status">
    <option value="Active">Active</option>
    <option value="Faulty">Faulty</option>
    <option value="Retired">Retired</option>
</select>
<label>Purchase Date</label>
<input type="date" name="purchase_date">
<label>Warranty Expiry</label>
<input type="date" name="warranty_expiry">
<button type="submit">Add Laptop</button>
</form>
</div>

<!-- ================= EDIT SECTION ================= -->
<div id="edit" class="section card">
<h2>Edit Laptop</h2>
<table>
<thead>
<tr>
<th>Asset Tag</th>
<th>Serial</th>
<th>Brand</th>
<th>Model</th>
<th>Department</th>
<th>Assigned To</th>
<th>Status</th>
<th>Purchase</th>
<th>Warranty</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach($laptops as $laptop): ?>
<tr>
<form method="POST" action="edit_laptop.php?id=<?php echo $laptop['id']; ?>">
<td><input type="text" name="asset_tag" value="<?php echo htmlspecialchars($laptop['asset_tag']); ?>"></td>
<td><input type="text" name="serial_number" value="<?php echo htmlspecialchars($laptop['serial_number']); ?>"></td>
<td><input type="text" name="brand" value="<?php echo htmlspecialchars($laptop['brand']); ?>"></td>
<td><input type="text" name="model" value="<?php echo htmlspecialchars($laptop['model']); ?>"></td>
<td><input type="text" name="department" value="<?php echo htmlspecialchars($laptop['department']); ?>"></td>
<td><input type="text" name="assigned_to" value="<?php echo htmlspecialchars($laptop['assigned_to']); ?>"></td>
<td>
<select name="status">
<option value="Active" <?php if($laptop['status']=="Active") echo "selected"; ?>>Active</option>
<option value="Faulty" <?php if($laptop['status']=="Faulty") echo "selected"; ?>>Faulty</option>
<option value="Retired" <?php if($laptop['status']=="Retired") echo "selected"; ?>>Retired</option>
</select>
</td>
<td><input type="date" name="purchase_date" value="<?php echo $laptop['purchase_date']; ?>"></td>
<td><input type="date" name="warranty_expiry" value="<?php echo $laptop['warranty_expiry']; ?>"></td>
<td><button type="submit">Update</button></td>
</form>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- ================= DELETE SECTION ================= -->
<div id="delete" class="section card">
<h2>Delete Laptop</h2>
<table>
<thead>
<tr>
<th>Asset Tag</th>
<th>Serial</th>
<th>Department</th>
<th>Status</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach($laptops as $laptop): ?>
<tr>
<td><?php echo htmlspecialchars($laptop['asset_tag']); ?></td>
<td><?php echo htmlspecialchars($laptop['serial_number']); ?></td>
<td><?php echo htmlspecialchars($laptop['department']); ?></td>
<td><?php echo htmlspecialchars($laptop['status']); ?></td>
<td>
<a href="delete_laptop.php?id=<?php echo $laptop['id']; ?>" 
   onclick="return confirm('Are you sure you want to delete this asset?');"
   style="color:red; font-weight:bold;">Delete</a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<script>
function showSection(sectionId) {
    document.querySelectorAll('.section').forEach(section => {
        section.classList.remove('active');
    });

    if(sectionId){
        document.getElementById(sectionId).classList.add('active');
    }
}

// Auto-hide flash
setTimeout(()=>{
    const flash=document.querySelector(".flash");
    if(flash) flash.style.display="none";
},3000);
</script>

</body>
</html>