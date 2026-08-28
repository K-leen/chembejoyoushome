<?php
/**
 * admin_config.php
 *
 * Simple admin configuration for the lightweight admin pages.
 *
 * For production, replace these defaults with environment variables or a
 * properly stored password hash. The file below generates a password hash at
 * runtime from the $DEFAULT_ADMIN_PASSWORD value — change that value.
 */

// Default credentials (change immediately)
const ADMIN_USERNAME = 'admin';
const DEFAULT_ADMIN_PASSWORD = 'admin123'; // change this

// If environment variables are present, prefer them. Otherwise generate a
// hash from the default password above (acceptable for development).
$envUser = getenv('ADMIN_USERNAME');
$envHash = getenv('ADMIN_PASSWORD_HASH');

if ($envUser && $envHash) {
    define('ADMIN_USER', $envUser);
    define('ADMIN_PASS_HASH', $envHash);
} else {
    define('ADMIN_USER', ADMIN_USERNAME);
    // Generate a hash for the default password. For a persistent hash,
    // replace this with the output from password_hash(...) and store it.
    define('ADMIN_PASS_HASH', password_hash(DEFAULT_ADMIN_PASSWORD, PASSWORD_DEFAULT));
}

// Default sender for admin reply emails. Can be overridden with env vars.
$envFrom = getenv('ADMIN_FROM_EMAIL');
$envFromName = getenv('ADMIN_FROM_NAME');
if ($envFrom) {
    define('ADMIN_FROM_EMAIL', $envFrom);
} else {
    define('ADMIN_FROM_EMAIL', 'no-reply@example.com');
}
if ($envFromName) {
    define('ADMIN_FROM_NAME', $envFromName);
} else {
    define('ADMIN_FROM_NAME', ADMIN_USER);
}

// Session cookie settings - adjust as needed
function admin_session_start()
{
    if (session_status() === PHP_SESSION_NONE) {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}
