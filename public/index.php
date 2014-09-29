<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Deadzone;

use Exception;
use Symfony\Component\Debug\ErrorHandler;

$root = __DIR__ . '/../';
if (!$container = @include $root . '/app/bootstrap.php') {
    http_response_code(500);
    echo "Boom goes the dynamite.\n";
    exit;
};

// Application
$app = $container->get('slim');

// Custom application logic here

# convert errors to exceptions
ErrorHandler::register();

// Set a global exception handler. NOTE: This should only handle exceptions thrown by the error handler.
set_exception_handler(function(Exception $exception) use ($app) {
    call_user_func([$app, 'error'], $exception);
});

$headers = $app->response()->headers['Content-Type'] = 'text/html; charset=utf-8';

$app->run();
