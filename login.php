<?php
<<<<<<< HEAD

require __DIR__ . '/bootstrap.php';

// If the user is already logged in, take them straight to the dashboard.
=======
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

require __DIR__ . '/bootstrap.php';

// Already authenticated.
>>>>>>> 81f8c5e (2FA Authentication)
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

<<<<<<< HEAD
    // Make sure the form request is valid.
=======
>>>>>>> 81f8c5e (2FA Authentication)
    verify_csrf();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

<<<<<<< HEAD
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
=======
    if ($email === '' || $password === '') {

        $error = 'Please enter your email and password.';

    } else {

        $stmt = $conn->prepare(
            "SELECT id, full_name, email, password, role, status
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->bind_param(
            's',
            $email
        );

        $stmt->execute();

        $user = $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        $loginSuccessful =
            $user &&
            $user['status'] === 'active' &&
            password_verify(
                $password,
                $user['password']
            );

        if (!$loginSuccessful) {

            $error =
                'Invalid email, password, or account status.';

        } else {

            /*
             * Generate the OTP.
             */
            $otp = create_login_otp(
                $conn,
                (int)$user['id']
            );

            /*
             * Send OTP to the user's registered email.
             */
            $emailSent = send_login_otp(
                $user['email'],
                $user['full_name'],
                $otp
            );

            if (!$emailSent) {

                $error =
                    'We could not send the verification code. ' .
                    'Please try again later.';

            } else {

                /*
                 * Do NOT authenticate the user yet.
                 *
                 * Store only temporary login information.
                 */
                $_SESSION['pending_login_user_id'] =
                    (int)$user['id'];

                $_SESSION['pending_login_email'] =
                    $user['email'];

                $_SESSION['pending_login_name'] =
                    $user['full_name'];

                $_SESSION['pending_login_role'] =
                    $user['role'];

                $_SESSION['otp_sent_at'] =
                    time();

                header(
                    'Location: verify_otp.php'
                );

                exit;
            }
        }
>>>>>>> 81f8c5e (2FA Authentication)
    }
}

layout_start('Sign in');
?>

<<<<<<< HEAD
<section class="panel" style="max-width: 460px; margin: 70px auto;">
=======
<section
    class="panel"
    style="max-width:460px;margin:70px auto;"
>
>>>>>>> 81f8c5e (2FA Authentication)

    <h1>Welcome back</h1>

    <p class="muted">
        Sign in to access your Asset Management account.
    </p>

    <?php if ($error): ?>
<<<<<<< HEAD
        <p class="notice error">
            <?= e($error) ?>
        </p>
=======

        <p class="notice error">
            <?= e($error) ?>
        </p>

>>>>>>> 81f8c5e (2FA Authentication)
    <?php endif; ?>

    <form method="post">

        <input
            type="hidden"
            name="csrf"
<<<<<<< HEAD
            value="<?= csrf() ?>"
=======
            value="<?= e(csrf()) ?>"
>>>>>>> 81f8c5e (2FA Authentication)
        >

        <label>
            Email
<<<<<<< HEAD
=======

>>>>>>> 81f8c5e (2FA Authentication)
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
<<<<<<< HEAD
=======

>>>>>>> 81f8c5e (2FA Authentication)
            <input
                type="password"
                name="password"
                autocomplete="current-password"
                placeholder="Enter your password"
                required
            >
        </label>

        <button type="submit">
<<<<<<< HEAD
            Sign in
=======
            Continue
>>>>>>> 81f8c5e (2FA Authentication)
        </button>

    </form>

    <p class="muted">
        First time setting up the system?
<<<<<<< HEAD
=======

>>>>>>> 81f8c5e (2FA Authentication)
        <a href="create_super_admin.php">
            Create the super administrator
        </a>.
    </p>

</section>

<?php
layout_end();
?>