<?php
/**
 * SNEM - Smart and Empowering Library
 */

namespace app\core;

require_once ADODB_DIR . '/adodb-active-recordx.inc.php';

use ADOConnection;
use ADODB_Active_Record;
use app\core\models\SEUser;
use DirectoryIterator;

class SNEMLibrary
{
    protected static ?SNEMLibrary $_app = null;
    public ADOConnection $db;
    protected ?array $config = null;
    public ?SEInput $input = null;
    public $helper = null;

    public ?SEUser $user = null;

    protected $page = null;
    protected $controller = null;
    protected $action = null;
    protected ?string $view = 'default';

    protected ?array $meta = null;
    protected ?array $head = null;
    protected ?array $footer = null;

    public const DS = DIRECTORY_SEPARATOR;

    public const CORE_PATH_ROOT = '@root';
    public const CORE_PATH_BASE = '@base';
    public const CORE_PATH_ABS = '@abs';
    public const CORE_PATH_ABS_TEMPLATE = '@abs/template';
    public const CORE_PATH_PAGE = '@page';
    public const CORE_PATH_TEMPLATE = '@template';
    public const CORE_PATH_HELPER = '@helper';

    public const SNEM_HEAD = 'SNEM_HEAD';
    public const SNEM_FOOTER = 'SNEM_FOOTER';

    public function __construct($config = null)
    {
        $this->config = $config;

        $isMaintenance = $this->getConfig('maintenanceMode')['enabledMode'];
        if ($isMaintenance) {
            require_once $this->getPath(self::CORE_PATH_TEMPLATE) . '/offline.php';
            die();
        }

        $this->getAllFiles('app' . self::DS . 'controllers');
        $this->getAllFiles('app' . self::DS . 'models');
        $this->getAllFiles('app' . self::DS . 'helpers');

        $this->db = new ADODatabase(
            $this->getDbConfig('driver'),
            $this->getDbConfig('host'),
            $this->getDbConfig('username'),
            $this->getDbConfig('password'),
            $this->getDbConfig('databaseName'),
            $this->getDbConfig('tablePrefix'),
            $this->getDbConfig('debugMode')
        );
        $conn = $this->db->getConn();

        ADOdb_Active_Record::SetDatabaseAdapter($conn);
    }

    private function getAllFiles($dir, $ignoreFiles = [], $isRecursive = true): array
    {
        if (!is_dir($dir)) {
            return [];
        }

        // TODO ignore files by patterns
        $ignoreFiles = array_merge(
            [
                'SNEMLibrary.php',
                'index.php',
                'desktop.ini',
                'GoogleDriveFS.exe',
            ],
            $ignoreFiles
        );

        $files = [];
        $dh = new DirectoryIterator($dir); // TODO should replace by GlobIterator(*.php) ?
        foreach ($dh as $item) {
            if (!$item->isDot()) {
                if ($isRecursive && $item->isDir()) {
                    $files = array_merge($files, $this->getAllFiles($dir . self::DS . $item, $ignoreFiles));
                } elseif ($item->isFile() && !in_array($item->getFilename(), $ignoreFiles)) {
                    $files = array_merge($files, [$item->getPathname()]);
                    require_once $dir . self::DS . $item->getFilename();
                }
            }
        }

        return array_merge($files, $ignoreFiles);
    }

    /**
     * @param array|string|null $config
     * @return SNEMLibrary
     * @throws SEException
     */
    public static function createApplication($config = null)
    {
        if (self::$_app === null) {
            if (is_string($config)) {
                // config is a path
                $config = require_once $config;
            }

            $app = new self($config);

            return self::$_app = $app;
        } else {
            throw new SEException('Application can only be created once.');
        }
    }

    public static function app()
    {
        return self::$_app;
    }

    public function run(): void
    {
        setlocale(LC_TIME, $this->getLocale());

        $isDebug = $this->getConfig('debugMode');
        if ($isDebug) {
            error_reporting(E_ALL);
        } else {
            error_reporting(0);
        }

        $this->user = new SEUser();
        $this->input = new SEInput();

        if (!$this->helper) {
            $this->helper = new SEHelper();
        }

        $this->page = $this->input->request('page', $this->getDefaultPage());
        $fileName = $this->getPath(self::CORE_PATH_PAGE) . "/{$this->page}/{$this->page}.php";
        if (!file_exists($fileName)) {
            // have no file name on URL or file name is incorrect
            // --> add 404 header for 404 page
            require_once $this->getPath(self::CORE_PATH_TEMPLATE) . '/notFound.php';
            die();
        }

        $controllerName = ucfirst($this->input->get('task', $this->input->post('task', 'default'))) . 'Controller';
        $this->action = $this->input->get('action', $this->input->post('action', 'default'));

        $controllerFileName = "{$this->getPath(self::CORE_PATH_PAGE)}/{$this->page}/controllers/{$controllerName}.php";

        if (file_exists("{$this->getPath(self::CORE_PATH_PAGE)}/{$this->page}")) {
            if (file_exists($controllerFileName)) {
                require_once $controllerFileName;
            }

            if (class_exists($controllerName) || class_exists($controllerName = 'app\core\SEController')) {
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
            require_once $this->getPath(self::CORE_PATH_TEMPLATE) . '/notFound.php';
            die();
        }

        ob_start(
            function ($buffer) {
                return preg_replace("^(<title>).*(</title>)^i", "\${1}" . $this->getSiteName() . "\${2}", $buffer, 1);
            }
        );

        require_once "{$this->getPath(self::CORE_PATH_TEMPLATE)}/{$this->getTemplateName()}/{$this->getTemplateName()}.php";

        ob_end_flush();
    }

    public function setHelper($helperInstance): SNEMLibrary
    {
        if (is_string($helperInstance)) {
            $clsName = ucfirst($helperInstance) . 'Helper';
            if (!class_exists($clsName)) {
                $clsName = 'app\\core\\helpers\\SE' . $clsName;
            }
            $this->helper = new $clsName();
        } else {
            $this->helper = $helperInstance;
        }

        return $this;
    }

    public function getDb(): ?ADODatabase
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

    public function getPath($pathNameAlias)
    {
        if (strpos($pathNameAlias, '@') !== 0) {
            // not a path name
            return $pathNameAlias;
        }

        switch ($pathNameAlias) {
            case self::CORE_PATH_ROOT:
                $path = $this->getRootPath();

                break;
            case self::CORE_PATH_BASE:
                $path = $this->getBasePath();

                break;
            case self::CORE_PATH_ABS:
                $path = $this->getAbsPath();

                break;
            case self::CORE_PATH_ABS_TEMPLATE:
                $path = $this->getAbsTemplatePath();

                break;
            case self::CORE_PATH_PAGE:
                $path = $this->getPagePath();

                break;
            case self::CORE_PATH_TEMPLATE:
                $path = $this->getTemplatePath();

                break;
            case self::CORE_PATH_HELPER:
                $path = $this->getHelperPath();

                break;
            default:
                $path = substr($pathNameAlias, 1);
                break;
        }

        return $path;
    }

    public function getRootPath()
    {
        return $this->getConfig('rootPath');
    }

    public function getBasePath()
    {
        return $this->getConfig('basePath');
    }

    public function getPagePath()
    {
        return $this->getConfig('pagePath');
    }

    public function getTemplatePath()
    {
        return $this->getConfig('templatePath');
    }

    public function getHelperPath()
    {
        return $this->getConfig('helperPath');
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

    public function getView(): ?string
    {
        return $this->view;
    }

    public function getInput(): ?SEInput
    {
        return $this->input;
    }

    public function getAbsPath(): string
    {
        return '.' . self::DS;
    }

    public function getAbsTemplatePath(): string
    {
        return $this->getAbsPath() . "templates/{$this->getTemplateName()}";
    }

    public function mainBody(): void
    {
        require_once "{$this->getPath(self::CORE_PATH_PAGE)}/{$this->getPage()}/{$this->getPage()}.php";
        $page = $this->getPage();
        new $page($this);
    }

    public static function redirect($url, $msg = ""): void
    {
        if (empty($url)) {
            $url = SNEMLibrary::app()->getAbsPath();
        } else {
            $arrUrl = parse_url($url);
            $hash = $arrUrl['fragment'] ?? '';
            $url = str_replace("#{$hash}", "", $url);
        }

        $msg = urlencode($msg);
        $finalUrl = $url . (empty($msg) ? '' : "?msg=$msg") . (empty($hash) ? '' : "#{$hash}");

        if (headers_sent()) {
            echo "<script>window.location.href='$finalUrl';</script>\n";
        } else {
            @ob_end_clean(); // clear output buffer
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: $finalUrl");
        }
        die();
    }

    public function isAjaxRequest(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
}
