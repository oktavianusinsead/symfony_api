<?php

use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

$loader = require __DIR__.'/../vendor/autoload.php';

// Use APC for autoloading to improve performance.
// Change 'sf2' to a unique prefix in order to prevent cache key conflicts
// with other applications also using APC.

require_once __DIR__.'/../app/AppKernel.php';
require_once __DIR__.'/../app/AppCache.php';

if (getenv("AWS_ENVIRONMENT") === 'dev')  Debug::enable();

$kernel = new AppKernel(getenv("AWS_ENVIRONMENT"), false);
Request::enableHttpMethodParameterOverride();

$request = Request::createFromGlobals();

// trust aws load balancer for forwarded protocol header
Request::setTrustedProxies([$request->server->get('REMOTE_ADDR')],Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO);

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
