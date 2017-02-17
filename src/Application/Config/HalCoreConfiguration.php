<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Application\Config;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class HalCoreConfiguration implements ConfigurationInterface
{

    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('hal_core');

        $rootNode
            ->validate()
                    ->ifTrue(function ($config) {
                        return (isset($config['cache']) && isset($config['cache']['type']));
                    })->then(function ($config) {
                        $type = $config['cache']['type'];

                        if ($type === 'predis' && !array_key_exists('predis_service_id', $config['cache'])) {
                            throw new InvalidConfigurationException('Hal Core predis cache type selected but the `predis_service_id` was not set');
                        } elseif ($type === 'custom' && !array_key_exists('custom_service_id', $config['cache'])) {
                            throw new InvalidConfigurationException('Hal Core custom cache type selected but the `custom_service_id` was not set');
                        }

                        return $config;
                    })
            ->end()
            ->children()
                ->booleanNode('dev_mode')->defaultValue(false)->end()
                ->scalarNode('lazy_user_loader')->end()
                ->scalarNode('proxy_dir')->isRequired()->cannotBeEmpty()->end()
                ->arrayNode('database_connection')
                    ->isRequired()
                    ->children()
                        ->scalarNode('driver')->isRequired()->end()
                        ->scalarNode('user')->isRequired()->end()
                        ->scalarNode('password')->isRequired()->end()
                        ->scalarNode('host')->isRequired()->end()
                        ->scalarNode('port')->isRequired()->end()
                        ->scalarNode('dbname')->isRequired()->end()
                    ->end()
                ->end()
                ->arrayNode('clock')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('service_id')->end()
                        ->scalarNode('date_timezone')->defaultValue('UTC')->end()
                    ->end()
                ->end()
                ->arrayNode('cache')
                    ->isRequired()
                    ->children()
                        ->enumNode('type')->values(['memory', 'predis', 'custom'])->isRequired()->end()
                        ->scalarNode('predis_service_id')->end()
                        ->scalarNode('custom_service_id')->end()
                        ->booleanNode('lvl2_enabled')->defaultValue(true)->end()
                        ->integerNode('lvl2_ttl')->defaultValue(600)->end()
                        ->integerNode('lvl2_lock')->defaultValue(60)->end()
                    ->end()
                ->end()
                ->arrayNode('event_listeners')
                    ->prototype('array')
                        ->prototype('scalar')
                    ->end()
                ->end()
            ->end();


        return $treeBuilder;
    }
}
