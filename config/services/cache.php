<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Predis\Client;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Simple\ArrayCache;
use Symfony\Component\Cache\Simple\RedisCache;

return function (ContainerConfigurator $container) {
    $s = $container->services();
    $p = $container->parameters();

    $p
        ('cache.redis.namespace', 'halcache')
        ('redis.options', ['prefix' => '%redis.prefix%'])
    ;

    $s
        ('cache', CacheInterface::class)
            ->factory([ref('service_container'), 'get'])
            ->arg('$id', 'cache.%cache.type.main%')

        ('cache.memory', ArrayCache::class)
            ->call('setLogger', [ref('cache.blackhole_logger')])
            ->public()

        ('cache.redis', RedisCache::class)
            ->arg('$redisClient', ref(Client::class))
            ->arg('$namespace', '%cache.redis.namespace%')
            ->call('setLogger', [ref('cache.blackhole_logger')])
            ->public()

        (Client::class)
            ->arg('$parameters', '%redis.server%')
            ->arg('$options', '%redis.options%')
    ;

    // Symfony cache blackhole
    $s
        ('cache.blackhole_logger', NullLogger::class)
    ;

    // Database caching
    $s
        ('doctrine.cache.redis', RedisCache::class)
            ->arg('$redisClient', ref(Client::class))
            ->call('setLogger', [ref('cache.blackhole_logger')])
            ->public()
    ;
};
