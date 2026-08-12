<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

require __DIR__ . '/bootstrap.php';

require_login(['admin', 'super_admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $asset = (int)($_POST['asset_id'] ?? 0);
    $issue = trim($_POST['issue'] ?? '');
    $cost = (float)($_POST['cost'] ?? 0);

    if ($asset && $issue !== '') {
        $user = (int)$_SESSION['user_id'];

        $stmt = $conn->prepare(
            "INSERT INTO maintenance_records
            (laptop_id, reported_by, issue_description, repair_cost, status)
            VALUES (?, ?, ?, ?, 'open')"
        );

        $stmt->bind_param('iisd', $asset, $user, $issue, $cost);
        $stmt->execute();

        $stmt = $conn->prepare(
            "UPDATE laptops SET status = 'Maintenance' WHERE id = ?"
        );

        $stmt->bind_param('i', $asset);
        $stmt->execute();

        audit(
            $conn,
            'maintenance_reported',
            'maintenance_record',
            $conn->insert_id,
            ['asset_id' => $asset]
        );

        flash('Maintenance record created and asset marked for maintenance.');
    }

    header('Location: maintenance.php');
    exit;
}

$assets = $conn->query("
    SELECT id, asset_tag, brand, model
    FROM laptops
    WHERE status NOT IN ('Retired', 'Disposed')
    ORDER BY asset_tag
");

$risks = $conn->query("
    SELECT
        l.id,
        l.asset_tag,
        l.brand,
        l.model,
        l.status,
        l.warranty_expiry,
        COUNT(m.id) AS repairs,
        COALESCE(SUM(m.status != 'resolved'), 0) AS open_repairs,

        CASE
            WHEN l.warranty_expiry IS NOT NULL
                 AND l.warranty_expiry < CURDATE()
                THEN 'Warranty expired'

            WHEN COALESCE(SUM(m.status != 'resolved'), 0) > 0
                THEN 'Repair required'

            WHEN COUNT(m.id) >= 3
                THEN 'Repeated repairs'

            ELSE 'Normal'
        END AS risk

    FROM laptops l
    LEFT JOIN maintenance_records m
        ON m.laptop_id = l.id

    GROUP BY
        l.id,
        l.asset_tag,
        l.brand,
        l.model,
        l.status,
        l.warranty_expiry

    ORDER BY
        CASE
            WHEN l.warranty_expiry IS NOT NULL
                 AND l.warranty_expiry < CURDATE()
                THEN 1

            WHEN COALESCE(SUM(m.status != 'resolved'), 0) > 0
                THEN 2

            WHEN COUNT(m.id) >= 3
                THEN 3

            ELSE 4
        END,
        repairs DESC
");

layout_start('Maintenance');
?>

<div class="hero">
    <div>
        <h1>Maintenance and lifecycle</h1>
        <p class="muted">
            Rule-based predictive prioritisation combines open repairs,
            repeat repair history, and warranty expiry.
        </p>
    </div>
</div>

<section class="split">

    <div class="panel">
        <h2>Report maintenance issue</h2>

        <form method="post">

            <input
                type="hidden"
                name="csrf"
                value="<?= e(csrf()) ?>"
            >

            <label>
                Asset

                <select name="asset_id" required>
                    <option value="">Choose asset</option>

                    <?php while ($a = $assets->fetch_assoc()): ?>
                        <option value="<?= $a['id'] ?>">
                            <?= e($a['asset_tag'] . ' — ' . $a['brand'] . ' ' . $a['model']) ?>
                        </option>
                    <?php endwhile; ?>

                </select>
            </label>

            <label>
                Issue description
                <textarea name="issue" required></textarea>
            </label>

            <label>
                Estimated repair cost
                <input
                    name="cost"
                    type="number"
                    step="0.01"
                    min="0"
                    value="0"
                >
            </label>

            <button type="submit">
                Create maintenance record
            </button>

        </form>
    </div>


    <div class="panel">
        <h2>How risk is calculated</h2>

        <p>
            <b>Repair required:</b>
            an unresolved maintenance record exists.
        </p>

        <p>
            <b>Repeated repairs:</b>
            three or more recorded repairs.
        </p>

        <p>
            <b>Warranty expired:</b>
            warranty date has passed.
        </p>

        <p class="muted">
            This is a transparent baseline. A later Flask ML service
            can replace this score once sufficient historical data exists.
        </p>
    </div>

</section>


<section style="margin-top:24px">

    <h2>Asset risk register</h2>

    <table>

        <thead>
            <tr>
                <th>Asset</th>
                <th>Status</th>
                <th>Warranty</th>
                <th>Repair records</th>
                <th>Open repairs</th>
                <th>Risk signal</th>
            </tr>
        </thead>

        <tbody>

            <?php while ($r = $risks->fetch_assoc()): ?>

                <tr>

                    <td>
                        <b><?= e($r['asset_tag']) ?></b><br>
                        <?= e($r['brand'] . ' ' . $r['model']) ?>
                    </td>

                    <td>
                        <?= e($r['status']) ?>
                    </td>

                    <td>
                        <?= e($r['warranty_expiry'] ?? 'Not recorded') ?>
                    </td>

                    <td>
                        <?= $r['repairs'] ?>
                    </td>

                    <td>
                        <?= $r['open_repairs'] ?>
                    </td>

                    <td class="<?= $r['risk'] === 'Normal' ? '' : 'danger' ?>">
                        <?= e($r['risk']) ?>
                    </td>

                </tr>

            <?php endwhile; ?>

        </tbody>

    </table>

</section>

<?php layout_end(); ?>