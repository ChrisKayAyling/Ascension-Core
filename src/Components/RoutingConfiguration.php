<?php
namespace Ascension\Components;

class RoutingConfiguration
{
    /**
     * @var array $routes
     */
    private array $routes = [];

    /**
     * @param string $name
     * @param string $path
     * @return Route
     */
    public function add(string $name, string $path): Route {
        $route = new Route($name, $path);
        $this->routes[$name] = $route;
        return $route;
    }

    /**
     * @return array
     */
    public function getRoutes(): array {
        return $this->routes;
    }

    /**
     * @wip
     * @author CKA
     * @return void
     */
    public function list() {
        echo "-------------------------------------------------------------------------------------------------------";
        echo "|   Name         |   Path                 |  Controller          |  Method           |  Verbs         |";

        foreach ($this->routes as $route) {
            die(Var_Export($route,true));
            sprintf("|----------------|------------------------|----------------------|-------------------|----------------|");
            sprintf("|----------------|------------------------|----------------------|-------------------|----------------|");
        }
    }

}