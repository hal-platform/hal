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
        ('organization.page', \Hal\UI\Controllers\Organization\OrganizationController::class)
            ->arg('$template', twig('organization/organization.twig'))

        ('organization.add.page', \Hal\UI\Controllers\Organization\AddOrganizationController::class)
            ->arg('$template', twig('organization/add_organization.twig'))
        ('organization.edit.page', \Hal\UI\Controllers\Organization\EditOrganizationController::class)
            ->arg('$template', twig('organization/edit_organization.twig'))
        ('organization.remove.handler', \Hal\UI\Controllers\Organization\RemoveOrganizationHandler::class)
    ;

    $s
        ('applications.page', \Hal\UI\Controllers\Application\ApplicationsController::class)
            ->arg('$template', twig('application/applications.twig'))
        ('applications.add.page', \Hal\UI\Controllers\Application\AddApplicationController::class)
            ->arg('$template', twig('application/add_application.twig'))

        ('application.page', \Hal\UI\Controllers\Application\ApplicationController::class)
            ->arg('$template', twig('application/application.twig'))
        ('application.edit.page', \Hal\UI\Controllers\Application\EditApplicationController::class)
            ->arg('$template', twig('application/edit_application.twig'))

        ('application.dashboard.page', \Hal\UI\Controllers\Application\ApplicationDashboardController::class)
            ->arg('$template', twig('application/dashboard.twig'))
        ('application.dashboard.sticky_env.middleware', \Hal\UI\Controllers\Application\DashboardStickyEnvironmentMiddleware::class)
        ('application.remove.handler', \Hal\UI\Controllers\Application\RemoveApplicationHandler::class)

    ;

    $s
        ('encrypted.configuration.page', \Hal\UI\Controllers\EncryptedConfiguration\EncryptedConfigurationController::class)
            ->arg('$template', twig('encrypted-configuration/encrypted_configuration.twig'))
        ('encrypted.page', \Hal\UI\Controllers\EncryptedConfiguration\EncryptedController::class)
            ->arg('$template', twig('encrypted-configuration/encrypted_property.twig'))
        ('encrypted.add.page', \Hal\UI\Controllers\EncryptedConfiguration\AddEncryptedPropertyController::class)
            ->arg('$template', twig('encrypted-configuration/add_encrypted.twig'))
        ('encrypted.add.middleware', \Hal\UI\Controllers\EncryptedConfiguration\AddEncryptedPropertyMiddleware::class)
        ('encrypted.remove.handler', \Hal\UI\Controllers\EncryptedConfiguration\RemoveEncryptedPropertyHandler::class)
    ;

    $s
        ('targets.page', \Hal\UI\Controllers\Target\TargetsController::class)
            ->arg('$template', twig('target/targets.twig'))
        ('targets.add.page', \Hal\UI\Controllers\Target\AddTargetController::class)
            ->arg('$template', twig('target/add_target.twig'))
        ('targets.add.middleware', \Hal\UI\Controllers\Target\AddTargetMiddleware::class)

        ('target.page', \Hal\UI\Controllers\Target\TargetController::class)
            ->arg('$template', twig('target/target.twig'))
        ('target.edit.page', \Hal\UI\Controllers\Target\EditTargetController::class)
            ->arg('$template', twig('application/edit_target.twig'))
        ('target.edit.middleware', \Hal\UI\Controllers\Target\EditTargetMiddleware::class)

        ('target.remove.handler', \Hal\UI\Controllers\Target\RemoveTargetHandler::class)
    ;
};
