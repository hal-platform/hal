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
        ('home.page', \Hal\UI\Controllers\HomeController::class)
            ->arg('$template', twig('home.twig'))

        ('maintenance.page', \Hal\UI\Controllers\StaticController::class)
            ->arg('$template', twig('error.maintenance.twig'))
        ('denied.page', \Hal\UI\Controllers\StaticController::class)
            ->arg('$template', twig('error.denied.twig'))
    ;

    $s
        ('latest_release.page', \Hal\UI\Controllers\StaticController::class)
            ->arg('$template', twig('latest_release.twig'))
        ('styleguide.page', \Hal\UI\Controllers\StaticController::class)
            ->arg('$template', twig('styleguide.html.twig'))
        ('styleguide.icons.page', \Hal\UI\Controllers\StaticController::class)
            ->arg('$template', twig('styleguide.icons.html.twig'))

        ('help.page', \Hal\UI\Controllers\StaticController::class)
            ->arg('$template', twig('help/help.twig'))
        ('help.scripting.page', \Hal\UI\Controllers\StaticController::class)
            ->arg('$template', twig('help/scripting.twig'))
        ('help.application_setup.page', \Hal\UI\Controllers\StaticController::class)
            ->arg('$template', twig('help/application_setup.twig'))
    ;

    $s
        ('signin.page', \Hal\UI\Controllers\Auth\SignInController::class)
            ->arg('$template', twig('auth/signin.twig'))

        ('signin.middleware',      \Hal\UI\Controllers\Auth\SignInHandler::class)
        ('signout.handler',        \Hal\UI\Controllers\Auth\SignOutHandler::class)
        ('signout.oauth2_handler', \Hal\UI\Controllers\Auth\SignInCallbackHandler::class)

        ('signin.setup.page', \Hal\UI\Controllers\Auth\SignInSetupController::class)
            ->arg('$template', twig('auth/signin_setup.twig'))
    ;

    $s
        ('user.page', \Hal\UI\Controllers\User\UserController::class)
            ->arg('$template', twig('user/user.twig'))
        ('users.page', \Hal\UI\Controllers\User\UsersController::class)
            ->arg('$template', twig('user/users.twig'))
        ('user.add.page', \Hal\UI\Controllers\User\AddUserController::class)
            ->arg('$template', twig('user/add_user.twig'))
        ('user.edit.page', \Hal\UI\Controllers\User\EditUserController::class)
            ->arg('$template', twig('user/edit_user.twig'))
        ('user.disable.handler', \Hal\UI\Controllers\User\DisableUserHandler::class)
        ('user.regenerate_setup.handler', \Hal\UI\Controllers\User\RegenerateSetupTokenHandler::class)

        ('user.settings.page', \Hal\UI\Controllers\User\SettingsController::class)
            ->arg('$template', twig('user/settings.twig'))
        ('user.settings.middleware', \Hal\UI\Controllers\User\SettingsMiddleware::class)
            ->arg('$preferencesExpiry', '%cookie.preferences.ttl%')

        ('user.token.add.handler', \Hal\UI\Controllers\User\Token\AddTokenHandler::class)
        ('user.token.remove.handler', \Hal\UI\Controllers\User\Token\RemoveTokenHandler::class)

    ;
};
