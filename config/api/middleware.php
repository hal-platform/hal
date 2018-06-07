<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Hal\UI\Middleware\APICachingMiddleware;
use Hal\UI\Middleware\APICrossOriginMiddleware;
use Hal\UI\Middleware\RequireEntityMiddleware;
use Neomerx\Cors\Strategies\Settings;

return function (ContainerConfigurator $container) {
    $s = $container->services();

    $s
        ->defaults()
            ->autowire()
    ;

    $s
        (APICachingMiddleware::class)
        (APICrossOriginMiddleware::class)
            ->arg('$corsSettings', ref(Settings::class))
    ;

    $s
        (Settings::class)
            ->call('setRequestCredentialsSupported', [true])
            ->call('setRequestAllowedHeaders', ['%api.cors.allowed_headers%'])
            ->call('setRequestAllowedOrigins', ['%api.cors.allowed_origins%'])
    ;

    $s
        ->alias('m.api.caching', APICachingMiddleware::class)
            ->public()

        ->alias('m.api.cors', APICrossOriginMiddleware::class)
            ->public()

        ->alias('m.api.require_entity', RequireEntityMiddleware::class)
            ->public()
    ;
};
