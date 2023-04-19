<?php

namespace Ascension;

/**
 * Class Request
 * @package Framework\HTTP
 */
class HTTP {

    /**
     * @var array|null
     */
    public $Server = NULL;

    /**
     * @var array|null
     */
    public $Files = NULL;

    /**
     * @var null
     */
    public $data = NULL;

    /**
     * @var null
     */
    public $query = NULL;

    /**
     * @var null
     */
    public $xml = NULL;

    /**
     * @var null
     */
    public $isJson = NULL;

    /**
     * @var null
     */
    public $controller = NULL;

    /**
     * @var null
     */
    public $action = NULL;

    /**
     * @var null
     */
    public $id = NULL;

    /**
     * @var array
     */
    public $filters = array();


    /**
     * @var string $requestMethod - POST,GET,DELETE or PATCH
     */
    public $requestMethod = "";

    /**
     * @var array
     */
    public $defaultRoute = array(
        'controller' => 'API',
        'action' => 'main',
        'id' => ""
    );

    /**
     * Request constructor.
     * @param array $Server
     * @param array $Post
     * @param string $PhpInput
     * @param array $Files
     * @param array $Request
     */
    public function __construct(
        $Server = array(),
        $Post = array(),
        $PhpInput = "",
        $Files = array(),
        $Request = array()
    ) {
        $this->Server = $Server;
        $this->processPostData($Post, $PhpInput);
        $request = array_merge(array('xml' => NULL), $Request);
        $this->xml = $request['xml'];
        $this->Files = $Files;
    }

    /**
     * @param array $Post
     * @param $PhpInput
     */
    public function processPostData($Post = array(), $PhpInput) {
        if ($this->isJson()) {
            $this->data = json_decode($PhpInput, TRUE);
        } else {
            $this->data = $Post;
        }
    }

    /**
     * @return bool
     */
    protected function isJson() {
        if (isset($this->Server['CONTENT_TYPE']) && strpos($this->Server['CONTENT_TYPE'], 'application/json') === 0) {
            $this->isJson = TRUE;
            return TRUE;
        } else if (strstr(isset($this->Server['REQUEST_URI']), 'angular.callbacks')) {
            $this->isJson = TRUE;
            return TRUE;
        } else {
            $this->isJson = FALSE;
            return FALSE;
        }
    }

    /**
     * route
     */
    public function route() {
        $this->extractQuery();

        //$this->extractParam(); // shift by one for sub directory
        $this->extractController();
        $this->extractAction();
        $this->extractId();

        if (empty($this->controller)) {
            $this->controller   = $this->defaultRoute['controller'];
            $this->action       = $this->defaultRoute['action'];
            $this->id           = $this->defaultRoute['id'];
        }

        if (empty($this->action)) {
            $this->action = 'main';
        }

        $this->extractFilters();
    }

    /**
     * extractFilters
     */
    protected function extractFilters() {
        foreach ($this->query as $param) {
            if (strpos($param, ':')) {
                $param = explode(':', $param, 2);
                $param[0] = preg_replace('/[^a-zA-Z_-]/', '', $param[0]);
                $this->filters[$param[0]] = $param[1];
            }
        }
    }

    /**
     * @throws \Framework\Exception\PageNotFoundException
     */
    protected function extractController() {
        $query =            $this->query;
        $controller =       $this->extractParam();
        $requestmethod =    $this->extractRequestMethod();

        if (!empty($controller) && is_dir(WEB_ROOT . DS . 'lib' . $controller)) {
            throw new \Framework\Exception\PageNotFoundException;
        }

        if ('Login' == $controller || 'Logout' == $controller) {
            $this->controller = 'AuthController';
            $this->action = strtolower($controller);
            $this->query = array();
            return;
        }

        $this->requestMethod = $requestmethod;
        $this->controller = $controller;
    }

    /**
     * extractAction
     */
    protected function extractAction() {
        if (empty($this->action)) {
            $action = $this->extractParam();

            if ($action) {
                $action = preg_replace('/[^a-zA-Z_-]/', "",$action);
                $this->action = $action;
            }
        }

    }

    /**
     * extractId
     */
    protected function extractId() {
        $this->id = $this->extractParam();

        if (!empty($this->id) && is_numeric($this->id)) {
            //throw new \Framework\Exception\IdNotValidException($this->id);
        }

    }

    /**
     * @return array|bool|mixed
     */
    protected function extractParam() {
        $param = array_shift($this->query);
        if (strpos($param, ':')) {
            array_unshift($this->query, $param);
            return FALSE;
        }

        $param = explode("?", $param);
        $param = $param[0];
        return $param;
    }

    /**
     * @return string
     */
    protected function extractRequestMethod() {
        $requestMethod = 'get';

        if (isset($_SERVER['REQUEST_METHOD']) && !empty($_SERVER['REQUEST_METHOD'])) {
            $requestMethod = strtolower($_SERVER['REQUEST_METHOD']);
        }

        return $requestMethod;
    }

    /**
     * @return array|null
     */
    protected function extractQuery() {
        $this->query = $this->query();
        return $this->query;
    }

    /**
     * @return array
     */
    protected function query() {
        $scriptPath =  $this->scriptPath();
        $redirect = $this->redirect();
        if (isset($redirect[0]) && empty($redirect[0])) {
            array_shift($redirect);
        }

        return array_merge(
            array_diff($scriptPath, $redirect),
            array_diff($redirect, $scriptPath)
        );
    }

    /**
     * @return array
     */
    protected function redirect() {
        $redirect = array();

        if (!empty($this->Server['REDIRECT_URL'])) {
            $redirect = explode('/', $this->Server['REDIRECT_URL']);
        }

        return $redirect;
    }

    /**
     * @return array
     */
    protected function scriptPath() {
        $scriptPath = array();
        if (!empty($this->Server['REQUEST_URI'])) {
            $scriptPath = explode('/', $this->Server['REQUEST_URI']);

            $scriptPath = array_values($scriptPath);

            if (isset($scriptPath[0]) && empty($scriptPath[0])) {
                array_shift($scriptPath);
            }

            if($scriptPath[0] == 'index.php') {
                array_pop($scriptPath);
            }
        }

        return $scriptPath;
    }

}