<?php
/**
 * Credential check for the starter app.
 *
 * Autoloaded by short name from app/models/ — no namespace required, the only
 * rule is that the class name matches the file name.
 */

use app\core\models\SEUserIdentity;
use app\core\SNEMLibrary;

class AppUserIdentity extends SEUserIdentity
{
    public function authenticate(): bool
    {
        $hash = SNEMLibrary::app()->db->getOne(
            "SELECT password_hash FROM #__users WHERE username = ?",
            [$this->username]
        );

        return !empty($hash) && password_verify($this->password, $hash);
    }
}
