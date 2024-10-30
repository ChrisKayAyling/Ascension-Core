<?php

namespace v1\Home\Controller;

class Controller
{

    public $templates;
    public $data;

    public function __construct() {

    }

    public function main() {
        $this->templates[] = "test.twig";
        $this->data['TestData'] = "TestData";
    }
}