<?php

namespace app\core\helpers;

class Input
{
    public function param(array $type, $key = '', $default = null)
    {
        return $type[$key] ?? $default;
    }

    public function request($key = '', $default = null)
    {
        return $this->param($_REQUEST, $key, $default);
    }

    public function get($key = '', $default = null)
    {
        return $this->param($_GET, $key, $default);
    }

    public function post($key = '', $default = null)
    {
        return $this->param($_POST, $key, $default);
    }
}
