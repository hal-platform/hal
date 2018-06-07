<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return function (ContainerConfigurator $container) {
    $s = $container->services();

    $s
        ->defaults()
            ->autowire()
    ;

    $s
        ('index.api', \Hal\UI\Controllers\API\IndexController::class)
        ('docs.api',  \Hal\UI\Controllers\API\DocsRedirectController::class)

        ('queue.api',         \Hal\UI\Controllers\API\Queue\QueueController::class)
        ('queue.refresh.api', \Hal\UI\Controllers\API\Queue\QueueRefreshController::class)
        ('queue.history.api', \Hal\UI\Controllers\API\Queue\QueueHistoryController::class)
            ->arg('$timezone', '%date.timezone%')

        ('organization.api',  \Hal\UI\Controllers\API\Organization\OrganizationController::class)
        ('organizations.api', \Hal\UI\Controllers\API\Organization\OrganizationsController::class)

        ('application.api',  \Hal\UI\Controllers\API\Application\ApplicationController::class)
        ('applications.api', \Hal\UI\Controllers\API\Application\ApplicationsController::class)

        ('template.api',  \Hal\UI\Controllers\API\Template\TemplateController::class)
        ('templates.api', \Hal\UI\Controllers\API\Template\TemplatesController::class)

        ('target.api',                 \Hal\UI\Controllers\API\Target\TargetController::class)
        ('targets.api',                \Hal\UI\Controllers\API\Target\TargetsController::class)
        ('target.history.api',         \Hal\UI\Controllers\API\Target\HistoryController::class)
        ('target.current_release.api', \Hal\UI\Controllers\API\Target\CurrentReleaseController::class)

        ('build.api',          \Hal\UI\Controllers\API\Build\BuildController::class)
        ('builds.api',         \Hal\UI\Controllers\API\Build\BuildsController::class)
        ('builds.history.api', \Hal\UI\Controllers\API\Queue\BuildsHistoryController::class)
        ('build.event.api',    \Hal\UI\Controllers\API\Build\EventsController::class)

        ('release.api',          \Hal\UI\Controllers\API\Release\ReleaseController::class)
        ('releases.api',         \Hal\UI\Controllers\API\Release\ReleasesController::class)
        ('releases.history.api', \Hal\UI\Controllers\API\Queue\ReleasesHistoryController::class)
        ('release.event.api',    \Hal\UI\Controllers\API\Release\EventsController::class)

        ('event.api',    \Hal\UI\Controllers\API\EventController::class)

        ('environment.api',  \Hal\UI\Controllers\API\Environment\EnvironmentController::class)
        ('environments.api', \Hal\UI\Controllers\API\Environment\EnvironmentsController::class)

        ('vcs_provider.api',  \Hal\UI\Controllers\API\VersionControl\VersionControlProviderController::class)
        ('vcs_providers.api', \Hal\UI\Controllers\API\VersionControl\VersionControlProvidersController::class)

        ('user.api',  \Hal\UI\Controllers\API\User\UserController::class)
        ('users.api', \Hal\UI\Controllers\API\User\UsersController::class)
    ;

};
