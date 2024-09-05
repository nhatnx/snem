<?php

namespace app\core;

class SEException extends \Exception
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName(): string
    {
        return 'SEException';
    }
}
