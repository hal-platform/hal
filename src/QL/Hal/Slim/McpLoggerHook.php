<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Slim;

use MCP\DataType\IPv4Address;
use MCP\Service\Logger\MessageFactoryInterface;
use Slim\Environment;

/**
 * Set default log message properties.
 *
 * This hook should be attached to the "slim.before" event.
 */
class McpLoggerHook
{
    /**
     * @var MessageFactoryInterface
     */
    private $factory;

    /**
     * @var Environment
     */
    private $env;

    /**
     * @param MessageFactoryInterface $factory
     * @param Environment $env
     */
    public function __construct(MessageFactoryInterface $factory, Environment $env)
    {
        $this->factory = $factory;
        $this->env = $env;
    }

    /**
     * @return null
     */
    public function __invoke()
    {
        $this->factory->setDefaultProperty('machineName', $this->env['SERVER_NAME']);

        // slim doesn't expose this var
        if (!isset($_SERVER['SERVER_ADDR'])) {
            return;
        }

        if ($serverIp = IPv4Address::create($_SERVER['SERVER_ADDR'])) {
            $this->factory->setDefaultProperty('machineIPAddress', $serverIp);
        }
    }
}
