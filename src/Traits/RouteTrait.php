<?php

namespace Ascension\Traits;
use Ascension\Components\RoutingConfiguration;


trait RouteTrait
{

    public function add($name, $route, $methods): Route {
        return new Route($name, $route, $methods);
    }

}