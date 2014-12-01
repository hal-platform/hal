<?php

namespace QL\Hal\Api;

use MCP\Cache\CachingTrait;
use QL\Hal\Api\Utility\HypermediaFormatter;
use QL\Hal\Helpers\UrlHelper;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Route;

/**
 * API Response Formatter
 */
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
     * @var UrlHelper
     */
    private $url;

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
     * @param UrlHelper $url
     * @param Normalizer $normalizer
     * @param HypermediaFormatter $formatter
     * @param array $cacheTimes
     */
    public function __construct(
        Request $request,
        Response $response,
        UrlHelper $url,
        Normalizer $normalizer,
        HypermediaFormatter $formatter,
        array $cacheTimes = []
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->url = $url;
        $this->normalizer = $normalizer;
        $this->formatter = $formatter;
        $this->cacheTimes = $cacheTimes;

        $this->start = microtime(true);
    }

    /**
     * Format and send the response data
     *
     * @param mixed $data
     * @param bool $cache
     */
    public function respond($data, $cache = true)
    {
        if (is_array($data)) {
            $data = $this->resolve($data);
        } elseif (is_object($data)) {
            $data = $this->normalizer->normalize($data);
        }

        $body = json_encode($this->formatter->format($data), JSON_UNESCAPED_SLASHES);

        // cache the result
        if ($cache && $this->request->isGet() && ($ttl = $this->cacheTime($this->url->currentRoute()))) {
            $this->setToCache($this->cacheKey($this->request), $body, $ttl);
        }

        $this->response->body($body);
        $this->response->header('Content-Type', self::TYPE);
        $this->response->header('hal_cache_status', 'NOT CACHED');
        $this->response->header('hal_response_time', round(microtime(true) - $this->start, 2));
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
