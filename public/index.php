<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use Exception;
use MCP\DataType\IPv4Address;

require_once __DIR__.'/../app/bootstrap.php';

// Application
$app = $container->get('slim');
$app->view($container->get('twigView'));

// 404 Error Handler
$app->notFound(function () use ($app) {
    $app->status(404);
    $app->render('error.html.twig', array('message' => 'Page Not Found'));
});

// 500 Error Handler
$app->error(function (Exception $e) use ($app) {
    $app->status(500);
    $app->render('error.html.twig', array('message' => 'Oh, snap! You broke it.'));
});

// Load Routes
$routeLoader = new RouteLoader($locator, $app, $container);
$routeLoader->load(ROUTES_FILE);

// Add Twig Globals
$twig = $container->get('twigEnv');
$twig->addGlobal('account', $container->get('session')->get('account'));
$twig->addGlobal('session', $container->get('session'));
//$twig->getExtension('core')->setTimezone('America/Detroit');

$app->response()->header('Content-Type', 'text/html; charset=utf-8');
$app->run();
