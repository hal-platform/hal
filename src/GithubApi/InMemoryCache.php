<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\GithubApi;

use Github\HttpClient\Cache\CacheInterface;
use Guzzle\Http\Message\Response;
use InvalidArgumentException;

class InMemoryCache implements CacheInterface
{
    /**
     * @var array
     */
    private $storage;

    public function __construct()
    {
        $this->storage = [];
    }

    /**
     * @param string $id
     * @return null|integer
     */
    public function getModifiedSince($id)
    {
        if ($this->has($id)) {
            return $this->storage[$this->buildKey($id)]['time'];
        }
    }

    /**
     * @param string $id
     * @return null|string
     */
    public function getETag($id)
    {
        $key = $this->buildKey($id);
        if (isset($this->storage[$key])) {
            return $this->storage[$key]['etag'];
        }
    }

    /**
     * @param string $id
     * @throws InvalidArgumentException If cache data don't exists
     * @return Response
     */
    public function get($id)
    {
        if ($this->has($id)) {
            $key = $this->buildKey($id);
            return $this->storage[$key]['response'];
        }

        throw new InvalidArgumentException('Cache item not found.');
    }

    /**
     * @param string $id
     * @return bool
     */
    public function has($id)
    {
        return isset($this->storage[$this->buildKey($id)]);
    }

    /**
     * @param string $id
     * @param Response $response
     * @return null
     */
    public function set($id, Response $response)
    {
        $key = $this->buildKey($id);
        $this->storage[$key] = [
            'etag' => $response->getHeader('ETag'),
            'response' => $response,
            'time' => time() // sorry
        ];
    }

    /**
     * @var string $id
     * @return string
     */
    private function buildKey($id)
    {
        return md5($id);
    }
}