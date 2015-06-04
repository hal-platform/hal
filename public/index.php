<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Bootstrap;

use Exception;
use QL\Hal\Slim\ErrorHandler;

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

// Custom https,port detection for our weirdo server config
if (!empty($_SERVER['HTTP_FRONT_END_HTTPS'])) {
    $_SERVER['HTTPS'] = 'on';
}
if (!empty($_SERVER['HTTP_X_FORWARDED_PORT'])) {
    $_SERVER['SERVER_PORT'] = (int) $_SERVER['HTTP_X_FORWARDED_PORT'];
}

// Application
$app = $container->get('slim');

// Custom application logic here
ini_set('session.use_cookies', '0');
ini_set('memory_limit','384M');
ini_set('display_errors', 0);

# convert errors to exceptions
ErrorHandler::register([$app, 'error']);

$headers = $app->response()->headers['Content-Type'] = 'text/html; charset=utf-8';

$app->run();
