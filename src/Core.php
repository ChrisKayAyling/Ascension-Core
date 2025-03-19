<?php

namespace Ascension;

use Ascension\Components\RoutingConfiguration;
use Ascension\Exceptions\ControllerNotFound;
use Ascension\Exceptions\DataStorageFailure;
use Ascension\Exceptions\EnvironmentSanityCheckFailure;
use Ascension\Exceptions\FrameworkFailure;
use Ascension\Exceptions\FrameworkSettingsFailure;
use Ascension\Exceptions\HttpException;
use Ascension\Exceptions\RequestHandlerFailure;
use Ascension\Exceptions\RequestIDFailure;
use Ascension\Exceptions\TemplateEngineFailure;
use Ascension\RabbitMQ\Base;
use Ascension\RabbitMQ\BaseFactory;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use Spatie\Ignition\Ignition;
use function PHPUnit\Framework\directoryExists;

class Core
{
    /**
     * @note Used for the CMS template structuring.
     * @var $TwigEnvironment
     */
    private static $TwigEnvironment;

    /**
     * @note Used for the parent CMS templates defined by the user.
     * @var $UserTwigEnvironment
     */
    private static $UserTwigEnvironment;

    /**
     * @internal templating definitions.
     * @var array|null[] $TwigCustomTemplating
     */
    private static array $TwigCustomTemplating = array(
        'Header' => null,
        'Navigation' => null,
        'Footer' => null
    );

    /**
     * @internal Contains all Core instantiated resources such as
     * Settings, DataConnectors etc.
     * @var array $Resources
     */
    public static array $Resources = array();

    /**
     * @internal Core templating property.
     * @var array $TwigTemplates
     */
    private static array $TwigTemplates = array();

    /**
     * @var bool
     * @internal Indicates if this is a versioned code base.
     */
    private static bool $VersionedCodebase = FALSE;

    /**
     * @internal Data from Modelling for output purpose.
     * @var array $ViewData
     */
    private static array $ViewData;

    /**
     * @var bool Enable/Disable debugging. | Defaults to TRUE
     */
    public static bool $Debug = true;

    /**
     * @var bool $ForceJSONResponse - Force 'application/json' response type.
     */
    private static bool $ForceJSONResponse = false;

    /**
     * @var bool Enable/Disable common return properties as part of XHR Calls.
     */
    public static bool $EnableCommonHelpers = false;

    /**
     * @var bool Enable/Disable twig cache. | Defaults to TRUE
     */
    public static bool $TemplateDevelopmentMode = true;

    /**
     * @var object $Routes
     */
    public static RoutingConfiguration $Routes;

    /* User data from requests */
    public static array $UserData = array();

    /**
     * @var HTTP - Compatibility Layer.
     */
    private static $HTTP;

    /* Routing - Sets default routes
    */
    public static array $Route = array(
        'version' => 'v1',
        'controller' => 'Home',
        'method' => 'main',
        'id' => 0,
        'content' => 'plain'
    );

    /*
     * Accessor for lib objects
     */
    public static array $Accessor = [];


    public static $RestClient;

    /**
     * @param bool $DisableDataConnectors - Explicit disable of data connectors add method call. This can be used when
     * external flow requires data connectivity outside of normal execution flow.
     * @return void
     * @throws \Exception
     */
    public static function ascend(bool $DisableDataConnectors = FALSE, bool $DisableIgnitionDebug = FALSE, bool $forceJSONResponseType = FALSE)
    {
        try {
            // Register our error handler
            if (!$DisableIgnitionDebug) {
                Ignition::make()->register();
            }

            // Response Type override
            if ($forceJSONResponseType) {
                self::$ForceJSONResponse = true;
            }

            // Telemetry
            self::telemetry();

            // Sanity Check
            self::__saneSys();

            try {
                self::__loadSettings();
            } catch (\Exception $e) {
                error_log("Exception raised: Core::__loadSettings. " . $e->getMessage());
                throw new \Exception("Error loading system setup and settings. \n" . $e->getTraceAsString());
            }

            if (!$DisableDataConnectors) {
                try {
                    self::addDataConnectors();
                } catch (\Exception $e) {
                    error_log("Exception raised: Core::addDataConnectors. " . $e->getMessage());
                    throw new \Exception("Exception raised during the loading of DataConnectors. \n");
                }
            }

            try {
                if (isset(self::$Resources['Declared-Middleware'])) {
                    self::executeMiddlewareChain();
                }
            } catch (\Exception $e) {
                error_log("Exception raised: Core::executeMiddlewareChain. " . $e->getMessage());
                throw new \Exception("Exception raised: Core::executeMiddlewareChain. \n");
            }

            try {
                self::requestHandler();
            } catch (\Exception $e) {
                error_log("Exception raised: Core::requestHandler. " . $e->getMessage());
                throw new \Exception("Exception raised during request handling. \n" . $e->getMessage());
            }

            try {
                self::$RestClient = new RestClient();
            } catch (\Exception $e) {
                error_log("Exception raised: self::RestClient = new RestClient()");
                throw new \Exception("Exception raised during creating a wrapper to the GuzzleClient.\n");
            }

            // Loader
            self::__loader();
            self::__output();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * announce
     * @param $type
     * @param $data
     * @return void
     */
    public static function announce($type, $data)
    {

        if ('routing' === strtolower($type)) {
            self::$Routes = $data;
        }
    }


    /**
     * @param string $middlewareNSClass
     * @return void
     * @todo complete this function once fully understood what it needs to-do.
     */
    public static function addMiddleware(string $middlewareNSClass = ''): void
    {
        self::$Resources['Declared-Middleware'][] = $middlewareNSClass;
    }


    /**
     * @return void
     * @throws \Exception
     */
    private static function executeMiddlewareChain()
    {
        try {
            $index = 0;
            $middlewareCount = count(self::$Resources['Declared-Middleware']);

            $next = function () use (&$index, $middlewareCount) {
                if ($index < $middlewareCount) {
                    $middlewareClass = self::$Resources['Declared-Middleware'][$index];
                    $middlewareInstance = new $middlewareClass();
                    $index++;

                    $middlewareInstance->handle(self::$HTTP, self::$ViewData, function () use (&$next) {
                        $next();
                    });
                }
            };

            $next();
        } catch (\Exception $e) {
            error_log("Exception raised: Core::executeMiddlewareChain. " . $e->getMessage());
            throw new \Exception("Exception raised during executing middleware. \n" . $e->getTraceAsString());
        }
    }

    /**
     * addDataConnectors
     *
     * @return void
     * @throws \ReflectionException
     */
    public static function addDataConnectors()
    {
        foreach (self::$Resources['DataConnectors'] as $configSection) {
            $configSection = (array)$configSection;
            if (array_key_exists('Resource', $configSection)) {
                try {
                    if ($configSection['RequiresParameters']) {
                        self::$Resources['DataStorage'][$configSection['Alias']] = new ($configSection['Resource'])((object)$configSection);
                    } else {
                        self::$Resources['DataStorage'][$configSection['Alias']] = new ($configSection['Resource'])();
                    }

                } catch (\Exception $e) {
                    throw new DataStorageFailure(sprintf("Core::addDataConnectors error connecting to database. Hostname: %s, Database: %s, Username: %s ",
                        $configSection['Hostname'],
                        $configSection['Database'],
                        $configSection['Username']
                    ), 1);
                }
            }
        }
    }


    /**
     * @throws \Exception
     */
    private
    static function __saneSys()
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
            error_log(sprintf("Core::__saneSys, throwing exception, required component could not be found on system. %s",
                    $error) . $e->getMessage());
            throw new EnvironmentSanityCheckFailure($error, 0);
        }

    }

    /**
     * @return void
     */
    private
    static function __setupSys()
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

        if (isset($_SERVER['SERVER_ADDR']) && !defined('SERVER_ADDR')) {
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
            $loader = new FilesystemLoader(COREROOT . DS . ".." . DS . 'layout');
            self::$TwigEnvironment = new Environment($loader, array(
                'debug' => self::$TemplateDevelopmentMode,
                'cache' => ".." . DS . "cache"
            ));

            self::$TwigEnvironment->addExtension(new DebugExtension());

        } catch (\Exception $e) {
            error_log(sprintf("Core::__setupSys, throwing exception. Twig templating engine throwing, %s",
                $e->getMessage()));
            throw new TemplateEngineFailure($e->getMessage(), 0);
        }

        try {
            $loader = new FilesystemLoader(ROOT . DS . "templates");
            self::$UserTwigEnvironment = new Environment($loader, array(
                'debug' => self::$TemplateDevelopmentMode,
                'cache' => ROOT . DS . "cache"
            ));

            self::$UserTwigEnvironment->addExtension(new DebugExtension());

        } catch (\Exception $e) {
            error_log(sprintf("Core::__setupSys, throwing exception. Twig templating engine throwing, %s",
                $e->getMessage()));
            throw new TemplateEngineFailure($e->getMessage(), 0);
        }


    }

    /**
     * @throws \Exception
     */
    public
    static function __loadSettings()
    {
        // Setup System
        self::__setupSys();

        try {
            $settings = json_decode(
                file_get_contents(ROOT . DS . "etc" . DS . "config.json")
            );

            self::__injectResource("Settings", $settings);

        } catch (\Exception $e) {
            error_log(sprintf("Core::__loadSettings, throwing exception. issue loading settings file throwing with:  %s",
                $e->getMessage()));
            throw new FrameworkSettingsFailure($e->getMessage(), 0);
        }

        // Load settings optionally from core db if present.
        try {
            if (extension_loaded("SQLite3")) {
                if (file_exists(ROOT . DS . "sqlite" . DS . "core.sqlite")) {
                    self::$Resources['DataStorage']['core'] = new \SQLite3(ROOT . DS . "sqlite" . DS . "core.sqlite");
                    $result = self::$Resources['DataStorage']['core']->query("SELECT * FROM settings");
                    $rows = array();
                    if ($result !== false) {
                        while ($row = $result->fetchArray()) {
                            $rows[$row['Environment']][$row['Group'] . "_" . $row['Name']] = (object)array(
                                "Value" => $row['Value'],
                                "Group" => $row['Group']
                            );
                        }

                        self::__injectResource("AppSettings", (object)$rows);
                    }

                    /* Data Connectors */

                    $result = self::$Resources['DataStorage']['core']->query(sprintf("SELECT * FROM DataConnectors WHERE Environment = '%s'", $settings->Environment));
                    $rows = array();
                    if ($result !== false) {
                        while ($row = $result->fetchArray()) {
                            $rows[$row['Alias']] = (object)array(
                                "Resource" => $row['Resource'],
                                "RequiresParameters" => $row['RequiresParameters'],
                                "Alias" => $row['Alias'],
                                "Hostname" => $row['Hostname'],
                                "Database" => $row['Database'],
                                "Username" => $row['Username'],
                                "Password" => $row['Password']
                            );
                        }
                        self::__injectResource("DataConnectors", (object)$rows);
                    }

                    /* Message Queue Table */
                    $result = self::$Resources['DataStorage']['core']->query("SELECT * FROM MessageQueue_Settings");

                    if ($result !== FALSE) {
                        $messageQueueSettings = [];
                        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                            $messageQueueSettings[$row['Exchange'] . '_' . $row['Queue'] . '_' . $row['Key']] = $row['Value'];
                        }
                        self::__injectResource("MessageQueues", (object)$messageQueueSettings);
                    }


                }
            }
        } catch (\Exception $e) {
            error_log(sprintf("Core::__loadSettings, throwing exception. issue loading settings from SQLite3 database:  %s",
                $e->getMessage()));
            throw new FrameworkSettingsFailure("Core: Application settings could not be loaded." . $e->getMessage(), 0);
        }
    }


    /**
     * Handles user request
     * @return void
     * @throws ControllerNotFound
     */
    public static function requestHandler()
    {
        try {
            /* Data Ingress */
            if (isset($_SERVER['CONTENT_TYPE'])) {
                if (preg_match("/application\/json/is", $_SERVER['CONTENT_TYPE'], $matches)) {
                    if ($matches) {
                        $decodePayload = json_decode(file_get_contents('php://input'), true);
                        if ($decodePayload === null) {
                            self::$UserData = array();
                        } else {
                            self::$UserData = $decodePayload;
                        }

                        self::$Route['content'] = 'json';
                    } else {
                        self::$UserData = $_REQUEST;
                        self::$Route['content'] = 'plain';
                    }
                }
            }

            /* Override Check */
            if (self::$ForceJSONResponse) {
                self::$Route['content'] = 'json';
            }

            /* Custom Routing */
            // Check to see if routes match
            if (!empty(self::$Routes->getRoutes())) {
                foreach (self::$Routes->getRoutes() as $routeDefinition) {
                    $routeMatch = false;
                    preg_match_all('/({\w+})/', $routeDefinition->getPath(), $paramKeyExtraction);

                    array_reverse($paramKeyExtraction);
                    array_pop($paramKeyExtraction);
                    array_reverse($paramKeyExtraction);

                    $uriKeyCollection = array();

                    foreach ($paramKeyExtraction as $k => $v) {
                        //clean up place holder for key name
                        $uriKeyCollection[] = str_replace("}", "", str_replace("{", "", $v));
                    }

                    $regex = preg_replace('/({\w+})+/', '([\w+%]+)', $routeDefinition->getPath());
                    $regex = str_replace("/", "\/", $regex);

                    preg_match('/^' . $regex . '$/m', $_SERVER['REQUEST_URI'], $matches);

                    if (!empty($matches)) {
                        $routeMatch = true;
                        // Custom route match.
                        if (in_array(strtoupper($_SERVER['REQUEST_METHOD']), $routeDefinition->getVerbs())) {

                            // Match confirmed proceed to parameter placement
                            $matches = array_reverse($matches);
                            array_pop($matches);
                            $matches = array_reverse($matches);

                            self::$UserData = array_combine($uriKeyCollection[0], $matches);
                            self::$HTTP = new HTTP($_SERVER, $_FILES, self::$UserData, self::$UserData['id']);

                            // assign controller and method to be called.
                            self::$Route['controller'] = $routeDefinition->getController();
                            self::$Route['method'] = $routeDefinition->getMethod();

                            $r = $routeDefinition->getInjectedClass();

                            try {
                                self::$Accessor['Repository'] = new $r(self::$Resources['DataStorage'], self::$Resources['Settings']);
                            } catch (\Exception $e) {
                                throw new FrameworkFailure($e->getMessage(), 0);
                            }

                            self::$Accessor['Controller'] = new self::$Route['controller'](self::$HTTP, self::$Resources['Settings'], self::$Accessor['Repository']);
                            error_log("----------- CUSTOM ROUTE MATCH -------------------");
                            break;
                        } else {
                            echo "Request Method does not match";
                            die();
                        }
                    }
                }

            }

            if ($routeMatch == false) {
                error_log("----------- FALLBACK ROUTING -------------------");
                // Fall back routing via PSR
                if (isset($_SERVER['REQUEST_URI']) && strlen($_SERVER['REQUEST_URI']) > 0) {

                    $path = array();

                    if (strcasecmp($_SERVER['REQUEST_URI'], "/") > 0) {
                        $path = array_values(explode("/", $_SERVER['REQUEST_URI']));
                        if (strcasecmp($path[0], '') == 0) {
                            $path = array_reverse($path, true);
                            array_pop($path);
                            $path = array_reverse($path, true);
                        }
                    } else {
                        $path[1] = "Home";
                        $path[2] = "main";
                    }

                    if (preg_match('/^[a-zA-Z][0-9]/', $path[1])) {

                        self::$VersionedCodebase = TRUE;
                        self::$Route['controller'] = ucfirst(preg_replace('/[^a-zA-z]/', '', $path[2]));

                        if ($path[3]) {
                            self::$Route['method'] = preg_replace('/[^a-zA-z]/', '', $path[3]);
                        } else {
                            self::$Route['method'] = "Home";
                        }

                        self::$Route['version'] = strtolower($path[1]);

                        $filterPos = 3;

                        if (!is_dir(ROOT . DS . 'lib' . DS . strtolower(self::$Route['version']) . DS . ucfirst(self::$Route['controller']))) {
                            throw new ControllerNotFound("Controller '" . ucfirst(self::$Route['controller']) . "' not found.", 1);
                        }
                    } else {

                        // Check to see if PSR directory exists
                        if (is_dir(ROOT . DS . "lib" . DS . ucfirst(self::$Route['controller']))) {
                            // none versioned codebase
                            self::$VersionedCodebase = FALSE;
                        } elseif (is_dir(ROOT . DS . "lib" . DS . "v1" . DS . ucfirst(self::$Route['controller']))) {
                            // version not specified but directory found under v1.
                            self::$VersionedCodebase = FALSE;
                            self::$Route['version'] = "v1";
                        } else {
                            throw new ControllerNotFound("Controller '" . ucfirst(self::$Route['controller']) . "' not found in PSR loadable directories.", 1);
                        }

                        self::$Route['controller'] = ucfirst(preg_replace('/[^a-zA-z]/', '', $path[1]));

                        if (isset($path[2])) {
                            self::$Route['method'] = preg_replace('/[^a-zA-z]/', '', $path[2]);
                        } else {
                            self::$Route['method'] = "main";
                        }

                        $filterPos = 2;
                    }


                    /* filters param extraction */

                    if (count($path) > $filterPos) {

                        $path = array_splice($path, $filterPos);
                        $filters = [];
                        foreach ($path as $filterVal) {
                            if (strlen($filterVal) > 0 && FALSE === strstr($filterVal, "?")) {
                                $filterSplit = explode(":", $filterVal);
                                $filters[$filterSplit[0]] = $filterSplit[1];

                                // id to route
                                if (isset($filterSplit[0]) && isset($filterSplit[1])) {
                                    self::$Route['id'] = array($filterSplit[0] => $filterSplit[1]);
                                }
                            } else {
                                // ID
                                self::$Route['id'] = intval($filterVal);
                            }
                        }
                        self::$HTTP = new HTTP($_SERVER, $_FILES, self::$UserData, $filters);
                        return;

                    }
                    self::$HTTP = new HTTP($_SERVER, $_FILES, self::$UserData, self::$Route['id']);
                }

                if (self::$VersionedCodebase == TRUE) {
                    $rStr = strtolower(self::$Route['version']) . "\\" . self::$Route['controller'] . "\\Repository\\Repository";
                } else {
                    // requirement for fallback
                    if (is_dir(ROOT . DS . "lib" . DS . "v1" . DS . ucfirst(self::$Route['controller']))) {

                        $rStr = "v1" . "\\" . self::$Route['controller'] . "\\Repository\\Repository";

                    } else {
                        $rStr = self::$Route['controller'] . "\\Repository\\Repository";
                    }

                }

                if (!class_exists($rStr)) {
                    throw new FrameworkFailure($rStr . " Repository class not found", 0);
                } else {
                    try {
                        self::$Accessor['Repository'] = new $rStr(self::$Resources['DataStorage'],
                            self::$Resources['Settings']);
                    } catch (\Exception $e) {
                        throw new FrameworkFailure($e->getMessage(), 0);
                    }
                }
                if (self::$VersionedCodebase == TRUE) {
                    $cStr = strtolower(self::$Route['version']) . "\\" . self::$Route['controller'] . "\\Controller\\Controller";
                } else {
                    if (is_dir(ROOT . DS . "lib" . DS . "v1" . DS . ucfirst(self::$Route['controller']))) {
                        $cStr = "v1" . "\\" . self::$Route['controller'] . "\\Controller\\Controller";
                    } else {
                        $cStr = self::$Route['controller'] . "\\Controller\\Controller";
                    }
                }

                if (!class_exists($cStr)) {
                    throw new FrameworkFailure($cStr . "Controller class not found.", 0);
                } else {
                    self::$Accessor['Controller'] = new $cStr(self::$HTTP, self::$Resources['Settings'],
                        self::$Accessor['Repository']);
                }
            }


        } catch (\Exception $e) {
            throw new \Exception($e);
            $exceptionMessage = sprintf("Core::requestHandler, throwing ControllerNotFound exception. User specified '%s'. Controller not registered with PSR04 autoloader or could not be found. ", ucfirst(self::$Route['controller'])) . $e->getMessage();
            error_log($exceptionMessage);
            throw new \Exception($exceptionMessage);
        }
    }

    /**
     * @throws \Exception
     */
    public
    static function __loader()
    {
        try {

            $a = self::$Route['method'];

            self::$Accessor['Controller']->$a();

            // if property has been implemented in class then check for acceptable request method.
            /* if (property_exists(self::$Accessor['Controller'], 'acceptedVerbs')) {
                 if (!in_array(strtoupper($_SERVER['REQUEST_METHOD']), self::$Accessor['Controller']->acceptedVerbs)) {
                     http_response_code(405);
                     throw new FrameworkFailure("Request method invalid for this method.", 0);
                 }
             }*/

            if (!self::$Accessor['Controller']->templates) {
                self::$TwigTemplates = array();
            } else {
                self::$TwigTemplates = self::$Accessor['Controller']->templates;
            }

            // Patch suggest by SM to enforce array return type.
            self::$ViewData = isset(self::$Accessor['Controller']->data) ? (array)self::$Accessor['Controller']->data : [];

            if (self::$EnableCommonHelpers) {
                self::$ViewData['Common'] = self::getCommon();
            }

            if (self::$Debug) {
                d("Ascension Core Debug Output");
            }
            if (self::$Debug) {
                d(self::$Resources);
            }
        } catch (HttpException $e) {
            if ('json' === Core::$Route['content']) {
                self::$ViewData = ['message' => $e->getMessage()];
            } else {
                self::$TwigTemplates = [self::$TwigEnvironment->load('exception.twig')];
                self::$ViewData = [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode()
                ];
            }
            http_response_code($e->getCode());
            self::__output();
        } catch (\Exception $e) {
            throw new FrameworkFailure($e, 0);
        }
    }

    /**
     * @return void
     */
    private
    static function __output()
    {
        // Process JSON
        if (self::$Route['content'] === 'json') {
            header("Content-Type: application/json");
            echo json_encode(self::$ViewData);
            exit();
        } else {
            // Provide access to SESSION vars within main templates.
            self::$ViewData['Session'] = $_SESSION;

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
     * create_rmq_worker
     *
     * @param string $action
     * @param string $unit
     * @param string $exchange
     * @param string $type
     * @param string $routeKey
     * @return void
     */
    public
    static function create_rmq_worker(
        string $action,
        string $unit,
        string $exchange,
        string $type,
        string $routeKey
    )
    {

        $factory = new BaseFactory();

        $channel = $factory->Resource->channel();

        $channel->exchange_declare($exchange, $type, true, true, true);

        $channel->queue_declare(
            $action . "_" . $unit . "_queue",
            true,
            true,
            false,
            false
        );

    }

    /**
     * Telemetry
     * @return void
     */
    private
    static function telemetry()
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
    private
    static function getCommon()
    {
        $data = array();

        // Server
        $data['Server']['SERVER_ADDR'] = $_SERVER['SERVER_ADDR'];
        $data['Server']['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
        $data['Server']['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];

        if (isset($_SERVER['HTTPS'])) {
            $data['Server']['HTTPS'] = $_SERVER['HTTPS'];
        }

        // Sessions
        if (isset($_SESSION)) {
            $data['Session'] = $_SESSION;
            $data['Server']['SESSION_ID'] = session_id();
        }

        // Day Of the Week
        $data['General']['DayShort'] = date('D');
        $data['General']['Day'] = date('l');
        $data['General']['DayNumber'] = date('d');
        $data['General']['MonthShort'] = date('M');
        $data['General']['MonthNumber'] = date('m');
        $data['General']['Year'] = date("Y");

        return $data;
    }

    /**
     * raiseError
     *
     * A custom error handler that can be used to raise an error and log to the syslog.
     *
     * @param $message
     * @param $level
     * @return true
     */
    public
    static function raiseEvent(
        $message,
        $level = E_USER_NOTICE
    )
    {
        $trace = debug_backtrace();
        $caller = next($trace);


        $msg = $message . ' in ' . $caller['function'] . ' called from ' . $caller['file'] . ' on line ' . $caller['line'] . '\r\n';
        $msg .= "class:" . $caller['class'] . "\r\n";

        if (isset($caller['object'])) {
            $msg .= "object: " . json_encode($caller['object']);
        }

        switch ($level) {
            case E_USER_ERROR:
            {
                syslog(E_ERROR, $msg);
                new ExceptionPrinter($msg);
                exit();
            }

            case E_USER_NOTICE:
            {
                syslog(E_NOTICE, $msg);
            }
        }

    }


    /**
     * Resource Injector
     * @param $Name
     * @param $Resource
     * @return boolean|void
     */
    public
    static function __injectResource(
        $Name,
        $Resource
    )
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
     * @return false
     */
    public
    static function __removeResource(
        $Name
    )
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
    public
    static function addCustomTemplate(
        $Name,
        $Path
    )
    {
        self::$TwigCustomTemplating[$Name] = $Path;
        return true;
    }

}
