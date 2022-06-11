<?php

namespace app\core;

class Page
{
    public function __construct(SNEM $snem)
    {
        $viewFileName = "{$snem->getPagePath()}/{$snem->getPage()}/views/{$snem->getView()}.php";
        if (file_exists($viewFileName)) {
            require_once($viewFileName);
        }
    }
}
