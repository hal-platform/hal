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
        ('admin.permissions.page', \Hal\UI\Controllers\Permissions\PermissionsController::class)
            ->arg('$template', twig('permissions/permissions.twig'))
        ('user_permissions.add.page', \Hal\UI\Controllers\Permissions\AddPermissionsController::class)
            ->arg('$template', twig('permissions/add_permission.twig'))
        ('user_permissions.remove.page', \Hal\UI\Controllers\Permissions\RemovePermissionsController::class)
            ->arg('$template', twig('permissions/remove_permission.twig'))

        ('application_permissions_add.page', \Hal\UI\Controllers\Permissions\AddEntityPermissionsController::class)
            ->arg('$template', twig('permissions/add_application_permissions.twig'))
        ('application_permissions_remove.page', \Hal\UI\Controllers\Permissions\RemoveEntityPermissionsController::class)
            ->arg('$template', twig('permissions/remove_application_permissions.twig'))

        ('organization_permissions_add.page', \Hal\UI\Controllers\Permissions\AddEntityPermissionsController::class)
            ->arg('$template', twig('permissions/add_organization_permissions.twig'))
        ('organization_permissions_remove.page', \Hal\UI\Controllers\Permissions\RemoveEntityPermissionsController::class)
            ->arg('$template', twig('permissions/remove_organization_permissions.twig'))
    ;

    $s
        ('hal_bootstrap.page', \Hal\UI\Controllers\Admin\HalBootstrapController::class)
            ->arg('$template', twig('admin/hal_bootstrap.twig'))
        ('admin.dashboard.page', \Hal\UI\Controllers\Admin\DashboardController::class)
            ->arg('$template', twig('admin/admin_dashboard.twig'))
        ('admin.audit_history.page', \Hal\UI\Controllers\Admin\AuditHistoryController::class)
            ->arg('$template', twig('admin/audit_history.twig'))
        ('admin.cache_management.page', \Hal\UI\Controllers\Admin\CacheManagementController::class)
            ->arg('$template', twig('admin/cache_management.twig'))
            ->arg('$root', '%root%')
            ->arg('$keyDelimiter', '%cache.delimiter%')
        ('admin.cache_management.handler', \Hal\UI\Controllers\Admin\CacheManagementHandler::class)
            ->arg('$keyDelimiter', '%cache.delimiter%')

        ('admin.global_banner.page', \Hal\UI\Controllers\Admin\GlobalBannerController::class)
            ->arg('$template', twig('admin/global_banner.twig'))
        ('admin.system_dashboard.page', \Hal\UI\Controllers\Admin\SystemDashboardController::class)
            ->arg('$template', twig('admin/system_dashboard.twig'))
    ;

    $s
        ('environments.page', \Hal\UI\Controllers\Admin\Environment\EnvironmentsController::class)
            ->arg('$template', twig('environment/environments.twig'))
        ('environment.page', \Hal\UI\Controllers\Admin\Environment\EnvironmentController::class)
            ->arg('$template', twig('environment/environment.twig'))
        ('environment.add.page', \Hal\UI\Controllers\Admin\Environment\AddEnvironmentController::class)
            ->arg('$template', twig('environment/add_environment.twig'))
        ('environment.edit.page', \Hal\UI\Controllers\Admin\Environment\EditEnvironmentController::class)
            ->arg('$template', twig('environment/edit_environment.twig'))
        ('environment.remove.handler', \Hal\UI\Controllers\Admin\Environment\RemoveEnvironmentHandler::class)
    ;

    $s
        ('vcs_providers.page', \Hal\UI\Controllers\Admin\VCS\VersionControlProvidersController::class)
            ->arg('$template', twig('vcs/vcs_providers.twig'))
        ('vcs_providers.add.page', \Hal\UI\Controllers\Admin\VCS\AddVersionControlController::class)
            ->arg('$template', twig('vcs/add_vcs.twig'))

        ('vcs_provider.page', \Hal\UI\Controllers\Admin\VCS\VersionControlController::class)
            ->arg('$template', twig('vcs/vcs_provider.twig'))
        ('vcs_provider.edit.page', \Hal\UI\Controllers\Admin\VCS\EditVersionControlController::class)
            ->arg('$template', twig('vcs/edit_vcs.twig'))
        ('vcs_provider.remove.handler', \Hal\UI\Controllers\Admin\VCS\RemoveVersionControlHandler::class)
    ;

    $s
        ('id_providers.page', \Hal\UI\Controllers\Admin\IDP\IdentityProvidersController::class)
            ->arg('$template', twig('idp/id_providers.twig'))
        ('id_providers.add.page', \Hal\UI\Controllers\Admin\IDP\AddIdentityProviderController::class)
            ->arg('$template', twig('idp/add_idp.twig'))

        ('id_provider.page', \Hal\UI\Controllers\Admin\IDP\IdentityProviderController::class)
            ->arg('$template', twig('idp/id_provider.twig'))
        ('id_provider.edit.page', \Hal\UI\Controllers\Admin\IDP\EditIdentityProviderController::class)
            ->arg('$template', twig('idp/edit_idp.twig'))
        ('id_provider.remove.handler', \Hal\UI\Controllers\Admin\IDP\RemoveIdentityProviderHandler::class)
    ;

    $s
        ('credentials.page', \Hal\UI\Controllers\Credentials\CredentialsController::class)
            ->arg('$template', twig('credentials/credentials.twig'))
        ('credentials.add.page', \Hal\UI\Controllers\Credentials\AddCredentialController::class)
            ->arg('$template', twig('credentials/add_credential.twig'))

        ('credential.page', \Hal\UI\Controllers\Credentials\CredentialController::class)
            ->arg('$template', twig('credentials/credential.twig'))
        ('credential.edit.page', \Hal\UI\Controllers\Credentials\EditCredentialController::class)
            ->arg('$template', twig('credentials/edit_credential.twig'))
        ('credential.remove.handler', \Hal\UI\Controllers\Credentials\RemoveCredentialHandler::class)
    ;

    $s
        ('templates.page', \Hal\UI\Controllers\TargetTemplate\TemplatesController::class)
            ->arg('$template', twig('target-template/templates.twig'))
        ('templates.add.page', \Hal\UI\Controllers\TargetTemplate\AddTemplateController::class)
            ->arg('$template', twig('target-template/add_template.twig'))

        ('template.page', \Hal\UI\Controllers\TargetTemplate\TemplateController::class)
            ->arg('$template', twig('target-template/template.twig'))
        ('template.edit.page', \Hal\UI\Controllers\TargetTemplate\EditTemplateController::class)
            ->arg('$template', twig('target-template/edit_template.twig'))
        ('template.remove.handler', \Hal\UI\Controllers\TargetTemplate\RemoveTemplateHandler::class)
    ;
};
