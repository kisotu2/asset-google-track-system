<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
// index.php
require 'db.php';
$laptops = getLaptops();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laptop Dashboard</title>
<style>
body { font-family: Arial, sans-serif; margin: 2rem; background:#f5f5f5; }
h1 { color:#b08116; }
.container { display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:1rem; }
.card { background:white; border-left:6px solid #99bb4f; padding:1rem; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.1); }
.status-Active { color:green; font-weight:bold; }
.status-Faulty { color:red; font-weight:bold; }
.status-Retired { color:gray; font-weight:bold; }
nav a { margin-right:1rem; color:#b08116; text-decoration:none; font-weight:bold; }
nav a:hover { text-decoration:underline; }
</style>
</head>
<body>

<header>
<h1>Laptop Dashboard</h1>
<nav>
    <a href="index.php">Home</a>
    <a href="admin.php">Admin</a>
</nav>
</header>

<div class="container" id="dashboard">
<?php foreach($laptops as $laptop): ?>
<div class="card">
    <h3><?php echo htmlspecialchars($laptop['asset_tag']); ?></h3>
    <p><strong>Serial:</strong> <?php echo htmlspecialchars($laptop['serial_number']); ?></p>
    <p><strong>Brand:</strong> <?php echo htmlspecialchars($laptop['brand']); ?></p>
    <p><strong>Model:</strong> <?php echo htmlspecialchars($laptop['model']); ?></p>
    <p><strong>Status:</strong> <span class="status-<?php echo $laptop['status']; ?>"><?php echo $laptop['status']; ?></span></p>
    <p><strong>Department:</strong> <?php echo htmlspecialchars($laptop['department']); ?></p>
    <p><strong>Assigned To:</strong> <?php echo htmlspecialchars($laptop['assigned_to']); ?></p>
</div>
<?php endforeach; ?>
</div>

</body>
</html>