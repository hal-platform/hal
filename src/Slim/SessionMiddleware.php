<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Slim;

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
    const SERVICE_KEY = 'session';

    /**
     * @var SessionHandler
     */
    private $handler;

    /**
     * @var ContainerInterface
     */
    private $di;

    /**
     * @param SessionHandler $handler
     * @param ContainerInterface $di
     */
    public function __construct(SessionHandler $handler, ContainerInterface $di)
    {
        $this->handler = $handler;
        $this->di = $di;
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

        $session = $this->di->get(static::SERVICE_KEY);
        $this->handler->save($session);
    }
}
