<?php

namespace Ascension;

class Core
{
    public $Resources = array();
    private $TwigTemplates = array();
    private $ViewData = array();

    public function __construct(

    ) {
        // Sanity Check
        $this->__saneSys();

        // Setup System
        $this->__setupSys();

        try {
            $this->__loadSettings();
        } catch (\Exception $e) {
            throw new \Exception("Could not load settings file. Exception given: " . $e->getMessage());
        }

        $Request = new HTTP($_SERVER, $_REQUEST, file_get_contents('php://input'), $_FILES);
        $this->__injectResource('Request', $Request);

        // Loader
        $this->__loader();
        $this->__output();

    }

    public function addDataStorageObject($Name, $Object) {
        if (!isset($this->Resources['DataStorage'][$Name])) {
            $this->Resources['DataStorage'][$Name] = $Object;
            return TRUE;
        }
        return FALSE;
    }

    /**
     * @throws \Exception
     */
    private function __saneSys() {
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

    private function __setupSys() {

        date_default_timezone_set('Europe/London');
        ini_set("session.gc_maxlifetime", "604800");
        session_start();

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
    public function __loadSettings() {
        try {
            $settings = json_encode(
                file_get_contents(ROOT . DS . "etc" . DS . "config.json")
            );

            $this->__injectResource("Settings", $settings);

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @throws \Exception
     */
    public function __loader() {
        try {

            $this->Resources['Request']->route();

            $rStr = ucfirst($this->Resources['Request']->controller) . "\\Repository\\Repository";
            if (!class_exists($rStr)) {
                throw new \Exception($rStr . " Repository class not found");
            } else {
                try {
                    $r = new $rStr($this->Resources['DataStorage'], $this->Resources['Settings']);
                } catch (\Exception $e) {
                    throw new \Exception($e);
                }
            }

            $cStr = ucfirst($this->Resources['Request']->controller) . "\\Controller\\Controller";

            if (!class_exists($cStr)) {
                throw new \Exception($cStr . "Controller class not found.");
            } else {
                $c = new $cStr($this->Resources['Request'], $this->Resources['Settings'], $r);
            }

            if ($this->Resources['Request']->action == "") {
                $this->Resources['Request']->action = 'main';
            }

            $a = $this->Resources['Request']->action;
            $c->$a();

            $this->TwigTemplates = $c->templates;
            $this->ViewData = $c->data;

            $this->ViewData['Ascension-Common'] = $this->getCommon();


        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * @return void
     */
    private function __output() {
        // Process JSON
        if ($this->Resources['Request']->isJson === TRUE) {
            header("Content-Type: application/json");
            echo json_encode($this->ViewData,true);
            exit();
        } else {
            echo "Twig templating not implemented yet.";
            exit();
        }
    }

    /**
     * @return array
     */
    private function getCommon() {
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
    public function __injectResource($Name, $Resource) {
        if (!isset($Name)) {
            $this->Resources[$Name] = $Resource;
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Resource Remover
     * @param $Name
     * @return false|void
     */
    public function __removeResource($Name) {
        if (isset($Name)) {
            unset($this->Resources[$Name]);
            return TRUE;
        }
        return FALSE;

    }

}