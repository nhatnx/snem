<?php

namespace app\core\models;

use app\core\SEModel;
use app\core\SNEMLibrary;

/**
 * Class SEUser
 *
 * @property mixed|null $userSession
 * @property bool $isGuest
 */
class SEUser extends SEModel
{
    private ?string $userSessionName;

    public function __construct()
    {
        parent::__construct();

        $this->userSessionName = SNEMLibrary::app()->getConfig('user_session_name') ?? 'user';
    }

    public function getUserSession()
    {
        return SNEMLibrary::app()->input->session($this->userSessionName);
    }

    public function setUserSession($sessionValue): void
    {
        $_SESSION[$this->userSessionName] = $sessionValue;
    }

    public function getIsGuest(): bool
    {
        return empty($this->getUserSession());
    }

    protected function beforeLogin($userId): bool
    {
        return true;
    }

    protected function afterLogin(): void
    {
        // this method is called after login
    }

    public function login(?SEUserIdentity $userIdentity, ?string $return = null): bool
    {
        if ($userIdentity === null) {
            return false;
        }

        $return = $return ?? $_SERVER['HTTP_REFERER'] ?? '';
        $return = str_replace('&logout=logout', '', $return);

        if ($this->beforeLogin($userIdentity->username)) {
            if ($userIdentity->authenticate()) {
                $this->setUserSession($userIdentity->username);

                SNEMLibrary::redirect($return);
            } else {
                $this->setUserSession(null);
            }

            $this->afterLogin();
        }

        return !$this->getIsGuest();
    }

    public function logout(): void
    {
        if (!empty($this->getUserSession())) {
            unset($_SESSION[$this->userSessionName]);
        }
    }

    public function checkAccess($extraNonAuthorizationConditions = false, $message = ''): bool
    {
        $isForbidden = $this->getIsGuest() || $extraNonAuthorizationConditions;

        if ($isForbidden) {
            $message = $message
                ?: SNEMLibrary::app()->getConfig('forbiddenMessage')
                ?: 'Please log in!';

            header('HTTP/1.0 403 Forbidden');
            echo '<div class="text-center">';
            echo $message;
            echo '</div>';
        }

        return !$isForbidden;
    }
}
