<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/db.php';


/**
 * Escape HTML output
 */
function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}


/**
 * Load application configuration
 */
function config(): array
{
    static $c;

    if ($c === null) {
        $configFile = __DIR__ . '/config.php';
        $c = is_file($configFile) ? require $configFile : [];
    }

    return $c;
}


/**
 * Generate CSRF token
 */
function csrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}


/**
 * Verify CSRF token
 */
function verify_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $sessionToken = $_SESSION['csrf'] ?? '';
        $postedToken = $_POST['csrf'] ?? '';

        if (
            empty($sessionToken) ||
            empty($postedToken) ||
            !hash_equals($sessionToken, $postedToken)
        ) {
            http_response_code(419);
            exit('Invalid form token. Refresh the page and try again.');
        }
    }
}


/**
 * Require user login
 */
function require_login(array $roles = []): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }

    if (
        $roles &&
        !in_array($_SESSION['role'] ?? '', $roles, true)
    ) {
        http_response_code(403);
        exit('Access denied.');
    }
}


/**
 * Flash messages
 */
function flash(
    string $message = '',
    string $type = 'success'
): ?array {

    if ($message !== '') {
        $_SESSION['flash'] = [$type, $message];
        return null;
    }

    $result = $_SESSION['flash'] ?? null;

    unset($_SESSION['flash']);

    return $result;
}


/**
 * Audit logging
 */
function audit(
    mysqli $conn,
    string $action,
    string $entity,
    ?int $entityId = null,
    array $metadata = []
): void {

    $userId = $_SESSION['user_id'] ?? null;

    $json = json_encode(
        $metadata,
        JSON_THROW_ON_ERROR
    );

    $stmt = $conn->prepare(
        'INSERT INTO audit_logs
        (user_id, action, entity_type, entity_id, metadata)
        VALUES (?, ?, ?, ?, ?)'
    );

    $stmt->bind_param(
        'issis',
        $userId,
        $action,
        $entity,
        $entityId,
        $json
    );

    $stmt->execute();
}


/**
 * Start page layout
 */
function layout_start(string $title): void
{
    $flash = flash();
    ?>

    <!doctype html>

    <html lang="en">

    <head>

        <meta charset="utf-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1"
        >

        <title>
            <?= e($title) ?> · Asset Management
        </title>

        <link
            rel="stylesheet"
            href="assets/css/app.css"
        >

    </head>

    <body>

        <header>

            <a
                class="brand"
                href="dashboard.php"
            >
                IRA Asset Management
            </a>

            <?php if (!empty($_SESSION['user_id'])): ?>

                <nav>

                    <a href="dashboard.php">
                        Dashboard
                    </a>

                    <?php if (
                        in_array(
                            $_SESSION['role'] ?? '',
                            ['admin', 'super_admin'],
                            true
                        )
                    ): ?>

                        <a href="locations.php">
                            Locations
                        </a>

                        <a href="maintenance.php">
                            Maintenance
                        </a>

                    <?php endif; ?>

                    <a href="check_in.php">
                        Location check-in
                    </a>

                    <a href="logout.php">
                        Sign out
                    </a>

                </nav>

            <?php endif; ?>

        </header>

        <main>

            <?php if ($flash): ?>

                <p class="notice <?= e($flash[0]) ?>">
                    <?= e($flash[1]) ?>
                </p>

            <?php endif; ?>

    <?php
}


/**
 * End page layout
 */
function layout_end(): void
{
    ?>

        </main>

    </body>

    </html>

    <?php
}