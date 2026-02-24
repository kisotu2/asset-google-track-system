<?php
// api.php
require 'db.php';
header('Content-Type: application/json');

$laptops = getLaptops();
echo json_encode($laptops);
?>