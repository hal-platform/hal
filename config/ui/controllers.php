<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use QL\Panthor\Twig\LazyTwig;

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
};

function twig($template) {
    return inline(LazyTwig::class)
        ->arg('$template', $template)
        ->autowire();
}
