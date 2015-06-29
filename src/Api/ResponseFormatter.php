<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Api;

use MCP\Cache\CachingTrait;
use QL\Hal\Api\Utility\HypermediaFormatter;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Route;

class ResponseFormatter
{
    use CachingTrait;

    const TYPE = 'application/hal+json; charset=utf-8';

    const CACHE_TIME = 10;
    const CACHE_KEY = 'api:%s';

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var Normalizer
     */
    private $normalizer;

    /**
     * @var HypermediaFormatter
     */
    private $formatter;

    /**
     * @var Route
     */
    private $currentRoute;

    /**
     * @var array
     */
    private $cacheTimes;

    /**
     * @var float
     */
    private $start;

    /**
     * @param Request $request
     * @param Response $response
     * @param Route $currentRoute
     *
     * @param Normalizer $normalizer
     * @param HypermediaFormatter $formatter
     * @param array $cacheTimes
     */
    public function __construct(
        Request $request,
        Response $response,
        Route $currentRoute,

        Normalizer $normalizer,
        HypermediaFormatter $formatter,
        array $cacheTimes = []
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->currentRoute = $currentRoute;

        $this->normalizer = $normalizer;
        $this->formatter = $formatter;
        $this->cacheTimes = $cacheTimes;

        $this->start = microtime(true);
    }

    /**
     * Format and send the response data
     *
     * @param mixed $data
     * @param int $status
     * @param bool $cache
     */
    public function respond($data, $status = 200, $cache = true)
    {
        if (is_array($data)) {
            $data = $this->resolve($data);
        } elseif (is_object($data)) {
            $data = $this->normalizer->normalize($data);
        }

        $body = json_encode($this->formatter->format($data), JSON_UNESCAPED_SLASHES);

        // cache the result
        if ($cache && $this->request->isGet() && ($ttl = $this->cacheTime($this->currentRoute))) {
            $this->setToCache($this->cacheKey($this->request), $body, $ttl);
        }

        $this->response->body($body);
        $this->response->header('Content-Type', self::TYPE);
        $this->response->header('hal_cache_status', 'NOT CACHED');
        $this->response->header('hal_response_time', round(microtime(true) - $this->start, 2));
        $this->response->setStatus($status);
    }

    /**
     * Recursively resolve any objects in the resource tree
     *
     * @param array $tree
     * @return array
     */
    private function resolve(array $tree)
    {
        array_walk_recursive($tree, function (&$leaf) {
            if (is_object($leaf)) {
                $leaf = $this->normalizer->normalize($leaf);
            }
        });

        return $tree;
    }

    /**
     * Send a cached response, if it exists
     *
     * @return bool
     */
    public function sendCachedResponse()
    {
        $key = $this->cacheKey($this->request);

        if ($this->request->isGet() && ($body = $this->getFromCache($key))) {
            $this->response->setBody($body);
            $this->response->headers->set('Content-Type', self::TYPE);
            $this->response->headers->set('hal_cache_status', 'CACHED');
            $this->response->headers->set('hal_response_time', round(microtime(true) - $this->start, 2));

            return true;
        }

        return false;
    }

    /**
     * Get the cache key for a given request
     *
     * @param Request $request
     * @return string
     */
    private function cacheKey(Request $request)
    {
        return sprintf(self::CACHE_KEY, sha1($request->getPathInfo() . '?' . http_build_query($request->get())));
    }

    /**
     * Get the cache time for a given route
     *
     * @param Route $route
     * @return int
     */
    private function cacheTime(Route $route)
    {
        $name = $route->getName();

        if (isset($this->cacheTimes[$name])) {
            return $this->cacheTimes[$name];
        }

        return self::CACHE_TIME;
    }
}
