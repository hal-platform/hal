<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Deadzone;

$root = __DIR__ . '/../';
if (!$container = @include $root . '/app/bootstrap.php') {
    echo "Boom goes the dynamite.\n";
    return;
};

// Application
$app = $container->get('slim');
$headers = $app->response()->headers['Content-Type'] = 'text/html; charset=utf-8';
$app->run();
