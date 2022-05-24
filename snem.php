<?php

class SNEM
{
    private static ?SNEM $_app = null;
    private ?Database $db = null;
    private ?array $config = null;
    private ?Input $input = null;
    private $page = null;
    private $controller = null;
    private $action = null;
    private ?array $meta = null;
    private ?array $head = null;
    private ?array $footer = null;

    public const SNEM_HEAD = 'SNEM_HEAD';
    public const SNEM_FOOTER = 'SNEM_FOOTER';

    public function __construct($config = null)
    {
        $this->config = $config;
        $this->input = new Input();
    }

    /**
     * @param mixed|null $config
     * @return SNEM
     * @throws Exception
     */
    public static function createApplication($config = null)
    {
        if (self::$_app === null) {
            if (is_string($config)) {
                // config is a path
                $config = require_once($config);
            }

            $app = new self($config);

            return self::$_app = $app;
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
        $isMaintenance = $this->getConfig('maintenanceMode')['enabledMode'];
        if ($isMaintenance) {
            require_once($this->getTemplatePath() . '/offline.php');
            exit;
        }

//        var_dump($this->config, $this->params('test'));

        setlocale(LC_TIME, $this->getLocale());
        $this->db = new Database(
            $this->getConfig('db')['host'],
            $this->getConfig('db')['username'],
            $this->getConfig('db')['password'],
            $this->getConfig('db')['databaseName'],
            $this->getConfig('db')['tablePrefix']
        );

        $isDebug = $this->getConfig('debugMode');
        if ($isDebug) {
            error_reporting(E_ALL);
        } else {
            error_reporting(0);
        }

        $fileName = $this->input->request('page', $this->getDefaultPage());
        $fileName = $this->getPagePath() . "/{$fileName}/{$fileName}.php";
        if (!file_exists($fileName)) {
            // have no file name on URL or file name is incorrect
            // --> add 404 header for 404 page
            require_once($this->getTemplatePath() . '/notFound.php');
            exit();
        }

        $this->page = $this->input->request('page', $this->getDefaultPage());
        $this->controller = $this->input->request('task', '');
        $this->action = $this->input->request('action', '');

        ob_start(
            function ($buffer) {
                return preg_replace("^(<title>).*(</title>)^i", "\${1}" . $this->getSiteName() . "\${2}", $buffer, 1);
            }
        );

        require_once("{$this->getTemplatePath()}/{$this->getTemplateName()}/{$this->getTemplateName()}.php");

        ob_end_flush();
    }

    public function getDb()
    {
        return $this->db;
    }

    public function getConfig($key = null)
    {
        if (!empty($key)) {
            return $this->config[$key] ?? null;
        }

        return $this->config;
    }

    public function params($key)
    {
        return $this->getConfig('params')[$key] ?? null;
    }

    public function getDefaultPage()
    {
        return $this->getConfig('defaultPage');
    }

    public function getPagePath()
    {
        return $this->getConfig('pagePath');
    }

    public function getBasePath()
    {
        return $this->getConfig('basePath');
    }

    public function getTemplatePath()
    {
        return $this->getConfig('templatePath');
    }

    public function getTemplateName()
    {
        return $this->getConfig('templateName');
    }

    public function getLanguage()
    {
        return $this->getConfig('language');
    }

    public function getLocale()
    {
        return $this->getConfig('locale');
    }

    public function getCharset()
    {
        return $this->getConfig('charset');
    }

    public function getDbCharset()
    {
        return $this->getConfig('db')['charset'];
    }

    public function getSiteName()
    {
        return $this->getConfig('name');
    }

    public function getMaintenanceMessage()
    {
        return $this->getConfig('maintenanceMode')['message'];
    }

    public function getNotFoundMessage()
    {
        return $this->getConfig('notFoundMessage');
    }

    public function getPage()
    {
        return $this->page ?? $this->getDefaultPage();
    }

    public function getController()
    {
        return $this->controller;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function getAbsAsset()
    {
        return './';
    }

    public function getAbsTemplate()
    {
        return "./templates/{$this->getTemplateName()}";
    }

    public function mainBody()
    {
        require_once("{$this->getPagePath()}/{$this->getPage()}/{$this->getPage()}.php");
    }
}
