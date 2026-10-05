<?php




ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../error/error.log');

date_default_timezone_set('Africa/Accra');


ob_start();


$document_root = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$application_root = realpath(__DIR__ . '/..');

if ($document_root === false || $application_root === false
    || strpos($application_root, $document_root) !== 0) {
    error_log('Unable to determine the application URL from the Apache document root.');
    define('BASE_URL', '/');
} else {
    $application_path = substr($application_root, strlen($document_root));
    $application_path = str_replace(DIRECTORY_SEPARATOR, '/', $application_path);
    define('BASE_URL', '/' . trim($application_path, '/') . '/');
}


/* ------------------------------------------------------------ CHECKPOINT: start and secure the session - session_start() must run before $_SESSION can be read written anywhere else in the app ------------------------------------------------------------. */


define('SESSION_TIMEOUT', 1800);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false,
        'httponly' => true, // JavaScript cannot read the session cookie.
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/db_class.php';




function get_ip() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if ($ip === '::1' || $ip === '127.0.0.1') {
        return '127.0.0.1';
    }
    return $ip;
}

function redirect($url) {
    if (strpos($url, 'http') !== 0) {
        $url = BASE_URL . ltrim($url, '/');
    }

    header('Location: ' . $url);
    exit;
}


function core_get_user_id() {
    return $_SESSION['customer_id'] ?? null;
}


function core_get_user_role() {
    return $_SESSION['user_role'] ?? null;
}


/* ------------------------------------------------------------ CHECKPOINT: check for login A function that checks if a "logged in" session value is set. */
function is_logged_in() {
    return isset($_SESSION['customer_id']);
}

function is_admin() {
    return core_get_user_role() === 1;
}

/* Authorisation gate for customer-only pages. */

function require_login() {
    if (!is_logged_in()) {
        $_SESSION['error'] = 'Please log in to continue.';
        redirect('views/login.php');
    }
}

/* Authorisation gate for admin-only pages. */
function require_admin() {
    if (!is_admin()) {
        $_SESSION['error'] = 'Access denied. Admin only.';
        redirect('index.php');
    }
}


/* ------------------------------------------------------------ CHECKPOINT: secure logout A function that clears all session data, deletes the session cookie, destroys the session, and starts a fresh one - used by both a manual "log out" click and the automatic checks below. */

function secureLogout() {
    $_SESSION = [];

    // 2.
    // Without this, a session ID captured before login stays.
    // valid afterwards - that is "session fixation".
    
    session_regenerate_id(true);

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $p['path'],
            $p['domain'],
            $p['secure'],
            $p['httponly']
        );
    }

    // 4.
    session_destroy();

    // 5.
    // to store "you have logged out" or "your session expired",.
    session_start();
    session_regenerate_id(true);
}


/* ------------------------------------------------------------ CHECKPOINT: session timeout Track the time of the last request. */
function checkSessionTimeout() {
    $now = time();

    if (isset($_SESSION['last_activity'])) {
        if (($now - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
            secureLogout();
            $_SESSION['error'] = 'Your session expired. Please log in again.';
            redirect('views/login.php');
        }
    }

    $_SESSION['last_activity'] = $now;
}


/* ------------------------------------------------------------ CHECKPOINT: detect session hijacking Store the user's IP address and browser (User-Agent) at login. */
function checkSessionHijack() {
    if (!isset($_SESSION['fingerprint_ip'])) {
        // First page load after login - record the fingerprint.
        $_SESSION['fingerprint_ip'] = get_ip();
        return;
    }

    if ($_SESSION['fingerprint_ip'] !== get_ip()) {
        // The same session cookie is suddenly being used from a.
        secureLogout();
        $_SESSION['error'] = 'Security alert: signed in from another location.';
        redirect('views/login.php');
    }
}


/* ------------------------------------------------------------ CHECKPOINT: actually run the session check(s) above Whatever function ties this all together (e.g. */
function sessionSecurity() {
    if (!is_logged_in()) {
        return;
    }

    checkSessionTimeout();
    checkSessionHijack();
}

sessionSecurity();
