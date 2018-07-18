<?php

namespace Hal\UI;

use QL\Panthor\Twig\LazyTwig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\inline;

function twig($template) {
    return inline(LazyTwig::class)
        ->arg('$template', $template)
        ->autowire();
}
