<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Hal\UI\Middleware\ACL\SignedInMiddleware;
use Hal\UI\Middleware\RequireEntityMiddleware;

return function (ContainerConfigurator $container) {
    $s = $container->services();

    $s
        ->alias('m.iapi.signed_in', SignedInMiddleware::class)
            ->public()

        ->alias('m.iapi.require_entity', RequireEntityMiddleware::class)
            ->public()
    ;
};
