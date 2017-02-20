<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Application\Config;

use QL\MCP\Logger\Service\Serializer\JSONSerializer;
use QL\MCP\Logger\Service\Serializer\LineSerializer;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class McpLoggerExtension extends Extension
{
    /**
     * @inheritdoc
     */
    public function getAlias()
    {
        return 'mcp_logger';
    }

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
        $configs = $this->processConfiguration(new McpLoggerConfiguration(), $configs);
        $configPath = __DIR__ . '/../../../vendor/ql/mcp-logger/configuration';

        $loader = new YamlFileLoader($container, new FileLocator($configPath));
        $loader->load('mcp-logger.yml');

        if ($configs['logging_service'] === 'syslog') {
            $this->setupSyslogService($configs, $container);
        }

        if ($configs['logging_service'] === 'errorlog') {
            $this->setupErrorlogService($configs, $container);
        }

        if ($configs['logging_service'] === 'guzzle') {
            $this->setupGuzzleService($configs, $container);
        }

        if ($configs['logging_service'] === 'null' || is_null($configs['logging_service'])) {
            $this->setLoggingService('mcp.logger.service.null', $container);
        }

        if ($configs['logging_service'] === 'custom') {
            $container->setAlias('mcp.logger.service', $configs['custom_service_id']);
        }

        if (isset($configs['default_properties'])) {
            $container->setParameter('mcp.logger.default_properties', $configs['default_properties']);
        }

        if (isset($configs['serializer_options'])) {
            $this->setSerializerOptions($configs, $container);
        }
    }

    /**
     * configures the container to use the syslog service
     *
     * @param array $configs
     * @param ContainerBuilder $container
     */
    private function setupSyslogService(array $configs, ContainerBuilder $container)
    {
        $syslogOptions = $configs['syslog_options'];
        // xor options together
        if (isset($syslogOptions['options'])) {
            $combinedOptions = 0;
            foreach ($syslogOptions['options'] as $value) {
                $combinedOptions = $combinedOptions | $value;
            }
            $syslogOptions['options'] = $combinedOptions;
        }

        $container->setParameter('mcp.logger.service.syslog.options', $syslogOptions);
        $this->setLoggingService('mcp.logger.service.syslog', $container);
    }

    /**
     * configures the container to use the errorlog service
     *
     * @param array $configs
     * @param ContainerBuilder $container
     */
    private function setupErrorlogService(array $configs, ContainerBuilder $container)
    {
        $errorlogOptions = $configs['errorlog_options'];
        $errorlogOptions['file'] = $errorlogOptions['destination'];
        unset($errorlogOptions['destination']);

        $container->setParameter('mcp.logger.service.errorLog.options', $errorlogOptions);
        $this->setLoggingService('mcp.logger.service.errorlog', $container);
    }

    /**
     * configures the container to use the guzzle service
     *
     * @param array $configs
     * @param ContainerBuilder $container
     */
    private function setupGuzzleService(array $configs, ContainerBuilder $container)
    {
        $guzzleOptions = $configs['guzzle_options'];

        $clientOptions = [];
        if (!isset($guzzleOptions['client'])) {
            $clientOptions = $guzzleOptions['client'];
        }

        $uri = '';
        if (!isset($guzzleOptions['uri'])) {
            $uri = $guzzleOptions['uri'];
        }

        $container->setParameter('mcp.logger.service.guzzle.options', $clientOptions);
        $container->setParameter('mcp.logger.service.guzzle.endpoint', $uri);

        $this->setLoggingService('mcp.logger.service.guzzle', $container);
    }

    /**
     * configures the container serializer options
     *
     * @param array $configs
     * @param ContainerBuilder $container
     */
    private function setSerializerOptions(array $configs, ContainerBuilder $container)
    {
        if (isset($configs['serializer_options']['line_format'])) {
            $container->setParameter(
                'mcp.logger.serializer.line.options',
                [LineSerializer::CONFIG_TEMPLATE => $configs['serializer_options']['line_format']]
            );
        }

        $parsedJsonOptions = [];
        if (isset($configs['serializer_options']['json_options'])) {
            $jsonOptions = $configs['serializer_options']['json_options'];

            // system options aka system constants such as JSON_UNESCAPED_SLASHES
            if (isset($jsonOptions['system_options'])) {
                $systemOptions = $jsonOptions['system_options'];
                if (!is_array($systemOptions)) {
                    $systemOptions = [$systemOptions];
                }

                $combinedOptions = 0;
                foreach ($systemOptions as $option) {
                    $combinedOptions = $combinedOptions | $option;
                }

                $parsedJsonOptions[JSONSerializer::CONFIG_JSON_OPTIONS] = $combinedOptions;
            }

            if (isset($jsonOptions['backload_limit'])) {
                $parsedJsonOptions[JSONSerializer::CONFIG_BACKLOAD_LIMIT] = $jsonOptions['backload_limit'];
            }

            $container->setParameter(
                'mcp.logger.serializer.json.options',
                $parsedJsonOptions
            );
        }
    }

    /**
     * @param $loggingService
     * @param ContainerBuilder $container
     */
    private function setLoggingService($loggingService, ContainerBuilder $container)
    {
        $loggerServiceDefinition = $container->getDefinition($loggingService);
        $container->setDefinition('mcp.logger.service', $loggerServiceDefinition);
    }
}
