<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Github\HttpClient\Builder;
use GuzzleHttp\Client;
use Hal\Core\Cache\NamespacedCache;
use Hal\Core\VersionControl\GitHub\GitHubAdapter;
use Hal\Core\VersionControl\GitHubEnterprise\GitHubEnterpriseAdapter;
use Hal\Core\VersionControl\VCSFactory;
use Hal\UI\VersionControl\BuildableRefs;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\SimpleCacheAdapter;

return function (ContainerConfigurator $container) {
    $s = $container->services();
    $p = $container->parameters();

    $p
        ('vcs.github_url', 'https://github.com')
        ('vcs.cache_type', 'memory')
        ('vcs.github_cache.namespace', 'github')
        ('vcs.github_cache.namespace_delimiter', '.')
        ('vcs.factory_adapters', [
            'gh' => ref(GitHubAdapter::class),
            'ghe' => ref(GitHubEnterpriseAdapter::Class)
        ])
    ;

    // Main clients
    $s
        (VCSFactory::class)
            ->arg('$adapters', '%vcs.factory_adapters%')

        (GitHubAdapter::class)
            ->arg('$httpClientBuilder', ref('github.http_builder'))
            ->arg('$githubBaseURL', '%vcs.github_url%')
            ->call('setCache', [ref('vcs.cache')])

        (GitHubEnterpriseAdapter::class)
            ->arg('$httpClientBuilder', ref('github_enterprise.http_builder'))
            ->call('setCache', [ref('vcs.cache')])
    ;

    // Caching
    $s
        ('vcs.cache', CacheInterface::class)
            ->factory([ref('service_container'), 'get'])
            ->arg('$id', 'cache.%vcs.cache_type%')

        ('github.cache', CacheInterface::class)
            ->factory([ref('service_container'), 'get'])
            ->arg('$id', 'cache.%cache.type.github%')

        ('github.cache_provider', SimpleCacheAdapter::class)
            ->arg('$pool', ref('github.cache_namespaced'))
            ->arg('$namespace', '')
            ->arg('$defaultLifetime', '%cache.github.default_ttl%')
            ->call('setLogger', [ref('github.cache.blackhole_logger')])

        ('github.cache_namespaced', NamespacedCache::class)
            ->arg('$cache', ref('github.cache'))
            ->arg('$namespace', '%vcs.github_cache.namespace%')
            ->arg('$delimiter', '%vcs.github_cache.namespace_delimiter%')

        ('github.cache.blackhole_logger', NullLogger::class)
    ;

    // GitHub SDK
    $s
        ('github.http_builder', Builder::class)
            ->call('addCache', [ref('github.cache_provider')])

        ('github_enterprise.http_builder', Builder::class)
            ->call('addCache', [ref('github.cache_provider')])
    ;

    // UI Only
    $s
        (BuildableRefs::class)
            ->autowire()
    ;
};
