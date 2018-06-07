<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Hal\UI\Service\JobEventsService;
use Hal\UI\Service\JobQueueService;
use Hal\UI\Service\StickyEnvironmentService;
use Hal\UI\System\GlobalBannerService;
use QL\MCP\Common\Clock;
use QL\Panthor\Utility\JSON;
use QL\Panthor\Utility\URI;
use QL\Panthor\HTTPProblem\ProblemRendererInterface;

return function (ContainerConfigurator $container) {
    $s = $container->services();

    $s
        ->alias('notFoundHandler',   'panthor.handler.notFoundHandler')->public()
        ->alias('notAllowedHandler', 'panthor.handler.notAllowedHandler')->public()
        ->alias('phpErrorHandler',   'panthor.handler.phpErrorHandler')->public()
        ->alias('errorHandler',      'panthor.handler.errorHandler')->public()
    ;

    $s
        ->set(GlobalBannerService::class)
            ->autowire()

        ->set(StickyEnvironmentService::class)
            ->arg('$cookies', ref('cookie.handler'))
            ->arg('$json', ref('json'))
            ->arg('$preferencesExpiry', '%cookie.preferences.ttl%')

        ->set(JobEventsService::class)
            ->autowire()

        ->set(JobQueueService::class)
            ->autowire()
    ;

    $s
        ->alias(Clock::class, 'clock')
        ->alias(JSON::class, 'json')
        ->alias(URI::class, 'uri')
        ->alias(ProblemRendererInterface::class, 'problem.renderer')
    ;
};
