<?php
/**
 * logout.php - ends the session.
 *
 * All the fiddly work (clearing data, regenerating the session id,
 * deleting the cookie, starting a fresh session) lives in
 * secureLogout() inside core.php, because the timeout and hijack
 * checks need to do exactly the same thing. This file just calls it.
 */

require_once __DIR__ . '/core/core.php';

secureLogout();

$_SESSION['error'] = 'You have been logged out.';

redirect('index.php');
