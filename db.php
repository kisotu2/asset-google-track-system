<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "ira_assets";

$conn = new mysqli($host,$user,$pass,$db);

if($conn->connect_error){
    die("Connection failed: " . $conn->connect_error);
}

function getLaptops(){
    global $conn;
    return $conn->query("SELECT * FROM laptops ORDER BY created_at DESC");
}
?>