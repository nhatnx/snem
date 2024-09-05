<?php

namespace app\core;

class SEJsonController extends SEController
{
    public function beforeAction($action)
    {
        $this->format = 'json';
    }
}
