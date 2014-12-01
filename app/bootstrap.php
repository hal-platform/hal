<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Bootstrap;

use QL\Hal\CachedContainer;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

$root = __DIR__ . '/../';
require_once $root . '/vendor/autoload.php';

// Set Timezone to UTC
ini_set('date.timezone', 'UTC');
date_default_timezone_set('UTC');

// DI Container
if (!class_exists('QL\Hal\CachedContainer') || (php_sapi_name() === 'cli')) {
    $container = buildDi($root);

} else {
    $container = new CachedContainer;

    // This is WEIRD, but its the only way to force a fresh container in a specific mode
    if ($container->getParameter('debug')) {
        $container = buildDi($root);
    }
}

// Set the synthetic root service. This must not ever be cached.
$container->set('root', $root);

return $container;

function buildDi($root)
{
    $container = new ContainerBuilder;
    $builder = new YamlFileLoader($container, new FileLocator($root));
    $builder->load('app/config.yml');

    // Overwrite sha param if env var is found. This will always be set for every script during the build process.
    if (array_key_exists('HAL_COMMIT', $_SERVER)) {
        $container->setParameter('application.sha', $_SERVER['HAL_COMMIT']);
    }

    // Overwrite the encryption secret if an env var is found.
    if (array_key_exists('HAL_ENCRYPTION_SECRET', $_SERVER)) {
        $container->setParameter('session.encryption.secret', $_SERVER['HAL_ENCRYPTION_SECRET']);
    }

    $container->compile();

    return $container;
}
