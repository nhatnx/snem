<?php

class JsonController extends Controller
{
    public function beforeAction($action)
    {
        $this->format = 'json';
    }
}
