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

$message = "";
$admin_id = $_SESSION['user_id'];

/* ================= ASSIGN / ISSUE ================= */
if(isset($_POST['assign_laptop'])){
    $laptop_id = intval($_POST['laptop_id']);
    $new_user_id = intval($_POST['user_id']);

    // Check current owner
    $check = $conn->prepare("SELECT assigned_to FROM laptops WHERE id=?");
    $check->bind_param("i",$laptop_id);
    $check->execute();
    $old = $check->get_result()->fetch_assoc();
    $old_user_id = $old['assigned_to'];

    // Record Reassignment if previously assigned
    if($old_user_id){
        $history = $conn->prepare("INSERT INTO laptop_history (laptop_id,user_id,admin_id,action_type) VALUES (?,?,?,'Reassigned')");
        $history->bind_param("iii",$laptop_id,$old_user_id,$admin_id);
        $history->execute();
    }

    // Update laptop assignment
    $stmt = $conn->prepare("UPDATE laptops SET assigned_to=?, status='Assigned' WHERE id=?");
    $stmt->bind_param("ii",$new_user_id,$laptop_id);
    $stmt->execute();

    // Record history
    $history = $conn->prepare("INSERT INTO laptop_history (laptop_id,user_id,admin_id,action_type) VALUES (?,?,?,'Assigned')");
    $history->bind_param("iii",$laptop_id,$new_user_id,$admin_id);
    $history->execute();

    $message = "Laptop assigned/issued successfully!";
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

    $message = "Laptop unassigned and marked as retired!";
}

/* ================= STATS ================= */
$total_assets = $conn->query("SELECT COUNT(*) as total FROM laptops")->fetch_assoc()['total'];
$assigned_assets = $conn->query("SELECT COUNT(*) as total FROM laptops WHERE assigned_to IS NOT NULL")->fetch_assoc()['total'];
$available_assets = $conn->query("SELECT COUNT(*) as total FROM laptops WHERE assigned_to IS NULL AND status='Available'")->fetch_assoc()['total'];
$retired_assets = $conn->query("SELECT COUNT(*) as total FROM laptops WHERE status='Retired'")->fetch_assoc()['total'];
$disposed_assets = $conn->query("SELECT COUNT(*) as total FROM laptops WHERE status='Disposed'")->fetch_assoc()['total'];

/* ================= USERS WITHOUT LAPTOP ================= */
$available_users = [];
$result = $conn->query("SELECT u.id, u.full_name FROM users u
                        LEFT JOIN laptops l ON u.id = l.assigned_to
                        WHERE l.assigned_to IS NULL AND u.role='user' AND u.status='active'");
while($row = $result->fetch_assoc()){ $available_users[] = $row; }

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
$result2 = $conn->query("SELECT l.*, u.full_name FROM laptops l LEFT JOIN users u ON l.assigned_to = u.id $whereClause ORDER BY l.created_at DESC");
while($row = $result2->fetch_assoc()){ $laptops[] = $row; }
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard</title>
<style>
body { font-family: Arial; background:#f4f6f9; margin:2rem; }
h1 { color:#b08116; }
.card-container { display:flex; gap:20px; margin-bottom:2rem; }
.card { background:white; padding:20px; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.1); flex:1; text-align:center; cursor:pointer; transition:0.2s; }
.card:hover { transform:translateY(-3px); }
.card h2 { margin:0; }
.active-card { border:3px solid #b08116; }
table { width:100%; border-collapse:collapse; margin-top:1rem; }
th, td { padding:0.8rem; border:1px solid #ccc; }
th { background:#99bb4f; color:white; }
button { padding:5px 10px; border:none; cursor:pointer; }
.assign { background:#28a745; color:white; }
.unassign { background:#dc3545; color:white; }
.logout { float:right; background:linear-gradient(to right,#b08116,#99bb4f); color:white; padding:1px 2px; border-radius:5px; text-decoration:none; font-size: 25px;}
.message { font-weight:bold; margin:10px 0; color:green; }

/* USER SEARCH */
.user-search-container { position: relative; width: 200px; display:inline-block; }
.user-search-input { width:100%; padding:6px; border:1px solid #ccc; border-radius:5px; }
.user-search-list { position:absolute; width:100%; max-height:150px; overflow-y:auto; background:white; border:1px solid #ccc; border-top:none; display:none; z-index:999; }
.user-option { padding:8px; cursor:pointer; }
.user-option:hover { background:#f4f6f9; }

/* STATUS COLORS */
.status-Available { color:green; font-weight:bold; }
.status-Assigned { color:blue; font-weight:bold; }
.status-Retired { color:orange; font-weight:bold; }
.status-Disposed { color:red; font-weight:bold; }
</style>
<script>
function goFilter(filter){ window.location.href="?filter="+filter; }
function filterUsers(input){
    const container = input.parentElement;
    const list = container.querySelector(".user-search-list");
    const options = list.querySelectorAll(".user-option");
    const filter = input.value.toLowerCase();
    list.style.display = "block";
    options.forEach(opt=>opt.style.display = opt.textContent.toLowerCase().includes(filter)? "block":"none");
}
function selectUser(el){
    const container = el.closest(".user-search-container");
    const input = container.querySelector(".user-search-input");
    const hiddenInput = container.querySelector("input[type=hidden]");
    const list = container.querySelector(".user-search-list");
    input.value = el.textContent;
    hiddenInput.value = el.getAttribute("data-id");
    list.style.display = "none";
}
document.addEventListener("click", e=>{
    if(!e.target.closest(".user-search-container")){
        document.querySelectorAll(".user-search-list").forEach(l=>l.style.display="none");
    }
});
</script>
</head>
<body>

<h1>
Admin Dashboard
<a href="logout.php" class="logout">Logout</a>
</h1>

<?php if($message): ?>
<p class="message"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<!-- FLOATING CARDS -->
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

<!-- LAPTOP TABLE -->
<h2>ICT Assets</h2>
<table>
<tr>
<th>Asset Tag</th><th>Serial</th><th>Brand</th><th>Model</th><th>Status</th><th>Assigned To</th><th>Action</th>
</tr>

<?php foreach($laptops as $lap): ?>
<tr>
<td><?= htmlspecialchars($lap['asset_tag']) ?></td>
<td><?= htmlspecialchars($lap['serial_number']) ?></td>
<td><?= htmlspecialchars($lap['brand']) ?></td>
<td><?= htmlspecialchars($lap['model']) ?></td>
<td class="status-<?= $lap['status'] ?>"><?= htmlspecialchars($lap['status']) ?></td>
<td><?= $lap['full_name'] ? htmlspecialchars($lap['full_name']) : 'Unassigned' ?></td>
<td>
<?php if(!$lap['assigned_to'] && $lap['status']=='Available'): ?>
<form method="POST" style="display:inline;">
<input type="hidden" name="laptop_id" value="<?= $lap['id'] ?>">
<div class="user-search-container">
<input type="text" placeholder="Type to search user..." class="user-search-input" onkeyup="filterUsers(this)" autocomplete="off">
<div class="user-search-list">
<?php foreach($available_users as $user): ?>
<div class="user-option" data-id="<?= $user['id'] ?>" onclick="selectUser(this)"><?= htmlspecialchars($user['full_name']) ?></div>
<?php endforeach; ?>
</div>
<input type="hidden" name="user_id" required>
</div>
<button type="submit" name="assign_laptop" class="assign">Assign</button>
</form>
<?php elseif($lap['assigned_to']): ?>
<form method="POST" style="display:inline;">
<input type="hidden" name="laptop_id" value="<?= $lap['id'] ?>">
<button type="submit" name="unassign_laptop" class="unassign">Unassign</button>
</form>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</table>

</body>
</html>