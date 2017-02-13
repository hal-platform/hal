<?php

namespace Hal\Bootstrap;

use Exception;
use QL\Panthor\ErrorHandling\FatalErrorHandler;

define('MAINTENANCE', false);

$root = __DIR__ . '/..';

if (MAINTENANCE) {
    require $root . '/templates/maintenance.html';
    exit;
}

if (!$container = @include $root . '/configuration/bootstrap.php') {
    http_response_code(500);
    echo "Boom goes the dynamite.\n";
    exit;
};

// Custom https,port detection for our weirdo load balancer config
if (!empty($_SERVER['HTTP_FRONT_END_HTTPS'])) {
    $_SERVER['HTTPS'] = 'on';
}
if (!empty($_SERVER['HTTP_X_FORWARDED_PORT'])) {
    $_SERVER['SERVER_PORT'] = (int) $_SERVER['HTTP_X_FORWARDED_PORT'];
}

// Error handling
$handler = $container->get('error.handler');
$handler->register();

ini_set('session.use_cookies', '0');
ini_set('memory_limit','384M');
ini_set('display_errors', 0);

// Application
$app = $container->get('slim');

// Custom application logic here
$handler->attach($app);

$headers = $app->response()->headers->set('Content-Type', 'text/html; charset=utf-8');

$app->run();
