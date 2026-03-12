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
ISSUE COMPLETE ASSETS LOGIC
=================================*/
if(isset($_POST['issue_assets'])){
    $user_id=intval($_POST['user_id']);
    $laptop_id=intval($_POST['laptop_id']);

    // Laptop history
    $history=$conn->prepare("INSERT INTO laptop_history (laptop_id,user_id,admin_id,action_type) VALUES (?,?,?,?)");
    $action="Laptop Issued";
    $history->bind_param("iiis",$laptop_id,$user_id,$admin_id,$action);
    $history->execute();

    // Accessories
    $mouse = isset($_POST['mouse']) ? 1 : 0;
    $charger = isset($_POST['charger']) ? 1 : 0;
    $acc=$conn->prepare("INSERT INTO laptop_accessories (laptop_id,user_id,mouse_given,charger_given,issued_by) VALUES (?,?,?,?,?)");
    $acc->bind_param("iiiii",$laptop_id,$user_id,$mouse,$charger,$admin_id);
    $acc->execute();

    // Software licenses
    $softwares=["Office 365","Kaspersky","PDF Reader","Teams","Zoom","AnyDesk","Idea Share","Ultraviewer","Dameware"];
    foreach($softwares as $software){
        if(isset($_POST['software'][$software])){
            $stmt=$conn->prepare("SELECT id,total_licenses,used_licenses FROM softwares WHERE software_name=?");
            $stmt->bind_param("s",$software);
            $stmt->execute();
            $data=$stmt->get_result()->fetch_assoc();
            if($data){
                $available=$data['total_licenses']-$data['used_licenses'];
                if($available>0){
                    $update=$conn->prepare("UPDATE softwares SET used_licenses=used_licenses+1 WHERE id=?");
                    $update->bind_param("i",$data['id']);
                    $update->execute();

                    $insert=$conn->prepare("INSERT INTO user_software (user_id,software_name,issued_by) VALUES (?,?,?)");
                    $insert->bind_param("isi",$user_id,$software,$admin_id);
                    $insert->execute();

                    $history=$conn->prepare("INSERT INTO software_history (software_id,user_id,admin_id,action_type) VALUES (?,?,?,?)");
                    $action="License Issued";
                    $history->bind_param("iiis",$data['id'],$user_id,$admin_id,$action);
                    $history->execute();
                }
            }
        }
    }

    if(!empty($_POST['other_software'])){
        $other=trim($_POST['other_software']);
        $insert=$conn->prepare("INSERT INTO user_software (user_id,software_name,issued_by) VALUES (?,?,?)");
        $insert->bind_param("isi",$user_id,$other,$admin_id);
        $insert->execute();
    }

    $update=$conn->prepare("UPDATE laptops SET status='issued',assigned_to=? WHERE id=?");
    $update->bind_param("ii",$user_id,$laptop_id);
    $update->execute();

    $message="✅ Laptop, accessories, and software issued successfully";
}

/* ===============================
FETCH USERS
=================================*/
$users=[]; $r=$conn->query("SELECT id,full_name FROM users WHERE role='user' AND status='active'");
while($row=$r->fetch_assoc()){ $users[]=$row; }

/* ===============================
FETCH SOFTWARE LICENSE COUNTS
=================================*/
$software_counts = [];
$result = $conn->query("SELECT software_name,total_licenses,used_licenses FROM softwares");
while($row = $result->fetch_assoc()){
    $software_counts[$row['software_name']] = $row['total_licenses'] - $row['used_licenses'];
}

/* ===============================
FETCH AVAILABLE LAPTOPS
=================================*/
$laptops=[]; $r=$conn->query("SELECT id,asset_tag FROM laptops WHERE status='active' AND assigned_to IS NULL");
while($row=$r->fetch_assoc()){ $laptops[]=$row; }
?>

<!DOCTYPE html>
<html>
<head>
<title>Issue IT Assets</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Poppins',sans-serif;background:linear-gradient(180deg,#99bb4f,#b08116);padding:40px;}

h1{color:#2c3e50;margin-bottom:25px;font-weight:600;text-align:center;}

/* CONTAINER CARD */
.container{
background:white;
padding:40px;
max-width:950px;
margin:auto;
border-radius:12px;
box-shadow:0 10px 30px rgba(0,0,0,0.08);
transition:0.3s;
}
.container:hover{box-shadow:0 15px 40px rgba(0,0,0,0.12);}

/* FORM GROUP */
.form-group{margin-bottom:25px;}
label{font-weight:500;display:block;margin-bottom:8px;color:#555;}
select,input[type=text]{width:100%;padding:14px;border-radius:8px;border:1px solid #dcdcdc;font-size:14px;transition:0.3s;}
select:focus,input:focus{outline:none;border-color:#b08116;box-shadow:0 0 0 3px rgba(176,129,22,0.15);}

/* ACCESSORIES */
.accessories{display:flex;gap:30px;margin-top:10px;}
.accessories label{display:flex;align-items:center;gap:8px;font-weight:400;cursor:pointer;}

/* SOFTWARE GRID */
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;margin-top:10px;}
.software-box{
border:1px solid #d29615;
border-radius:10px;
padding:14px;
display:flex;
align-items:center;
gap:10px;
cursor:pointer;
transition:0.3s;
background:white;
}
.software-box:hover{
border-color:#b08116;
background:linear-gradient(180deg,#99bb4f,#b08116,0.1);
color:#b08116;
}
.software-box input{cursor:pointer;}

/* LICENSE COUNT */
.license-count{
margin-left:auto;
font-size:12px;
background:rgba(0,119,204,0.1);
padding:3px 8px;
border-radius:6px;
color:#0077cc;
font-weight:500;
}

/* BUTTON */
button{
background:linear-gradient(180deg,#99bb4f,#b08116);
color:white;
border:none;
padding:14px 25px;
font-size:16px;
border-radius:8px;
cursor:pointer;
margin-top:20px;
font-weight:500;
transition:0.3s;
width:100%;
}
button:hover{
transform:translateY(-2px);
box-shadow:0 6px 14px rgba(0,0,0,0.2);
}

/* MESSAGE */
.message{
background:#e8f9ec;
color:#2e7d32;
padding:14px 18px;
border-radius:8px;
margin-bottom:25px;
font-weight:500;
text-align:center;
}

/* SECTION TITLE */
.section-title{
margin-bottom:12px;
font-weight:600;
color:#333;
font-size:16px;
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
<option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option>
<?php endforeach; ?>
</select>
</div>

<div class="form-group">
<label>Select Laptop</label>
<select name="laptop_id" required>
<option value="">Select Laptop</option>
<?php foreach($laptops as $l): ?>
<option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['asset_tag']) ?></option>
<?php endforeach; ?>
</select>
</div>

<div class="form-group">
<div class="section-title">Accessories</div>
<div class="accessories">
<label><input type="checkbox" name="mouse"> Mouse</label>
<label><input type="checkbox" name="charger"> Laptop Charger</label>
</div>
</div>

<div class="form-group">
<div class="section-title">Software Licenses</div>
<div class="grid">
<?php foreach($software_counts as $sw => $count): ?>
<label class="software-box">
<input type="checkbox" name="software[<?= htmlspecialchars($sw) ?>]">
<?= htmlspecialchars($sw) ?>
<span class="license-count"><?= $count ?> left</span>
</label>
<?php endforeach; ?>
</div>
</div>

<div class="form-group">
<label>Other Software</label>
<input type="text" name="other_software" placeholder="Enter other software if needed">
</div>

<button type="submit" name="issue_assets">Issue Assets</button>
</form>
</div>

</body>
</html>