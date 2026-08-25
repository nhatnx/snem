<?php
/**
 * Rendered by DefaultController::actionView().
 *
 * @var stdClass $post
 */

use app\core\SNEMLibrary;

$text = SNEMLibrary::app()->helper->text;
?>
<article class="post">
    <h1><?= htmlspecialchars($post->title) ?></h1>
    <p class="meta">
        by <?= htmlspecialchars($post->author) ?>
        on <?= $text->date($post->created_at) ?>
    </p>
    <div class="body">
        <?= nl2br(htmlspecialchars($post->body)) ?>
    </div>
    <p><a href="?page=posts">&larr; All posts</a></p>
</article>
