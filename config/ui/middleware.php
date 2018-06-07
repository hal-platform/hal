<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Hal\UI\Middleware\ACL\AdminMiddleware;
use Hal\UI\Middleware\ACL\AdminOrSelfMiddleware;
use Hal\UI\Middleware\ACL\OwnerMiddleware;
use Hal\UI\Middleware\ACL\SignedInMiddleware;
use Hal\UI\Middleware\ACL\SuperMiddleware;
use Hal\UI\Middleware\CSRFMiddleware;
use Hal\UI\Middleware\NestedEntityMiddleware;
use Hal\UI\Middleware\RemovalConfirmationMiddleware;
use Hal\UI\Middleware\RequireEntityMiddleware;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Twig\LazyTwig;

return function (ContainerConfigurator $container) {
    $s = $container->services();

    $s
        ->defaults()
            ->autowire()
    ;

    $s
        (SignedInMiddleware::class)
        (AdminMiddleware::class)
            ->arg('$template', ref('middleware.permission_denied_template'))
        (SuperMiddleware::class)
            ->arg('$template', ref('middleware.permission_denied_template'))
        (OwnerMiddleware::class)
            ->arg('$template', ref('middleware.permission_denied_template'))
        (AdminOrSelfMiddleware::class)
            ->arg('$template', ref('middleware.permission_denied_template'))

        (CSRFMiddleware::class)

        (RequireEntityMiddleware::class)
            ->arg('$notFound', ref('notFoundHandler'))

        (NestedEntityMiddleware::class)
            ->arg('$notFound', ref('notFoundHandler'))
    ;

    $s
        ->alias('m.signed_in', SignedInMiddleware::class)->public()
        ->alias('m.is_admin', AdminMiddleware::class)->public()
        ->alias('m.is_super', SuperMiddleware::class)->public()
        ->alias('m.is_owner', OwnerMiddleware::class)->public()
        ->alias('m.is_admin_or_self', AdminOrSelfMiddleware::class)->public()

        ->alias('m.require_csrf', CSRFMiddleware::class)->public()
        ->alias('m.require_entity', RequireEntityMiddleware::class)->public()
        ->alias('m.nested_entity', NestedEntityMiddleware::class)->public()
    ;

    foreach (array_keys(RequireEntityMiddleware::KNOWN_ENTITIES) as $entity) {
        $s("m.confirm_remove.${entity}", RemovalConfirmationMiddleware::class)
            ->arg('$template', ref('middleware.remove_entity_template'))
            ->arg('$removeEntityType', $entity)
            ->public();
    }

    $s = $container->services();

    $s
        ->defaults()
            ->bind('$environment', ref('twig.environment'))
            ->bind('$context', ref('twig.context'))
            ->autowire()

        ('middleware.permission_denied_template', LazyTwig::class)
            ->arg('$template', 'error.denied.twig')

        ('middleware.remove_entity_template', LazyTwig::class)
            ->arg('$environment', ref('twig.environment'))
    ;
};
