<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Application;

use QL\Panthor\Bootstrap\Di as PanthorDi;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class Di extends PanthorDi
{
    public static function buildDi($root, callable $preLoad = null, callable $postLoad = null)
    {
        $container = new ContainerBuilder;
        $yamlLoader = new YamlFileLoader($container, new FileLocator($root));

        if (is_callable($preLoad)) {
            $preLoad($container);
        }

        $yamlLoader->load(static::PRIMARY_CONFIGURATION_FILE);

        if (is_callable($postLoad)) {
            $postLoad($container);
        }

        $container->compile();

        return $container;
    }

    public static function getDi($root, $class, callable $preLoad = null, callable  $postLoad = null)
    {
        $root = rtrim($root, '/');

        if (class_exists($class)) {
            $container = new $class;

            // Force a fresh container in debug mode
            if (static::shouldRefreshContainer($container)) {
                $container = static::buildDi($root, $preLoad, $postLoad);
            }

        } else {
            $container = static::buildDi($root, $preLoad, $postLoad);
        }

        // Set the synthetic root service. This must not ever be cached.
        $container->set(static::KEY_ROOT_PATH, $root);

        return $container;
    }
}
