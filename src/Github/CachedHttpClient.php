<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Github;

use DateTime;
use DateTimeZone;
use Github\HttpClient\Cache\CacheInterface;
use Github\HttpClient\Cache\FilesystemCache;
use Github\HttpClient\HttpClient;
use Guzzle\Http\Message\Response;
use RuntimeException;

/**
 * Extending the knplabs api to cache based on url + query string (instead of just url) and cache to redis.
 */
class CachedHttpClient extends HttpClient
{
    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * Contains the lastResponse fetched from cache
     *
     * @var Response
     */
    protected $lastCachedResponse;

    /**
     * @throws RuntimeException
     * @return CacheInterface
     */
    public function getCache()
    {
        if ($this->cache === null) {
            throw new RuntimeException('No cache was set for the http client');
        }

        return $this->cache;
    }

    /**
     * @param $cache CacheInterface
     * @return null
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function request($path, $body = null, $httpMethod = 'GET', array $headers = array(), array $options = array())
    {
        $cacheKey = $this->buildCacheKey($path, $options);

        $response = parent::request($path, $body, $httpMethod, $headers, $options);

        if (304 == $response->getStatusCode() && $this->getCache()->has($cacheKey)) {
            $this->lastCachedResponse = $this->getCache()->get($cacheKey);
            return $this->lastCachedResponse;
        }

        $this->getCache()->set($cacheKey, $response);
        return $response;
    }

    /**
     * Create requests with If-Modified-Since headers
     *
     * {@inheritdoc}
     */
    protected function createRequest($httpMethod, $path, $body = null, array $headers = array(), array $options = array())
    {
        $cacheKey = $this->buildCacheKey($path, $options);

        $request = parent::createRequest($httpMethod, $path, $body, $headers, $options);

        if ($modifiedAt = $this->getCache()->getModifiedSince($cacheKey)) {
            $modifiedAt = new DateTime('@' . $modifiedAt);
            $modifiedAt->setTimezone(new DateTimeZone('GMT'));

            $request->addHeader('If-Modified-Since', sprintf('%s GMT', $modifiedAt->format('l, d-M-y H:i:s')));
        }

        if ($etag = $this->getCache()->getETag($cacheKey)) {
            $request->addHeader('If-None-Match', $etag);
        }

        return $request;
    }

    /**
     * @param bool $force
     * @return Response
     */
    public function getLastResponse($force = false)
    {
        $lastResponse =  parent::getLastResponse();
        if (304 != $lastResponse->getStatusCode()) {
            $force = true;
        }

        return ($force) ? $lastResponse : $this->lastCachedResponse;
    }

    /**
     * Append the query string to the path for generating a unique cache key
     *
     * @param string $path
     * @param array $options
     *
     * @return string
     */
    private function buildCacheKey($path, $options)
    {
        $cacheKey = $path;
        if (isset($options['query'])) {
            $cacheKey .= '?' . http_build_query($options['query']);
        }

        return $cacheKey;
    }
}
