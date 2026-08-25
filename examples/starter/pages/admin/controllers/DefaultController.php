<?php
/**
 * A protected page. beforeAction() runs ahead of every action on this
 * controller, which makes it the natural place for an access check.
 */

use app\core\SEController;
use app\core\SNEMLibrary;

class DefaultController extends SEController
{
    /**
     * checkAccess() emits a 403 and the configured forbiddenMessage when the
     * visitor is a guest; exit() stops the page from rendering at all.
     */
    public function beforeAction($action): void
    {
        if (!$this->snem->user->checkAccess()) {
            echo ' <a href="?page=login">Sign in</a>';
            exit();
        }
    }

    public function actionDefault(): void
    {
        $author = User::model()->find('username = ?', [$this->snem->user->userSession])[0] ?? null;

        // Relations resolve lazily: this runs a second query on first access.
        $posts = $author ? $author->posts : [];

        $this->render('dashboard', ['author' => $author, 'posts' => $posts]);
    }

    /**
     * Creates a post through the Active Record model.
     */
    public function actionCreate(): void
    {
        $input = $this->snem->input;

        if (!$input->isPost()) {
            SNEMLibrary::redirect('?page=admin');
        }

        $author = User::model()->find('username = ?', [$this->snem->user->userSession])[0] ?? null;
        if (!$author) {
            SNEMLibrary::redirect('?page=admin');
        }

        $post = new Post();
        $post->title = trim((string)$input->post('title'));
        $post->body = trim((string)$input->post('body'));
        $post->author_id = $author->id;
        $post->published = 1;

        // save() returns false when Post::beforeSave() rejects the record.
        $saved = $post->save();

        SNEMLibrary::redirect('?page=admin', $saved ? 'Post published.' : 'A title is required.');
    }
}
