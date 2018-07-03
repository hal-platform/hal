<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Psr\Log\LoggerInterface;
use QL\MCP\Logger\Logger;
use QL\Panthor\ErrorHandling\ContentHandler\LoggingContentHandler;

return function (ContainerConfigurator $container) {
    $s = $container->services();
    $p = $container->parameters();

    $p
        ('logger.error_handling.logging_levels', [
            'error' => 'critical'
        ])
    ;

    $s
        (LoggerInterface::class, Logger::class)
            ->parent(ref('mcp_logger'))
            ->public()

        (LoggingContentHandler::class)
            ->decorate('content_handler')
            ->arg('$handler', ref(LoggingContentHandler::class . '.inner'))
            ->arg('$logger', ref(LoggerInterface::class))
            ->arg('$configuration', '%logger.error_handling.logging_levels%')
    ;
};
