<?php
require 'db.php';

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=softwares.csv');

$output = fopen("php://output", "w");

fputcsv($output, ['ID','Software','Vendor','License','Total','Used','Expiry']);

$result = $conn->query("SELECT * FROM softwares");

while($row = $result->fetch_assoc()){
    fputcsv($output, [
        $row['id'],
        $row['software_name'],
        $row['vendor'],
        $row['license_type'],
        $row['total_licenses'],
        $row['used_licenses'],
        $row['expiry_date']
    ]);
}

fclose($output);