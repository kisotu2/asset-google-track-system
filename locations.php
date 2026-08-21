<?php

require __DIR__ . '/bootstrap.php';

require_login(['admin', 'super_admin']);

audit(
    $conn,
    'location_dashboard_view',
    'location_dashboard',
    null
);

/* POST handling */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'area') {

        $name = trim($_POST['name'] ?? '');
        $lat = (float)($_POST['latitude'] ?? 0);
        $lng = (float)($_POST['longitude'] ?? 0);
        $radius = max(1, (int)($_POST['radius'] ?? 250));

        if (
            $name !== '' &&
            $lat >= -90 &&
            $lat <= 90 &&
            $lng >= -180 &&
            $lng <= 180
        ) {

            $s = $conn->prepare("
                INSERT INTO approved_areas
                (name, latitude, longitude, radius_meters)
                VALUES (?, ?, ?, ?)
            ");

            $s->bind_param(
                'sddi',
                $name,
                $lat,
                $lng,
                $radius
            );

            $s->execute();

            audit(
                $conn,
                'approved_area_created',
                'approved_area',
                $conn->insert_id
            );

            flash('Approved area added.');

        } else {

            flash(
                'Please provide valid area details.',
                'error'
            );
        }
    }

    if ($action === 'ack') {

        $id = (int)($_POST['id'] ?? 0);

        $s = $conn->prepare("
            UPDATE location_alerts
            SET status = 'acknowledged'
            WHERE id = ?
        ");

        $s->bind_param('i', $id);
        $s->execute();

        audit(
            $conn,
            'location_alert_acknowledged',
            'location_alert',
            $id
        );

        flash('Alert acknowledged.');
    }

    header('Location: locations.php');
    exit;
}


/* ============================
   FETCH DATA
============================ */

$locations = $conn->query("
    SELECT
        x.*,
        l.asset_tag,
        l.brand,
        l.model,
        u.full_name
    FROM device_locations x
    JOIN laptops l
        ON l.id = x.laptop_id
    JOIN users u
        ON u.id = x.created_by
    WHERE x.id IN (
        SELECT MAX(id)
        FROM device_locations
        GROUP BY laptop_id
    )
    ORDER BY x.captured_at DESC
");

if (!$locations) {
    die(
        'Location query failed: ' .
        e($conn->error)
    );
}


$history = $conn->query("
    SELECT
        x.*,
        l.asset_tag,
        u.full_name
    FROM device_locations x
    JOIN laptops l
        ON l.id = x.laptop_id
    JOIN users u
        ON u.id = x.created_by
    ORDER BY x.captured_at DESC
    LIMIT 50
");

if (!$history) {
    die(
        'History query failed: ' .
        e($conn->error)
    );
}


$alerts = $conn->query("
    SELECT
        a.*,
        l.asset_tag
    FROM location_alerts a
    JOIN laptops l
        ON l.id = a.laptop_id
    WHERE a.status = 'open'
    ORDER BY a.created_at DESC
");

if (!$alerts) {
    die(
        'Alerts query failed: ' .
        e($conn->error)
    );
}


$areas = $conn->query("
    SELECT *
    FROM approved_areas
    WHERE active = 1
");

if (!$areas) {
    die(
        'Approved areas query failed: ' .
        e($conn->error)
    );
}


$locationRows = $locations->fetch_all(MYSQLI_ASSOC);
$areaRows = $areas->fetch_all(MYSQLI_ASSOC);

$openAlerts = $alerts->num_rows;

$approvedAreaCount = count($areaRows);

$trackedDevices = count($locationRows);

$historyRows = $history->fetch_all(MYSQLI_ASSOC);

$mapKey = config()['google_maps_api_key'] ?? '';


/* ============================
   PAGE
============================ */

layout_start('Location Management');

?>

<div class="page-header">

    <div>

        <h1>Location Management</h1>

        <p class="muted">
            Monitor authorised device locations,
            approved areas and location alerts.
        </p>

    </div>

    <span class="status-badge">
        <span class="status-dot"></span>
        Monitoring active
    </span>

</div>


<!-- METRICS -->

<section class="metrics-grid">

    <article class="metric-card">

        <div class="metric-top">
            <span>Tracked Devices</span>
            <div class="metric-icon">⌖</div>
        </div>

        <strong class="metric-number">
            <?= $trackedDevices ?>
        </strong>

        <span class="metric-label">
            Devices with known locations
        </span>

    </article>


    <article class="metric-card">

        <div class="metric-top">
            <span>Open Alerts</span>
            <div class="metric-icon">!</div>
        </div>

        <strong class="metric-number">
            <?= $openAlerts ?>
        </strong>

        <span class="metric-label">
            Require administrator attention
        </span>

    </article>


    <article class="metric-card">

        <div class="metric-top">
            <span>Approved Areas</span>
            <div class="metric-icon">◉</div>
        </div>

        <strong class="metric-number">
            <?= $approvedAreaCount ?>
        </strong>

        <span class="metric-label">
            Active geofenced locations
        </span>

    </article>


    <article class="metric-card">

        <div class="metric-top">
            <span>Recent Records</span>
            <div class="metric-icon">◷</div>
        </div>

        <strong class="metric-number">
            <?= count($historyRows) ?>
        </strong>

        <span class="metric-label">
            Latest location records
        </span>

    </article>

</section>


<!-- MANAGEMENT -->

<section class="location-tools">


    <!-- APPROVED AREA -->

    <div class="panel">

        <div class="panel-header">

            <div>

                <h2>Add Approved Area</h2>

                <p class="muted">
                    Create a permitted geofenced location.
                </p>

            </div>

        </div>


        <form method="post">

            <input
                type="hidden"
                name="csrf"
                value="<?= csrf() ?>"
            >

            <input
                type="hidden"
                name="action"
                value="area"
            >


            <div class="form-grid">

                <label>
                    Area name

                    <input
                        name="name"
                        placeholder="IRA Headquarters"
                        required
                    >
                </label>


                <label>
                    Radius (metres)

                    <input
                        name="radius"
                        type="number"
                        value="250"
                        min="1"
                        required
                    >
                </label>


                <label>
                    Latitude

                    <input
                        name="latitude"
                        type="number"
                        step="any"
                        min="-90"
                        max="90"
                        placeholder="-1.286389"
                        required
                    >
                </label>


                <label>
                    Longitude

                    <input
                        name="longitude"
                        type="number"
                        step="any"
                        min="-180"
                        max="180"
                        placeholder="36.817223"
                        required
                    >
                </label>

            </div>


            <button type="submit">
                + Add Approved Area
            </button>

        </form>

    </div>


    <!-- ALERTS -->

    <div class="panel">

        <div class="panel-header">

            <div>

                <h2>Open Alerts</h2>

                <p class="muted">
                    Devices outside approved areas.
                </p>

            </div>

            <span class="alert-count">
                <?= $openAlerts ?>
            </span>

        </div>


        <?php if ($openAlerts === 0): ?>

            <div class="empty-alert">

                <div class="empty-alert-icon">
                    ✓
                </div>

                <strong>No open alerts</strong>

                <p>
                    All monitored devices are currently
                    within approved areas.
                </p>

            </div>

        <?php else: ?>

            <?php while ($a = $alerts->fetch_assoc()): ?>

                <div class="location-alert">

                    <div class="alert-icon">
                        !
                    </div>


                    <div style="flex:1">

                        <strong>
                            <?= e($a['asset_tag']) ?>
                        </strong>

                        <p>
                            <?= e($a['message']) ?>
                        </p>

                        <small>
                            <?= e($a['created_at']) ?>
                        </small>

                    </div>


                    <form method="post">

                        <input
                            type="hidden"
                            name="csrf"
                            value="<?= csrf() ?>"
                        >

                        <input
                            type="hidden"
                            name="action"
                            value="ack"
                        >

                        <input
                            type="hidden"
                            name="id"
                            value="<?= $a['id'] ?>"
                        >

                        <button
                            type="submit"
                            class="secondary"
                        >
                            Acknowledge
                        </button>

                    </form>

                </div>

            <?php endwhile; ?>

        <?php endif; ?>

    </div>

</section>


<!-- MAP -->

<section class="panel" style="margin-top:25px">

    <div class="panel-header">

        <div>

            <h2>Device Location Map</h2>

            <p class="muted">
                Last known device locations and approved areas.
            </p>

        </div>

        <span class="status-badge">
            <span class="status-dot"></span>
            Monitoring
        </span>

    </div>


    <?php if (!$mapKey): ?>

        <div class="map-notice">

            <strong>
                Google Maps is not configured
            </strong>

            <p>
                Add your Google Maps JavaScript API key
                to <code>config.php</code>.
            </p>

        </div>

    <?php else: ?>

        <div
            id="map"
            class="location-map"
        ></div>


        <script>

        const locations =
            <?= json_encode(
                $locationRows,
                JSON_HEX_TAG |
                JSON_HEX_AMP |
                JSON_HEX_APOS |
                JSON_HEX_QUOT
            ) ?>;


        const areas =
            <?= json_encode(
                $areaRows,
                JSON_HEX_TAG |
                JSON_HEX_AMP |
                JSON_HEX_APOS |
                JSON_HEX_QUOT
            ) ?>;


        function initMap() {

            const centre = locations.length

                ? {
                    lat: Number(locations[0].latitude),
                    lng: Number(locations[0].longitude)
                }

                : {
                    lat: -1.286389,
                    lng: 36.817223
                };


            const map = new google.maps.Map(
                document.getElementById('map'),
                {
                    zoom: locations.length ? 13 : 6,
                    center: centre,
                    mapTypeControl: true,
                    streetViewControl: false,
                    fullscreenControl: true
                }
            );


            locations.forEach(location => {

                const marker =
                    new google.maps.Marker({

                        position: {
                            lat: Number(location.latitude),
                            lng: Number(location.longitude)
                        },

                        map: map,

                        title: location.asset_tag
                    });


                const infoWindow =
                    new google.maps.InfoWindow({

                        content: `
                            <div style="min-width:200px">

                                <strong>
                                    ${location.asset_tag}
                                </strong>

                                <br>

                                ${location.brand ?? ''}
                                ${location.model ?? ''}

                                <br>

                                <small>
                                    ${location.full_name ?? ''}
                                </small>

                                <br>

                                <small>
                                    ${location.captured_at}
                                </small>

                            </div>
                        `
                    });


                marker.addListener(
                    'click',
                    () => infoWindow.open(map, marker)
                );

            });


            areas.forEach(area => {

                new google.maps.Circle({

                    map: map,

                    center: {
                        lat: Number(area.latitude),
                        lng: Number(area.longitude)
                    },

                    radius:
                        Number(area.radius_meters),

                    fillColor: '#99bb4f',

                    fillOpacity: 0.18,

                    strokeColor: '#6f8f2e',

                    strokeOpacity: 0.8,

                    strokeWeight: 2

                });

            });

        }

        </script>


        <script
            async
            defer
            src="https://maps.googleapis.com/maps/api/js?key=<?= e($mapKey) ?>&callback=initMap"
        ></script>

    <?php endif; ?>

</section>


<!-- HISTORY -->

<section class="panel" style="margin-top:25px">

    <div class="panel-header">

        <div>

            <h2>Location History</h2>

            <p class="muted">
                Latest 50 device check-ins.
            </p>

        </div>

    </div>


    <div class="table-wrapper">

        <table>

            <thead>

                <tr>
                    <th>Asset</th>
                    <th>Recorded By</th>
                    <th>Captured</th>
                    <th>Accuracy</th>
                    <th>Coordinates</th>
                    <th>Source</th>
                </tr>

            </thead>


            <tbody>

            <?php if (!$historyRows): ?>

                <tr>

                    <td
                        colspan="6"
                        style="text-align:center;padding:40px"
                    >
                        No location history available.
                    </td>

                </tr>

            <?php else: ?>

                <?php foreach ($historyRows as $h): ?>

                    <tr>

                        <td>
                            <strong>
                                <?= e($h['asset_tag']) ?>
                            </strong>
                        </td>

                        <td>
                            <?= e($h['full_name']) ?>
                        </td>

                        <td>
                            <?= e($h['captured_at']) ?>
                        </td>

                        <td>
                            <?= e(
                                (string)$h['accuracy_meters']
                            ) ?> m
                        </td>

                        <td>
                            <code>
                                <?= e(
                                    $h['latitude'] .
                                    ', ' .
                                    $h['longitude']
                                ) ?>
                            </code>
                        </td>

                        <td>
                            <?= e($h['source']) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</section>


<style>

.location-tools {
    display:grid;
    grid-template-columns:
        minmax(0, 1.2fr)
        minmax(320px, .8fr);
    gap:20px;
}

.form-grid {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:15px;
    margin-bottom:18px;
}

.alert-count {
    min-width:28px;
    height:28px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#fceaea;
    color:#dc3545;
    font-weight:700;
}

.location-alert {
    display:flex;
    align-items:flex-start;
    gap:12px;
    padding:13px;
    margin-bottom:10px;
    border-radius:10px;
    background:#fff8f8;
    border:1px solid #f5dddd;
}

.alert-icon {
    width:32px;
    height:32px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#fceaea;
    color:#dc3545;
    font-weight:800;
}

.empty-alert {
    text-align:center;
    padding:30px 10px;
}

.empty-alert-icon {
    width:45px;
    height:45px;
    border-radius:50%;
    background:#eaf7ee;
    color:#198754;
    display:flex;
    align-items:center;
    justify-content:center;
    margin:0 auto 10px;
}

.map-notice {
    min-height:220px;
    background:#f7f8f5;
    border:1px dashed #d8ded3;
    border-radius:12px;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    text-align:center;
}

.location-map {
    width:100%;
    height:500px;
    border-radius:12px;
    overflow:hidden;
}

.status-badge {
    display:inline-flex;
    align-items:center;
    gap:7px;
    padding:7px 11px;
    border-radius:20px;
    background:#eaf7ee;
    color:#198754;
    font-size:11px;
    font-weight:700;
}

.status-dot {
    width:7px;
    height:7px;
    border-radius:50%;
    background:#198754;
}

@media(max-width:900px) {

    .location-tools {
        grid-template-columns:1fr;
    }

}

@media(max-width:600px) {

    .form-grid {
        grid-template-columns:1fr;
    }

    .location-alert {
        flex-wrap:wrap;
    }

    .location-map {
        height:400px;
    }

}

</style>


<?php

layout_end();