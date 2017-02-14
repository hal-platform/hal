<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Slim;

use QL\MCP\Common\IPv4Address;
use QL\MCP\Logger\MessageFactoryInterface;
use QL\MCP\Logger\MessageInterface;
use Slim\Http\Request;
use Slim\Middleware;

class LoggerDetailsMiddleware extends Middleware
{
    /**
     * @var MessageFactoryInterface
     */
    private $factory;

    /**
     * @var Request
     */
    private $request;

    /**
     * @param MessageFactoryInterface $factory
     * @param Request $request
     */
    public function __construct(MessageFactoryInterface $factory, Request $request)
    {
        $this->factory = $factory;
        $this->request = $request;
    }

    /**
     * Call
     */
    public function call()
    {
        // server
        $this->factory->setDefaultProperty(MessageInterface::SERVER_HOSTNAME, gethostname());

        if (isset($_SERVER['SERVER_ADDR']) && $serverIP = $_SERVER['SERVER_ADDR']) {
            if ($ip = IPv4Address::create($serverIP)) {
                $this->factory->setDefaultProperty(MessageInterface::SERVER_IP, $ip);
            }
        }

        // request
        $this->factory->setDefaultProperty(MessageInterface::REQUEST_METHOD,  $this->request->getMethod());
        $this->factory->setDefaultProperty(MessageInterface::REQUEST_URL, $this->request->getUrl() . $this->request->getPathInfo());


        // client
        if ($referer = $this->request->getReferrer()) {
            $this->factory->setDefaultProperty('referrer', $referer);
        }
        if ($agent = $this->request->getUserAgent()) {
            $this->factory->setDefaultProperty(MessageInterface::USER_AGENT, $agent);
        }
        if ($userIP = $this->request->getIp()) {
            if ($ip = IPv4Address::create($userIP)) {
                $this->factory->setDefaultProperty(MessageInterface::USER_IP, $ip);
            }
        }

        $this->next->call();
    }
}
