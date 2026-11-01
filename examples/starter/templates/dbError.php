<?php
/**
 * Rendered by index.php when a database error reaches the front controller,
 * including a failed connection before run() has even started. The driver
 * error and SQL are for developers, so they only appear in debug mode.
 *
 * @var \app\core\SEDbException $e
 */

use app\core\SNEMLibrary;

$snem = SNEMLibrary::app();

// The query may have failed mid-render: drop whatever the page had buffered.
while (ob_get_level() > 0) {
    ob_end_clean();
}

if (!headers_sent()) {
    header('HTTP/1.1 500 Internal Server Error');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Something went wrong</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="site-main">
    <h1>Something went wrong</h1>
    <p>We could not reach the data for this page. Please try again shortly.</p>
    <?php if ($snem->getConfig('debugMode')): ?>
        <pre><?= htmlspecialchars($e->getMessage()) ?></pre>
    <?php endif; ?>
</main>
</body>
</html>
