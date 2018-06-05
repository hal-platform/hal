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
        ->set('middleware.session_options', [
            'lifetime' => '%session.lifetime%'
        ])
    ;

    $s
        ->set(GlobalMiddlewareLoader::class)
            ->arg('$di', ref('service_container'))
            ->arg('$middleware', '%global_middleware%')

        ->set(RouteLoader::class)
            ->parent('panthor.router.loader')
            ->call('addRoutes', ['%routes.api%'])
            ->call('addRoutes', ['%routes.api_internal%'])
            ->call('addRoutes', ['%routes.api_writes%'])

        ->set(LoggerGlobalMiddleware::class)
            ->arg('$factory', ref('mcp_logger_factory'))

        ->set(TemplateContextGlobalMiddleware::class)
            ->arg('$context', ref('twig.context'))

        ->set(FlashGlobalMiddleware::class)
            ->arg('$handler', ref('cookie.handler'))

        ->set(SessionMiddleware::class)
            ->arg('$handler', ref('cookie.handler'))
            ->arg('$options', '%middleware.session_options%')

        ->set(UserSessionGlobalMiddleware::class)
            ->arg('$userHandler', ref(UserSessionHandler::class))
            ->arg('$csrf', ref(CSRFManager::class))
            ->arg('$uri', ref('uri'))

        ->set(SrsBusinessGlobalMiddleware::class)
            ->arg('$cookies', ref('cookie.handler'))

        ->set(SystemSettingsGlobalMiddleware::class)
            ->arg('$bannerService', ref(GlobalBannerService::class))
    ;
};