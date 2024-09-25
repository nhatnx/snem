<?php

namespace app\core;

use app\core\helpers\SEFileHelper;

/**
 * @property SEFileHelper $file
 */
class SEHelper
{
    public function __get($name)
    {
        $clsName = ucfirst($name) . 'Helper';
        if (!class_exists($clsName)) {
            $clsName = 'app\\core\\helpers\\SE' . $clsName;
        }

        return new $clsName();
    }
}
