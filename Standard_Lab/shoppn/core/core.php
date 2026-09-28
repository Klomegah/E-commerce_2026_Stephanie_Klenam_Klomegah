<?php

// ============================================================
// core.php
// ------------------------------------------------------------
// Included at the top of every page: require_once __DIR__ . '/core/core.php';
// Anything that must happen on EVERY page load lives here.
// It is NOT the place for SQL - that is core/db_class.php.
// ============================================================


// ------------------------------------------------------------
// 1. ERROR HANDLING  (added)
// Log errors to a file instead of showing them to the visitor.
// Showing them leaks your folder paths and database structure.

// ------------------------------------------------------------
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../error/error.log');

date_default_timezone_set('Africa/Accra');


/* ------------------------------------------------------------
   CHECKPOINT: start output buffering (ob_start())
   header('Location: ...') redirects fail if any output was already
   sent to the browser. Buffering output here means pages further
   down the line can still redirect safely even after printing
   something.
   ------------------------------------------------------------ */
ob_start();


// ------------------------------------------------------------
// Site base path. One line controls every link in the whole app,
// so moving the project never breaks a single href.
// Must match the folder path exactly, including the repository
// root folder, or the stylesheet and every link will 404.
// ------------------------------------------------------------
define('BASE_URL', '/~stephanie.klomegah/E-commerce_2026_Stephanie_Klenam_Klomegah/Standard_Lab/shoppn/');


/* ------------------------------------------------------------
   CHECKPOINT: start and secure the session
   - session_start() must run before $_SESSION can be read/written
     anywhere else in the app
   - on a real (HTTPS) server, harden the session cookie:
     session.cookie_secure, session.cookie_httponly, session.cookie_samesite
   ------------------------------------------------------------ */

// How long a logged-in user may sit idle before being logged out.
define('SESSION_TIMEOUT', 1800); // 30 minutes

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false,  // set to true on real HTTPS hosting
        'httponly' => true,   // JavaScript cannot read the session cookie
        'samesite' => 'Lax',  // blocks cross-site form submissions
    ]);
    session_start();
}

// The database base class. Child classes (CustomerClass etc.) extend it.
require_once __DIR__ . '/db_class.php';


// ============================================================
// SHARED HELPER FUNCTIONS
// ============================================================


/**
 * The visitor's IP address.
 *
 * The cart table stores guest carts against ip_add, so this value
 * decides whose cart a product gets added to. localhost can arrive
 * as either ::1 or 127.0.0.1 depending on the browser, so both are
 * normalised - otherwise one person gets two separate carts.
 * 
 */
function get_ip() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if ($ip === '::1' || $ip === '127.0.0.1') {
        return '127.0.0.1';
    }
    return $ip;
}

/**
 * Send the browser to another page and stop everything.
 *
 * header() only QUEUES a redirect; it does not halt the script.
 * Without exit, the rest of the page keeps printing and some
 * browsers show that output instead of following the redirect.
 *
 * Bare paths like 'views/login.php' get BASE_URL prepended.
 */
function redirect($url) {
    if (strpos($url, 'http') !== 0) {
        $url = BASE_URL . ltrim($url, '/');
    }
    header('Location: ' . $url);
    exit;
}


/* ------------------------------------------------------------
   CHECKPOINT: get the logged-in user's id
   A small getter so pages don't touch $_SESSION directly - they
   just call something like core_get_user_id().
   ------------------------------------------------------------ */
function core_get_user_id() {
    return $_SESSION['customer_id'] ?? null;
}


/* ------------------------------------------------------------
   CHECKPOINT: get the logged-in user's role
   Same idea as above, for role (e.g. admin, customer, staff) so
   pages can decide what to show based on who's looking.
   ------------------------------------------------------------ */
function core_get_user_role() {
    return $_SESSION['user_role'] ?? null;
}


/* ------------------------------------------------------------
   CHECKPOINT: check for login
   A function that checks if a "logged in" session value is set.
   ------------------------------------------------------------
   NOTE - a deliberate difference from the original note above.

   This does NOT force a login on every page. "Not logged in" is a
   perfectly valid state for the home page, the register page and
   the login page itself. If this redirected everyone, anonymous
   visitors could not browse the shop at all.

   Enforcing a login is a separate, opt-in step - require_login() -
   which each protected page calls deliberately at the top, before
   it prints any HTML.
   ------------------------------------------------------------ */
function is_logged_in() {
    return isset($_SESSION['customer_id']);
}

/**
 * True when the signed-in user has user_role 1 (admin).
 *
 * Strict === is safe only because the login action stores the role
 * as an integer. mysqli can return the string "1" instead of the
 * number 1, and "1" === 1 is FALSE - which would silently hide the
 * admin links forever. That is why login_action.php casts to (int).
 */
function is_admin() {
    return core_get_user_role() === 1;
}

/**
 * Authorisation gate for customer-only pages.
 * Call as the very first line, before any HTML is output.
 */
function require_login() {
    if (!is_logged_in()) {
        $_SESSION['error'] = 'Please log in to continue.';
        redirect('views/login.php');
    }
}

/**
 * Authorisation gate for admin-only pages.
 */
function require_admin() {
    if (!is_admin()) {
        $_SESSION['error'] = 'Access denied. Admin only.';
        redirect('index.php');
    }
}


/* ------------------------------------------------------------
   CHECKPOINT: secure logout
   A function that clears all session data, deletes the session
   cookie, destroys the session, and starts a fresh one - used by
   both a manual "log out" click and the automatic checks below.
   ------------------------------------------------------------ */
function secureLogout() {
    // 1. Empty the data.
    $_SESSION = [];

    // 2. Issue a brand-new session ID and delete the old one.
    //    Without this, a session ID captured before login stays
    //    valid afterwards - that is "session fixation".
    session_regenerate_id(true);

    // 3. Delete the cookie in the browser.
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

    // 4. Delete the session file on the server.
    session_destroy();

    // 5. Start a fresh empty session. Without this there is nowhere
    //    to store "you have logged out" or "your session expired",
    //    and the message silently vanishes.
    session_start();
    session_regenerate_id(true);
}


/* ------------------------------------------------------------
   CHECKPOINT: session timeout
   Track the time of the last request. If too much time has passed
   since then, log the user out automatically.
   ------------------------------------------------------------ */
function checkSessionTimeout() {
    $now = time();

    if (isset($_SESSION['last_activity'])) {
        if (($now - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
            secureLogout();
            $_SESSION['error'] = 'Your session expired. Please log in again.';
            redirect('views/login.php');
        }
    }

    // Store "right now" so the countdown restarts on this request.
    $_SESSION['last_activity'] = $now;
}


/* ------------------------------------------------------------
   CHECKPOINT: detect session hijacking
   Store the user's IP address and browser (User-Agent) at login.
   On every page load, compare them to the current request - if
   they don't match, something is wrong, so log the user out.
   ------------------------------------------------------------
   Only the IP is enforced. The User-Agent is deliberately NOT
   compared: browsers update themselves mid-session and phones
   roam between Wi-Fi and mobile data, either of which would log
   an honest user out at random. IP alone catches the realistic
   attacks with far fewer false alarms.
   ------------------------------------------------------------ */
function checkSessionHijack() {
    if (!isset($_SESSION['fingerprint_ip'])) {
        // First page load after login - record the fingerprint.
        $_SESSION['fingerprint_ip'] = get_ip();
        return;
    }

    if ($_SESSION['fingerprint_ip'] !== get_ip()) {
        // The same session cookie is suddenly being used from a
        // different IP address. Treat it as stolen.
        secureLogout();
        $_SESSION['error'] = 'Security alert: signed in from another location.';
        redirect('views/login.php');
    }
}


/* ------------------------------------------------------------
   CHECKPOINT: actually run the session check(s) above
   Whatever function ties this all together (e.g. sessionSecurity())
   should be called here, so simply including this file is enough
   to protect a page - no extra function calls needed on every page.
   ------------------------------------------------------------ */
function sessionSecurity() {
    // Nothing to secure if nobody is signed in. Public pages stay public.
    if (!is_logged_in()) {
        return;
    }

    checkSessionTimeout();
    checkSessionHijack();
}

sessionSecurity();
