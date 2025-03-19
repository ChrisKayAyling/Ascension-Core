<?php

namespace Ascension\Components;

class Route
{
    public string $name;
    private string $path;
    private $controller;
    private $injected; //  injected class (typically repository)
    private array $verbs = [];
    private string $method;

    public function __construct(string $name, string $path) {
        $this->name = $name;
        $this->path = $path;
    }

    public function controller($controller): self {
        $this->controller = $controller;
        return $this;
    }

    public function inject($injectedClass): self {
        $this->injected = $injectedClass;
        return $this;
    }

    public function verbs(array $verbs): self {
        $this->verbs = $verbs;
        return $this;
    }

    public function method($method): self {
        $this->method = $method;
        return $this;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getInjectedClass() {
        return $this->injected[0];
    }

    public function getPath(): string {
        return $this->path;
    }

    public function getController() {
        return $this->controller[0];
    }

    public function getVerbs(): array {
        return $this->verbs;
    }

    public function getMethod(): string {
        return $this->method;
    }
}