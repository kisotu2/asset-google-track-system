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
   ADD SOFTWARE LOGIC
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
   RENEW LICENSE LOGIC
=================================*/
if(isset($_POST['renew_license'])){
    $id = $_POST['license_id'];
    $new_expiry = $_POST['new_expiry_date'];
    $stmt = $conn->prepare("UPDATE softwares SET expiry_date=? WHERE id=?");
    $stmt->bind_param("si", $new_expiry, $id);
    if($stmt->execute()){
        $message = "✅ License renewed successfully";
    }else{
        $message = "❌ Error renewing license";
    }
}

/* ===============================
   DELETE LICENSE LOGIC
=================================*/
if(isset($_POST['delete_license'])){
    $id = $_POST['license_id'];
    $stmt = $conn->prepare("DELETE FROM softwares WHERE id=?");
    $stmt->bind_param("i", $id);
    if($stmt->execute()){
        $message = "✅ License deleted successfully";
    }else{
        $message = "❌ Error deleting license";
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
$available=$conn->query("SELECT COUNT(*) as t FROM softwares WHERE expiry_date >= '$today'")->fetch_assoc()['t'];

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
/* BODY & RESET */
body{
    font-family: 'Segoe UI', Arial, sans-serif;
    margin:0;
    background:#f4f6f9;
    display:flex;
}

/* SIDEBAR */
.sidebar{
    width:220px;
    background:linear-gradient(180deg,#99bb4f,#b08116);
    color:white;
    height:100vh;
    position:fixed;
    top:0;
    left:0;
    display:flex;
    flex-direction:column;
}
.sidebar h2{
    text-align:center;
    padding:20px 0;
    font-size:20px;
    border-bottom:1px solid rgba(255,255,255,0.2);
}
.sidebar a{
    padding:15px 20px;
    color:white;
    text-decoration:none;
    font-size:16px;
    border-bottom:1px solid rgba(255,255,255,0.1);
    transition:0.2s;
    cursor:pointer;
}
.sidebar a:hover{
    background:#1d2731;
}

/* MAIN CONTENT */
.main{
    margin-left:220px;
    width:calc(100% - 220px);
    padding:20px 30px;
}

/* HEADER */
.header{
    font-size:24px;
    font-weight:bold;
    color:#333;
    margin-bottom:25px;
}

/* DASHBOARD CARDS */
.cards{
    display:flex;
    gap:20px;
    flex-wrap:wrap;
    margin-bottom:30px;
}
.card{
    flex:1;
    min-width:180px;
    background:#99bb4f;
    padding:20px;
    border-radius:12px;
    color:white;
    text-align:center;
    box-shadow:0 4px 12px rgba(0,0,0,0.1);
    transition:0.3s;
}
.card:hover{
    transform:translateY(-5px);
}
.card h3{
    margin:0 0 10px 0;
    font-size:16px;
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
    background:#b08116;
    color:white;
}
tr:hover{
    background:#f1f1f1;
}
.active-text{ color:green; font-weight:bold; }
.expired-text{ color:red; font-weight:bold; }
.delete{ color:red; text-decoration:none; font-weight:bold; }

/* MODALS */
.modal{
    display:none;
    position:fixed;
    z-index:1000;
    left:0; top:0;
    width:100%; height:100%;
    overflow:auto;
    background:rgba(0,0,0,0.5);
}
.modal-content{
    background:white;
    margin:10% auto;
    padding:20px;
    border-radius:10px;
    width:400px;
    position:relative;
}
.close{
    position:absolute;
    top:10px; right:15px;
    font-size:22px;
    cursor:pointer;
    color:#333;
}
</style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">

<h2>License Management</h2>

<a onclick="openModal('addModal')">
<i class="fa fa-plus-circle"></i> Add License
</a>

<a onclick="openModal('renewModal')">
<i class="fa fa-sync-alt"></i> Renew License
</a>

<a onclick="openModal('deleteModal')">
<i class="fa fa-trash"></i> Delete License
</a>

<a href="#reports">
<i class="fa fa-chart-line"></i> Reports
</a>

<!-- Spacer to push buttons to bottom -->
<div style="flex-grow:1;"></div>

<a href="admin_dashboard.php">
<i class="fa fa-arrow-left"></i> Back
</a>

<a href="logout.php">
<i class="fa fa-sign-out-alt"></i> Logout
</a>

</div>

<!-- MAIN CONTENT -->
<div class="main">

<div class="header"><i class="fa fa-box"></i> IRA Software Subscription Dashboard</div>

<?php if($message){ ?>
<div class="alert"><?php echo $message; ?></div>
<?php } ?>

<!-- DASHBOARD CARDS -->
<div class="cards">
    <div class="card">
        <h3>Total Licenses</h3>
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
    <div class="card">
        <h3>Available</h3>
        <p><?php echo $available; ?></p>
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
</tr>
<?php } ?>
</table>
</div>

<!-- MODALS -->
<div id="addModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeModal('addModal')">&times;</span>
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
  </div>
</div>

<div id="renewModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeModal('renewModal')">&times;</span>
    <form method="POST">
      <h3><i class="fa fa-sync-alt"></i> Renew License</h3>
      <input type="number" name="license_id" placeholder="License ID" required>
      <label>New Expiry Date</label>
      <input type="date" name="new_expiry_date" required>
      <button name="renew_license"><i class="fa fa-sync-alt"></i> Renew</button>
    </form>
  </div>
</div>

<div id="deleteModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeModal('deleteModal')">&times;</span>
    <form method="POST">
      <h3><i class="fa fa-trash"></i> Delete License</h3>
      <input type="number" name="license_id" placeholder="License ID" required>
      <button name="delete_license"><i class="fa fa-trash"></i> Delete</button>
    </form>
  </div>
</div>

<script>
function openModal(id){ document.getElementById(id).style.display='block'; }
function closeModal(id){ document.getElementById(id).style.display='none'; }
window.onclick = function(event){
    ['addModal','renewModal','deleteModal'].forEach(id=>{
        let modal=document.getElementById(id);
        if(event.target==modal) modal.style.display='none';
    });
}
</script>

</body>
</html>