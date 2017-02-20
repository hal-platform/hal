<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Application\Config;

use Closure;
use QL\MCP\Logger\Service\ErrorLogService;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

class McpLoggerConfiguration implements ConfigurationInterface
{

    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('mcp_logger');

        $rootNode
            ->validate()
            ->ifTrue(function ($config) {
                return (isset($config['logging_service']) && $config['logging_service'] === 'custom');
            })->then(function ($config) {
                if (!isset($config['custom_service_id'])) {
                    throw new InvalidConfigurationException(
                        'Logging service `custom` was selected but `custom_service_id` was not set.'
                    );
                }
                return $config;
            })
            ->end()
            ->children()
                ->arrayNode('default_properties')
                    ->prototype('variable')->end()
                ->end()
                ->arrayNode('syslog_options')
                    ->children()
                        ->scalarNode('ident')->end()
                        ->scalarNode('facility')
                            ->validate()
                            ->ifString()->then($this->getConstantFromString())
                            ->end()
                        ->end()
                        ->booleanNode('silent')->end()
                        ->arrayNode('options')
                            ->prototype('scalar')
                                ->validate()
                                ->ifString()->then($this->getConstantFromString())
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('errorlog_options')
                    ->children()
                        ->scalarNode('type')
                            ->validate()
                            ->ifString()->then($this->validateErrorLogType())
                            ->end()
                        ->end()
                        ->scalarNode('destination')->end()
                    ->end()
                ->end()
                ->arrayNode('guzzle_options')
                    ->children()
                        ->arrayNode('client')
                            ->prototype('variable')->end()
                        ->end()
                        ->scalarNode('uri')->end()
                    ->end()
                ->end()
                ->arrayNode('serializer_options')
                    ->children()
                        ->scalarNode('line_format')->end()
                        ->arrayNode('json_options')
                            ->children()
                                ->arrayNode('system_options')
                                    ->prototype('scalar')
                                    ->validate()
                                        ->ifString()->then($this->getConstantFromString())
                                    ->end()
                                ->end()
                                ->scalarNode('backload_limit')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->enumNode('logging_service')
                    ->isRequired()
                    ->values(['errorlog', 'syslog', 'guzzle', null, 'null', 'custom'])
                    ->end()
                ->scalarNode('custom_service_id')->end()
            ->end();

        return $treeBuilder;
    }

    /**
     * @return Closure
     */
    private function getConstantFromString()
    {
        return function ($stringConstant) {
            $uppercased = mb_strtoupper($stringConstant, 'UTF-8');

            if (!defined($uppercased)) {
                throw new InvalidArgumentException(
                    sprintf('valid php constant not found checked for `%s`', $stringConstant)
                );
            }
            return constant($uppercased);
        };
    }

    /**
     * @return Closure
     */
    private function validateErrorLogType()
    {
        $constantReplace = $this->getConstantFromString();

        return function ($type) use ($constantReplace) {
            if (is_string($type)) {
                $type = $constantReplace($type);
            }

            if (!in_array(ErrorLogService::VALID_TYPES, $type)) {
                throw new InvalidArgumentException(sprintf('The errorlog type is invaild. Recived %s', $type));
            }

            return $type;
        };
    }
}
