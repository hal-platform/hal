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
use Slim\Http\Request;

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
     * @var Request
     */
    private $request;

    /**
     * @param MessageFactoryInterface $factory
     * @param Environment $env
     * @param Request $request
     */
    public function __construct(MessageFactoryInterface $factory, Environment $env, Request $request)
    {
        $this->factory = $factory;
        $this->env = $env;
        $this->request = $request;
    }

    /**
     * @return null
     */
    public function __invoke()
    {
        // server
        $this->factory->setDefaultProperty('machineName', $this->request->getHost());

        // client
        $this->factory->setDefaultProperty('Referrer',  $this->request->getReferrer());
        $this->factory->setDefaultProperty('Url',  $this->request->getUrl() . $this->request->getPathInfo());
        $this->factory->setDefaultProperty('UserAgentBrowser', $this->request->getUserAgent());
        $this->factory->setDefaultProperty('UserIPAddress', $this->request->getIp());

        // slim doesn't expose this var
        if (!isset($_SERVER['SERVER_ADDR'])) {
            return;
        }

        if ($serverIp = IPv4Address::create($_SERVER['SERVER_ADDR'])) {
            $this->factory->setDefaultProperty('machineIPAddress', $serverIp);
        }
    }
}
