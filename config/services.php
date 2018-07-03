<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Hal\UI\Service\JobEventsService;
use Hal\UI\Service\JobQueueService;
use Hal\UI\Service\StickyEnvironmentService;
use Hal\UI\System\GlobalBannerService;
use QL\Panthor\HTTPProblem\ProblemRendererInterface;

return function (ContainerConfigurator $container) {
    $s = $container->services();

    $s
        ->defaults()
            ->autowire()
    ;

    $s
        ->set(GlobalBannerService::class)
        ->set(StickyEnvironmentService::class)
            ->arg('$preferencesExpiry', '%cookie.preferences.ttl%')
        ->set(JobEventsService::class)
        ->set(JobQueueService::class)
    ;

    // Panthor services
    $s
        ->alias(ProblemRendererInterface::class, 'problem.renderer')
    ;
};
