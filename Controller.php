<?php

namespace app\core;

class Controller
{
    public ?SNEM $snem;

    public function __construct(SNEM $snem)
    {
        $this->snem = $snem;
    }

    public function render($view, $args = [])
    {
        extract($args);
        require_once("{$this->snem->getPagePath()}/{$this->snem->getPage()}/views/{$view}.php");
    }

    public function actionDefault()
    {
        $this->render('default');
    }
}
