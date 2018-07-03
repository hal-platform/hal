<?php

namespace Hal\Bootstrap;

use QL\Panthor\Bootstrap\GlobalMiddlewareLoader;
use QL\Panthor\Bootstrap\RouteLoader;
use QL\Panthor\ErrorHandling\ErrorHandler;
use QL\Panthor\ErrorHandling\ExceptionHandler;

define('HAL_APP_START', microtime(true));

$root = realpath(__DIR__ . '/..');

$container = require "${root}/config/bootstrap.php";

// Error handling
$handler = $container->get(ErrorHandler::class);
$handler->register();
$handler->registerShutdown();

ini_set('session.use_cookies', '0');
ini_set('memory_limit','384M');
ini_set('display_errors', 0);

// Build Slim application
$app = $container->get('slim');

// Load routes onto Slim
$container->get(RouteLoader::class)($app);

// Add global middleware to Slim
$container->get(GlobalMiddlewareLoader::class)($app);

// Attach Slim to exception handler for error rendering
$container->get(ExceptionHandler::class)->attachSlim($app);

$app->run();

$mem = round(memory_get_usage() / 1000000, 2);
$peak = round(memory_get_peak_usage() / 1000000, 2);
$time = round(microtime(true) - HAL_APP_START, 3);
// echo "<pre>Memory: ${mem}mb (Peak: ${peak}mb)\nTime: ${time}s</pre>";
