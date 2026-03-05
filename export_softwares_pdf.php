<?php

use Com\Tecnick\Pdf\Tcpdf;

require 'db.php';
require_once(__DIR__ . '/tcpdf/tcpdf.php');

$pdf=new Tcpdf();
$pdf->AddPage();

$html="<h2>IRA Software Report</h2>";

$html.="<table border='1' cellpadding='5'>
<tr>
<th>ID</th>
<th>Software</th>
<th>Vendor</th>
<th>License</th>
<th>Total</th>
<th>Expiry</th>
</tr>";

$result=$conn->query("SELECT * FROM softwares");

while($row=$result->fetch_assoc()){

$html.="<tr>
<td>".$row['id']."</td>
<td>".$row['software_name']."</td>
<td>".$row['vendor']."</td>
<td>".$row['license_type']."</td>
<td>".$row['total_licenses']."</td>
<td>".$row['expiry_date']."</td>
</tr>";

}

$html.="</table>";

$pdf->writeHTML($html);
$pdf->Output("software_report.pdf","I");