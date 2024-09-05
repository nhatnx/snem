<?php

namespace app\core;

class SEInput
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

    public function session($key = '', $default = null)
    {
        return $this->param($_SESSION, $key, $default);
    }

    public function files($key = '', $default = null)
    {
        return $this->param($_FILES, $key, $default);
    }

    public function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] == 'POST';
    }

    public function isGet(): bool
    {
        return $_SERVER['REQUEST_METHOD'] == 'GET';
    }

    /**
     * Returns the raw HTTP request body
     *
     * @return string the request body
     */
    public function getRawBody(): string
    {
        static $rawBody;

        if ($rawBody === null) {
            $rawBody = file_get_contents('php://input');
        }

        return $rawBody;
    }
}
