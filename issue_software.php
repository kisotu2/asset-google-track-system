<?php
require 'db.php';
session_start();

/* ===============================
ACCESS CONTROL
=================================*/
if(!isset($_SESSION['role']) || ($_SESSION['role']!='admin' && $_SESSION['role']!='super_admin')){
header("Location: login.php");
exit();
}

$message="";
$admin_id=$_SESSION['user_id'];

/* ===============================
ISSUE COMPLETE ASSETS
=================================*/
if(isset($_POST['issue_assets'])){

$user_id=intval($_POST['user_id']);
$laptop_id=intval($_POST['laptop_id']);

/* ===============================
ISSUE LAPTOP
=================================*/

$history=$conn->prepare("INSERT INTO laptop_history
(laptop_id,user_id,admin_id,action_type)
VALUES (?,?,?,?)");

$action="Laptop Issued";
$history->bind_param("iiis",$laptop_id,$user_id,$admin_id,$action);
$history->execute();

/* ===============================
ACCESSORIES
=================================*/

$mouse = isset($_POST['mouse']) ? 1 : 0;
$charger = isset($_POST['charger']) ? 1 : 0;

$acc=$conn->prepare("INSERT INTO laptop_accessories
(laptop_id,user_id,mouse_given,charger_given,issued_by)
VALUES (?,?,?,?,?)");

$acc->bind_param("iiiii",$laptop_id,$user_id,$mouse,$charger,$admin_id);
$acc->execute();

/* ===============================
SOFTWARE LICENSES
=================================*/

$softwares=[
"Office 365",
"Antivirus",
"PDF Reader",
"Teams",
"Zoom",
"AnyDesk",
"Idea Share",
"Ultraviewer",
"Dameware"
];

foreach($softwares as $software){

if(isset($_POST['software'][$software])){

$stmt=$conn->prepare("SELECT id,total_licenses,used_licenses FROM softwares WHERE software_name=?");
$stmt->bind_param("s",$software);
$stmt->execute();
$data=$stmt->get_result()->fetch_assoc();

if($data){

$available=$data['total_licenses']-$data['used_licenses'];

if($available>0){

/* increase used license */

$update=$conn->prepare("UPDATE softwares 
SET used_licenses=used_licenses+1 
WHERE id=?");

$update->bind_param("i",$data['id']);
$update->execute();

/* record user software */

$insert=$conn->prepare("INSERT INTO user_software
(user_id,software_name,issued_by)
VALUES (?,?,?)");

$insert->bind_param("isi",$user_id,$software,$admin_id);
$insert->execute();

/* software history */

$history=$conn->prepare("INSERT INTO software_history
(software_id,user_id,admin_id,action_type)
VALUES (?,?,?,?)");

$action="License Issued";
$history->bind_param("iiis",$data['id'],$user_id,$admin_id,$action);
$history->execute();

}

}

}

}

/* ===============================
OTHER SOFTWARE
=================================*/

if(!empty($_POST['other_software'])){

$other=trim($_POST['other_software']);

$insert=$conn->prepare("INSERT INTO user_software
(user_id,software_name,issued_by)
VALUES (?,?,?)");

$insert->bind_param("isi",$user_id,$other,$admin_id);
$insert->execute();

}

/* ===============================
UPDATE LAPTOP STATUS
=================================*/

$update=$conn->prepare("UPDATE laptops SET status='issued',assigned_to=? WHERE id=?");
$update->bind_param("ii",$user_id,$laptop_id);
$update->execute();

$message="✅ Laptop, accessories and software issued successfully";

}

/* ===============================
FETCH USERS
=================================*/

$users=[];
$r=$conn->query("SELECT id,full_name FROM users WHERE role='user' AND status='active'");
while($row=$r->fetch_assoc()){
$users[]=$row;
}

/* ===============================
FETCH SOFTWARE LICENSE COUNTS
=================================*/

$software_counts = [];

$result = $conn->query("SELECT software_name,total_licenses,used_licenses FROM softwares");

while($row = $result->fetch_assoc()){

$remaining = $row['total_licenses'] - $row['used_licenses'];

$software_counts[$row['software_name']] = $remaining;

}

/* =============================== FETCH AVAILABLE LAPTOPS =================================*/ 
$laptops=[]; $r=$conn->query("SELECT id,asset_tag FROM laptops WHERE status='active' AND assigned_to IS NULL");
 while($row=$r->fetch_assoc()){ $laptops[]=$row; }
?>

<!DOCTYPE html>
<html>
<head>

<title>Issue IT Assets</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>

*{
box-sizing:border-box;
margin:0;
padding:0;
}

body{
font-family:'Poppins',sans-serif;
background:#f4f7fb;
padding:40px;
}

/* PAGE TITLE */

h1{
color:#2c3e50;
margin-bottom:20px;
font-weight:600;
}

/* CARD */

.container{
background:white;
padding:35px;
max-width:900px;
border-radius:12px;
box-shadow:0 8px 20px rgba(0,0,0,0.05);
}

/* FORM GROUP */

.form-group{
margin-bottom:25px;
}

label{
font-weight:500;
display:block;
margin-bottom:8px;
}

/* SELECT */

select,input[type=text]{
width:100%;
padding:12px;
border-radius:8px;
border:1px solid #dcdcdc;
font-size:14px;
transition:0.2s;
}

select:focus,
input:focus{
outline:none;
border-color:#b08116;
box-shadow:0 0 0 3px rgba(176,129,22,0.15);
}

/* ACCESSORIES */

.accessories{
display:flex;
gap:30px;
margin-top:10px;
}

.accessories label{
display:flex;
align-items:center;
gap:8px;
font-weight:400;
}

/* SOFTWARE GRID */

.grid{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
gap:15px;
margin-top:10px;
}

.software-box{
border:1px solid #e6e6e6;
border-radius:10px;
padding:14px;
display:flex;
align-items:center;
gap:10px;
cursor:pointer;
transition:0.2s;
}

.software-box:hover{
border-color:#b08116;
background:#faf6ed;
}

/* BUTTON */

button{
background:linear-gradient(135deg,#b08116,#99bb4f);
color:white;
border:none;
padding:14px 25px;
font-size:15px;
border-radius:8px;
cursor:pointer;
margin-top:20px;
transition:0.2s;
}

button:hover{
transform:translateY(-1px);
box-shadow:0 6px 12px rgba(0,0,0,0.15);
}

/* MESSAGE */

.message{
background:#e8f9ec;
color:#2e7d32;
padding:12px 15px;
border-radius:8px;
margin-bottom:20px;
font-weight:500;
}

/* SECTION TITLE */

.section-title{
margin-bottom:10px;
font-weight:600;
color:#555;
}

.license-count{
margin-left:auto;
font-size:12px;
background:#eaf6ff;
padding:3px 8px;
border-radius:6px;
color:#0077cc;
}
</style>

</head>

<body>

<h1>Issue IT Assets</h1>

<?php if($message): ?>
<div class="message"><?= $message ?></div>
<?php endif; ?>

<div class="container">

<form method="POST">

<div class="form-group">

<label>Select User</label>

<select name="user_id" required>

<option value="">Select User</option>

<?php foreach($users as $u): ?>

<option value="<?= $u['id'] ?>">
<?= htmlspecialchars($u['full_name']) ?>
</option>

<?php endforeach; ?>

</select>

</div>


<div class="form-group">

<label>Select Laptop</label>

<select name="laptop_id" required>

<option value="">Select Laptop</option>

<?php foreach($laptops as $l): ?>

<option value="<?= $l['id'] ?>">
<?= htmlspecialchars($l['asset_tag']) ?>
</option>

<?php endforeach; ?>

</select>

</div>


<div class="form-group">

<div class="section-title">Accessories</div>

<div class="accessories">

<label>
<input type="checkbox" name="mouse">
Mouse
</label>

<label>
<input type="checkbox" name="charger">
Laptop Charger
</label>

</div>

</div>


<div class="form-group">

<div class="section-title">Software Licenses</div>

<div class="grid">

<label class="software-box">
<input type="checkbox" name="software[Office 365]">
Office 365
<span class="license-count">
<?= $software_counts['Office 365'] ?? 0 ?> left
</span>
</label>

<label class="software-box">
<input type="checkbox" name="software[kaspersky]">
Kaspersky
<span class="license-count">
<?= $software_counts['Kaspersky'] ?? 0 ?> left
</span>
</label>

<label class="software-box">
<input type="checkbox" name="software[PDF Reader]">
PDF Reader
<span class="license-count">
<?= $software_counts['PDF Reader'] ?? 0 ?> left
</span>
</label>

<label class="software-box">
<input type="checkbox" name="software[Teams]">
Teams
<span class="license-count">
<?= $software_counts['Teams'] ?? 0 ?> left
</span>
</label>

<label class="software-box">
<input type="checkbox" name="software[Zoom]">
Zoom
<span class="license-count">
<?= $software_counts['Zoom'] ?? 0 ?> left
</span>
</label>

<label class="software-box">
<input type="checkbox" name="software[AnyDesk]">
AnyDesk
<span class="license-count">
<?= $software_counts['AnyDesk'] ?? 0 ?> left
</span>
</label>

<label class="software-box">
<input type="checkbox" name="software[Idea Share]">
Idea Share
<span class="license-count">
<?= $software_counts['Idea Share'] ?? 0 ?> left
</span>
</label>

<label class="software-box">
<input type="checkbox" name="software[Ultraviewer]">
Ultraviewer
<span class="license-count">
<?= $software_counts['Ultraviewer'] ?? 0 ?> left
</span>
</label>

<label class="software-box">
<input type="checkbox" name="software[Dameware]">
Dameware
<span class="license-count">
<?= $software_counts['Dameware'] ?? 0 ?> left
</span>
</label>

</div>

</div>


<div class="form-group">

<label>Other Software</label>

<input type="text" name="other_software" placeholder="Enter other software if needed">

</div>

<button type="submit" name="issue_assets">
Issue Assets
</button>

</form>

</div>

</body>
</html>