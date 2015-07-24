<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Twig;

use Exception;
use QL\Hal\Core\Entity\User;
use QL\Hal\Session;
use Slim\Http\Request;
use Symfony\Component\DependencyInjection\IntrospectableContainerInterface;
use Twig_Extension;

/**
 * Twig Extension for declaring and preparing global variables
 */
class GlobalExtension extends Twig_Extension
{
    const NAME = 'hal_global';

    /**
     * @type IntrospectableContainerInterface
     */
    private $di;

    /**
     * @type array
     */
    private $globals;

    /**
     * @type Request
     */
    private $request;

    /**
     * @type Session
     */
    private $session;

    /**
     * @param IntrospectableContainerInterface $di
     * @param Request $request
     * @param Session $session
     */
    public function __construct(
        IntrospectableContainerInterface $di,
        Request $request,
        Session $session
    ) {
        $this->di = $di;

        $this->request = $request;
        $this->session = $session;

        $this->globals = [];
    }

    /**
     *  Get the extension name
     *
     *  @return string
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getGlobals()
    {
        $this->addGlobal('currentUser', $this->getCurrentUser());
        $this->addGlobal('isFirstLogin', $this->session->get('is-first-login'));
        $this->addGlobal('ishttpsOn', ($this->request->getScheme() === 'https'));

        return $this->globals;
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return null
     */
    public function addGlobal($key, $value)
    {
        $this->globals[$key] = $value;
    }

    /**
     * This is required because we need to force the user to load, if the user is available.
     *
     * The "lazy user" loader used by the doctrine change logger fails gracefully if no user is available, it does not force a user load.
     *
     * @return User|null
     */
    private function getCurrentUser()
    {
        try {
            // already loaded
            if ($this->di->initialized('currentUser')) {
                $user = $this->di->get('currentUser', IntrospectableContainerInterface::NULL_ON_INVALID_REFERENCE);

            // read from db if session is set
            } elseif ($userId = $this->session->get('user_id')) {
                $user = $this->di->get('doctrine.em')->getRepository(User::CLASS)->find($userId);
            }
        } catch (Exception $ex) {
            $user = null;
        }

        return $user;
    }
}
