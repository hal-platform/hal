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
use Symfony\Component\DependencyInjection\IntrospectableContainerInterface;
use Twig_Extension;
use Twig_Extension_GlobalsInterface;

/**
 * Twig Extension for declaring and preparing global variables
 */
class GlobalExtension extends Twig_Extension implements Twig_Extension_GlobalsInterface
{
    const NAME = 'hal_global';

    /**
     * @type IntrospectableContainerInterface
     */
    private $di;

    /**
     * @type Session
     */
    private $session;

    /**
     * @type array
     */
    private $globals;

    /**
     * @param IntrospectableContainerInterface $di
     * @param Session $session
     * @param array $globals
     */
    public function __construct(IntrospectableContainerInterface $di, Session $session, array $globals = [])
    {
        $this->di = $di;

        $this->session = $session;

        $this->globals = $globals;
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
        $this->globals['currentUser'] = $this->getCurrentUser();

        return $this->globals;
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
            $user = null;

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
