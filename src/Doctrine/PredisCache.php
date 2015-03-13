<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Doctrine;

use Predis\Client as Predis;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;

class PredisCache extends CacheProvider
{
    const KEY = 'doctrine:%s';

    /**
     * @type Predis
     */
    private $redis;

    /**
     * @type int
     */
    private $defaultTTL;

    /**
     * @param Predis $redis
     * @param int $defaultTTL
     */
    public function __construct(Predis $redis, $defaultTTL)
    {
        $this->redis = $redis;
        $this->defaultTTL = (int) $defaultTTL;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($id)
    {
        $key = sprintf(self::KEY, $id);

        if (!$cached = $this->redis->get($key)) {
            return false;
        }

        return unserialize($cached);
    }

    /**
     * {@inheritdoc}
     */
    protected function doContains($id)
    {
        $key = sprintf(self::KEY, $id);

        return $this->redis->exists($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        $key = sprintf(self::KEY, $id);

        $ttl = ($lifeTime > 0) ? $lifeTime : $this->defaultTTL;
        $serialized = serialize($data);

        $response = $this->redis->setex($key, $ttl, $serialized);
        return ('OK' === (string) $response);
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id)
    {
        $key = sprintf(self::KEY, $id);

        $response = $this->redis->del($key);
        return ($response > 0);
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        $key = sprintf(self::KEY, '*');

        if (!$keys = $this->redis->keys($key)) {
            return false;
        }

        $response = call_user_func_array([$this->redis, 'del'], $keys);
        return ($response > 0);
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetStats()
    {
        $info = $this->redis->info();

        return [
            Cache::STATS_HITS   => false,
            Cache::STATS_MISSES => false,
            Cache::STATS_UPTIME => $info['uptime_in_seconds'],
            Cache::STATS_MEMORY_USAGE      => $info['used_memory'],
            Cache::STATS_MEMORY_AVAILABLE  => false
        ];
    }
}
