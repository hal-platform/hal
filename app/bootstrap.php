<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Deadzone;

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
$isCli = php_sapi_name() === 'cli';
if (!class_exists('QL\Hal\CachedContainer') || $isCli) {
    $container = buildDi($root);

} else {
    $container = new CachedContainer;

    // This is WEIRD, but its the only way to force a refresh container in a specific mode
    // if ($container->getParameter('debug')) {
    //     $container = buildDi($root);
    // }
}

return $container;

function buildDi($root)
{
    $container = new ContainerBuilder;
    $builder = new YamlFileLoader($container, new FileLocator($root));
    $builder->load('app/config.yml');

    $container->setParameter('root', $root);
    $container->set('dic', $container);

    $container->compile();

    return $container;
}
