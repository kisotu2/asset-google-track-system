<?php
require __DIR__ . '/bootstrap.php';
require_login(['super_admin']);

$users = $conn->query(
    "SELECT u.id, u.full_name, u.email, u.role, u.status, u.created_at,
            COUNT(l.id) AS assigned_assets
     FROM users u
     LEFT JOIN laptops l ON l.assigned_to = u.id AND l.status = 'Assigned'
     GROUP BY u.id, u.full_name, u.email, u.role, u.status, u.created_at
     ORDER BY FIELD(u.role, 'super_admin', 'admin', 'user'), u.full_name"
);

layout_start('Users');
?>
<div class="hero">
    <div>
        <h1>Registered users</h1>
        <p class="muted">Only super administrators can view this account register.</p>
    </div>
    <a class="button" href="register.php">Register user</a>
</div>

<section style="margin-top:24px">
    <table>
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Assigned assets</th><th>Created</th></tr></thead>
        <tbody>
        <?php while ($user = $users->fetch_assoc()): ?>
            <tr>
                <td><b><?= e($user['full_name']) ?></b></td>
                <td><?= e($user['email']) ?></td>
                <td><?= e($user['role']) ?></td>
                <td><?= e($user['status']) ?></td>
                <td><?= e((string) $user['assigned_assets']) ?></td>
                <td><?= e($user['created_at']) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</section>
<?php layout_end();
