<?php

require __DIR__ . '/bootstrap.php';

// If the user is already logged in, take them straight to the dashboard.
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Make sure the form request is valid.
    verify_csrf();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Look for the user using their email address.
    $stmt = $conn->prepare(
        "SELECT id, full_name, password, role, status
         FROM users
         WHERE email = ?
         LIMIT 1"
    );

    $stmt->bind_param('s', $email);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();

    /*
     * The login is only successful when:
     * 1. The account exists.
     * 2. The account is active.
     * 3. The password matches.
     */
    $loginSuccessful =
        $user &&
        $user['status'] === 'active' &&
        password_verify($password, $user['password']);

    if (!$loginSuccessful) {

        // Keep the message general so we don't reveal whether
        // an email exists in the system.
        $error = 'Invalid email, password, or account status.';

    } else {

        // Create a new session ID after successful login
        // to help protect against session fixation.
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['full_name'];

        // Record the login in the system audit trail.
        audit(
            $conn,
            'login',
            'user',
            (int) $user['id']
        );

        // Send the user to their dashboard.
        header('Location: dashboard.php');
        exit;
    }
}

layout_start('Sign in');
?>

<section class="panel" style="max-width: 460px; margin: 70px auto;">

    <h1>Welcome back</h1>

    <p class="muted">
        Sign in to access your Asset Management account.
    </p>

    <?php if ($error): ?>
        <p class="notice error">
            <?= e($error) ?>
        </p>
    <?php endif; ?>

    <form method="post">

        <input
            type="hidden"
            name="csrf"
            value="<?= csrf() ?>"
        >

        <label>
            Email
            <input
                type="email"
                name="email"
                autocomplete="email"
                placeholder="Enter your email address"
                required
            >
        </label>

        <label>
            Password
            <input
                type="password"
                name="password"
                autocomplete="current-password"
                placeholder="Enter your password"
                required
            >
        </label>

        <button type="submit">
            Sign in
        </button>

    </form>

    <p class="muted">
        First time setting up the system?
        <a href="create_super_admin.php">
            Create the super administrator
        </a>.
    </p>

</section>

<?php
layout_end();
?>