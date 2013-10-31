<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use Exception;
use Slim\Route;
use Slim\Slim;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

/**
 *  YML Route Loader for Slim
 *
 *  @package QL\Hal
 */
class RouteLoader
{
    /**
     *  @var FileLocatorInterface
     */
    private $locator;

    /**
     *  @var Slim
     */
    private $app;

    /**
     *  @var ContainerBuilder
     */
    private $container;

    /**
     * @param FileLocatorInterface $locator
     * @param Slim $app
     * @param ContainerBuilder $container
     */
    public function __construct(FileLocatorInterface $locator, Slim $app, ContainerBuilder $container)
    {
        $this->locator = $locator;
        $this->app = $app;
        $this->container = $container;
    }

    /**
     *  Load routes into application
     *
     *  @param $definitionFile
     *  @throws \Exception
     */
    public function load($definitionFile)
    {
        $definitionFile = $this->locator->locate($definitionFile);
        $routes = Yaml::parse($definitionFile);

        foreach ($routes as $name => $details) {
            $method     = $details['method'];
            $route      = $details['route'];
            $stack      = $this->convertMiddlewareKeysToCallables($details['stack']);

            array_unshift($stack, $route);

            // Create Controller
            switch($method) {
                case 'GET':
                case 'POST':
                case 'PUT':
                case 'DELETE':
                    $controller = call_user_func_array(array($this->app, $method), $stack);
                    break;
                default:
                    throw new Exception("Unknown HTTP method $method.");
            }

            // Add Conditions
            $conditions = (isset($details['conditions'])) ? $details['conditions'] : array();
            $controller->conditions($conditions);

            // Add Name
            $controller->name($name);
        }
    }

    /**
     *  Convert an array of keys to middleware callables
     *
     *  @param string[] $stack
     *  @return callable[]
     */
    private function convertMiddlewareKeysToCallables(array $stack)
    {
        $container  = $this->container;
        $request    = $this->app->request();
        $response   = $this->app->response();
        $router     = $this->app->router();
        $notFound   = array($this->app, 'notFound');
        $handler    = array_pop($stack);

        foreach ($stack as &$service) {
            $key = $service;
            $service = function (Route $route) use ($container, $key, $request, $response, $notFound) {
                $middleware = $container->get($key);
                $params = $route->getParams();
                call_user_func($middleware, $request, $response, $params, $notFound);
            };
        }

        $page = function () use ($handler, $request, $response, $router, $notFound, $container) {
            $params = $router->getCurrentRoute()->getParams();
            $pageHandler = $container->get($handler);
            call_user_func($pageHandler, $request, $response, $params, $notFound);
        };

        $stack[] = $page;

        return $stack;
    }
}
