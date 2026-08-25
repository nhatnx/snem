<?php
/**
 * Site-wide layout. Rendered last; `mainBody()` drops the current page's
 * captured output in place.
 *
 * @var app\core\SNEMLibrary $snem provided by the framework scope
 */

use app\core\SNEMLibrary;

$snem = SNEMLibrary::app();
$user = $snem->user;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="<?= $snem->getCharset() ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The framework rewrites this with the configured site name -->
    <title>SNEM Starter</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="?page=home"><?= htmlspecialchars($snem->getSiteName()) ?></a>
    <nav>
        <a href="?page=home">Home</a>
        <a href="?page=posts">Posts</a>
        <a href="?page=admin">Admin</a>
        <?php if ($user->isGuest): ?>
            <a href="?page=login">Sign in</a>
        <?php else: ?>
            <span class="who">Signed in as <strong><?= htmlspecialchars($user->userSession) ?></strong></span>
            <a href="?page=login&amp;task=auth&amp;action=logout">Sign out</a>
        <?php endif; ?>
    </nav>
</header>

<main class="site-main">
    <?php $snem->mainBody(); ?>
</main>

<footer class="site-footer">
    <p><?= htmlspecialchars($snem->params('tagline')) ?></p>
</footer>
</body>
</html>
