<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Hal\UI\Middleware\FlashGlobalMiddleware;
use Hal\UI\Middleware\LoggerGlobalMiddleware;
use Hal\UI\Middleware\SrsBusinessGlobalMiddleware;
use Hal\UI\Middleware\SystemSettingsGlobalMiddleware;
use Hal\UI\Middleware\TemplateContextGlobalMiddleware;
use Hal\UI\Middleware\UserSessionGlobalMiddleware;
use Hal\UI\Security\CSRFManager;
use Hal\UI\Security\UserSessionHandler;
use Hal\UI\System\GlobalBannerService;
use QL\Panthor\Bootstrap\RouteLoader;
use QL\Panthor\Middleware\SessionMiddleware;
use QL\Panthor\Bootstrap\GlobalMiddlewareLoader;

return function (ContainerConfigurator $container) {
    $s = $container->services();
    $p = $container->parameters();

    $p
        ('middleware.session_options', [
            'lifetime' => '%session.lifetime%'
        ])
    ;

    $s
        ->defaults()
            ->autowire()
    ;

    $s
        (RouteLoader::class)
            ->arg('$routes', '%routes%')
            ->call('addRoutes', ['%routes.api%'])
            ->call('addRoutes', ['%routes.api_internal%'])
            ->call('addRoutes', ['%routes.api_writes%'])

        (LoggerGlobalMiddleware::class)
            ->arg('$factory', ref('mcp_logger_factory'))

        (TemplateContextGlobalMiddleware::class)
        (FlashGlobalMiddleware::class)
        (SessionMiddleware::class)
            ->arg('$options', '%middleware.session_options%')

        (UserSessionGlobalMiddleware::class)
        (SrsBusinessGlobalMiddleware::class)
        (SystemSettingsGlobalMiddleware::class)
    ;
};
