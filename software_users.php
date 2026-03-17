<?php
require 'db.php';
session_start();

if(!isset($_GET['software_id'])){
    header("Location: software_reports.php");
    exit();
}

$software_id = intval($_GET['software_id']);

/* GET SOFTWARE NAME */
$soft = $conn->query("SELECT software_name FROM softwares WHERE id=$software_id")->fetch_assoc();

/* FETCH USERS */
$query = "
SELECT u.full_name, u.email, sa.assigned_date
FROM software_assignments sa
JOIN users u ON sa.user_id = u.id
WHERE sa.software_id = $software_id
";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
<title>Software Users</title>

<style>
body{font-family:Segoe UI;background:#f4f6f9;margin:0}
.container{padding:30px}

table{
width:100%;
border-collapse:collapse;
background:white;
}
th,td{padding:12px;border-bottom:1px solid #ddd}
th{background:#b08116;color:white}
</style>
</head>

<body>

<div class="container">

<h2>Users with access to: <?php echo $soft['software_name']; ?></h2>

<table>
<tr>
<th>Name</th>
<th>Email</th>
<th>Assigned Date</th>
</tr>

<?php while($row=$result->fetch_assoc()){ ?>
<tr>
<td><?php echo $row['full_name']; ?></td>
<td><?php echo $row['email']; ?></td>
<td><?php echo $row['assigned_date']; ?></td>
</tr>
<?php } ?>

</table>

<br>
<a href="software_reports.php">⬅ Back to Reports</a>

</div>

</body>
</html>