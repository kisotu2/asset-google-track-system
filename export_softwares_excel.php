<?php
require 'db.php';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=software_report.xls");

echo "ID\tSoftware\tVendor\tLicense\tTotal\tUsed\tExpiry\n";

$result=$conn->query("SELECT * FROM softwares");

while($row=$result->fetch_assoc()){

echo $row['id']."\t";
echo $row['software_name']."\t";
echo $row['vendor']."\t";
echo $row['license_type']."\t";
echo $row['total_licenses']."\t";
echo $row['used_licenses']."\t";
echo $row['expiry_date']."\n";

}
?>