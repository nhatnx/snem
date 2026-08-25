<?php
/**
 * SNEM - Smart and Empowering Library
 */

namespace app\core;

if (!defined('ADODB_DIR')) {
    // Composer's "files" autoload of adodb/adodb-php defines ADODB_DIR; this
    // fallback covers setups that load this file without vendor/autoload.php.
    require_once __DIR__ . '/vendor/adodb/adodb-php/adodb.inc.php';
}
require_once ADODB_DIR . '/adodb-active-recordx.inc.php';

use ADODB_Active_Record;
use app\core\models\SEUser;
use DirectoryIterator;

class SNEMLibrary
{
    protected static ?SNEMLibrary $_app = null;
    public ADODatabase $db;
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
        $this->config = $this->resolveConfig($config);

        $isMaintenance = $this->getConfig('maintenanceMode')['enabledMode'] ?? false;
        if ($isMaintenance) {
            require_once $this->getPath(self::CORE_PATH_TEMPLATE) . '/offline.php';
            die();
        }

        $this->registerAppAutoloader($this->getConfig('autoloadPaths'));

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

    /**
     * Merges the app-supplied config over the framework defaults, so a new
     * project only has to provide what differs (typically `name`, `basePath`
     * and `db`). Path defaults are derived from `basePath`.
     */
    private function resolveConfig(?array $config): array
    {
        $config = $config ?? [];
        $basePath = $config['basePath'] ?? '.';

        $defaults = [
            'name' => '',
            'debugMode' => false,
            'defaultPage' => 'index',
            'templateName' => 'default',
            'charset' => 'UTF-8',
            'notFoundMessage' => 'Page not found.',
            'forbiddenMessage' => '',
            'maintenanceMode' => [
                'enabledMode' => false,
                'message' => '',
            ],
            'rootPath' => $basePath,
            'basePath' => $basePath,
            'pagePath' => $basePath . self::DS . 'pages',
            'templatePath' => $basePath . self::DS . 'templates',
            'helperPath' => $basePath . self::DS . 'helpers',
            'autoloadPaths' => [
                'app' . self::DS . 'controllers',
                'app' . self::DS . 'models',
                'app' . self::DS . 'helpers',
            ],
            'params' => [],
        ];

        $resolved = array_replace_recursive($defaults, $config);

        // list-style keys replace wholesale, index-wise merging makes no sense
        if (isset($config['autoloadPaths'])) {
            $resolved['autoloadPaths'] = $config['autoloadPaths'];
        }

        return $resolved;
    }

    /**
     * Registers a lazy autoloader over the given directories instead of eagerly
     * require_once-ing every file in them on every request. Classes in these
     * directories aren't necessarily PSR-4 namespaced (some are global-namespace),
     * so this maps by unqualified class name — the one convention that already
     * holds across every file: the class's short name always matches its filename.
     */
    private function registerAppAutoloader(array $dirs): void
    {
        $classMap = [];
        foreach ($dirs as $dir) {
            $this->collectClassMap($dir, $classMap);
        }

        spl_autoload_register(
            static function ($class) use ($classMap) {
                $pos = strrpos($class, '\\');
                $shortName = $pos === false ? $class : substr($class, $pos + 1);
                if (isset($classMap[$shortName])) {
                    require_once $classMap[$shortName];
                }
            }
        );
    }

    private function collectClassMap($dir, array &$classMap): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $ignoreFiles = ['index.php'];

        $dh = new DirectoryIterator($dir);
        foreach ($dh as $item) {
            if ($item->isDot()) {
                continue;
            }
            if ($item->isDir()) {
                $this->collectClassMap($dir . self::DS . $item, $classMap);
            } elseif ($item->isFile() && $item->getExtension() === 'php' && !in_array($item->getFilename(), $ignoreFiles)) {
                $classMap[$item->getBasename('.php')] = $item->getPathname();
            }
        }
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
                $config = require $config;
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
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        if ($this->getLocale()) {
            setlocale(LC_TIME, $this->getLocale());
        }

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
        $pageDir = "{$this->getPath(self::CORE_PATH_PAGE)}/{$this->page}";
        if (!file_exists("{$pageDir}/{$this->page}.php")) {
            // have no file name on URL or file name is incorrect
            // --> add 404 header for 404 page
            require_once $this->getPath(self::CORE_PATH_TEMPLATE) . '/notFound.php';
            die();
        }

        $this->dispatch($pageDir);

        ob_start(
            function ($buffer) {
                $siteName = $this->getSiteName();
                if (empty($siteName)) {
                    return $buffer;
                }

                return preg_replace('~(<title>).*(</title>)~i', "\${1}{$siteName}\${2}", $buffer, 1);
            }
        );

        require_once "{$this->getPath(self::CORE_PATH_TEMPLATE)}/{$this->getTemplateName()}/{$this->getTemplateName()}.php";

        ob_end_flush();
    }

    /**
     * Resolves the controller from `task` (falling back to the base
     * SEController) and runs `action{Action}` between the beforeAction /
     * afterAction hooks, capturing the action's output into the view.
     */
    protected function dispatch(string $pageDir): void
    {
        $controllerName = ucfirst($this->input->get('task', $this->input->post('task', 'default'))) . 'Controller';
        $this->action = $this->input->get('action', $this->input->post('action', 'default'));

        $controllerFileName = "{$pageDir}/controllers/{$controllerName}.php";
        if (file_exists($controllerFileName)) {
            require_once $controllerFileName;
        }

        if (!class_exists($controllerName) && !class_exists($controllerName = SEController::class)) {
            return;
        }

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

    public static function redirect($url, $msg = "", int $statusCode = 302): void
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
            $jsUrl = json_encode($finalUrl);
            echo "<script>window.location.href={$jsUrl};</script>\n";
        } else {
            @ob_end_clean(); // clear output buffer
            header("Location: $finalUrl", true, $statusCode);
        }
        die();
    }

    public function isAjaxRequest(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
}
