<?php

namespace Mock\lib\Middleware;

use Ascension\Core;
use Ascension\Middleware\MiddlewareInterface;

class FormattingMiddleware implements MiddlewareInterface
{
    public function handle($request, $response, $next)
    {

        if (!isset($_SESSION['user'])) {
            return "user not authenticated";
        }

        // Core::$HTTP - Change route
        // /Dashboard
        // /FailedLogin


        $_SERVER['content-type'] = "text/xml";
        // formatting
        Core::$UserData['soap'] = $

        $_SERVER['uri'] = "auth/logout";
        Core::$Route['controller'] = "Auth";
        Core::$Route['method'] = "logout";

        $next(); // allow chaining (might remove)
    }


    public function method1() {

    }
    public function method2() {

    }
}