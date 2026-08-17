<?php
require __DIR__.'/bootstrap.php'; 
require_login(['admin','super_admin']);
if($_SERVER['REQUEST_METHOD']==='POST')
    {verify_csrf();
$action=$_POST['action']??'';
if($action==='area'){$name=trim($_POST['name']);
$lat=(float)$_POST['latitude'];
$lng=(float)$_POST['longitude'];
$radius=max(1,(int)$_POST['radius']);
if($name && $lat>=-90&&$lat<=90&&$lng>=-180&&$lng<=180)
    {$s=$conn->prepare('INSERT INTO approved_areas(name,latitude,longitude,radius_meters) 
    VALUES(?,?,?,?)');
    $s->bind_param('sddi',$name,$lat,$lng,$radius);
    $s->execute();audit($conn,'approved_area_created','approved_area',$conn->insert_id);
    flash('Approved area added.');}}if($action==='ack'){$id=(int)$_POST['id'];
    $s=$conn->prepare("UPDATE location_alerts SET status='acknowledged' WHERE id=?");
    $s->bind_param('i',$id);$s->execute();
    audit($conn,'location_alert_acknowledged','location_alert',$id);
    flash('Alert acknowledged.');
    }
    header('Location: locations.php');exit;
    }
$locations=$conn->query('SELECT x.*,l.asset_tag,l.brand,l.model,u.full_name FROM device_locations x JOIN laptops l ON l.id=x.laptop_id JOIN users u ON u.id=x.created_by WHERE x.id IN (SELECT MAX(id) FROM device_locations GROUP BY laptop_id) ORDER BY x.captured_at DESC');
$history=$conn->query('SELECT x.*,l.asset_tag,u.full_name FROM device_locations x JOIN laptops l ON l.id=x.laptop_id JOIN users u ON u.id=x.created_by ORDER BY x.captured_at DESC LIMIT 50');
$alerts=$conn->query("SELECT a.*,l.asset_tag FROM location_alerts a JOIN laptops l ON l.id=a.laptop_id WHERE a.status='open' ORDER BY a.created_at DESC");
$areas=$conn->query('SELECT * FROM approved_areas WHERE active=1');
$mapKey=config()['google_maps_api_key']??'';
layout_start('Authorised locations');
?>
<div class="hero"><div>
    <h1>Authorised device locations</h1>
    <p class="muted">Last known check-ins only. Every view is recorded in the audit log.</p>
    </div></div><?php audit($conn,'location_dashboard_view','location_dashboard',null);
    ?>
    <section class="split">
        <div class="panel">
            <h2>Approved area</h2>
            <form method="post">
                <input type="hidden" name="csrf" value="<?=csrf()?>">
                <input type="hidden" name="action" value="area">
                <label>Name<input name="name" required></label>
                <label>Latitude<input name="latitude" type="number" step="any" min="-90" max="90" required></label>
                <label>Longitude<input name="longitude" type="number" step="any" min="-180" max="180" required></label>
                <label>Radius (metres)<input name="radius" type="number" value="250" min="1" required></label>
                <button>Add approved area</button>
                </form>
        </div>
                <div class="panel">
                    <h2>Open approved-area alerts</h2>
                    <?php
                     while($a=$alerts->fetch_assoc()):
                        ?>
                        <p><b>
                            <?=e($a['asset_tag'])?>
                            </b> — <?=e($a['message'])?>
                            <form method="post">
                                <input type="hidden" name="csrf" value="<?=csrf()?>">
                                <input type="hidden" name="action" value="ack">
                                <input type="hidden" name="id" value="<?=$a['id']?>">
                                <button class="secondary">Acknowledge</button>
                                </form>
                                </p>
                                <?php endwhile;?>
                                </div></section><section class="panel" style="margin-top:24px"><h2>Map</h2><?php if(!$mapKey):?><p class="notice">Add a restricted Google Maps JavaScript API key to <code>config.php</code> to enable the map. The location table remains available.</p><?php else:?><div id="map" style="height:440px"></div><script>const locations=<?=json_encode($locations->fetch_all(MYSQLI_ASSOC),JSON_HEX_TAG|JSON_HEX_AMP)?>, areas=<?=json_encode($areas->fetch_all(MYSQLI_ASSOC))?>;function initMap(){const centre=locations[0]?{lat:+locations[0].latitude,lng:+locations[0].longitude}:{lat:-1.286389,lng:36.817223};const map=new google.maps.Map(document.getElementById('map'),{zoom:locations[0]?13:6,center:centre});locations.forEach(x=>new google.maps.Marker({position:{lat:+x.latitude,lng:+x.longitude},map,title:x.asset_tag+' — '+x.captured_at}));areas.forEach(a=>new google.maps.Circle({map,center:{lat:+a.latitude,lng:+a.longitude},radius:+a.radius_meters,fillColor:'#547d24',strokeColor:'#547d24'}));}</script><script async src="https://maps.googleapis.com/maps/api/js?key=<?=e($mapKey)?>&callback=initMap"></script><?php endif;?></section><section style="margin-top:24px"><h2>Location history</h2><table><thead><tr><th>Asset</th><th>Recorded by</th><th>Captured at (UTC)</th><th>Accuracy</th><th>Coordinates</th><th>Source</th></tr></thead><tbody><?php while($h=$history->fetch_assoc()):?><tr><td><?=e($h['asset_tag'])?></td><td><?=e($h['full_name'])?></td><td><?=e($h['captured_at'])?></td><td><?=e((string)$h['accuracy_meters'])?> m</td><td><?=e($h['latitude'].', '.$h['longitude'])?></td><td><?=e($h['source'])?></td></tr><?php endwhile;?></tbody></table></section><?php layout_end();
