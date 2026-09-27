<?php
/**
 * views/layout/footer.php - closes the page opened by header.php.
 *
 * A PARTIAL: no core.php include, no SQL, no logic beyond the year.
 */

if (!function_exists('is_logged_in')) {
    exit('Direct access forbidden.');
}
?>
<footer class="site-footer">
    <p>&copy; <?= date('Y') ?> Shoppn. All rights reserved.</p>
</footer>
</body>
</html>
