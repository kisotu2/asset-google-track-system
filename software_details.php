<?php
require 'db.php';
session_start();

// Ensure user is logged in
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

// Check if ID is provided
if(!isset($_GET['id'])){
    header("Location: admin_dashboard.php");
    exit();
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM softwares WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0){
    echo "<h2>Software not found</h2>";
    exit();
}

$software = $result->fetch_assoc();

// Calculate available licenses
$available = $software['total_licenses'] - $software['used_licenses'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Software Details</title>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f0f2f5;
    margin: 0;
    padding: 0;
}

.container {
    max-width: 600px;
    margin: 50px auto;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    padding: 30px 40px;
    position: relative;
}

h2 {
    text-align: center;
    margin-bottom: 25px;
    color: #333;
}

.detail {
    margin-bottom: 15px;
    font-size: 16px;
    line-height: 1.5;
    color: #555;
}

.label {
    font-weight: 600;
    color: #222;
}

.backBtn {
    position: absolute;
    top: 20px;
    right: 20px;
    padding: 8px 16px;
    background: #007bff;
    border: none;
    color: white;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    transition: background 0.2s ease;
}

.backBtn:hover {
    background: #0056b3;
}

.license-bar-container {
    background: #e0e0e0;
    border-radius: 10px;
    overflow: hidden;
    height: 20px;
    margin-top: 5px;
}

.license-bar-used {
    background: #28a745;
    height: 100%;
    width: <?php echo $software['used_licenses'] / max($software['total_licenses'],1) * 100; ?>%;
    transition: width 0.5s ease;
}

.license-text {
    font-size: 14px;
    margin-top: 5px;
    color: #333;
}
</style>

</head>
<body>

<div class="container">

<button class="backBtn" onclick="window.location.href='super_dashboard.php'">← Back</button>

<h2><?php echo $software['software_name']; ?></h2>

<div class="detail"><span class="label">Vendor:</span> <?php echo $software['vendor'] ?? 'N/A'; ?></div>
<div class="detail"><span class="label">License Type:</span> <?php echo $software['license_type'] ?? 'N/A'; ?></div>
<div class="detail"><span class="label">Total Licenses:</span> <?php echo $software['total_licenses'] ?? 0; ?></div>
<div class="detail"><span class="label">Used Licenses:</span> <?php echo $software['used_licenses'] ?? 0; ?></div>
<div class="detail">
    <span class="label">Available Licenses:</span> <?php echo $available; ?>
    <div class="license-bar-container">
        <div class="license-bar-used"></div>
    </div>
    <div class="license-text"><?php echo $software['used_licenses']; ?> / <?php echo $software['total_licenses']; ?> used</div>
</div>
<div class="detail"><span class="label">Purchase Date:</span> <?php echo $software['purchase_date'] ?? 'N/A'; ?></div>
<div class="detail"><span class="label">Expiry Date:</span> <?php echo $software['expiry_date'] ?? 'N/A'; ?></div>
<div class="detail"><span class="label">Cost:</span> $<?php echo $software['cost'] ?? 0; ?></div>
<div class="detail"><span class="label">Notes:</span> <?php echo $software['notes'] ?? 'No notes available'; ?></div>

</div>

</body>
</html>