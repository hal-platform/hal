<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use Slim\Route;
use Slim\Slim;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

class RouteLoader
{
    /**
     * @var FileLocatorInterface
     */
    private $locator;

    /**
     * @var Slim
     */
    private $app;

    /**
     * @var ContainerBuilder
     */
    private $dic;

    /**
     * @param FileLocatorInterface $locator
     * @param Slim $app
     * @param ContainerBuilder $dic
     */
    public function __construct(FileLocatorInterface $locator, Slim $app, ContainerBuilder $dic)
    {
        $this->locator = $locator;
        $this->app = $app;
        $this->dic = $dic;
    }

    /**
     * @param string $definitionFile
     */
    public function load($definitionFile)
    {
        $definitionFile = $this->locator->locate($definitionFile);
        $definitions = Yaml::parse($definitionFile);

        foreach ($definitions as $methodAndUrl => $middlewareKeys) {
            list($method, $url) = $this->splitMethodAndUrl($methodAndUrl);
            $callableStack = $this->convertMiddlewareKeysToCallables($middlewareKeys);
            array_unshift($callableStack, $url);
            call_user_func_array(array($this->app, $method), $callableStack);
        }
    }

    /**
     * @param string $methodAndUrl
     * @return string[]
     */
    private function splitMethodAndUrl($methodAndUrl)
    {
        $methodAndUrl = explode(' ', $methodAndUrl);
        $method = strtolower(array_shift($methodAndUrl));
        $url = array_pop($methodAndUrl);

        return array($method, $url);
    }

    /**
     * @param string[] $middlewareKeys
     * @return callable[]
     */
    private function convertMiddlewareKeysToCallables(array $middlewareKeys)
    {
        $dic = $this->dic;
        $req = $this->app->request();
        $res = $this->app->response();
        $rtr = $this->app->router();
        $notFound = array($this->app, 'notFound');

        $pageHandler = array_pop($middlewareKeys);

        foreach ($middlewareKeys as &$key) {
            $serviceName = $key;
            $key = function (Route $route) use ($dic, $serviceName, $req, $res, $notFound) {
                $middleware = $dic->get($serviceName);
                call_user_func($middleware, $req, $res, $route->getParams(), $notFound);
            };
        }

        $pageBootstrap = function () use ($pageHandler, $req, $res, $rtr, $notFound, $dic) {
            $params = $rtr->getCurrentRoute()->getParams();
            $pageHandler = $dic->get($pageHandler);
            call_user_func($pageHandler, $req, $res, $params, $notFound);
        };

        $middlewareKeys[] = $pageBootstrap;

        return $middlewareKeys;
    }
}
