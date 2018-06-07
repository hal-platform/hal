<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return function (ContainerConfigurator $container) {
    $s = $container->services();

    $s
        ->defaults()
            ->autowire()
    ;

    $s
        ('build.start.api', \Hal\UI\Controllers\API\Build\StartBuildController::class)
        ('deploy.api',      \Hal\UI\Controllers\API\Release\DeployController::class)
    ;
};
