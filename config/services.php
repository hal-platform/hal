<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Hal\UI\Service\JobEventsService;
use Hal\UI\Service\JobQueueService;
use Hal\UI\Service\StickyEnvironmentService;
use Hal\UI\System\GlobalBannerService;
use QL\MCP\Common\Clock;
use QL\Panthor\HTTPProblem\ProblemRendererInterface;
use QL\Panthor\Twig\Context;
use QL\Panthor\Utility\JSON;
use QL\Panthor\Utility\URI;
use Twig\Environment;

return function (ContainerConfigurator $container) {
    $s = $container->services();

    $s
        ->defaults()
            ->autowire()
    ;

    $s
        ->set(GlobalBannerService::class)
        ->set(StickyEnvironmentService::class)
            ->arg('$cookies', ref('cookie.handler'))
            ->arg('$json', ref('json'))
            ->arg('$preferencesExpiry', '%cookie.preferences.ttl%')
        ->set(JobEventsService::class)
        ->set(JobQueueService::class)
    ;

    $s
        ->alias('notFoundHandler',   'panthor.handler.notFoundHandler')->public()
        ->alias('notAllowedHandler', 'panthor.handler.notAllowedHandler')->public()
        ->alias('phpErrorHandler',   'panthor.handler.phpErrorHandler')->public()
        ->alias('errorHandler',      'panthor.handler.errorHandler')->public()
    ;

    // Panthor services
    $s
        ->alias(Clock::class, 'clock')
        ->alias(JSON::class, 'json')
        ->alias(URI::class, 'uri')
        ->alias(ProblemRendererInterface::class, 'problem.renderer')

        ->alias(Environment::class, ref('twig.environment'))
        ->alias(Context::class, ref('twig.context'))
    ;
};
