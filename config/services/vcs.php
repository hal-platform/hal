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
        ->set('vcs.github_url', 'https://github.com')
        ->set('vcs.cache_type', 'memory')
        ->set('vcs.github_cache.namespace', 'github')
        ->set('vcs.github_cache.namespace_delimiter', '.')
        ->set('vcs.factory_adapters', [
            'gh' => ref(GitHubAdapter::class),
            'ghe' => ref(GitHubEnterpriseAdapter::Class)
        ])
    ;

    // Main clients
    $s
        ->set(VCSFactory::class)
            ->arg('$adapters', '%vcs.factory_adapters%')

        ->set(GitHubAdapter::class)
            ->arg('$httpClientBuilder', ref('github.http_builder'))
            ->arg('$githubBaseURL', '%vcs.github_url%')
            ->call('setCache', [ref('vcs.cache')])

        ->set(GitHubEnterpriseAdapter::class)
            ->arg('$httpClientBuilder', ref('github_enterprise.http_builder'))
            ->call('setCache', [ref('vcs.cache')])
    ;

    // Caching
    $s
        ->set('vcs.cache', CacheInterface::class)
            ->factory([ref('service_container'), 'get'])
            ->arg('$id', 'cache.%vcs.cache_type%')

        ->set('github.cache', CacheInterface::class)
            ->factory([ref('service_container'), 'get'])
            ->arg('$id', 'cache.%cache.type.github%')

        ->set('github.cache_provider', SimpleCacheAdapter::class)
            ->arg('$pool', ref('github.cache_namespaced'))
            ->arg('$namespace', '')
            ->arg('$defaultLifetime', '%cache.github.default_ttl%')
            ->call('setLogger', [ref('github.cache.blackhole_logger')])

        ->set('github.cache_namespaced', NamespacedCache::class)
            ->arg('$cache', ref('github.cache'))
            ->arg('$namespace', '%vcs.github_cache.namespace%')
            ->arg('$delimiter', '%vcs.github_cache.namespace_delimiter%')

        ->set('github.cache.blackhole_logger', NullLogger::class)
    ;

    // GitHub SDK
    $s
        ->set('github.http_builder', Builder::class)
            ->call('addCache', [ref('github.cache_provider')])

        ->set('github_enterprise.http_builder', Builder::class)
            ->call('addCache', [ref('github.cache_provider')])
    ;

    // UI Only
    $s
        ->set(BuildableRefs::class)
            ->autowire()
    ;
};
