<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use Slim\Slim;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Yaml\Yaml;

$root = implode('/', array_slice(explode('/', __DIR__), 0, -1));

require $root . '/vendor/autoload.php';

$locator = new FileLocator($root);

$config = new ParameterBag(Yaml::parse(file_get_contents($root . '/config.yml')));

$dic = new ContainerBuilder($config);
$dil = new YamlFileLoader($dic, $locator);
$dil->load('di.yml');
$dic->setParameter('root', $root);

$app = new Slim($dic->getParameter('slim'));
// setting a default content type here
$app->response()->header('Content-Type', 'text/html; charset=utf-8');

$routeLoader = new RouteLoader($locator, $app, $dic);
$routeLoader->load('routes.yml');

$app->run();
