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
            ->arg('$template', ref('home.twig'))
    ;

    $s
        ('home.twig', LazyTwig::class)
            ->arg('$template', 'home.twig')
    ;
};
