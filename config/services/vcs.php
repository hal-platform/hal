<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Github\HttpClient\Builder;
use GuzzleHttp\Client;
use Hal\Core\VersionControl\Downloader\GitHubDownloader;
use Hal\Core\VersionControl\VCS;
use Hal\Core\VersionControl\VCS\GitHubEnterpriseVCS;
use Hal\Core\VersionControl\VCS\GitHubVCS;
use Hal\UI\VersionControl\BuildableRefs;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\SimpleCacheAdapter;
use Symfony\Component\Cache\Simple\ArrayCache;
use Symfony\Component\Cache\Simple\RedisCache;

return function (ContainerConfigurator $container) {
    $s = $container->services();
    $p = $container->parameters();

    $p
        ->set('vcs.github_url', 'https://github.com')
        ->set('vcs.cache_type', 'memory')
        ->set('vcs.github_cache_namespace', 'github')
    ;

    // Main clients
    $s
        ->set(VCS::class)
            ->arg('$adapters', [
                'gh' => ref(GitHubVCS::class),
                'ghe' => ref(GitHubEnterpriseVCS::Class)
            ])

        ->set(GitHubVCS::class)
            ->arg('$httpClientBuilder', ref('github.http_builder'))
            ->arg('$githubBaseURL', '%vcs.github_url%')
            ->call('setCache', [ref('vcs.cache')])

        ->set(GitHubEnterpriseVCS::class)
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
            ->arg('$pool', ref('github.cache'))
            ->arg('$namespace', '%vcs.github_cache_namespace%')
            ->arg('$defaultLifetime', '%cache.github.default_ttl%')
            ->call('setLogger', [ref('github.cache.blackhole_logger')])

        ->set('github.cache.blackhole_logger', NullLogger::class)
    ;

    // Downloading
    $s
        ->set(GitHubDownloader::class)
            ->arg('$guzzle', ref('github.downloader_guzzle'))

        ->set('github.downloader_guzzle', Client::class)
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
        ->arg('$vcs', ref(VCS::class))
    ;
};
