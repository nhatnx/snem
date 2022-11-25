<?php

use app\core\SNEM;
use app\core\helpers\Database;

class Controller
{
    protected ?SNEM $snem;
    protected ?Database $db;
    public string $format = 'xml';

    public function __construct(SNEM $snem)
    {
        $this->snem = $snem;
        $this->db = $snem->db;
    }

    public function render($view, $args = [])
    {
        extract($args);
        require_once("{$this->snem->getPagePath()}/{$this->snem->getPage()}/views/{$view}.php");
    }

    public function partialRender($file, $args = [])
    {
        extract($args);
        require_once("{$this->snem->getPagePath()}/{$this->snem->getPage()}/views/{$file}.php");
        unset($file, $args);
        exit();
    }

    protected function parseFormat()
    {
        $format = strtolower($this->format);
        if (isset($_GET['format']) && $_GET['format']) {
            $format = strtolower($_GET['format']);
        }
        return $format;
    }

    public function getStatusCodeMessage($status)
    {
        $codes = [
            100 => 'Continue',
            101 => 'Switching Protocols',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            306 => '(Unused)',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported'
        ];
        return (isset($codes[$status])) ? $codes[$status] : '';
    }

    public function sendResponse($result = '', $status = 200, $content_type = '')
    {
        $format = $this->parseFormat();

        if (empty($content_type)) {
            switch ($format) {
                case 'json':
                    $content_type = 'application/json';
                    break;
                case 'xml':
                    $content_type = 'text/xml';
                    break;
                default:
                    $content_type = 'text/html';
            }
        }

        header('HTTP/1.1 ' . $status . ' ' . $this->getStatusCodeMessage($status));
        header('Content-type: ' . $content_type . '; charset=UTF-8');

        if ($format == 'json') {
            echo json_encode($result);
        } else {
            echo $result;
        }

        exit();
    }

    public function jsonResponse($result, $status = 200)
    {
        $this->format = 'json';
        $this->sendResponse($result, $status);
    }

    public function actionDefault()
    {
        $this->render($this->snem->getPage());
    }
}
