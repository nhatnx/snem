<?php
/**
 * Rendered by DefaultController::actionDefault().
 *
 * Entries of the $args array passed to render() arrive as local variables.
 * Views are included from inside the controller method, so $this is the
 * controller here.
 *
 * @var stdClass[] $posts
 * @var DefaultController $this
 */
?>
<h1>Posts</h1>
<p>Also available as <a href="?page=posts&amp;action=api">JSON</a>.</p>

<?php if (empty($posts)): ?>
    <p class="empty">No posts yet. <a href="?page=admin">Write the first one.</a></p>
<?php else: ?>
    <ul class="post-list">
        <?php foreach ($posts as $post): ?>
            <?php
            // A partial, rendered once per row: render() uses require rather
            // than require_once, so the same file may be included repeatedly.
            $this->render('_summary', ['post' => $post]);
            ?>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
