<?php
/**
 * Default view for ?page=login — no controller matches the default task, so
 * the base SEController renders the view named after the page.
 *
 * The form posts back with task/action as hidden fields, which is how a POST
 * reaches AuthController::actionLogin().
 */

use app\core\SNEMLibrary;

$snem = SNEMLibrary::app();
$error = $snem->input->get('error');
?>
<h1>Sign in</h1>

<?php if (!$snem->user->isGuest): ?>
    <p>You are signed in as <strong><?= htmlspecialchars($snem->user->userSession) ?></strong>.</p>
    <p><a href="?page=login&amp;task=auth&amp;action=logout">Sign out</a></p>
<?php else: ?>
    <?php if ($error): ?>
        <p class="error">Those credentials were not recognised.</p>
    <?php endif; ?>

    <form method="post" action="index.php" class="card">
        <input type="hidden" name="page" value="login">
        <input type="hidden" name="task" value="auth">
        <input type="hidden" name="action" value="login">

        <label>
            Username
            <input type="text" name="username" value="demo" autocomplete="username" required>
        </label>
        <label>
            Password
            <input type="password" name="password" value="demo1234" autocomplete="current-password" required>
        </label>

        <button type="submit">Sign in</button>
    </form>

    <p class="hint">The seeded demo account is <code>demo</code> / <code>demo1234</code>.</p>
<?php endif; ?>
