<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Twig;

use QL\Hal\Core\Repository\UserRepository;
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
     * @type UserRepository
     */
    private $repository;

    /**
     * @param IntrospectableContainerInterface $di
     * @param Request $request
     * @param Session $session
     * @param UserRepository $repository
     */
    public function __construct(
        IntrospectableContainerInterface $di,
        Request $request,
        Session $session,
        UserRepository $repository
    ) {
        $this->di = $di;
        $this->request = $request;
        $this->session = $session;
        $this->repository = $repository;

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
     * @return User|null
     */
    private function getCurrentUser()
    {
        $user = null;

        // already loaded
        if ($this->di->initialized('currentUser')) {
            $user = $this->di->get('currentUser', IntrospectableContainerInterface::NULL_ON_INVALID_REFERENCE);

        // read from db if session is set
        } elseif ($userId = $this->session->get('user_id')) {
            $user = $this->repository->find($userId);
        }

        return $user;
    }
}
