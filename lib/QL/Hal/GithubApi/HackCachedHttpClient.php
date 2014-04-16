<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\GithubApi;

use Github\HttpClient\Cache\CacheInterface;
use Github\HttpClient\Cache\FilesystemCache;
use Github\HttpClient\HttpClient;

/**
 * Extending the knplabs api to not be completely horrible and stupid.
 *
 * @internal
 */
class HackCachedHttpClient extends HttpClient
{
    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @return CacheInterface
     */
    public function getCache()
    {
        if (null === $this->cache) {
            $this->cache = new FilesystemCache($this->options['cache_dir'] ?: sys_get_temp_dir().DIRECTORY_SEPARATOR.'php-github-api-cache');
        }

        return $this->cache;
    }

    /**
     * @param $cache CacheInterface
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
        $cacheKey = $path;
        if (isset($options['query'])) {
            $cacheKey .= serialize($options['query']);
        }
        $response = parent::request($path, $body, $httpMethod, $headers, $options);

        if (304 == $response->getStatusCode()) {
            return $this->getCache()->get($cacheKey);
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
        $cacheKey = $path;
        if (isset($options['query'])) {
            $cacheKey .= serialize($options['query']);
        }
        $request = parent::createRequest($httpMethod, $path, $body, $headers = array(), $options);

        if ($etag = $this->getCache()->getETag($cacheKey)) {
            $request->addHeader(
                'If-None-Match',
                $etag
            );
        }

        return $request;
    }
}
