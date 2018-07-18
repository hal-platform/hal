<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use function Hal\UI\twig;

return function (ContainerConfigurator $container) {
    $s = $container->services();

    $s
        ->defaults()
            ->autowire()
    ;

    $s
        ('queue.page', \Hal\UI\Controllers\Queue\QueueController::class)
            ->arg('$template', twig('queue/queue.twig'))
        ('queue.history.page', \Hal\UI\Controllers\Queue\QueueHistoryController::class)
            ->arg('$template', twig('queue/queue_history.twig'))
            ->arg('$timezone', '%date.timezone%')

        ('builds.history.page', \Hal\UI\Controllers\Queue\BuildsHistoryController::class)
            ->arg('$template', twig('queue/builds_history.twig'))
        ('builds.history.page', \Hal\UI\Controllers\Queue\ReleasesHistoryController::class)
            ->arg('$template', twig('queue/releases_history.twig'))
    ;

    $s
        ('builds.page', \Hal\UI\Controllers\Build\BuildsController::class)
            ->arg('$template', twig('build/builds.twig'))
        ('build.page', \Hal\UI\Controllers\Build\BuildController::class)
            ->arg('$template', twig('build/build.twig'))

        ('build.start.page', \Hal\UI\Controllers\Build\StartBuildController::class)
            ->arg('$template', twig('build/start_build.twig'))
        ('build.start.middleware', \Hal\UI\Controllers\Build\StartBuildMiddleware::class)
    ;

    $s
        ('releases.page', \Hal\UI\Controllers\Release\ReleasesController::class)
            ->arg('$template', twig('build/releases.twig'))
        ('release.page', \Hal\UI\Controllers\Release\ReleaseController::class)
            ->arg('$template', twig('release/release.twig'))

        ('release.start.page', \Hal\UI\Controllers\Release\StartReleaseController::class)
            ->arg('$template', twig('release/start_release.twig'))
        ('deploy.middleware', \Hal\UI\Controllers\Release\DeployMiddleware::class)

        ('release.rollback.page', \Hal\UI\Controllers\Release\RollbackController::class)
            ->arg('$template', twig('release/rollback_release.twig'))
    ;
};
