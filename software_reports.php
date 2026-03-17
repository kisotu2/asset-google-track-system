<?php
require 'db.php';
session_start();

if(!isset($_SESSION['role'])){
    header("Location: login.php");
    exit();
}

/* FETCH SOFTWARE SUMMARY */
$query = "
SELECT s.id, s.software_name, s.vendor,
       s.total_licenses,
       COUNT(sa.id) as used_licenses
FROM softwares s
LEFT JOIN software_assignments sa ON s.id = sa.software_id
GROUP BY s.id
ORDER BY s.software_name ASC
";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
<title>Software Reports</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body{font-family:Segoe UI;background:#f4f6f9;margin:0}
.container{padding:30px}

h2{margin-bottom:20px}

table{
width:100%;
border-collapse:collapse;
background:white;
box-shadow:0 4px 10px rgba(0,0,0,0.1);
}
th,td{padding:12px;border-bottom:1px solid #ddd}
th{background:#b08116;color:white}

tr:hover{background:#f1f1f1; cursor:pointer}

.card{
background:white;
padding:20px;
border-radius:10px;
margin-bottom:20px;
box-shadow:0 4px 10px rgba(0,0,0,0.1);
}


</style>
</head>

<body>

<div class="container">

<h2><i class="fa fa-chart-line"></i> Software Reports</h2>

<div class="card">
<table>
<tr>
<th>Software</th>
<th>Vendor</th>
<th>Total Licenses</th>
<th>Used</th>
<th>Available</th>
<th>Users Assigned</th>
</tr>
<a href="software_dashboard.php" style="
    display:inline-block;
    background:#6c757d;
    color:white;
    padding:10px 15px;
    border-radius:6px;
    text-decoration:none;
    margin-bottom:15px;
">
    <i class="fa fa-arrow-left"></i> Back
</a>

<?php while($row=$result->fetch_assoc()){ 
$available = $row['total_licenses'] - $row['used_licenses'];
?>

<tr onclick="viewDetails(<?php echo $row['id']; ?>)">
<td><?php echo $row['software_name']; ?></td>
<td><?php echo $row['vendor']; ?></td>
<td><?php echo $row['total_licenses']; ?></td>
<td><?php echo $row['used_licenses']; ?></td>
<td><?php echo $available; ?></td>
<td><?php echo $row['used_licenses']; ?></td>
</tr>

<?php } ?>
</table>
</div>

</div>

<script>
function viewDetails(id){
    window.location.href = "software_users.php?software_id=" + id;
}
</script>
<script>
function goBack(){
    window.history.back();
}
</script>
</body>
</html>