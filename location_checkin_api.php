<?php
require __DIR__.'/bootstrap.php'; require __DIR__.'/alert_mail.php'; require_login();
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD']!=='POST') {http_response_code(405);echo json_encode(['message'=>'Method not allowed.']);exit;}
$data=json_decode(file_get_contents('php://input'),true) ?: [];
if (!hash_equals($_SESSION['csrf']??'', $data['csrf']??'')) {http_response_code(419);echo json_encode(['message'=>'Invalid form token.']);exit;}
$asset=(int)($data['asset_id']??0);$lat=(float)($data['latitude']??999);$lng=(float)($data['longitude']??999);$accuracy=(float)($data['accuracy_meters']??0);$user=(int)$_SESSION['user_id'];
if(!$asset || !$data['consent'] || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 || $accuracy < 0 || $accuracy > 100000) {http_response_code(422);echo json_encode(['message'=>'Invalid location check-in data.']);exit;}
$own=$conn->prepare("SELECT id FROM laptops WHERE id=? AND assigned_to=? AND status='Assigned'");$own->bind_param('ii',$asset,$user);$own->execute();if(!$own->get_result()->fetch_assoc()){http_response_code(403);echo json_encode(['message'=>'That asset is not assigned to you.']);exit;}
$captured=(new DateTimeImmutable($data['captured_at']??'now'))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');$source='browser';$consent='granted';
$stmt=$conn->prepare('INSERT INTO device_locations(laptop_id,latitude,longitude,accuracy_meters,captured_at,source,consent_status,created_by) VALUES(?,?,?,?,?,?,?,?)');$stmt->bind_param('idddsssi',$asset,$lat,$lng,$accuracy,$captured,$source,$consent,$user);$stmt->execute();$locationId=$conn->insert_id;
// Haversine comparison against active approved areas. A location outside all areas raises an auditable alert.
$areas=$conn->query('SELECT latitude,longitude,radius_meters FROM approved_areas WHERE active=1');$inside=$areas->num_rows===0;while($a=$areas->fetch_assoc()){ $dlat=deg2rad($a['latitude']-$lat);$dlng=deg2rad($a['longitude']-$lng);$h=sin($dlat/2)**2+cos(deg2rad($lat))*cos(deg2rad($a['latitude']))*sin($dlng/2)**2;$meters=6371000*2*atan2(sqrt($h),sqrt(1-$h));if($meters<=(int)$a['radius_meters']){$inside=true;break;}}
if(!$inside){$message='Latest authorised check-in is outside an approved area.';$a=$conn->prepare('INSERT INTO location_alerts(laptop_id,location_id,message) VALUES(?,?,?)');$a->bind_param('iis',$asset,$locationId,$message);$a->execute();$emailed=send_location_alert_email($conn,$asset,$message);audit($conn,'approved_area_alert','location_alert',$conn->insert_id,['asset_id'=>$asset,'email_sent'=>$emailed]);}
audit($conn,'location_checkin','device_location',$locationId,['asset_id'=>$asset,'source'=>'browser']);echo json_encode(['message'=>$inside?'Check-in recorded successfully.':'Check-in recorded; an approved-area alert was created'.($emailed?' and emailed to the configured administrator.':'.')]);
