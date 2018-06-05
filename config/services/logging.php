<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Psr\Log\LoggerInterface;
use QL\MCP\Logger\Logger;
use QL\Panthor\ErrorHandling\ContentHandler\LoggingContentHandler;

return function (ContainerConfigurator $container) {
    $s = $container->services();
    $p = $container->parameters();

    $p
        ->set('logger.error_handling.logging_levels', [
            'error' => 'critical'
        ])
    ;

    $s
        ->set(LoggerInterface::class, Logger::class)
            ->parent(ref('mcp_logger'))
            ->public()

        ->set('content_handler', LoggingContentHandler::class)
            ->arg('$handler', ref('panthor.content_handler'))
            ->arg('$logger', ref(LoggerInterface::class))
            ->arg('$configuration', '%logger.error_handling.logging_levels%')
    ;
};