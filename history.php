<?php
require_once 'db.php';
session_start();

/* =====================================================
   ACCESS CONTROL
===================================================== */
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

/* =====================================================
   INITIALIZE FILTER VARIABLES
===================================================== */
$where  = "WHERE 1=1";
$params = [];
$types  = "";

/* =====================================================
   FILTER: USER NAME
===================================================== */
if (!empty($_GET['user_name'])) {
    $userName = trim($_GET['user_name']);
    $where .= " AND u.full_name LIKE ?";
    $params[] = "%{$userName}%";
    $types .= "s";
}

/* =====================================================
   FILTER: DATE RANGE
===================================================== */
if (!empty($_GET['from']) && !empty($_GET['to'])) {
    $from = $_GET['from'] . " 00:00:00";
    $to   = $_GET['to'] . " 23:59:59";

    $where .= " AND h.action_date BETWEEN ? AND ?";
    $params[] = $from;
    $params[] = $to;
    $types .= "ss";
}

/* =====================================================
   MAIN HISTORY QUERY
===================================================== */
$query = "
SELECT 
    h.id,
    h.action_type,
    h.action_date,
    l.asset_tag,
    u.full_name AS user_name,
    a.full_name AS admin_name
FROM laptop_history h
LEFT JOIN laptops l ON h.laptop_id = l.id
LEFT JOIN users u ON h.user_id = u.id
LEFT JOIN users a ON h.admin_id = a.id
{$where}
ORDER BY h.action_date DESC
";

$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

/* =====================================================
   CHART DATA (RESPECTING FILTERS)
===================================================== */
$chartQuery = "
SELECT h.action_type, COUNT(*) AS total
FROM laptop_history h
LEFT JOIN users u ON h.user_id = u.id
{$where}
GROUP BY h.action_type
";

$chartStmt = $conn->prepare($chartQuery);

if (!empty($params)) {
    $chartStmt->bind_param($types, ...$params);
}

$chartStmt->execute();
$chartResult = $chartStmt->get_result();

$chartData = [];
while ($row = $chartResult->fetch_assoc()) {
    $chartData[$row['action_type']] = $row['total'];
}

/* =====================================================
   LOAD USER LIST FOR FILTER DROPDOWN
===================================================== */
$userList = $conn->query("SELECT DISTINCT full_name FROM users WHERE role='user' ORDER BY full_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Laptop Assignment History</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body {
    font-family: Arial, sans-serif;
    background-color: #f4f6f9;
    margin: 2rem;
}

h1 {
    margin-bottom: 1rem;
}

.filter-box {
    background: #ffffff;
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 20px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.filter-box input,
.filter-box button {
    padding: 6px 10px;
    margin-right: 8px;
}

.filter-box button {
    background-color: #b08116;
    color: #ffffff;
    border: none;
    cursor: pointer;
}

.filter-box button:hover {
    background-color: #9f7004;
}

table {
    width: 100%;
    border-collapse: collapse;
    background-color: #ffffff;
}

th, td {
    padding: 10px;
    border: 1px solid #dddddd;
    text-align: left;
}

th {
    background:linear-gradient(to right,#b08116,#99bb4f);
    color: #ffffff;
}

.chart-container {
    width: 500px;
    margin-top: 30px;
}
</style>
</head>

<body>

<h1>Laptop Assignment History</h1>

<!-- ================= FILTER SECTION ================= -->
<div class="filter-box">
<form method="GET">

<label>User:</label>
<input list="usernames"
       name="user_name"
       placeholder="Type or select user..."
       value="<?= htmlspecialchars($_GET['user_name'] ?? '') ?>">

<datalist id="usernames">
<?php while($u = $userList->fetch_assoc()): ?>
<option value="<?= htmlspecialchars($u['full_name']) ?>">
<?php endwhile; ?>
</datalist>

<label>From:</label>
<input type="date" name="from" value="<?= htmlspecialchars($_GET['from'] ?? '') ?>">

<label>To:</label>
<input type="date" name="to" value="<?= htmlspecialchars($_GET['to'] ?? '') ?>">

<button type="submit">Apply Filter</button>
<a href="history.php">Reset</a>

</form>
</div>

<!-- ================= HISTORY TABLE ================= -->
<table>
<thead>
<tr>
    <th>Asset Tag</th>
    <th>User</th>
    <th>Admin</th>
    <th>Action</th>
    <th>Date</th>
</tr>
</thead>
<tbody>
<?php if($result->num_rows > 0): ?>
    <?php while($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= htmlspecialchars($row['asset_tag']) ?></td>
        <td><?= htmlspecialchars($row['user_name']) ?></td>
        <td><?= htmlspecialchars($row['admin_name']) ?></td>
        <td><?= htmlspecialchars($row['action_type']) ?></td>
        <td><?= htmlspecialchars($row['action_date']) ?></td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
<tr>
    <td colspan="5" style="text-align:center;">No records found.</td>
</tr>
<?php endif; ?>
</tbody>
</table>

<!-- ================= CHART SECTION ================= -->
<div class="chart-container">
<canvas id="historyChart"></canvas>
</div>

<script>
const ctx = document.getElementById('historyChart');

new Chart(ctx, {
    type: 'pie',
    data: {
        labels: <?= json_encode(array_keys($chartData)) ?>,
        datasets: [{
            data: <?= json_encode(array_values($chartData)) ?>,
            backgroundColor: ['#2c7be5','#00d97e','#e63757','#f6c343','#6f42c1']
        }]
    }
});
</script>

</body>
</html>