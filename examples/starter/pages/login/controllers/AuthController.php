<?php
/**
 * A named task: reached as ?page=login&task=auth&action=... — "auth" is
 * ucfirst'd and suffixed with "Controller" to find this class.
 */

use app\core\SEController;
use app\core\SNEMLibrary;

class AuthController extends SEController
{
    /**
     * POST target of the sign-in form.
     *
     * SEUser::login() redirects to $return and exits when authentication
     * succeeds, so reaching the line after it means the attempt failed.
     */
    public function actionLogin(): void
    {
        $input = $this->snem->input;

        if (!$input->isPost()) {
            SNEMLibrary::redirect('?page=login');
        }

        $identity = new AppUserIdentity(
            (string)$input->post('username'),
            (string)$input->post('password')
        );

        $this->snem->user->login($identity, '?page=admin');

        SNEMLibrary::redirect('?page=login&error=1');
    }

    public function actionLogout(): void
    {
        $this->snem->user->logout();

        SNEMLibrary::redirect('?page=home');
    }
}
