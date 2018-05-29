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
        ->set('cache.redis.namespace', 'halcache')
        ->set('redis.options', ['prefix' => '%redis.prefix%'])
    ;

    $s
        ->set('cache', CacheInterface::class)
            ->factory([ref('service_container'), 'get'])
            ->arg('$id', 'cache.%cache.type.main%')

        ->set('cache.memory', ArrayCache::class)
            ->call('setLogger', [ref('cache.blackhole_logger')])
            ->public()

        ->set('cache.redis', RedisCache::class)
            ->arg('$redisClient', ref(Client::class))
            ->arg('$namespace', '%cache.redis.namespace%')
            ->call('setLogger', [ref('cache.blackhole_logger')])
            ->public()

        ->set(Client::class)
            ->arg('$parameters', '%redis.server%')
            ->arg('$options', '%redis.options%')
    ;

    // Symfony cache blackhole
    $s
        ->set('cache.blackhole_logger', NullLogger::class)
    ;

    // Database caching
    $s
        ->set('doctrine.cache.redis', RedisCache::class)
            ->arg('$redisClient', ref(Client::class))
            ->call('setLogger', [ref('cache.blackhole_logger')])
            ->public()
    ;
};
