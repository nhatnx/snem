<?php

namespace app\core;

use app\core\helpers\Database;
use app\core\helpers\Input;
use Exception;

class SNEM
{
    private static ?SNEM $_app = null;
    public ?Database $db = null;
    private ?array $config = null;
    public ?Input $input = null;
    private $page = null;
    private $controller = null;
    private $action = null;
    private ?string $view = 'default';
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
            die();
        }

        setlocale(LC_TIME, $this->getLocale());
        $this->db = new Database(
            $this->getDbConfig('host'),
            $this->getDbConfig('username'),
            $this->getDbConfig('password'),
            $this->getDbConfig('databaseName'),
            $this->getDbConfig('tablePrefix')
        );

        $isDebug = $this->getConfig('debugMode');
        if ($isDebug) {
            error_reporting(E_ALL);
        } else {
            error_reporting(0);
        }

        $this->page = $this->input->request('page', $this->getDefaultPage());
        $fileName = $this->getPagePath() . "/{$this->page}/{$this->page}.php";
        if (!file_exists($fileName)) {
            // have no file name on URL or file name is incorrect
            // --> add 404 header for 404 page
            require_once($this->getTemplatePath() . '/notFound.php');
            die();
        }

        $controllerName = ucfirst($this->input->get('task', $this->input->post('task', 'default'))) . 'Controller';
        $this->action = $this->input->get('action', $this->input->post('action', 'default'));

        $controllerFileName = "{$this->getPagePath()}/{$this->page}/controllers/{$controllerName}.php";
        if (file_exists($controllerFileName)) {
            require_once($controllerFileName);

            if (class_exists($controllerName)) {
                $this->controller = new $controllerName($this);

                if (method_exists($this->controller, 'beforeAction')) {
                    $this->controller->beforeAction($this->action);
                }

                $actionName = 'action' . ucfirst($this->action);
                if (method_exists($this->controller, $actionName)) {
                    ob_start();
                    ob_implicit_flush(false);
                    $this->controller->$actionName();
                    $this->view = ob_get_clean();
                }

                if (method_exists($this->controller, 'afterAction')) {
                    $this->controller->afterAction($this->action);
                }
            }
        } else {
            require_once($this->getTemplatePath() . '/notFound.php');
            die();
        }

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

    public function getDbConfig($key = null)
    {
        if (!empty($key)) {
            return $this->getConfig('db')[$key] ?? null;
        }

        return $this->getConfig('db');
    }

    public function params($key)
    {
        return $this->getConfig('params')[$key] ?? null;
    }

    public function getDefaultPage()
    {
        return $this->getConfig('defaultPage') ?? 'index';
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
        return $this->getDbConfig('charset');
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

    public function getView()
    {
        return $this->view;
    }

    public function getInput()
    {
        return $this->input;
    }

    public function getAbsAssetPath()
    {
        return './';
    }

    public function getAbsTemplatePath()
    {
        return "./templates/{$this->getTemplateName()}";
    }

    public function mainBody()
    {
        require_once("{$this->getPagePath()}/{$this->getPage()}/{$this->getPage()}.php");
        $page = $this->getPage();
        new $page($this);
    }

    public function redirect($url, $msg = "")
    {
        $arrUrl = parse_url($url);
        $hash = $arrUrl['fragment'];
        $url = str_replace("#{$hash}", "", $url);
        $msg = urlencode($msg);

        if (headers_sent()) {
            echo "<script>window.location.href='$url" . (empty($msg) ? '' : "&msg=$msg") . (empty($hash) ? '' : "#{$hash}") . "';</script>\n";
        } else {
            @ob_end_clean(); // clear output buffer
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: " . $url . (empty($msg) ? '' : "&msg=$msg") . (empty($hash) ? '' : "#{$hash}"));
        }
        die();
    }
}
