<?php
/**
 * @var User|null $author
 * @var Post[] $posts
 */

use app\core\SNEMLibrary;

$snem = SNEMLibrary::app();
$message = $snem->input->get('msg');
?>
<h1>Admin</h1>

<?php if ($message): ?>
    <p class="flash"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<p>
    Signed in as <strong><?= htmlspecialchars($author->display_name ?? '?') ?></strong>
    — <?= count($posts) ?> post(s) written.
</p>

<h2>New post</h2>
<form method="post" action="index.php" class="card">
    <input type="hidden" name="page" value="admin">
    <input type="hidden" name="action" value="create">

    <label>
        Title
        <input type="text" name="title" required>
    </label>
    <label>
        Body
        <textarea name="body" rows="4"></textarea>
    </label>

    <button type="submit">Publish</button>
</form>

<h2>Your posts</h2>
<?php if (empty($posts)): ?>
    <p class="empty">Nothing yet.</p>
<?php else: ?>
    <ul class="post-list">
        <?php foreach ($posts as $post): ?>
            <li>
                <a href="?page=posts&amp;action=view&amp;id=<?= (int)$post->id ?>">
                    <?= htmlspecialchars($post->title) ?>
                </a>
                <span class="meta"><?= htmlspecialchars((string)$post->created_at) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
