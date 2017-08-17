<?php

namespace Hal\Bootstrap;

define('MAINTENANCE', false);
$start = microtime(true);

$root = __DIR__ . '/..';

if (MAINTENANCE) {
    require $root . '/templates/maintenance.html';
    exit;
}

if (!$container = @include $root . '/config/bootstrap.php') {
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
$handler->registerShutdown();

ini_set('session.use_cookies', '0');
ini_set('memory_limit','384M');
ini_set('display_errors', 0);

// Build Slim application
$app = $container->get('slim');

// Load routes onto Slim
$routes = $container->get('slim.router.loader');
$routes($app);

// Add global middleware to Slim
$container
    ->get('slim.global_middleware')
    ->attach($app);

// Attach Slim to exception handler for error rendering
$container
    ->get('exception.handler')
    ->attachSlim($app);

$app->run();

$mem = round(memory_get_usage() / 1000000, 2);
$peak = round(memory_get_peak_usage() / 1000000, 2);
$time = round(microtime(true) - $start, 3);
// echo "<pre>Memory: ${mem}mb (Peak: ${peak}mb)\nTime: ${time}s</pre>";
