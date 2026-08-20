<?php

require __DIR__ . '/bootstrap.php';

/*
 * The user must have successfully passed
 * the email/password stage first.
 */
if (empty($_SESSION['pending_login_user_id'])) {

    header('Location: login.php');
    exit;
}

$error = '';

$userId = (int) $_SESSION['pending_login_user_id'];

$email = $_SESSION['pending_login_email'] ?? '';

$name = $_SESSION['pending_login_name'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $otp = trim($_POST['otp'] ?? '');

    /*
     * OTP must be exactly six digits.
     */
    if (!preg_match('/^\d{6}$/', $otp)) {

        $error =
            'Please enter the 6-digit verification code.';

    } else {

        if (verify_login_otp($conn, $userId, $otp)) {

            /*
             * OTP is correct.
             *
             * Now we can finally authenticate the user.
             */
            session_regenerate_id(true);

            $_SESSION['user_id'] = $userId;

            $_SESSION['role'] =
                $_SESSION['pending_login_role'];

            $_SESSION['name'] =
                $_SESSION['pending_login_name'];

            /*
             * Remove temporary login information.
             */
            unset(
                $_SESSION['pending_login_user_id'],
                $_SESSION['pending_login_email'],
                $_SESSION['pending_login_name'],
                $_SESSION['pending_login_role'],
                $_SESSION['otp_sent_at']
            );

            /*
             * Record successful login.
             */
            audit(
                $conn,
                'login',
                'user',
                $userId,
                [
                    'method' => 'password_and_email_otp'
                ]
            );

            header(
                'Location: dashboard.php'
            );

            exit;

        } else {

            $error =
                'Invalid or expired verification code.';
        }
    }
}

layout_start('Email Verification');
?>

<section
    class="panel"
    style="max-width:460px;margin:70px auto;"
>

    <h1>Verify your email</h1>

    <p class="muted">

        Hello <?= e($name) ?>.

        We have sent a 6-digit verification code to:

        <strong>
            <?= e($email) ?>
        </strong>

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
            value="<?= e(csrf()) ?>"
        >

        <label>
            Verification Code

            <input
                type="text"
                name="otp"
                inputmode="numeric"
                pattern="[0-9]{6}"
                maxlength="6"
                autocomplete="one-time-code"
                placeholder="Enter 6-digit code"
                required
                autofocus
            >
        </label>

        <button type="submit">
            Verify & Sign In
        </button>

    </form>

    <p class="muted">

        The code expires after
        <strong>10 minutes</strong>.

    </p>

    <p>

        <a href="login.php">
            Back to login
        </a>

    </p>

</section>

<?php
layout_end();
?>