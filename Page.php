<?php

namespace app\core;

class Page
{
    public ?SNEM $snem;

    public function __construct(SNEM $snem)
    {
        $this->snem = $snem;
        echo $this->snem->getView();
    }
}
