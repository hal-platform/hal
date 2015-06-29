<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Slim;

use MCP\DataType\IPv4Address;
use MCP\Logger\MessageFactoryInterface;
use Slim\Slim;

/**
 * Set default log message properties.
 *
 * This hook should be attached to the "slim.before" event.
 */
class McpLoggerHook
{
    /**
     * @type MessageFactoryInterface
     */
    private $factory;

    /**
     * @param MessageFactoryInterface $factory
     */
    public function __construct(MessageFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param Slim $slim
     * @return null
     */
    public function __invoke(Slim $slim)
    {
        $request = $slim->request();

        // server
        $this->factory->setDefaultProperty('machineName', $request->getHost());

        // client
        $this->factory->setDefaultProperty('Referrer',  $request->getReferrer());
        $this->factory->setDefaultProperty('Url',  $request->getUrl() . $request->getPathInfo());
        $this->factory->setDefaultProperty('UserAgentBrowser', $request->getUserAgent());
        $this->factory->setDefaultProperty('UserIPAddress', $request->getIp());

        // slim doesn't expose this var
        if (!isset($_SERVER['SERVER_ADDR'])) {
            return;
        }

        if ($serverIp = IPv4Address::create($_SERVER['SERVER_ADDR'])) {
            $this->factory->setDefaultProperty('machineIPAddress', $serverIp);
        }
    }
}
