<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\Service\JobEventsService;
use Hal\UI\Service\JobQueueService;
use Hal\UI\Service\StickyEnvironmentService;
use Hal\UI\System\GlobalBannerService;
use Predis\Client;

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
            ->arg('$em', ref(EntityManagerInterface::class))
            ->arg('$clock', ref('clock'))
            ->arg('$json', ref('json'))

        ->set(StickyEnvironmentService::class)
            ->arg('$cookies', ref('cookie.handler'))
            ->arg('$json', ref('json'))
            ->arg('$preferencesExpiry', '%cookie.preferences.ttl%')

        ->set(JobEventsService::class)
            ->arg('$em', ref(EntityManagerInterface::class))
            ->arg('$predis', ref(Client::class))
            ->arg('$json', ref('json'))
            ->arg('$clock', ref('clock'))

        ->set(JobQueueService::class)
            ->arg('$em', ref(EntityManagerInterface::class))
            ->arg('$clock', ref('clock'))
    ;
};
