<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\API;

use Hal\Core\Utility\CachingTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\HTTP\NewBodyTrait;
use QL\Panthor\Utility\URI;
use Slim\Route;
use const JSON_UNESCAPED_SLASHES;

class ResponseFormatter
{
    use CachingTrait;
    use NewBodyTrait;

    public const API_CONTENT_TYPE = 'application/hal+json; charset=utf-8';

    private const DEFAULT_CACHE_TIME = 10;
    private const TEMPLATE_CACHE_KEY = 'api.%s.%s';

    /**
     * @var NormalizerInterface
     */
    private $normalizer;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @var array
     */
    private $cacheTimes;

    /**
     * @param NormalizerInterface $normalizer
     * @param URI $uri
     */
    public function __construct(NormalizerInterface $normalizer, URI $uri)
    {
        $this->normalizer = $normalizer;
        $this->uri = $uri;

        $this->cacheTimes = [];
    }

    /**
     * Add configuration for caching endpoints (in seconds)
     *
     * @param array $cacheTimes
     *
     * @return void
     */
    public function setCacheTimes(array $cacheTimes)
    {
        $this->cacheTimes = $cacheTimes;
    }

    /**
     * Format API data for rendering.
     *
     * @param ServerRequestInterface $request
     * @param HypermediaResource $resource
     *
     * @return string
     */
    public function buildHypermediaResponse(ServerRequestInterface $request, HypermediaResource $resource)
    {
        $route = $this->getRouteName($request);
        $params = $this->getRouteParams($request);
        $query = $request->getQueryParams();
        $self = $this->uri->absoluteURIFor($request->getUri(), $route, $params, $query);

        $data = $resource->resolved($this->normalizer, $self);
        $data = json_encode($data, JSON_UNESCAPED_SLASHES);

        // Handle caching
        $cacheTTL = $this->cacheTime($request);
        $cacheKey = $this->cacheKey($request);

        if ($this->isCacheable($request) && $cacheKey) {
            $this->setToCache($cacheKey, $data, $cacheTTL);
        }

        return $data;
    }

    /**
     * Get a cached response, if it exists.
     *
     * @return string|null
     */
    public function getCachedResponse(ServerRequestInterface $request): ?string
    {
        $cacheKey = $this->cacheKey($request);

        if (!$this->isCacheable($request)) {
            return null;
        }

        if (!$body = $this->getFromCache($cacheKey)) {
            return null;
        }

        return $body;
    }

    /**
     * Determine if the request is cacheable.
     *
     * @param ServerRequestInterface $request
     *
     * @return bool
     */
    private function isCacheable(ServerRequestInterface $request): bool
    {
        if ($request->getMethod() !== 'GET') {
            return false;
        }

        return true;
    }

    /**
     * Get the cache key for a given request.
     *
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    private function cacheKey(ServerRequestInterface $request)
    {
        $name = $this->getRouteName($request);

        $uri = $request->getUri();
        $key = $uri->getPath() . $uri->getQuery();

        return sprintf(self::TEMPLATE_CACHE_KEY, $name, hash('sha256', $key));
    }

    /**
     * Get the configured cache time for a given request.
     *
     * @param ServerRequestInterface $request
     *
     * @return int
     */
    private function cacheTime(ServerRequestInterface $request): int
    {
        $name = $this->getRouteName($request);

        if (isset($this->cacheTimes[$name])) {
            return $this->cacheTimes[$name];
        }

        return self::DEFAULT_CACHE_TIME;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    private function getRouteName(ServerRequestInterface $request): string
    {
        $route = $request->getAttribute('route');
        if ($route instanceof Route) {
            return $route->getName();
        }

        return 'unknown';
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    private function getRouteParams(ServerRequestInterface $request): array
    {
        $route = $request->getAttribute('route');
        if ($route instanceof Route) {
            return $route->getArguments();
        }

        return [];
    }
}
