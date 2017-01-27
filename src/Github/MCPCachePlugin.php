<?php
/**
 * @copyright ©2017 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */
namespace QL\Hal\Github;

use Http\Client\Common\Plugin;
use Http\Message\StreamFactory;
use Http\Promise\FulfilledPromise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use QL\MCP\Cache\CacheInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MCPCachePlugin implements Plugin
{
    const SHORT_TTL = 20;
    const DEFAULT_TTL = 60;

    const GITHUB_NAME = '[A-Za-z0-9\_\.\-]+';

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var StreamFactory
     */
    private $streamFactory;

    /**
     * @var array
     */
    private $config;

    /**
     * @param CacheInterface $simpleCache
     * @param StreamFactory $streamFactory
     * @param array $config {
     *
     * @var bool $respect_cache_headers Whether to look at the cache directives or ignore them
     * @var int $default_ttl (seconds) If we do not respect cache headers or can't calculate a good ttl, use this
     *              value
     * @var string $hash_algo The hashing algorithm to use when generating cache keys
     * @var int $cache_lifetime (seconds) To support serving a previous stale response when the server answers 304
     *              we have to store the cache for a longer time than the server originally says it is valid for.
     *              We store a cache item for $cache_lifetime + max age of the response.
     * }
     */
    public function __construct(CacheInterface $simpleCache, StreamFactory $streamFactory, array $config = [])
    {
        $this->cache = $simpleCache;
        $this->streamFactory = $streamFactory;

        $optionsResolver = new OptionsResolver();
        $this->configureOptions($optionsResolver);
        $this->config = $optionsResolver->resolve($config);
    }

    /**
     * {@inheritdoc}
     */
    public function handleRequest(RequestInterface $request, callable $next, callable $first)
    {
        $method = strtoupper($request->getMethod());
        // if the request not is cachable, move to $next
        if ($method !== 'GET' && $method !== 'HEAD') {
            return $next($request);
        }

        // If we can cache the request
        $key = $this->createCacheKey($request);
        $data = $this->cache->get($key);

        if ($data) {
            // The array_key_exists() is to be removed in 2.0.
            if (array_key_exists('expiresAt', $data) && ($data['expiresAt'] === null || time() < $data['expiresAt'])) {
                // This item is still valid according to previous cache headers
                return new FulfilledPromise($this->createResponseFromCacheItem($data));
            }

            // Add headers to ask the server if this cache is still valid
            if ($modifiedSinceValue = $this->getModifiedSinceHeaderValue($data)) {
                $request = $request->withHeader('If-Modified-Since', $modifiedSinceValue);
            }

            if ($etag = $this->getETag($data)) {
                $request = $request->withHeader('If-None-Match', $etag);
            }
        }

        return $next($request)->then(function (ResponseInterface $response) use ($data, $key, $request) {
            if (304 === $response->getStatusCode()) {
                if (!$data) {
                    /*
                     * We do not have the item in cache. This plugin did not add If-Modified-Since
                     * or If-None-Match headers. Return the response from server.
                     */
                    return $response;
                }

                // The cached response we have is still valid
                $maxAge = $this->getMaxAge($response);
                $data['expiresAt'] = $this->calculateResponseExpiresAt($maxAge);
                $ttl = $this->calculateCacheItemExpiresAfter($maxAge, $request);
                $this->cache->set($key, $data, $ttl);

                return $this->createResponseFromCacheItem($data);
            }

            if ($this->isCacheable($response)) {
                $bodyStream = $response->getBody();
                $body = $bodyStream->__toString();
                if ($bodyStream->isSeekable()) {
                    $bodyStream->rewind();
                } else {
                    $response = $response->withBody($this->streamFactory->createStream($body));
                }

                $maxAge = $this->getMaxAge($response);
                $data = [
                    'response' => $response,
                    'body' => $body,
                    'expiresAt' => $this->calculateResponseExpiresAt($maxAge),
                    'createdAt' => time(),
                    'etag' => $response->getHeader('ETag'),
                ];
                $ttl = $this->calculateCacheItemExpiresAfter($maxAge, $request);
                $this->cache->set($key, $data, $ttl);
            }

            return $response;
        });
    }

    /**
     * Calculate the timestamp when this cache item should be dropped from the cache. The lowest value that can be
     * returned is $maxAge.
     *
     * @param $request $maxAge
     *
     * @return int|null Unix system time passed to cache
     */
    private function calculateCacheItemExpiresAfter($maxAge, RequestInterface $request)
    {
        if ($url = $request->getUri()->getPath()) {
            // List pull requests, Get data for a pull request.
            $pullRegex = sprintf('#repos/%s/%s/pulls#', self::GITHUB_NAME, self::GITHUB_NAME);
            // Get data for a git reference. Resolving a branch or tag to a commit
            $refRegex = sprintf('#repos/%s/%s/git/refs#', self::GITHUB_NAME, self::GITHUB_NAME);
            if (preg_match($pullRegex, $url) || preg_match($refRegex, $url)) {
                return self::SHORT_TTL + $maxAge;
            }
        }

        return $this->config['default_ttl'] + $maxAge;
    }

    /**
     * Calculate the timestamp when a response expires. After that timestamp, we need to send a
     * If-Modified-Since / If-None-Match request to validate the response.
     *
     * @param int|null $maxAge
     *
     * @return int|null Unix system time. A null value means that the response expires when the cache item expires
     */
    private function calculateResponseExpiresAt($maxAge)
    {
        if ($maxAge === null) {
            return;
        }

        return time() + $maxAge;
    }

    /**
     * Verify that we can cache this response.
     *
     * @param ResponseInterface $response
     *
     * @return bool
     */
    protected function isCacheable(ResponseInterface $response)
    {
        if (!in_array($response->getStatusCode(), [200, 203, 300, 301, 302, 404, 410])) {
            return false;
        }
        if (!$this->config['respect_cache_headers']) {
            return true;
        }
        if ($this->getCacheControlDirective($response, 'no-store') || $this->getCacheControlDirective($response, 'private')) {
            return false;
        }

        return true;
    }

    /**
     * Get the value of a parameter in the cache control header.
     *
     * @param ResponseInterface $response
     * @param string $name The field of Cache-Control to fetch
     *
     * @return bool|string The value of the directive, true if directive without value, false if directive not present
     */
    private function getCacheControlDirective(ResponseInterface $response, $name)
    {
        $headers = $response->getHeader('Cache-Control');
        foreach ($headers as $header) {
            if (preg_match(sprintf('|%s=?([0-9]+)?|i', $name), $header, $matches)) {

                // return the value for $name if it exists
                if (isset($matches[1])) {
                    return $matches[1];
                }

                return true;
            }
        }

        return false;
    }

    /**
     * @param RequestInterface $request
     *
     * @return string
     */
    private function createCacheKey(RequestInterface $request)
    {
        return hash($this->config['hash_algo'], $request->getMethod() . ' ' . $request->getUri());
    }

    /**
     * Get a ttl in seconds. It could return null if we do not respect cache headers and got no defaultTtl.
     *
     * @param ResponseInterface $response
     *
     * @return int|null
     */
    private function getMaxAge(ResponseInterface $response)
    {
        if (!$this->config['respect_cache_headers']) {
            return $this->config['default_ttl'];
        }

        // check for max age in the Cache-Control header
        $maxAge = $this->getCacheControlDirective($response, 'max-age');
        if (!is_bool($maxAge)) {
            $ageHeaders = $response->getHeader('Age');
            foreach ($ageHeaders as $age) {
                return $maxAge - ((int)$age);
            }

            return (int)$maxAge;
        }

        // check for ttl in the Expires header
        $headers = $response->getHeader('Expires');
        foreach ($headers as $header) {
            return (new \DateTime($header))->getTimestamp() - (new \DateTime())->getTimestamp();
        }

        return $this->config['default_ttl'];
    }

    /**
     * Configure an options resolver.
     *
     * @param OptionsResolver $resolver
     */
    private function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'cache_lifetime' => 86400 * 30, // 30 days
            'default_ttl' => self::DEFAULT_TTL,
            'respect_cache_headers' => true,
            'hash_algo' => 'sha1',
        ]);

        $resolver->setAllowedTypes('cache_lifetime', ['int', 'null']);
        $resolver->setAllowedTypes('default_ttl', ['int', 'null']);
        $resolver->setAllowedTypes('respect_cache_headers', 'bool');
        $resolver->setAllowedValues('hash_algo', hash_algos());
    }

    /**
     * @param array $cacheItem
     *
     * @return ResponseInterface
     */
    private function createResponseFromCacheItem(array $cacheItem)
    {
        /** @var ResponseInterface $response */
        $response = $cacheItem['response'];
        $response = $response->withBody($this->streamFactory->createStream($cacheItem['body']));

        return $response;
    }

    /**
     * Get the value of the "If-Modified-Since" header.
     *
     * @param array $cacheItem
     *
     * @return string|null
     */
    private function getModifiedSinceHeaderValue(array $cacheItem)
    {
        // The isset() is to be removed in 2.0.
        if (!isset($cacheItem['createdAt'])) {
            return;
        }

        $modified = new \DateTime('@' . $cacheItem['createdAt']);
        $modified->setTimezone(new \DateTimeZone('GMT'));

        return sprintf('%s GMT', $modified->format('l, d-M-y H:i:s'));
    }

    /**
     * Get the ETag from the cached response.
     *
     * @param array $cacheItem
     *
     * @return string|null
     */
    private function getETag(array $cacheItem)
    {
        // The isset() is to be removed in 2.0.
        if (!isset($cacheItem['etag'])) {
            return;
        }

        if (!is_array($cacheItem['etag'])) {
            return $cacheItem['etag'];
        }

        foreach ($cacheItem['etag'] as $etag) {
            if (!empty($etag)) {
                return $etag;
            }
        }
    }
}
