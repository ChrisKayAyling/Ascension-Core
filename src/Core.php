<?php

namespace Ascension;

use Ascension\Exceptions\ControllerNotFound;
use Ascension\Exceptions\DataStorageFailure;
use Ascension\Exceptions\FrameworkFailure;
use Ascension\Exceptions\FrameworkSettingsFailure;
use Ascension\Exceptions\RequestHandlerFailure;
use Ascension\Exceptions\RequestIDFailure;
use Ascension\Exceptions\TemplateEngineFailure;

class Core
{
    private static $TwigEnvironment;
    private static $UserTwigEnvironment;
    private static $TwigCustomTemplating = array(
        'Header' => null,
        'Navigation' => null,
        'Footer' => null
    );

    public static $Resources = array();
    private static $TwigTemplates = array();
    private static $ViewData = array();

    /**
     * @var bool Enable/Disable debugging. | Defaults to TRUE
     */
    public static $Debug = true;

    /**
     * @var bool Enable/Disable twig cache. | Defaults to TRUE
     */
    public static $TemplateDevelopmentMode = true;

    /* User data from requests */
    public static $UserData = array();

    /**
     * @var HTTP - Compatibility Layer.
     */
    private static $HTTP;

    /* Routing - Sets default routes
    */
    public static $Route = array(
        'controller' => 'Home',
        'method' => 'main',
        'id' => 0,
        'content' => 'plain'
    );

    /*
     * Accessor for lib objects
     */
    public static $Accessor = [];


    /**
     * @throws \Exception
     */
    public static function ascend()
    {
        // Telemetry
        self::telemetry();

        // Sanity Check
        self::__saneSys();

        try {
            self::__loadSettings();
        } catch (\Exception $e) {
            throw new \Exception("Error loading system setup and settings, : " . $e->getMessage());
        }

        self::requestHandler();

        // Loader
        self::__loader();
        self::__output();
    }


    /**
     * requestHandler - Handles user request
     * @return void
     */
    public static function requestHandler()
    {


        /* Data Ingress */
        if (isset($_SERVER['CONTENT_TYPE'])) {
            switch (strtolower($_SERVER['CONTENT_TYPE'])) {
                case 'application/json':
                    self::$UserData = json_decode(file_get_contents('php://input'), true);
                    self::$Route['content'] = 'json';
                    break;

                default;
                    self::$UserData = $_REQUEST;
                    self::$Route['content'] = 'plain';
                    break;
            }
        }

        /* Router */
        if (isset($_SERVER['REQUEST_URI']) && strlen($_SERVER['REQUEST_URI']) > 0) {
            $path = array();

            if ($_SERVER['REQUEST_URI'] !== "/") {
                $path = array_values(explode("/", $_SERVER['REQUEST_URI']));
                if ($path[0] === '') {
                    $path = array_reverse($path, true);
                    array_pop($path);
                    $path = array_reverse($path, true);
                }
            }


            /* Controller */
            if (isset($path[1])) {
                self::$Route['controller'] = preg_replace('/[^a-zA-z]/', '', $path[1]);
                if (!is_dir(ROOT . DS . 'lib' . DS . self::$Route['controller'])) {
                    throw new ControllerNotFound("Controller '" . self::$Route['controller'] . "' not found.", 1);
                }
            } else {
                self::$Route['controller'] = 'Home';
            }
            /* Method extraction */
            if (isset($path[2])) {
                self::$Route['method'] = preg_replace('/[^a-zA-z]/', '', $path[2]);
            } else {
                self::$Route['method'] = 'main';
            }

            /* filters param extraction */

            if (count($path) > 2) {
                $path = array_splice($path, 2);
                $filters = [];
                foreach ($path as $filterVal) {
                    $filterSplit = explode(":", $filterVal);
                    $filters[$filterSplit[0]] = $filterSplit[1];

                    // id to route
                    if (isset($filterSplit[0]) && isset($filterSplit[1])) {
                        self::$Route['id'] = array($filterSplit[0] => $filterSplit[1]);
                    }
                }
                self::$HTTP = new HTTP($_SERVER, $_FILES, self::$UserData, $filters);
                return;
            }
            self::$HTTP = new HTTP($_SERVER, $_FILES, self::$UserData, self::$Route['id']);
            return;
        }


    }


    /**
     * Load data storage objects
     * @return void
     * @throws \Exception
     */
    public static function addDataStorageObjects()
    {
        try {
            foreach (self::$Resources['Settings']->DataConnectors as $instance) {
                $dObject = "DataStorageObjects\\" . $instance->Connector;
                if (class_exists($dObject)) {
                    self::$Resources['DataStorage'][$instance->Database] = new $dObject($instance);
                }
            }
        } catch (\Exception $e) {
            throw new DataStorageFailure("Unable to setup datastorage objects: " . $e->getMessage(), 1);
        }
    }

    /**
     * @throws \Exception
     */
    private static function __saneSys()
    {
        try {
            if (!extension_loaded('curl')) {
                $error = "PHP Extension curl not enabled.";
            }

            if (!extension_loaded("simplexml")) {
                $error = "PHP Extension simplexml not enabled.";
            }

            if (!extension_loaded("sqlite3")) {
                $error = "PHP Extension sqlite3 not enabled.";
            }

        } catch (\Exception $e) {
            throw new EnvironmentSanityCheckFailure($error, 0);
        }

    }

    /**
     * @return void
     */
    private static function __setupSys()
    {
        date_default_timezone_set('Europe/London');

        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");

        ini_set("display_errors", 0);
        ini_set('error_reporting', E_ALL);

        if (!defined('DOCUMENT_ROOT')) {
            define('DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT']);
        }

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            if (!defined("DS")) {
                define("DS", "\\");
            }
        } else {
            if (!defined("DS")) {
                define("DS", "/");
            }
        }

        if (isset($_SERVER['SERVER_ADDR'])) {
            define('SERVER_ADDR', $_SERVER['SERVER_ADDR']);
        } else {
            $_SERVER['SERVER_ADDR'] = "127.0.0.1";
        }

        if (isset($_SERVER['REMOTE_ADDR']) && !defined('REMOTE_ADDR')) {
            define('REMOTE_ADDR', $_SERVER['REMOTE_ADDR']);
        } else {
            $_SERVER['REMOTE_ADDR'] = "127.0.0.1";
        }

        if (!defined("COREROOT")) {
            define("COREROOT", dirname(__FILE__));
        }

        if (!defined('ROOT')) {
            define('ROOT',
                dirname(
                    dirname(
                        dirname(
                            dirname(
                                dirname(__FILE__)
                            )
                        )
                    )
                )
            );
        }

        if (!defined('WEB_ROOT')) {
            define('WEB_ROOT', ROOT . DS . 'public_html');
        }

        if (!defined('FRAMEWORK_DIR')) {
            define('FRAMEWORK_DIR', ROOT . DS . 'lib');
        }

        try {
            $loader = new \Twig\Loader\FilesystemLoader(COREROOT . DS . ".." . DS . 'layout');
            self::$TwigEnvironment = new \Twig\Environment($loader, array(
                'debug' => self::$TemplateDevelopmentMode,
                'cache' => ".." . DS . "cache"
            ));

            self::$TwigEnvironment->addExtension(new \Twig\Extension\DebugExtension());

        } catch (\Exception $e) {
            throw new TemplateEngineFailure($e->getMessage(), 0);
        }

        try {
            $loader = new \Twig\Loader\FilesystemLoader(ROOT . DS . "templates");
            self::$UserTwigEnvironment = new \Twig\Environment($loader, array(
                'debug' => self::$TemplateDevelopmentMode,
                'cache' => ROOT . DS . "cache"
            ));

            self::$UserTwigEnvironment->addExtension(new \Twig\Extension\DebugExtension());

        } catch (\Exception $e) {
            throw new TemplateEngineFailure($e->getMessage(), 0);
        }


    }

    /**
     * @throws \Exception
     */
    public static function __loadSettings()
    {
        // Setup System
        self::__setupSys();

        try {
            $settings = json_decode(
                file_get_contents(ROOT . DS . "etc" . DS . "config.json")
            );

            self::__injectResource("Settings", $settings);

        } catch (\Exception $e) {
            throw new FrameworkSettingsFailure($e->getMessage(), 0);
        }

        // Load settings optionally from CMS db if present.
        try {
            if (extension_loaded("SQLite3")) {
                if (file_exists(ROOT . DS . "etc" . DS . "db.db")) {
                    $handle = new \SQLite3(ROOT . DS . "etc" . DS . "db.db");
                    $result = $handle->query("SELECT * FROM settings");
                    $rows = array();
                    if ($result !== false) {
                        while ($row = $result->fetchArray()) {
                            $rows[$row['Environment']][$row['Name']] = (object)array(
                                "Value" => $row['Value'],
                                "Group" => $row['Group']
                            );
                        }
                        self::__injectResource("AppSettings", (object)$rows);
                    }
                }
            }
        } catch (\Exception $e) {
            throw new FrameworkSettingsFailure("Core: Application settings could not be loaded." . $e->getMessage(), 0);
        }
    }

    /**
     * @throws \Exception
     */
    public static function __loader()
    {
        try {

            $rStr = ucfirst(self::$Route['controller']) . "\\Repository\\Repository";
            if (!class_exists($rStr)) {
                throw new FrameworkFailure($rStr . " Repository class not found", 0);
            } else {
                try {
                    self::$Accessor['Repository'] = new $rStr(self::$Resources['DataStorage'], self::$Resources['Settings']);
                } catch (\Exception $e) {
                    throw new FrameworkFailure($e->getMessage(), 0);
                }
            }

            $cStr = ucfirst(self::$Route['controller']) . "\\Controller\\Controller";

            if (!class_exists($cStr)) {
                throw new FrameworkFailure($cStr . "Controller class not found.", 0);
            } else {
                self::$Accessor['Controller'] = new $cStr(self::$HTTP, self::$Resources['Settings'], self::$Accessor['Repository']);
            }

            $a = self::$Route['method'];
            self::$Accessor['Controller']->$a();

            self::$TwigTemplates = self::$Accessor['Controller']->templates;
            self::$ViewData = self::$Accessor['Controller']->data;

            self::$ViewData['Common'] = self::getCommon();

            if (self::$Debug) {
                d("Ascension Core Debug Output");
            }
            if (self::$Debug) {
                d(self::$Resources);
            }

        } catch (\Exception $e) {
            throw new FrameworkFailure($e, 0);
        }
    }

    /**
     * @return void
     */
    private static function __output()
    {
        // Process JSON
        if (self::$Route['content'] === 'json') {
            header("Content-Type: application/json");
            echo json_encode(self::$ViewData, true);
            exit();
        } else {
            // Process HTML Templating
            foreach (self::$TwigCustomTemplating as $customTemplateKey => $customTemplateValue) {
                if (null !== $customTemplateValue) {
                    $customTemplateResource[$customTemplateKey] = self::$UserTwigEnvironment->load($customTemplateValue);
                } else {
                    $customTemplateResource[$customTemplateKey] = self::$TwigEnvironment->load('empty.twig');;
                }
            }

            $contentRendered = "";
            foreach (self::$TwigTemplates as $viewTemplate) {
                $contentTemplate = self::$UserTwigEnvironment->load($viewTemplate);
                $contentData = array(
                    'data' => self::$ViewData
                );
                $contentRendered .= $contentTemplate->render($contentData);
            };

            $mainTemplate = self::$TwigEnvironment->load('layout.twig');
            $mainRendered = $mainTemplate->render(
                array(
                    'header' => $customTemplateResource['Header']->render(array(
                        'data' => self::$ViewData
                    )),
                    'navigation' => $customTemplateResource['Navigation']->render(array(
                        'data' => self::$ViewData
                    )),
                    'body' => $contentRendered,
                    'footer' => $customTemplateResource['Footer']->render(array(
                        'data' => self::$ViewData
                    ))
                )
            );

            echo $mainRendered;
            exit();
        }
    }

    /**
     * Telemetry
     * @return void
     */
    private static function telemetry()
    {
       // $o = (object)json_decode(file_get_contents(base64_decode("aHR0cHM6Ly93d3cuaW9ob3N0LmNvLnVrL2ZyYW1ld29ya1BpbmcucGhw")),
       //     true);
       // if ($o->LicenseStatus !== "OK") {
       //     exit();
        //}
    }

    /**
     * @return array
     */
    private static function getCommon()
    {
        $data = array();

        // Server
        $data['Server']['SERVER_ADDR'] = $_SERVER['SERVER_ADDR'];
        $data['Server']['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
        $data['Server']['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];

        if (isset($_SERVER['HTTPS'])) {
            $data['Server']['HTTPS'] = $_SERVER['HTTPS'];
        }

        $data['Server']['SESSION_ID'] = session_id();

        // Sessions
        if (isset($_SESSION)) {
            $data['Session'] = $_SESSION;
        }

        // Day Of the Week
        $data['General']['DayShort'] = date('D');
        $data['General']['Day'] = date('l');
        $data['General']['DayNumber'] = date('d');
        $data['General']['MonthShort'] = date('M');
        $data['General']['MontNumber'] = date('m');
        $data['General']['Year'] = date("Y");

        return $data;
    }


    /**
     * Resource Injector
     * @param $Name
     * @param $Resource
     * @return false|void
     */
    public static function __injectResource($Name, $Resource)
    {

        if (!isset(self::$Resources[$Name])) {
            self::$Resources[$Name] = $Resource;
            return true;
        }
        return false;
    }

    /**
     * Resource Remover
     * @param $Name
     * @return false|void
     */
    public static function __removeResource($Name)
    {
        if (isset(self::$Resources[$Name])) {
            unset(self::$Resources[$Name]);
            return true;
        }
        return false;

    }

    /**
     * addCustomTemplate - Add a custom template by name and path
     * @param $Name - Header|Navigation|Footer
     * @param $Path - Relative file path
     * @return bool
     */
    public static function addCustomTemplate($Name, $Path)
    {
        self::$TwigCustomTemplating[$Name] = $Path;
        return true;
    }

}
