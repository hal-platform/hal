<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Helpers;

use MCP\Cache\CachingTrait;
use Slim\Http\Request;
use Slim\Http\Response;

class ApiHelper
{
    use CachingTrait;

    const API_RESPONSE_CACHE_TIME = 10;
    const CACHE_KEY = 'api:%s';

    /**
     * @var UrlHelper
     */
    private $url;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var float
     */
    private $pageResponseStart;

    /**
     * @var array
     */
    private $customCacheTimes;

    /**
     * @var string|null
     */
    private $cacheKey;

    /**
     * @param UrlHelper $url
     * @param Request $request
     * @param array $customCacheTimes
     */
    public function __construct(UrlHelper $url, Request $request, array $customCacheTimes)
    {
        $this->url = $url;
        $this->request = $request;

        $this->customCacheTimes = $customCacheTimes;
        $this->pageResponseStart = microtime(true);
    }

    /**
     * Format content and prepare response object
     *
     * @param Response $response
     * @param $content
     *
     * @return null
     */
    public function prepareResponse(Response $response, $content)
    {
        $body = json_encode($content, JSON_UNESCAPED_SLASHES);

        $response->headers->set('Content-Type', 'application/hal+json; charset=utf-8');
        $response->setBody($body);

        // attempt to cache the response
        if ($this->request->isGet()) {
            // do not cache if cache time is 0
            if ($cacheTime = $this->cacheTime()) {
                $this->setToCache($this->cacheKey(), $body, $cacheTime);
            }
        }

        $response->headers->set('hal_cache_status', 'NOT CACHED');
        $response->headers->set('hal_response_time', round(microtime(true) - $this->pageResponseStart, 2));
    }

    /**
     * Check if the response is cached and attach to the response
     *
     * Returns true if response is cached.
     *
     * @param Response $response
     *
     * @return boolean
     */
    public function checkForCachedResponse(Response $response)
    {
        // Only get requests are cached
        if (!$this->request->isGet()) {
            return false;
        }

        $key = $this->cacheKey();

        if ($cached = $this->getFromCache($key)) {
            $response->headers->set('Content-Type', 'application/hal+json; charset=utf-8');
            $response->setBody($cached);

            $response->headers->set('hal_cache_status', 'CACHED');
            $response->headers->set('hal_response_time', round(microtime(true) - $this->pageResponseStart, 2));

            return true;
        }

        return false;
    }

    /**
     * Formats a link from properties.
     *
     * "href" can be in the following formats:
     *   - 'route.key'
     *   - ['route.key', [route.parameters]]
     *   - ['route.key', [route.parameters], [get.parameters]]
     *
     * @param array $properties
     * @return array
     */
    public function parseLink(array $properties)
    {
        foreach ($properties as $property => &$value) {
            if ($property == 'href') {
                if (is_array($value) && count($value) >= 2) {
                    $suffix = '';
                    if (isset($value[2])) {
                        $suffix .= '?' . http_build_query($value[2]);
                    }

                    $value = $this->url->urlFor($value[0], $value[1]) . $suffix;

                } else {
                    $value = $this->url->urlFor($value);
                }
            }
        }

        return $properties;
    }

    /**
     * Formats a collection of links
     *
     * @param array $links
     * @return array
     */
    public function parseLinks(array $links)
    {
        $parsed = [];

        foreach ($links as $relation => $properties) {
            $parsed[$relation] = $this->parseLink($properties);
        }

        return $parsed;
    }

    /**
     * Lazy load the cache key from the request
     *
     * url + query string = unique-ish cache key
     *
     * @return string
     */
    private function cacheKey()
    {
        if (!$this->cacheKey) {
            $unique = $this->request->getPathInfo() . '?' . http_build_query($this->request->get());
            $this->cacheKey = sprintf(self::CACHE_KEY, sha1($unique));
        }

        return $this->cacheKey;
    }

    /**
     * Get the cache time in seconds
     *
     * @return int
     */
    private function cacheTime()
    {
        $name = $this->url->currentRoute()->getName();
        if (isset($this->customCacheTimes[$name])) {
            return $this->customCacheTimes[$name];
        }

        return self::API_RESPONSE_CACHE_TIME;
    }
}
