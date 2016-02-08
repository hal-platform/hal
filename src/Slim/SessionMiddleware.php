<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Slim;

use QL\Hal\Session;
use QL\Hal\SessionHandler;
use Slim\Middleware;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This is a custom SessionCookie handler to retrieve request cookies from EncryptedCookies instead of the slim request.
 *
 * The session superglobal ($_SESSION) is not used.
 */
class SessionMiddleware extends Middleware
{
    const DEFAULT_SERVICE = 'session';

    /**
     * @var SessionHandler
     */
    private $handler;

    /**
     * @var ContainerInterface
     */
    private $di;

    /**
     * @var string
     */
    private $sessionService;

    /**
     * @param SessionHandler $handler
     * @param ContainerInterface $di
     * @param string $sessionServiceName
     */
    public function __construct(SessionHandler $handler, ContainerInterface $di, $sessionServiceName = null)
    {
        $this->handler = $handler;
        $this->di = $di;

        $this->sessionService = $sessionServiceName ?: self::DEFAULT_SERVICE;
    }

    /**
     * Call
     */
    public function call()
    {
        $this->next->call();
        $this->saveSession();
    }

    /**
     * Save session
     */
    private function saveSession()
    {
        if (!$this->handler->isLoaded()) {
            return;
        }

        $session = $this->di->get($this->sessionService);

        if ($session instanceof Session) {
            $this->handler->save($session);
        }
    }
}
