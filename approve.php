<?php
require 'db.php';

if(!isset($_GET['token']) || !isset($_GET['action'])){
    die("Invalid request");
}

$token = $_GET['token'];
$action = $_GET['action'];

$status = ($action == 'approve') ? 'approved' : 'declined';

// UPDATE APPROVAL STATUS
$stmt = $conn->prepare("UPDATE asset_approvals SET status=? WHERE token=?");
$stmt->bind_param("ss", $status, $token);
$stmt->execute();

echo "<h2>Thank you. You have $status this asset assignment.</h2>";
?>