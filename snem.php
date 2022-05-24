<?php

class SNEM
{
    private static ?SNEM $_app = null;

    /**
     * @param mixed|null $config
     * @throws Exception
     */
    public static function createApplication($config = null)
    {
        if (self::$_app === null) {
            self::$_app = new self();

            if (is_string($config)) {
                $config = require_once($config);
            }

            return self::$_app;
        } else {
            throw new Exception('Application can only be created once.');
        }
    }

    public static function app()
    {
        return self::$_app;
    }

    public function run()
    {
    }
}
