<?php
/**
 * Rendered by the framework when `?page=` names a page that does not exist.
 * The 404 status header has already been sent at this point.
 */

use app\core\SNEMLibrary;

$snem = SNEMLibrary::app();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Not found</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="site-main">
    <h1>404</h1>
    <p><?= htmlspecialchars($snem->getNotFoundMessage()) ?></p>
    <p><a href="?page=home">Back to the home page</a></p>
</main>
</body>
</html>
