<?php

namespace Ascension\Exceptions;

class RequestIDFailure extends \Exception
{
    public function __construct($message, $code, Throwable $previous = null) {

        parent::__construct($message, $code, $previous);
    }

    public function stdOutput($code, $message) {
        echo "Ascension Exception Raised: " . $code . " - " . $message . "\n";
    }
}