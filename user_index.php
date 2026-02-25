<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM laptops WHERE assigned_to = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>My Assigned Devices</title>

<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f4f6f9;
}

.header {
    background: linear-gradient(to right, #b08116, #99bb4f);
    padding: 15px 30px;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header h2 {
    margin: 0;
}

.logout-btn {
    background: white;
    color: #b08116;
    padding: 8px 15px;
    text-decoration: none;
    border-radius: 5px;
    font-weight: bold;
}

.logout-btn:hover {
    background: #f1f1f1;
}

.container {
    width: 90%;
    max-width: 1000px;
    margin: 40px auto;
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

table {
    width: 100%;
    border-collapse: collapse;
}

table thead {
    background: #b08116;
    color: white;
}

table th, table td {
    padding: 12px;
    text-align: left;
}

table th {
    font-weight: bold;
}

table tr:nth-child(even) {
    background: #f2f2f2;
}

table tr:hover {
    background: #e9f5dc;
}

.no-data {
    text-align: center;
    padding: 20px;
    color: #999;
}
</style>

</head>
<body>

<div class="header">
    <h2>My Assigned Devices</h2>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

<div class="container">

<table>
    <thead>
        <tr>
            <th>Asset Tag</th>
            <th>Serial Number</th>
            <th>Brand</th>
            <th>Model</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>

    <?php if($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['asset_tag']) ?></td>
            <td><?= htmlspecialchars($row['serial_number']) ?></td>
            <td><?= htmlspecialchars($row['brand']) ?></td>
            <td><?= htmlspecialchars($row['model']) ?></td>
            <td><?= htmlspecialchars($row['status']) ?></td>
        </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="5" class="no-data">No devices assigned to you.</td>
        </tr>
    <?php endif; ?>

    </tbody>
</table>

</div>

</body>
</html>