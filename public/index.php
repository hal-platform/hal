<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use Exception;
use Slim\Slim;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Yaml\Yaml;

use Twig_Environment;

const CONFIG_FILE       = 'app/config.yml';
const DI_CONFIG_FILE    = 'app/di.yml';
const ROUTES_FILE       = 'app/routes.yml';

require '../vendor/autoload.php';

/**
 *  Check if a file exists and is readable the path
 *
 *  @param string $path
 *  @return string
 *  @throws Exception
 */
function check_file($path)
{
    if (is_readable($path)) {
        return $path;
    } else {
        throw new Exception("File $path is not readable");
    }
}

$root       = implode('/', array_slice(explode('/', __DIR__), 0, -1));
$locator    = new FileLocator($root);

// Check Required Files
check_file($locator->locate(CONFIG_FILE));
check_file($locator->locate(DI_CONFIG_FILE));
check_file($locator->locate(ROUTES_FILE));

// Import Config
$yml        = file_get_contents($locator->locate(CONFIG_FILE));
$config     = new ParameterBag(Yaml::parse($yml));

// DI Container
$container = new ContainerBuilder($config);
$builder = new YamlFileLoader($container, $locator);
$builder->load(DI_CONFIG_FILE);
$container->setParameter('root', $root);
$container->set('dic', $container);
$container->compile();

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
