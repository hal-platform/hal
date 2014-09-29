<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Slim;

use Exception;
use Slim\Slim;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Convert a flat array into slim routes and attaches them to the slim application.
 *
 * This hook should be attached to the "slim.before.router" event.
 */
class RouteLoaderHook
{
    /**
     * A hash of valid http methods. The keys are the methods.
     *
     * @var array
     */
    private $methods;

    /**
     * @var Slim
     */
    private $app;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array
     */
    private $routes;

    /**
     * @param ContainerInterface $container
     * @param array $routes
     */
    public function __construct(ContainerInterface $container, array $routes)
    {
        $this->container = $container;
        $this->routes = $routes;

        $validMethods = ['DELETE', 'GET', 'HEAD', 'OPTIONS', 'PATCH', 'POST', 'PUT'];
        $this->methods = array_fill_keys($validMethods, true);
    }

    /**
     * Load routes into the application
     *
     * @param Slim $app
     * @return null
     */
    public function __invoke(Slim $app)
    {
        // Keep a ref to the app so closures have access to it.
        $this->app = $app;

        foreach ($this->routes as $name => $details) {

            $methods = $this->methods($details);
            $conditions = $this->nullable('conditions', $details);
            $url = rtrim($details['route'], '/').'/';
            $stack = $this->convertStackToCallables($details['stack']);

            // Prepend the url to the stack
            array_unshift($stack, $url);

            // Create route
            // Special note: slim is really stupid in the way it uses func_get_args EVERYWHERE
            $route = call_user_func_array([$this->app, 'map'], $stack);
            call_user_func_array([$route, 'via'], $methods);

            // Add Name
            $route->name($name);

            // Add Conditions
            if ($conditions) {
                $route->conditions($conditions);
            }
        }
    }

    /**
     * Convert an array of keys to middleware callables
     *
     * @param string[] $stack
     * @return callable[]
     */
    private function convertStackToCallables(array $stack)
    {
        foreach ($stack as &$key) {
            $key = function () use ($key) {
                call_user_func_array(
                    $this->container->get($key),
                    $this->getServiceParameters()
                );
            };
        }

        return $stack;
    }

    /**
     * @return array
     */
    private function getServiceParameters()
    {
        return [
            $this->app->request(),
            $this->app->response(),
            $this->app->router()->getCurrentRoute()->getParams(),
            [$this->app, 'notFound']
        ];
    }

    /**
     * @param array $routeDetails
     * @throws Exception
     * @return string[]
     */
    private function methods(array $routeDetails)
    {
        // No method matches ANY method
        if (!$methods = $this->nullable('method', $routeDetails)) {
            return ['ANY'];
        }

        if ($methods && !is_array($methods)) {
            $methods = [$methods];
        }

        // check for invalid method types
        foreach ($methods as $method) {
            if (!isset($this->methods[$method])) {
                throw new Exception("Unknown HTTP method $method.");
            }
        }

        if ($methods === ['GET']) {
            array_push($methods, 'HEAD');
        }

        return $methods;
    }

    /**
     * @param string $key
     * @param array $data
     * @return mixed
     */
    private function nullable($key, array $data)
    {
        if (isset($data[$key])) {
            return $data[$key];
        }

        return null;
    }
}
