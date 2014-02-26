<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use Slim\Slim;

require_once __DIR__.'/../app/bootstrap.php';

// Application
$app = new Slim($container->getParameter('slim'));
$app->view($container->get('twigView'));

// Load Routes
$routeLoader = new RouteLoader($locator, $app, $container);
$routeLoader->load(ROUTES_FILE);

// 404 Error Handler
$app->notFound(function () use ($app) {
    $app->status(404);
    $app->render('error.html.twig', array('message' => 'Page Not Found'));
});

// Add Twig Globals
$twig = $container->get('twigEnv');
$twig->addGlobal('account', $container->get('session')->get('account'));
$twig->addGlobal('session', $container->get('session'));

$app->response()->header('Content-Type', 'text/html; charset=utf-8');
$app->run();
