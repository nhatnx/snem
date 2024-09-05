<?php

namespace app\core;

class SEPage
{
    public ?SNEMLibrary $snem;

    public function __construct($snem)
    {
        $this->snem = $snem;
        echo $this->snem->getView();
    }
}
