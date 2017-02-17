<?php
/**
 * @copyright (c) 2014 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\Bootstrap;

use Hal\UI\Application\Config\HalCoreExtension;
use Hal\UI\Application\Di;
use Hal\UI\CachedContainer;
use Symfony\Component\DependencyInjection\ContainerBuilder;

$root = __DIR__ . '/..';
require_once $root . '/vendor/autoload.php';

// Set Timezone to UTC
ini_set('date.timezone', 'UTC');
date_default_timezone_set('UTC');

$halCoreExtension = new HalCoreExtension();
$addHalCoreExtension = function (ContainerBuilder $container) use ($halCoreExtension) {
    $container->registerExtension($halCoreExtension);
};

$loadHalCoreExtension = function (ContainerBuilder $container) use ($halCoreExtension) {
    $container->loadFromExtension($halCoreExtension->getAlias());
};

$container = Di::getDi($root, CachedContainer::class, $addHalCoreExtension, $loadHalCoreExtension);

// Custom application logic here

return $container;
