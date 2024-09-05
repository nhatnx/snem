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
        $name = ucfirst(strtolower($name)) . 'Helper';
        $clsName = 'app\\helpers\\' . $name;
        if (!class_exists($clsName)) {
            $clsName = 'app\\core\\helpers\\SE' . $name;
        }

        return new $clsName();
    }
}
