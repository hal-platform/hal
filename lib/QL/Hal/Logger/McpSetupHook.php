<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Logger;

use MCP\DataType\IPv4Address;
use MCP\Service\Logger\MessageFactoryInterface;
use Slim\Environment;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This is a hook that sets up the logger with environment settings.
 *
 * It is intended to run as soon as possible in the application process but after slim is built.
 */
class McpSetupHook
{
    /**
     * @var MessageFactoryInterface
     */
    private $factory;

    /**
     * @var ContainerInterface
     */
    private $di;

    /**
     * @var array
     */
    private $rawEnvironment;

    /**
     * @param MessageFactoryInterface $factory
     * @param ContainerInterface $di
     */
    public function __construct(MessageFactoryInterface $factory, ContainerInterface $di)
    {
        $this->factory = $factory;
        $this->di = $di;

        $this->rawEnvironment = $_SERVER; // I am a bad person
    }

    /**
     * @return null
     */
    public function __invoke()
    {
        $environment = $this->di->get('slim.environment');

        $this->factory->setDefaultProperty('machineName', $environment['SERVER_NAME']);

        if (!isset($this->rawEnvironment['SERVER_ADDR'])) {
            return;
        }

        if ($serverIp = IPv4Address::create($this->rawEnvironment['SERVER_ADDR'])) {
            $this->factory->setDefaultProperty('machineIPAddress', $serverIp);
        }
    }
}