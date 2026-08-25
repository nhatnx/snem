<?php
/**
 * Rendered instead of the whole app while `maintenanceMode.enabledMode`
 * is true in config.php. Nothing else boots — not even the database.
 */

use app\core\SNEMLibrary;

$snem = SNEMLibrary::app();
$message = $snem ? $snem->getMaintenanceMessage() : 'Down for maintenance.';

if (!headers_sent()) {
    header('HTTP/1.1 503 Service Unavailable');
    header('Retry-After: 3600');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Maintenance</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="site-main">
    <h1>Be right back</h1>
    <p><?= htmlspecialchars($message) ?></p>
</main>
</body>
</html>
