<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Application\Config;

use Doctrine\Common\Cache\ArrayCache;
use QL\Hal\Core\Listener\DoctrineChangeLogger;
use QL\Hal\Core\Listener\DoctrinePersistListener;
use QL\Hal\Core\Utility\DoctrinePredisCache;
use QL\MCP\Common\Time\Clock;
use QL\Panthor\Utility\Stringify;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

class HalCoreExtension extends Extension
{
    /**
     * Loads a specific configuration.
     *
     * @param array $configs An array of configuration values
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @throws \InvalidArgumentException When provided tag is not defined in this extension
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new HalCoreConfiguration();
        $configs = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../../vendor/ql/hal-core/configuration')
        );
        $loader->load('hal-core.yml');

        // Save database password from encrypted properties on Hal deployment
        if (false !== ($property = getenv('ENCRYPTED_HAL_DB_PASS'))) {
            $configs['database_connection']['password'] = $property;
        }

        $container->setParameter('doctrine.devmode', $configs['dev_mode']);
        $container->setParameter('doctrine.connection', $configs['database_connection']);
        $container->setParameter('doctrine.lvl2cache.enabled', $configs['cache']['lvl2_enabled']);
        $container->setParameter('doctrine.lvl2cache.ttl', $configs['cache']['lvl2_ttl']);
        $container->setParameter('doctrine.lvl2cache.lock', $configs['cache']['lvl2_lock']);

        $this->configureDynamicServices($configs, $container);
        $this->configureCache($configs, $container);

        //Attach configured event listeners
        foreach ($configs['event_listeners'] as $event => $listeners) {
            foreach ($listeners as $service) {
                $container->getDefinition('doctrine.em.events')->addMethodCall('addEventListener', [[$event], new Reference($service)]);
            }
        }
    }

    /**
     * Process cache configuration
     *
     * @param array $configs
     * @param ContainerBuilder $container
     */
    private function configureCache(array $configs, ContainerBuilder $container)
    {
        $cacheType = $configs['cache']['type'];

        if ($cacheType === 'redis') {
            $container->setDefinition('doctrine.cache', new Definition(DoctrinePredisCache::class, [
                    $configs['predis_service_id'],
                    $configs['cache']['lvl2_ttl']
            ]));
        } elseif ($cacheType === 'memory') {
            $container->setDefinition('doctrine.cache', new Definition(ArrayCache::class));
        } elseif ($cacheType === 'custom') {
            //they've already defined a cache so lets just call it that
            $container->setAlias('doctrine.cache', $configs['cache']['custom_cache_service_id']);
        }
    }

    private function configureDynamicServices(array $configs, ContainerBuilder $container)
    {
        /**
         * Use the applications supplied clock or make our own
         * Configuration defaults date_timezone to 'UTC'
         */
        if (isset($configs['clock']['service_id'])) {
            $clock = new Reference($configs['clock']['service_id']);
        } else {
            $clock = new Clock('now', $configs['clock']['date_timezone']);
        }

        /**
         * In the future these definitions would be set in hal-core.yml
         * and the extension would take the `clock` and `lazy_user_loader` config options
         * and modify the existing definitions
         */
        $listenerLogger = new Definition(DoctrineChangeLogger::class, [
            $clock,
            $container->get('doctrine.random'),
            isset($configs['lazy_user_loader']) ? new Reference($configs['lazy_user_loader']): null
        ]);
        $persisterListener = new Definition(DoctrinePersistListener::class, [$clock]);

        /**
         * proxy directory
         */
        $proxyDir = new Definition('stdClass', []);
        $proxyDir->setFactory([Stringify::class, 'template'])
            ->addArgument('%%s/%%s')
            ->addArgument([new Reference('root'), $configs['proxy_dir']]);

        $container->setDefinition('doctrine.listener.logger', $listenerLogger);
        $container->setDefinition('doctrine.listener.persist', $persisterListener);
        $container->setDefinition('doctrine.proxy.dir', $proxyDir);
    }

    public function getAlias()
    {
        return 'hal_core';
    }
}
