<?php
/**
 * One row of the post list. Included once per post by list.php.
 *
 * @var stdClass $post
 */

use app\core\SNEMLibrary;

// $snem->helper->text resolves to TextHelper in app/helpers/.
$text = SNEMLibrary::app()->helper->text;
?>
<li class="post-summary">
    <h3>
        <a href="?page=posts&amp;action=view&amp;id=<?= (int)$post->id ?>">
            <?= htmlspecialchars($post->title) ?>
        </a>
    </h3>
    <p class="meta">
        by <?= htmlspecialchars($post->author) ?>
        on <?= $text->date($post->created_at) ?>
    </p>
    <p><?= htmlspecialchars($text->excerpt($post->body, 120)) ?></p>
</li>
