<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Hal\UI\Twig\GitHubExtension;
use Hal\UI\Twig\HalExtension;
use Hal\UI\Twig\SecurityExtension;
use Hal\UI\Utility\TimeFormatter;
use Twig\Environment;

return function (ContainerConfigurator $container) {
    $s = $container->services();

    $s
        ->defaults()
            ->autowire()
    ;

    $s
        (HalExtension::class)
            ->arg('$gravatarFallbackImageURL', '%gravatar.fallback%')

        (GitHubExtension::class)
            ->call('setCache', [ref('vcs.cache')])

        (SecurityExtension::class)
    ;

    $s
        (TimeFormatter::class)
            ->arg('$timezone', '%date.timezone%')
    ;

    $s = $container->services();

    $s
        ('twig.environment', Environment::class)
            ->parent(ref('panthor.twig.environment'))
            ->call('addExtension', [ref(HalExtension::class)])
            ->call('addExtension', [ref(GitHubExtension::class)])
            ->call('addExtension', [ref(SecurityExtension::class)])
            ->call('addGlobal', ['application_title', '%application.title%'])
            ->call('addGlobal', ['application_environment', '%application.environment%'])
            ->call('addGlobal', ['application_sha', '%application.sha%'])
            ->call('addGlobal', ['application_version', '%application.version%'])
            ->call('addGlobal', ['hal_administrators_email', '%administrator_email%'])
    ;
};

