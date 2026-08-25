<?php
/**
 * The controller for ?page=posts.
 *
 * `?task=` selects the controller: it is ucfirst'd and suffixed with
 * "Controller", so the default task resolves to this class. See
 * pages/login/controllers/AuthController.php for a named task.
 *
 * Controllers live in the global namespace.
 */

use app\core\SEController;
use app\core\SNEMLibrary;

class DefaultController extends SEController
{
    /**
     * ?page=posts
     */
    public function actionDefault(): void
    {
        // #__ expands to the configured table prefix; always bind user input.
        // The limit is an int cast from config, so it is safe to interpolate.
        $limit = (int)$this->snem->params('postsPerPage');

        $posts = $this->db->getAll(
            "SELECT p.id, p.title, p.body, p.created_at, u.display_name AS author
               FROM #__posts p
               JOIN #__users u ON u.id = p.author_id
              WHERE p.published = ?
           ORDER BY p.created_at DESC
              LIMIT {$limit}",
            [1]
        );

        $this->render('list', ['posts' => $posts]);
    }

    /**
     * ?page=posts&action=view&id=1
     */
    public function actionView(): void
    {
        $id = (int)$this->snem->input->get('id');

        $post = $this->db->getRow(
            "SELECT p.*, u.display_name AS author
               FROM #__posts p
               JOIN #__users u ON u.id = p.author_id
              WHERE p.id = ?",
            [$id]
        );

        if (empty($post)) {
            SNEMLibrary::redirect('?page=posts');
        }

        $this->render('view', ['post' => $post]);
    }

    /**
     * ?page=posts&action=api — sends JSON and exits, so the site template
     * is never rendered.
     */
    public function actionApi(): void
    {
        $posts = $this->db->getAll(
            "SELECT id, title, created_at FROM #__posts WHERE published = ? ORDER BY id",
            [1]
        );

        $this->jsonResponse(['count' => count($posts), 'posts' => $posts]);
    }
}
