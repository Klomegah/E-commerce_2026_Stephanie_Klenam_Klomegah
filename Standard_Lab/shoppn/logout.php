<?php
/**
 * logout.php - ends the session.
 *
  */

require_once __DIR__ . '/core/core.php';

secureLogout();

$_SESSION['error'] = 'You have been logged out.';

redirect('index.php');
