<?php

namespace Ascension;

class Core
{
    public static $Resources = array();
    private static $TwigTemplates = array();
    private static $ViewData = array();
    public static $Debug = true;

    /**
     * @throws \Exception
     */
    public static function ascend() {
        // Sanity Check
        self::__saneSys();

        // Setup System
        self::__setupSys();

        try {
            self::__loadSettings();
        } catch (\Exception $e) {
            throw new \Exception("Could not load settings file. Exception given: " . $e->getMessage());
        }

        $Request = new HTTP($_SERVER, $_REQUEST, file_get_contents('php://input'), $_FILES);
        self::__injectResource('Request', $Request);

        // Loader
        self::__loader();
        self::__output();
    }

    /**
     * @param $Name
     * @param $Object
     * @return bool
     */
    public function addDataStorageObject($Name, $Object) {
        if (!isset($this->Resources['DataStorage'][$Name])) {
            self::$Resources['DataStorage'][$Name] = $Object;
            return TRUE;
        }
        return FALSE;
    }

    /**
     * @throws \Exception
     */
    private static function __saneSys() {
        try {
            if (!function_exists("curl_init")) {
                $error = "PHP Extension curl not installed.";
            }

            if (!function_exists("simplexml_load_file")) {
                $error = "PHP Extension simplexml not installed.";
            }

        } catch (\Exception $e) {
            throw new \Exception($error);
        }

    }

    /**
     * @return void
     */
    private static function __setupSys() {
        session_start();
        date_default_timezone_set('Europe/London');

        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");

        ini_set("display_errors", 0);
        ini_set('error_reporting', E_ALL);

        if (!defined('DOCUMENT_ROOT')) {
            define('DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT']);
        }

        if (!defined('DS')) {
            define('DS', '/');
        }

        if (isset($_SERVER['SERVER_ADDR'])) {
            define('SERVER_ADDR', $_SERVER['SERVER_ADDR']);
        } else {
            $_SERVER['SERVER_ADDR'] = "127.0.0.1";
        }

        if (isset($_SERVER['REMOTE_ADDR'])) {
            define('REMOTE_ADDR', $_SERVER['REMOTE_ADDR']);
        } else {
            $_SERVER['REMOTE_ADDR'] = "127.0.0.1";
        }

        if (!defined('ROOT')) {
            define('ROOT', dirname(__FILE__));
        }

        if (!defined('WEB_ROOT')) {
            define('WEB_ROOT', ROOT . DS . 'public_html');
        }

        if (!defined('FRAMEWORK_DIR')) {
            define('FRAMEWORK_DIR', ROOT . DS . 'lib');
        }
    }

    /**
     * @throws \Exception
     */
    public static function __loadSettings() {
        try {
            $settings = json_encode(
                file_get_contents(ROOT . DS . "etc" . DS . "config.json")
            );

            self::__injectResource("Settings", $settings);

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @throws \Exception
     */
    public static function __loader() {
        try {

            self::$Resources['Request']->route();

            $rStr = ucfirst(self::$Resources['Request']->controller) . "\\Repository\\Repository";
            if (!class_exists($rStr)) {
                throw new \Exception($rStr . " Repository class not found");
            } else {
                try {
                    $r = new $rStr(self::$Resources['DataStorage'], self::$Resources['Settings']);
                } catch (\Exception $e) {
                    throw new \Exception($e);
                }
            }

            $cStr = ucfirst(self::$Resources['Request']->controller) . "\\Controller\\Controller";

            if (!class_exists($cStr)) {
                throw new \Exception($cStr . "Controller class not found.");
            } else {
                $c = new $cStr(self::$Resources['Request'], self::$Resources['Settings'], $r);
            }

            if (self::$Resources['Request']->action == "") {
                self::$Resources['Request']->action = 'main';
            }

            $a = self::$Resources['Request']->action;
            $c->$a();

            self::$TwigTemplates = $c->templates;
            self::$ViewData = $c->data;

            self::$ViewData['Ascension-Common'] = self::getCommon();


            if (self::$Debug) d("Ascension Core Debug Output");
            if (self::$Debug) d(self::$Resources);

        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * @return void
     */
    private static function __output() {
        // Process JSON
        if (self::$Resources['Request']->isJson === TRUE) {
            header("Content-Type: application/json");
            echo json_encode(self::$ViewData,true);
            exit();
        } else {
            echo "Twig templating not implemented yet.";
            exit();
        }
    }

    /**
     * @return array
     */
    private static function getCommon() {
        $data = array();

        // Server
        $data['Server']['SERVER_ADDR']            = $_SERVER['SERVER_ADDR'];
        $data['Server']['REMOTE_ADDR']            = $_SERVER['REMOTE_ADDR'];
        $data['Server']['HTTP_USER_AGENT']        = $_SERVER['HTTP_USER_AGENT'];

        if (isset($_SERVER['HTTPS'])) {
            $data['Server']['HTTPS'] = $_SERVER['HTTPS'];
        }

        $data['Server']['SESSION_ID']             = session_id();

        // Sessions
        $data['Session']                          = $_SESSION;

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
    public static function __injectResource($Name, $Resource) {
        if (!isset($Name)) {
            self::$Resources[$Name] = $Resource;
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Resource Remover
     * @param $Name
     * @return false|void
     */
    public static function __removeResource($Name) {
        if (isset($Name)) {
            unset(self::$Resources[$Name]);
            return TRUE;
        }
        return FALSE;

    }

}