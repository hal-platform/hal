<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Github;

use Github\HttpClient\Cache\CacheInterface as GithubCacheInterface;
use Guzzle\Http\Message\Response;
use InvalidArgumentException;
use MCP\Cache\CacheInterface;

class MCPCache implements GithubCacheInterface
{
    const KEY_RESPONSE = 'github:%s';
    const KEY_ETAG = 'github:%s.etag';
    const KEY_MODIFIED = 'github:%s.modifiedsince';

    const GITHUB_NAME = '[A-Za-z0-9\_\.\-]+';

    const SHORT_TTL = 10;
    const DEFAULT_TTL = 60;

    /**
     * @type CacheInterface
     */
    private $cache;

    /**
     * @param CacheInterface $cache
     */
    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        $key = sprintf(self::KEY_RESPONSE, sha1($id));

        $cached = $this->cache->get($key);
        if ($cached !== null) {
            return $cached;
        }

        throw new InvalidArgumentException(sprintf('Response for URL "%s" not found', $id));
    }

    /**
     * {@inheritdoc}
     */
    public function getModifiedSince($id)
    {
        $key = sprintf(self::KEY_MODIFIED, sha1($id));

        return $this->cache->get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getETag($id)
    {
        $key = sprintf(self::KEY_ETAG, sha1($id));

        return $this->cache->get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function has($id)
    {
        $key = sprintf(self::KEY_ETAG, sha1($id));

        return ($this->cache->get($key) !== null);
    }

    /**
     * {@inheritdoc}
     */
    public function set($id, Response $response)
    {
        $key = sha1($id);

        $responseKey = sprintf(self::KEY_RESPONSE, $key);
        $etagKey = sprintf(self::KEY_ETAG, $key);
        $modifiedKey = sprintf(self::KEY_MODIFIED, $key);

        $ttl = $this->determineTTL($response);

        $this->cache->set($responseKey, $response, $ttl);
        $this->cache->set($etagKey, $response->getHeader('ETag'), $ttl);
        $this->cache->set($modifiedKey, time(), $ttl);
    }

    /**
     * @param string $id
     * @return int
     */
    private function determineTTL($id)
    {
        // List pull requests, Get data for a pull request.
        $pullRegex = sprintf('#repos/%s/%s/pulls#', self::GITHUB_NAME, self::GITHUB_NAME);

        // Get data for a git reference. Resolving a branch or tag to a commit
        $refRegex = sprintf('#repos/%s/%s/git/refs#', self::GITHUB_NAME, self::GITHUB_NAME);

        if (preg_match($pullRegex, $id) || preg_match($refRegex, $id)) {
            return self::SHORT_TTL;
        }

        return self::DEFAULT_TTL;
    }
}
