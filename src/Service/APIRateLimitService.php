<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Service;

use QL\Hal\Core\Entity\User;
use Predis\Client as Predis;

/**
 * Very simple rate-limiter using the "Rate limiter 1" pattern from the Redis docs:
 *
 * @see https://redis.io/commands/incr
 *
 * Each route may provide unique rate limiting settings for MAX REQUESTS per MINUTE.
 *
 * The default is 60, or 1 requests per second.
 *
 * Rate limits are PER USER x ROUTE.
 */
class APIRateLimitService
{
    private const RATE_LIMIT_KEY = 'api:rate-limit:%s.%s';

    private const DEFAULT_RATE_LIMIT_TIME = 120;

    /**
     * @var Predis
     */
    private $predis;

    /**
     * @var array
     */
    private $rateLimitTimes;

    /**
     * @param Predis $predis
     * @param array $rateLimitTimes
     */
    public function __construct(Predis $predis, array $rateLimitTimes = [])
    {
        $this->predis = $predis;
        $this->rateLimitTimes = $rateLimitTimes;
    }

    /**
     * @param User|null $user
     * @param string $routeName
     *
     * @return bool
     */
    public function isLimited(?User $user, string $routeName): bool
    {
        if (!$routeName) {
            return true;
        }

        $cacheKey = $this->cacheKey($user, $routeName);
        $limitTime = $this->cacheTime($routeName);

        $current = $this->predis->get($cacheKey);
        if (!$current) {
            return false;
        }

        if ($current > $limitTime) {
            return true;
        }

        return false;
    }

    /**
     * @param User|null $user
     * @param string $routeName
     *
     * @return void
     */
    public function increment(?User $user, string $routeName): void
    {
        if (!$routeName) {
            return;
        }

        $cacheKey = $this->cacheKey($user, $routeName);

        $this->predis->multi();
        $this->predis->incr($cacheKey);
        $this->predis->expire($cacheKey, 60);
        $this->predis->exec();
    }

    /**
     * Get the cache key for the rate limit time of a given request.
     *
     * @param User|null $user
     * @param string $routeName
     *
     * @return string
     */
    private function cacheKey($user, $routeName)
    {
        $username = $user ? $user->handle() : 'anonymous';

        return sprintf(self::RATE_LIMIT_KEY, $username, $routeName);
    }

    /**
     * Get the configured rate limit time for a given request.
     *
     * @param string $routeName
     *
     * @return int
     */
    private function cacheTime($routeName): int
    {
        if (isset($this->rateLimitTimes[$routeName])) {
            return (int) $this->rateLimitTimes[$routeName];
        }

        return self::DEFAULT_RATE_LIMIT_TIME;
    }
}
