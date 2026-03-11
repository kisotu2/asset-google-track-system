```php
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

$current_page = basename($_SERVER['PHP_SELF']);
$message = "";
$admin_id = $_SESSION['user_id'];

/* ================= ASSIGN LAPTOP ================= */
if(isset($_POST['assign_laptop'])){

    $laptop_id = intval($_POST['laptop_id']);
    $new_user_id = intval($_POST['user_id']);

    $check = $conn->prepare("SELECT assigned_to FROM laptops WHERE id=?");
    $check->bind_param("i",$laptop_id);
    $check->execute();
    $old = $check->get_result()->fetch_assoc();
    $old_user_id = $old['assigned_to'];

    if($old_user_id){
        $history = $conn->prepare("INSERT INTO laptop_history (laptop_id,user_id,admin_id,action_type) VALUES (?,?,?,'Reassigned')");
        $history->bind_param("iii",$laptop_id,$old_user_id,$admin_id);
        $history->execute();
    }

    $stmt = $conn->prepare("UPDATE laptops SET assigned_to=?, status='Assigned' WHERE id=?");
    $stmt->bind_param("ii",$new_user_id,$laptop_id);
    $stmt->execute();

    $history = $conn->prepare("INSERT INTO laptop_history (laptop_id,user_id,admin_id,action_type) VALUES (?,?,?,'Assigned')");
    $history->bind_param("iii",$laptop_id,$new_user_id,$admin_id);
    $history->execute();

    $message = "Laptop assigned successfully!";
}

/* ================= UNASSIGN ================= */
if(isset($_POST['unassign_laptop'])){

    $laptop_id = intval($_POST['laptop_id']);

    $getUser = $conn->prepare("SELECT assigned_to FROM laptops WHERE id=?");
    $getUser->bind_param("i",$laptop_id);
    $getUser->execute();
    $result = $getUser->get_result()->fetch_assoc();
    $user_id = $result['assigned_to'];

    $stmt = $conn->prepare("UPDATE laptops SET assigned_to=NULL, status='Retired' WHERE id=?");
    $stmt->bind_param("i",$laptop_id);
    $stmt->execute();

    $history = $conn->prepare("INSERT INTO laptop_history (laptop_id,user_id,admin_id,action_type) VALUES (?,?,?,'Unassigned & Retired')");
    $history->bind_param("iii",$laptop_id,$user_id,$admin_id);
    $history->execute();

    $message = "Laptop unassigned and retired.";
}

/* ================= DASHBOARD STATS ================= */
$total_assets = $conn->query("SELECT COUNT(*) as total FROM laptops")->fetch_assoc()['total'];
$assigned_assets = $conn->query("SELECT COUNT(*) as total FROM laptops WHERE assigned_to IS NOT NULL")->fetch_assoc()['total'];
$available_assets = $conn->query("SELECT COUNT(*) as total FROM laptops WHERE assigned_to IS NULL AND status='Available'")->fetch_assoc()['total'];
$retired_assets = $conn->query("SELECT COUNT(*) as total FROM laptops WHERE status='Retired'")->fetch_assoc()['total'];
$disposed_assets = $conn->query("SELECT COUNT(*) as total FROM laptops WHERE status='Disposed'")->fetch_assoc()['total'];

/* ================= USERS WITHOUT LAPTOP ================= */
$available_users = [];
$result = $conn->query("
SELECT u.id, u.full_name 
FROM users u
LEFT JOIN laptops l ON u.id = l.assigned_to
WHERE l.assigned_to IS NULL 
AND u.role='user'
AND u.status='active'
");

while($row = $result->fetch_assoc()){
    $available_users[] = $row;
}

/* ================= FILTER ================= */
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$whereClause = "";

switch($filter){

    case 'assigned':
        $whereClause = "WHERE l.assigned_to IS NOT NULL";
        break;

    case 'available':
        $whereClause = "WHERE l.assigned_to IS NULL AND l.status='Available'";
        break;

    case 'retired':
        $whereClause = "WHERE l.status='Retired'";
        break;

    case 'disposed':
        $whereClause = "WHERE l.status='Disposed'";
        break;

    default:
        $whereClause = "";
}

/* ================= FETCH LAPTOPS ================= */
$laptops = [];

$result2 = $conn->query("
SELECT l.*, u.full_name 
FROM laptops l 
LEFT JOIN users u ON l.assigned_to = u.id 
$whereClause 
ORDER BY l.created_at DESC
");

while($row = $result2->fetch_assoc()){
    $laptops[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>

<title>Admin Dashboard</title>

<style>

body{
margin:0;
font-family:Arial;
background:#f4f6f9;
}

/* LAYOUT */
.wrapper{
display:flex;
}

/* SIDEBAR */
.sidebar{
width:230px;
height:100vh;
background:linear-gradient(180deg,#99bb4f,#b08116);
color:white;
position:fixed;
left:0;
top:0;
padding-top:20px;
}

.sidebar h2{
text-align:center;
margin-bottom:30px;
}

.sidebar a{
display:block;
color:white;
text-decoration:none;
padding:14px 20px;
transition:0.2s;
}

.sidebar a:hover{
background:rgba(255,255,255,0.15);
}

.sidebar a.active{
background:rgba(255,255,255,0.25);
font-weight:bold;
}

/* MAIN CONTENT */
.main{
margin-left:230px;
padding:30px;
width:100%;
}

h1{
color:#b08116;
}

/* CARDS */
.card-container{
display:flex;
gap:20px;
margin-bottom:2rem;
}

.card{
background:white;
padding:20px;
border-radius:8px;
box-shadow:0 2px 6px rgba(0,0,0,0.1);
flex:1;
text-align:center;
cursor:pointer;
}

.active-card{
border:3px solid #b08116;
}

/* TABLE */
table{
width:100%;
border-collapse:collapse;
background:white;
box-shadow:0 2px 6px rgba(0,0,0,0.08);
}

th,td{
padding:10px;
border:1px solid #ccc;
}

th{
background:#99bb4f;
color:white;
}

tr:hover{
background:#f9fafb;
}

/* BUTTONS */
.assign{
background:#28a745;
color:white;
border:none;
padding:6px 12px;
cursor:pointer;
}

.unassign{
background:#dc3545;
color:white;
border:none;
padding:6px 12px;
cursor:pointer;
}

/* SEARCH BAR */
.search-bar{
padding:10px;
width:320px;
border:1px solid #ccc;
border-radius:6px;
}

.user-search-container{
position:relative;
width:200px;
}

.user-search-input{
width:100%;
padding:6px;
border:1px solid #ccc;
}

.user-search-list{
position:absolute;
width:100%;
background:white;
border:1px solid #ccc;
max-height:150px;
overflow-y:auto;
display:none;
z-index:1000;
}

.user-option{
padding:8px;
cursor:pointer;
}

.user-option:hover{
background:#f4f6f9;
}

/* STATUS COLORS */
.status-Available{color:green;font-weight:bold;}
.status-Assigned{color:blue;font-weight:bold;}
.status-Retired{color:orange;font-weight:bold;}
.status-Disposed{color:red;font-weight:bold;}

</style>

<script>

/* FILTER CARDS */
function goFilter(filter){
window.location="?filter="+filter;
}

/* SEARCH LAPTOPS */
function searchLaptop(){

let input = document.getElementById("searchLaptop").value.toLowerCase();
let rows = document.querySelectorAll("tbody tr");

rows.forEach(row=>{

let text = row.textContent.toLowerCase();
row.style.display = text.includes(input) ? "" : "none";

});

}

/* USER SEARCH */
function filterUsers(input){

let container=input.parentElement;
let list=container.querySelector(".user-search-list");
let options=list.querySelectorAll(".user-option");

let filter=input.value.toLowerCase();

list.style.display="block";

options.forEach(opt=>{
opt.style.display=opt.textContent.toLowerCase().includes(filter) ? "block":"none";
});

}

function selectUser(el){

let container=el.closest(".user-search-container");
container.querySelector(".user-search-input").value=el.textContent;
container.querySelector("input[type=hidden]").value=el.dataset.id;
container.querySelector(".user-search-list").style.display="none";

}

</script>

</head>

<body>

<div class="wrapper">

<!-- SIDEBAR -->
<div class="sidebar">

<h2>IRA Assets</h2>

<a href="admin_dashboard.php" class="<?= $current_page=='admin_dashboard.php'?'active':'' ?>">🏠 Dashboard</a>

<a href="issue_software.php" class="<?= $current_page=='issue_software.php'?'active':'' ?>">💾 Issue Software</a>

<a href="history.php" class="<?= $current_page=='history.php'?'active':'' ?>">📜 Asset History</a>

<a href="logout.php">🚪 Logout</a>

</div>

<!-- MAIN -->
<div class="main">

<h1>Admin Dashboard</h1>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px">

<h2>ICT Assets</h2>

<input type="text" id="searchLaptop" class="search-bar" placeholder="Search assets or users..." onkeyup="searchLaptop()">

</div>

<!-- CARDS -->
<div class="card-container">

<div class="card <?= $filter=='all'?'active-card':'' ?>" onclick="goFilter('all')">
<h2><?= $total_assets ?></h2>
<p>Total</p>
</div>

<div class="card <?= $filter=='available'?'active-card':'' ?>" onclick="goFilter('available')">
<h2><?= $available_assets ?></h2>
<p>Available</p>
</div>

<div class="card <?= $filter=='assigned'?'active-card':'' ?>" onclick="goFilter('assigned')">
<h2><?= $assigned_assets ?></h2>
<p>Assigned</p>
</div>

<div class="card <?= $filter=='retired'?'active-card':'' ?>" onclick="goFilter('retired')">
<h2><?= $retired_assets ?></h2>
<p>Retired</p>
</div>

<div class="card <?= $filter=='disposed'?'active-card':'' ?>" onclick="goFilter('disposed')">
<h2><?= $disposed_assets ?></h2>
<p>Disposed</p>
</div>

</div>

<table>

<thead>
<tr>
<th>Asset Tag</th>
<th>Serial</th>
<th>Brand</th>
<th>Model</th>
<th>Status</th>
<th>Assigned To</th>
<th>Action</th>
</tr>
</thead>

<tbody>

<?php foreach($laptops as $lap): ?>

<tr>

<td><?= htmlspecialchars($lap['asset_tag']) ?></td>
<td><?= htmlspecialchars($lap['serial_number']) ?></td>
<td><?= htmlspecialchars($lap['brand']) ?></td>
<td><?= htmlspecialchars($lap['model']) ?></td>

<td class="status-<?= $lap['status'] ?>">
<?= htmlspecialchars($lap['status']) ?>
</td>

<td>
<?= $lap['full_name'] ? htmlspecialchars($lap['full_name']) : 'Unassigned' ?>
</td>

<td>

<?php if(!$lap['assigned_to'] && $lap['status']=='Available'): ?>

<form method="POST">

<input type="hidden" name="laptop_id" value="<?= $lap['id'] ?>">

<div class="user-search-container">

<input type="text" placeholder="Search user..." class="user-search-input" onkeyup="filterUsers(this)">

<div class="user-search-list">

<?php foreach($available_users as $user): ?>

<div class="user-option" data-id="<?= $user['id'] ?>" onclick="selectUser(this)">
<?= htmlspecialchars($user['full_name']) ?>
</div>

<?php endforeach; ?>

</div>

<input type="hidden" name="user_id" required>

</div>

<button type="submit" name="assign_laptop" class="assign">Assign</button>

</form>

<?php elseif($lap['assigned_to']): ?>

<form method="POST">

<input type="hidden" name="laptop_id" value="<?= $lap['id'] ?>">

<button type="submit" name="unassign_laptop" class="unassign">Unassign</button>

</form>

<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

</body>
</html>
```
