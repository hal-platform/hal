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
        (GlobalMiddlewareLoader::class)
            ->arg('$di', ref('service_container'))
            ->arg('$middleware', '%global_middleware%')

        (RouteLoader::class)
            ->parent('panthor.router.loader')
            ->call('addRoutes', ['%routes.api%'])
            ->call('addRoutes', ['%routes.api_internal%'])
            ->call('addRoutes', ['%routes.api_writes%'])

        (LoggerGlobalMiddleware::class)
            ->arg('$factory', ref('mcp_logger_factory'))

        (TemplateContextGlobalMiddleware::class)
            ->arg('$context', ref('twig.context'))

        (FlashGlobalMiddleware::class)
            ->arg('$handler', ref('cookie.handler'))

        (SessionMiddleware::class)
            ->arg('$handler', ref('cookie.handler'))
            ->arg('$options', '%middleware.session_options%')

        (UserSessionGlobalMiddleware::class)
            ->autowire()

        (SrsBusinessGlobalMiddleware::class)
            ->arg('$cookies', ref('cookie.handler'))

        (SystemSettingsGlobalMiddleware::class)
            ->autowire()
    ;
};
