<?php
/*
 * core/csrf.php - one secret token per session, required on every POST.
 *
 * The session cookie rides along with ANY request to this site, even one
 * started by a form on a different site. So a form must also carry a value
 * the other site cannot read. That value is the CSRF token.
 */

function csrf_token()
{
    // Created once per session and reused, so multiple tabs stay valid.
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/* The hidden input to drop inside every <form method="POST">. */
function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="'
         . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/* True only when the posted token matches this session's token.
   hash_equals() is constant-time, so the token cannot be guessed byte
   by byte from the response time. */
function csrf_verify($token)
{
    return is_string($token)
        && $token !== ''
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}
