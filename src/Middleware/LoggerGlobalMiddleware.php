<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\MCP\Logger\MessageInterface;
use QL\MCP\Logger\MessageFactoryInterface;
use QL\Panthor\MiddlewareInterface;

/**
 * This middleware requires "ql/mcp-logger" >= 3.0
 *
 * Sets the following default log message properties:
 * - serverHostname
 * - serverIP
 * - requestMethod
 * - requestURL
 * - userAgent
 * - userIP
 *
 * You must manually set the following in your configuration:
 * - serverEnvironment
 * - applicationID
 *  ```
 */
class LoggerGlobalMiddleware implements MiddlewareInterface
{
    /**
     * @var MessageFactoryInterface
     */
    private $factory;

    /**
     * Trusted headers for client IP sources
     *
     * @var array
     */
    private $headers = [
        'Forwarded',
        'Forwarded-For',
        'Client-Ip',
        'X-Forwarded',
        'X-Forwarded-For',
        'X-Cluster-Client-Ip',
    ];

    /**
     * @param MessageFactoryInterface $factory
     */
    public function __construct(MessageFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        // server
        $this->factory->setDefaultProperty(MessageInterface::SERVER_HOSTNAME, gethostname());

        if ($serverIP = $this->getServerIP($request)) {
            $this->factory->setDefaultProperty(MessageInterface::SERVER_IP, $serverIP);
        }

        // request
        $this->factory->setDefaultProperty(MessageInterface::REQUEST_METHOD, $request->getMethod());
        $this->factory->setDefaultProperty(MessageInterface::REQUEST_URL, (string) $request->getUri());

        // client
        if ($agent = $this->getFirstHeader($request, 'user-agent')) {
            $this->factory->setDefaultProperty(MessageInterface::USER_AGENT, $agent);
        }

        if ($userIP = $this->getClientIP($request)) {
            $this->factory->setDefaultProperty(MessageInterface::USER_IP, reset($userIP));
        }

        return $next($request, $response);
    }

    /**
     * @see https://github.com/oscarotero/psr7-middlewares/blob/master/src/Middleware/ClientIp.php
     *
     * @param ServerRequestInterface $request
     *
     * @return string[]
     */
    private function getClientIP(ServerRequestInterface $request)
    {
        $server = $request->getServerParams();
        $ips = [];

        if (!empty($server['REMOTE_ADDR']) && filter_var($server['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
            $ips[] = $server['REMOTE_ADDR'];
        }

        foreach ($this->headers as $name) {
            $header = $request->getHeaderLine($name);
            if (!empty($header)) {
                foreach (array_map('trim', explode(',', $header)) as $ip) {
                    if ((array_search($ip, $ips) === false) && filter_var($ip, FILTER_VALIDATE_IP)) {
                        $ips[] = $ip;
                    }
                }
            }
        }

        return $ips;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    private function getServerIP(ServerRequestInterface $request)
    {
        $server = $request->getServerParams();

        if (!empty($server['SERVER_ADDR']) && filter_var($server['SERVER_ADDR'], FILTER_VALIDATE_IP)) {
            return $server['SERVER_ADDR'];
        }

        return '';
    }

    /**
     * @param ServerRequestInterface $request
     * @param string $header
     *
     * @return string
     */
    private function getFirstHeader(ServerRequestInterface $request, $header)
    {
        if (!$values = $request->getHeader($header)) {
            return '';
        }

        if (!$value = reset($values)) {
            return '';
        }

        return $value;
    }
}
