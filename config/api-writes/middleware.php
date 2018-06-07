<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Hal\UI\Middleware\ACL\TokenMiddleware;
use Hal\UI\Middleware\APICrossOriginMiddleware;
use Hal\UI\Middleware\APIRateLimitingMiddleware;
use Hal\UI\Middleware\RequireEntityMiddleware;

return function (ContainerConfigurator $container) {
    $s = $container->services();

    $s
        ->set(TokenMiddleware::class)
            ->call('setLoggerMessageFactory', [ref('mcp_logger_factory')])
            ->autowire()
        ->set(APIRateLimitingMiddleware::class)
            ->autowire()
    ;

    $s
        ->set(Settings::class)
            ->call('setRequestCredentialsSupported', [true])
            ->call('setRequestAllowedHeaders', ['%api.cors.allowed_headers%'])
            ->call('setRequestAllowedOrigins', ['%api.cors.allowed_origins%'])
    ;

    $s
        ->alias('m.api_rw.require_auth', TokenMiddleware::class)->public()
        ->alias('m.api_rw.rate_limiter', APIRateLimitingMiddleware::class)->public()
        ->alias('m.api_rw.require_entity', RequireEntityMiddleware::class)->public()
        ->alias('m.api_rw.cors', APICrossOriginMiddleware::class)->public()
    ;
};
