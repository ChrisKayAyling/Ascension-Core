<?php

namespace Ascension\Middleware;

interface MiddlewareInterface
{
    public function handle($request, $response, $next);
}