<?php
require 'db.php';
session_start();

/* ===============================
   ACCESS CONTROL
=================================*/
if(!isset($_SESSION['role']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'super_admin')){
    header("Location: login.php");
    exit();
}

$message="";

/* ===============================
// ADD SOFTWARE LOGIC (same as your previous code)
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
   FETCH SOFTWARES AND STATS
=================================*/
$today=date("Y-m-d");
$warning=date("Y-m-d",strtotime("+30 days"));

$total=$conn->query("SELECT COUNT(*) as t FROM softwares")->fetch_assoc()['t'];
$expired=$conn->query("SELECT COUNT(*) as t FROM softwares WHERE expiry_date<'$today'")->fetch_assoc()['t'];
$expiring=$conn->query("SELECT COUNT(*) as t FROM softwares WHERE expiry_date BETWEEN '$today' AND '$warning'")->fetch_assoc()['t'];

$alerts=$conn->query("SELECT software_name,expiry_date FROM softwares WHERE expiry_date BETWEEN '$today' AND '$warning'");

$where="WHERE 1=1";
if(!empty($_GET['software'])) $where.=" AND software_name LIKE '%".$_GET['software']."%'";
if(!empty($_GET['vendor'])) $where.=" AND vendor LIKE '%".$_GET['vendor']."%'";
if(!empty($_GET['status'])){
    if($_GET['status']=="active") $where.=" AND expiry_date >= '$today'";
    if($_GET['status']=="expired") $where.=" AND expiry_date < '$today'";
}

$result=$conn->query("SELECT * FROM softwares $where ORDER BY expiry_date ASC");
?>

<!DOCTYPE html>
<html>
<head>
<title>IRA Software Dashboard</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body{
    font-family: 'Segoe UI', Arial, sans-serif;
    margin:0;
    background:#f4f6f9;
}

/* HEADER */
.header{
    background: linear-gradient(90deg, #b08116, #99bb4f);
    color:white;
    padding:20px 30px;
    font-size:24px;
    font-weight:bold;
    box-shadow:0 4px 8px rgba(0,0,0,0.1);
}

/* CONTAINER */
.container{
    width:95%;
    margin:auto;
    padding:25px;
}

/* CARDS */
.dashboard{
    display:flex;
    gap:20px;
    margin-bottom:30px;
}

.card{
    flex:1;
    background:linear-gradient(145deg, #b08116, #99bb4f);
    padding:25px;
    border-radius:12px;
    color:white;
    box-shadow:0 4px 15px rgba(0,0,0,0.15);
    transition:0.3s;
    text-align:center;
}
.card:hover{
    transform:translateY(-5px);
}
.card h3{
    margin:0 0 10px 0;
    font-size:18px;
}
.card p{
    font-size:28px;
    font-weight:bold;
}

/* ALERTS */
.alert{
    background:#ffe6e6;
    border-left:6px solid #b08116;
    padding:15px 20px;
    border-radius:6px;
    margin-bottom:25px;
}

/* FORM */
form{
    background:white;
    padding:25px;
    border-radius:10px;
    box-shadow:0 4px 12px rgba(0,0,0,0.1);
    margin-bottom:30px;
}
form h3{
    margin-top:0;
    color:#333;
    margin-bottom:15px;
}
input,select,textarea{
    width:100%;
    padding:10px;
    margin-top:8px;
    margin-bottom:15px;
    border:1px solid #ccc;
    border-radius:6px;
}
button{
    background:#99bb4f;
    color:white;
    border:none;
    padding:12px 20px;
    font-size:16px;
    cursor:pointer;
    border-radius:6px;
    transition:0.3s;
}
button:hover{
    background:#b08116;
}

/* SEARCH */
.search input, .search select{
    display:inline-block;
    width:auto;
    margin-right:10px;
}

/* TABLE */
table{
    width:100%;
    border-collapse:collapse;
    background:white;
    border-radius:10px;
    overflow:hidden;
    box-shadow:0 4px 10px rgba(0,0,0,0.1);
}
th,td{
    padding:12px;
    text-align:left;
    border-bottom:1px solid #ddd;
}
th{
    background:linear-gradient(90deg,#b08116,#99bb4f);
    color:white;
}
tr:hover{
    background:#f1f1f1;
}

.active-text{
    color:green;
    font-weight:bold;
}
.expired-text{
    color:red;
    font-weight:bold;
}
.delete{
    color:red;
    text-decoration:none;
    font-weight:bold;
}
</style>

</head>
<body>

<div class="header">
<i class="fa fa-box"></i> IRA Software Subscription Dashboard
</div>

<div class="container">

<?php if($message){ ?>
<div class="alert">
    <?php echo $message; ?>
</div>
<?php } ?>

<!-- DASHBOARD CARDS -->
<div class="dashboard">
    <div class="card">
        <h3>Total Software</h3>
        <p><?php echo $total; ?></p>
    </div>
    <div class="card">
        <h3>Expiring Soon</h3>
        <p><?php echo $expiring; ?></p>
    </div>
    <div class="card">
        <h3>Expired</h3>
        <p><?php echo $expired; ?></p>
    </div>
</div>

<!-- ALERTS -->
<?php if($alerts->num_rows>0){ ?>
<div class="alert">
    <b>⚠️ Renewal Reminder:</b>
    <ul>
    <?php while($a=$alerts->fetch_assoc()){ ?>
        <li><?php echo $a['software_name']; ?> expires on <b><?php echo $a['expiry_date']; ?></b></li>
    <?php } ?>
    </ul>
</div>
<?php } ?>

<!-- ADD SOFTWARE FORM -->
<form method="POST">
<h3><i class="fa fa-plus-circle"></i> Add Software</h3>
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
<button name="add_software"><i class="fa fa-plus"></i> Add Software</button>
</form>

<!-- SEARCH -->
<form method="GET" class="search">
<input type="text" name="software" placeholder="Software">
<input type="text" name="vendor" placeholder="Vendor">
<select name="status">
    <option value="">Status</option>
    <option value="active">Active</option>
    <option value="expired">Expired</option>
</select>
<button><i class="fa fa-search"></i> Search</button>
</form>

<!-- EXPORT -->
<div class="export" style="margin:20px 0;">
<a href="export_softwares_excel.php"><button><i class="fa fa-file-excel"></i> Export Excel</button></a>
<a href="export_softwares_pdf.php"><button><i class="fa fa-file-pdf"></i> Export PDF</button></a>
</div>

<!-- SOFTWARE TABLE -->
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

<?php while($row=$result->fetch_assoc()){
$status="Active"; $class="active-text";
if($row['expiry_date'] < date("Y-m-d")){ $status="Expired"; $class="expired-text"; }
?>
<tr>
<td><?php echo $row['id']; ?></td>
<td><?php echo $row['software_name']; ?></td>
<td><?php echo $row['vendor']; ?></td>
<td><?php echo $row['license_type']; ?></td>
<td><?php echo $row['total_licenses']; ?></td>
<td><?php echo $row['used_licenses']; ?></td>
<td><?php echo $row['expiry_date']; ?></td>
<td class="<?php echo $class; ?>"><?php echo $status; ?></td>
<td><a class="delete" href="software_dashboard.php?delete=<?php echo $row['id']; ?>" onclick="return confirm('Delete software?')">Delete</a></td>
</tr>
<?php } ?>

</table>
</div>
</body>
</html>