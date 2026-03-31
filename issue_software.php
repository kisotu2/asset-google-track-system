<?php
require 'db.php';
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

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
INITIALIZE VARIABLES
=================================*/
$mouse = 0;
$charger = 0;
$softwares = ["Office 365","Kaspersky","PDF Reader","Teams","Zoom","AnyDesk","Idea Share","Ultraviewer","Dameware"];
$other = '';
$software_counts = [];

/* ===============================
FETCH SOFTWARE LICENSE COUNTS
=================================*/
$result = $conn->query("SELECT software_name, total_licenses, used_licenses FROM softwares");
if($result){
    while($row = $result->fetch_assoc()){
        $software_counts[$row['software_name']] = $row['total_licenses'] - $row['used_licenses'];
    }
}

/* ===============================
FETCH USERS
=================================*/
$users = [];
$r = $conn->query("SELECT id, full_name FROM users WHERE role='user' AND status='active'");
while($row = $r->fetch_assoc()){
    $users[] = $row;
}

/* ===============================
FETCH AVAILABLE LAPTOPS
=================================*/
$laptops = [];
$r = $conn->query("SELECT id, asset_tag FROM laptops WHERE status='active' AND assigned_to IS NULL");
while($row = $r->fetch_assoc()){
    $laptops[] = $row;
}

/* ===============================
ISSUE COMPLETE ASSETS LOGIC
=================================*/
if(isset($_POST['issue_assets'])){
    $asset_type = $_POST['asset_type'] ?? '';
$laptop_id = $desktop_id = $phone_id = null;

if($asset_type == 'laptop'){
    $laptop_id = intval($_POST['laptop_id']);
    // assign laptop logic (same as before)
    $update = $conn->prepare("UPDATE laptops SET status='issued', assigned_to=? WHERE id=?");
    $update->bind_param("ii", $user_id, $laptop_id);
    $update->execute();

    // Insert laptop history
    $history = $conn->prepare("INSERT INTO laptop_history (laptop_id,user_id,admin_id,action_type) VALUES (?,?,?,?)");
    $action = "Laptop Issued";
    $history->bind_param("iiis", $laptop_id, $user_id, $admin_id, $action);
    $history->execute();

} elseif($asset_type == 'desktop'){
    $desktop_id = intval($_POST['desktop_id']);
    // assign desktop logic
    $update = $conn->prepare("UPDATE desktops SET status='issued', assigned_to=? WHERE id=?");
    $update->bind_param("ii", $user_id, $desktop_id);
    $update->execute();

    // Insert desktop history
    $history = $conn->prepare("INSERT INTO desktop_history (desktop_id,user_id,admin_id,action_type) VALUES (?,?,?,?)");
    $action = "Desktop Issued";
    $history->bind_param("iiis", $desktop_id, $user_id, $admin_id, $action);
    $history->execute();
}

// Assign phone if selected
if(!empty($_POST['phone_id'])){
    $phone_id = intval($_POST['phone_id']);
    $phone_ext = trim($_POST['phone_ext'] ?? '');
    
    $update = $conn->prepare("UPDATE phones SET status='issued', assigned_to=? WHERE id=?");
    $update->bind_param("ii", $user_id, $phone_id);
    $update->execute();

    // Insert phone history
    $history = $conn->prepare("INSERT INTO phone_history (phone_id,user_id,admin_id,extension,action_type) VALUES (?,?,?,?,?)");
    $action = "Phone Issued";
    $history->bind_param("iiiss", $phone_id, $user_id, $admin_id, $phone_ext, $action);
    $history->execute();
}

    $user_id = intval($_POST['user_id']);
    $laptop_id = intval($_POST['laptop_id']);

    // Check if user already has a laptop
    $stmt_check = $conn->prepare("SELECT id FROM laptops WHERE assigned_to=?");
    $stmt_check->bind_param("i", $user_id);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();
    if($res_check->num_rows > 0){
        $message = "⚠️ This user already has a laptop assigned.";
    } else {
        // Check if laptop is already assigned
        $stmt_check_laptop = $conn->prepare("SELECT assigned_to FROM laptops WHERE id=?");
        $stmt_check_laptop->bind_param("i", $laptop_id);
        $stmt_check_laptop->execute();
        $res_laptop = $stmt_check_laptop->get_result()->fetch_assoc();
        if(!empty($res_laptop['assigned_to'])){
            $message = "⚠️ This laptop is already assigned to another user.";
        } else {
            // Proceed with assignment
            $mouse = isset($_POST['mouse']) ? 1 : 0;
            $charger = isset($_POST['charger']) ? 1 : 0;
            $other = !empty($_POST['other_software']) ? trim($_POST['other_software']) : '';

            // Laptop history
            $history = $conn->prepare("INSERT INTO laptop_history (laptop_id,user_id,admin_id,action_type) VALUES (?,?,?,?)");
            $action = "Laptop Issued";
            $history->bind_param("iiis", $laptop_id, $user_id, $admin_id, $action);
            $history->execute();

            // Accessories
            $bag = isset($_POST['bag']) ? 1 : 0;

$acc = $conn->prepare("
    INSERT INTO laptop_accessories (laptop_id,user_id,mouse_given,charger_given,bag_given,issued_by)
    VALUES (?,?,?,?,?,?)
");
$acc->bind_param("iiiiii", $laptop_id, $user_id, $mouse, $charger, $bag, $admin_id);
$acc->execute();

            // Software licenses
            foreach($softwares as $software){
                if(isset($_POST['software'][$software])){
                    $stmt = $conn->prepare("SELECT id,total_licenses,used_licenses FROM softwares WHERE software_name=?");
                    $stmt->bind_param("s", $software);
                    $stmt->execute();
                    $data = $stmt->get_result()->fetch_assoc();
                    if($data && ($data['total_licenses'] - $data['used_licenses']) > 0){
                        $update = $conn->prepare("UPDATE softwares SET used_licenses=used_licenses+1 WHERE id=?");
                        $update->bind_param("i", $data['id']);
                        $update->execute();

                        $insert = $conn->prepare("INSERT INTO user_software (user_id,software_name,issued_by) VALUES (?,?,?)");
                        $insert->bind_param("isi", $user_id, $software, $admin_id);
                        $insert->execute();

                        // Insert into software_assignments
$assign = $conn->prepare("
    INSERT INTO software_assignments (software_id, user_id, assigned_date)
    VALUES (?, ?, NOW())
");
$assign->bind_param("ii", $data['id'], $user_id);
$assign->execute();

                        $history = $conn->prepare("INSERT INTO software_history (software_id,user_id,admin_id,action_type) VALUES (?,?,?,?)");
                        $action = "License Issued";
                        $history->bind_param("iiis", $data['id'], $user_id, $admin_id, $action);
                        $history->execute();
                    }
                }
            }

            // Other software
            if(!empty($other)){
                $insert = $conn->prepare("INSERT INTO user_software (user_id,software_name,issued_by) VALUES (?,?,?)");
                $insert->bind_param("isi", $user_id, $other, $admin_id);
                $insert->execute();
            }

            // Update laptop assignment
            $update = $conn->prepare("UPDATE laptops SET status='issued', assigned_to=? WHERE id=?");
            $update->bind_param("ii", $user_id, $laptop_id);
            $update->execute();

            /* ===============================
            SEND ASSET APPROVAL EMAIL
            =================================*/
            $stmt = $conn->prepare("SELECT email, full_name FROM users WHERE id=?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            $token = bin2hex(random_bytes(16));

            $approval = $conn->prepare("INSERT INTO asset_approvals (user_id,laptop_id,token,admin_id) VALUES (?,?,?,?)");
            $approval->bind_param("iisi", $user_id, $laptop_id, $token, $admin_id);
            $approval->execute();

            // Detect protocol and host dynamically
$host = $_SERVER['HTTP_HOST'];

// If running on localhost, force http (bypass HSTS/HTTPS issues)
if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
    $protocol = 'http://';
} else {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "https://";
}

// Build approval/decline links
$approve_link = "{$protocol}{$host}/approve.php?token={$token}&action=approve";
$decline_link = "{$protocol}{$host}/approve.php?token={$token}&action=decline";

            $accessories_list = [];
            if($mouse) $accessories_list[] = "Mouse";
            if($charger) $accessories_list[] = "Charger";

            $software_list = [];
            foreach($softwares as $software){
                if(isset($_POST['software'][$software])){
                    $software_list[] = $software;
                }
            }
            if(!empty($other)) $software_list[] = $other;

            $subject = "IRA IT Asset Assignment Approval";
            $body = "
            <h3>IRA Asset Management System</h3>
            <p>Hello {$user['full_name']},</p>
            <p>You have been assigned the following IT assets:</p>
            <strong>Laptop:</strong> $laptop_id<br>
            <strong>Accessories:</strong> ".implode(", ", $accessories_list)."<br>
            <strong>Software:</strong> ".implode(", ", $software_list)."<br><br>
            <p>Please approve or decline your assignment:</p>
            <a href='$approve_link' style='padding:10px 15px;background:#28a745;color:white;text-decoration:none;border-radius:5px;'>Approve</a>
            <a href='$decline_link' style='padding:10px 15px;background:#dc3545;color:white;text-decoration:none;border-radius:5px;margin-left:10px;'>Decline</a>
            <br><br>
            <small>If you did not receive this assignment, please contact ICT.</small>
            ";

            $mail = new PHPMailer(true);
            try{
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'kisotusamuel2@gmail.com';
                $mail->Password   = 'pgveakwibzlhicqs';
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;

                $mail->setFrom('kisotusamuel2@gmail.com', 'IRA Asset Management System');
                $mail->addAddress($user['email'], $user['full_name']);

                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $body;

                $mail->send();
            } catch(Exception $e){
                $message .= " ⚠️ Failed to send asset approval email: {$mail->ErrorInfo}";
            }

            $message .= " ✅ Laptop, accessories, and software issued successfully and awaiting user approval.";
        }
    }
}
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
    <label>Asset Type</label>
    <select name="asset_type" id="asset_type" required onchange="toggleAssetSelection()">
        <option value="">Select Type</option>
        <option value="laptop">Laptop</option>
        <option value="desktop">Desktop</option>
    </select>
</div>

<div class="form-group" id="laptop_select">
    <label>Select Laptop</label>
    <select name="laptop_id">
        <option value="">Select Laptop</option>
        <?php foreach($laptops as $l): ?>
        <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['asset_tag']) ?></option>
        <?php endforeach; ?>
    </select>
</div>

<div class="form-group" id="desktop_select" style="display:none;">
    <label>Select Desktop</label>
    <select name="desktop_id">
        <option value="">Select Desktop</option>
        <?php 
        $desktops = [];
        $r = $conn->query("SELECT id, asset_tag FROM desktops WHERE status='active' AND assigned_to IS NULL");
        while($row = $r->fetch_assoc()){ $desktops[] = $row; }
        foreach($desktops as $d): ?>
        <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['asset_tag']) ?></option>
        <?php endforeach; ?>
    </select>
</div>

<div class="form-group">
    <label>Select Phone</label>
    <select name="phone_id">
        <option value="">Select Phone</option>
        <?php 
        $phones = [];
        $r = $conn->query("SELECT id, asset_tag FROM phones WHERE status='active' AND assigned_to IS NULL");
        while($row = $r->fetch_assoc()){ $phones[] = $row; }
        foreach($phones as $p): ?>
        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['asset_tag']) ?></option>
        <?php endforeach; ?>
    </select>
</div>

<div class="form-group">
    <label>Phone Extension</label>
    <input type="text" name="phone_ext" placeholder="Enter phone extension">
</div>

<script>
function toggleAssetSelection(){
    let type = document.getElementById('asset_type').value;
    document.getElementById('laptop_select').style.display = type=='laptop'?'block':'none';
    document.getElementById('desktop_select').style.display = type=='desktop'?'block':'none';
}
</script>

<div class="form-group">
<div class="section-title">Accessories</div>
<div class="accessories">
<div class="accessories">
    <label><input type="checkbox" name="mouse"> Mouse</label>
    <label><input type="checkbox" name="charger"> Laptop Charger</label>
    <label><input type="checkbox" name="bag"> Laptop Bag</label>
</div>
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

<div style="display:flex; gap:15px;">

<button type="submit" name="issue_assets" style="flex:1;">
    Issue Assets
</button>

<a href="super_dashboard.php" style="
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
width:50%;
">
    Back
</a>

</div>
</form>
</div>

</body>
</html>