<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return function (ContainerConfigurator $container) {
    $s = $container->services();

    $s
        ->defaults()
            ->autowire()
    ;

    $s
        ('settings.fav_apps.add.iapi',    \Hal\UI\Controllers\User\FavoriteApplications\AddFavoriteApplicationHandler::class)
        ('settings.fav_apps.remove.iapi', \Hal\UI\Controllers\User\FavoriteApplications\RemoveFavoriteApplicationHandler::class)
    ;
};
