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

$message="";

/* ===============================
   ADD SOFTWARE
=================================*/
if(isset($_POST['add_software'])){

$name = trim($_POST['software_name']);
$vendor = trim($_POST['vendor']);
$license = $_POST['license_type'];
$total = $_POST['total_licenses'];
$purchase = $_POST['purchase_date'];
$expiry = $_POST['expiry_date'];
$cost = $_POST['cost'];
$notes = $_POST['notes'];

/* CHECK IF SOFTWARE ALREADY EXISTS */

$check = $conn->prepare("SELECT id FROM softwares WHERE software_name=? AND vendor=?");
$check->bind_param("ss",$name,$vendor);
$check->execute();
$check->store_result();

if($check->num_rows > 0){

$message = "⚠️ This software already exists in the system.";

}else{

$stmt = $conn->prepare("INSERT INTO softwares
(software_name,vendor,license_type,total_licenses,purchase_date,expiry_date,cost,notes)
VALUES (?,?,?,?,?,?,?,?)");

$stmt->bind_param("sssissds",$name,$vendor,$license,$total,$purchase,$expiry,$cost,$notes);

if($stmt->execute()){
$message = "✅ Software added successfully";
}else{
$message = "❌ Error adding software";
}

}
}

/* ===============================
   DELETE SOFTWARE
=================================*/
if(isset($_GET['delete'])){
$id = $_GET['delete'];

$stmt=$conn->prepare("DELETE FROM softwares WHERE id=?");
$stmt->bind_param("i",$id);
$stmt->execute();

header("Location: software_dashboard.php");
}

/* ===============================
   SEARCH FILTERS
=================================*/

$where="WHERE 1=1";

if(!empty($_GET['software'])){
$software=$_GET['software'];
$where.=" AND software_name LIKE '%$software%'";
}

if(!empty($_GET['vendor'])){
$vendor=$_GET['vendor'];
$where.=" AND vendor LIKE '%$vendor%'";
}

if(!empty($_GET['status'])){

$today=date("Y-m-d");

if($_GET['status']=="active"){
$where.=" AND expiry_date >= '$today'";
}

if($_GET['status']=="expired"){
$where.=" AND expiry_date < '$today'";
}

}

/* ===============================
   FETCH SOFTWARES
=================================*/

$result=$conn->query("SELECT * FROM softwares $where ORDER BY expiry_date ASC");

/* ===============================
   DASHBOARD STATS
=================================*/

$today=date("Y-m-d");
$warning=date("Y-m-d",strtotime("+30 days"));

$total=$conn->query("SELECT COUNT(*) as t FROM softwares")->fetch_assoc()['t'];

$expired=$conn->query("SELECT COUNT(*) as t FROM softwares WHERE expiry_date<'$today'")
->fetch_assoc()['t'];

$expiring=$conn->query("SELECT COUNT(*) as t FROM softwares
WHERE expiry_date BETWEEN '$today' AND '$warning'")
->fetch_assoc()['t'];

$alerts=$conn->query("SELECT software_name,expiry_date FROM softwares
WHERE expiry_date BETWEEN '$today' AND '$warning'");

?>

<!DOCTYPE html>
<html>
<head>

<title>Software Monitoring Dashboard</title>

<style>

body{
font-family:Arial;
background:#f4f6f9;
margin:0;
}

.header{
background:#003366;
color:white;
padding:15px;
font-size:22px;
}

.container{
width:95%;
margin:auto;
padding:20px;
}

/* DASHBOARD */

.dashboard{
display:flex;
gap:20px;
margin-bottom:25px;
}

.card{
flex:1;
padding:20px;
border-radius:8px;
color:white;
}

.total{background:#b08116;}
.warning{background:#99bb4f;}
.expired{background:#b08116;}

.card h3{
margin:0;
}

.card p{
font-size:30px;
}

/* ALERT */

.alert{
background:#ffe6e6;
border-left:6px solid red;
padding:15px;
margin-bottom:20px;
}

/* FORM */

form{
background:white;
padding:20px;
border-radius:6px;
margin-bottom:25px;
}

input,select,textarea{
width:100%;
padding:8px;
margin-top:5px;
margin-bottom:12px;
border:1px solid #ccc;
border-radius:4px;
}

/* BUTTON */

button{
background:#003366;
color:white;
border:none;
padding:10px 15px;
cursor:pointer;
border-radius:4px;
}

button:hover{
background:#0055aa;
}

/* TABLE */

table{
width:100%;
border-collapse:collapse;
background:beige;
}

th,td{
padding:10px;
border:1px solid #ddd;
}

th{
background:#003366;
color:white;
}

.expired-text{
color:red;
font-weight:bold;
}

.active-text{
color:green;
font-weight:bold;
}

.delete{
color:red;
text-decoration:none;
}

/* EXPORT */

.export{
margin-bottom:20px;
}

</style>

</head>

<body>

<div class="header">
IRA Software Subscription Monitoring
</div>

<div class="container">

<!-- DASHBOARD -->

<div class="dashboard">

<div class="card total">
<h3>Total Software</h3>
<p><?php echo $total; ?></p>
</div>

<div class="card warning">
<h3>Expiring Soon</h3>
<p><?php echo $expiring; ?></p>
</div>

<div class="card expired">
<h3>Expired</h3>
<p><?php echo $expired; ?></p>
</div>

</div>

<!-- ALERTS -->

<?php if($alerts->num_rows>0){ ?>

<div class="alert">

<b>Renewal Reminder</b>

<ul>

<?php while($a=$alerts->fetch_assoc()){ ?>

<li>
<?php echo $a['software_name']; ?> expires on
<b><?php echo $a['expiry_date']; ?></b>
</li>

<?php } ?>

</ul>

</div>

<?php } ?>

<!-- ADD SOFTWARE -->

<form method="POST">

<h3>Add Software</h3>

<input type="text" name="software_name" placeholder="Software Name" required>

<input type="text" name="vendor" placeholder="Vendor">

<input type="text" name="license_type" placeholder="License Type">

<input type="number" name="total_licenses" placeholder="Total Licenses">

<label>Purchase Date</label>
<input type="date" name="purchase_date">

<label>Expiry Date</label>
<input type="date" name="expiry_date">

<input type="number" step="0.01" name="cost" placeholder="Cost">

<textarea name="notes" placeholder="Notes"></textarea>

<button name="add_software">Add Software</button>

</form>

<!-- SEARCH -->

<form method="GET">

<input type="text" name="software" placeholder="Search Software">

<input type="text" name="vendor" placeholder="Vendor">

<select name="status">

<option value="">Status</option>
<option value="active">Active</option>
<option value="expired">Expired</option>

</select>

<button>Search</button>

</form>

<!-- EXPORT -->

<div class="export">

<a href="export_softwares_excel.php">
<button>Export Excel</button>
</a>

<a href="export_softwares_pdf.php">
<button>Export PDF</button>
</a>

</div>

<!-- TABLE -->

<table>

<tr>
<th>ID</th>
<th>Software</th>
<th>Vendor</th>
<th>License</th>
<th>Total</th>
<th>Used</th>
<th>Expiry</th>
<th>Status</th>
<th>Action</th>
</tr>

<?php

while($row=$result->fetch_assoc()){

$status="Active";
$class="active-text";

if($row['expiry_date'] < date("Y-m-d")){
$status="Expired";
$class="expired-text";
}

echo "<tr>";

echo "<td>".$row['id']."</td>";
echo "<td>".$row['software_name']."</td>";
echo "<td>".$row['vendor']."</td>";
echo "<td>".$row['license_type']."</td>";
echo "<td>".$row['total_licenses']."</td>";
echo "<td>".$row['used_licenses']."</td>";
echo "<td>".$row['expiry_date']."</td>";
echo "<td class='$class'>$status</td>";

echo "<td>
<a class='delete'
href='software_dashboard.php?delete=".$row['id']."'
onclick='return confirm(\"Delete software?\")'>
Delete
</a>
</td>";

echo "</tr>";

}

?>

</table>

</div>

</body>
</html>