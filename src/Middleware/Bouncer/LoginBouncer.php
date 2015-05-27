<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Middleware\Bouncer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\User;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Session;
use QL\Panthor\MiddlewareInterface;
use Slim\Exception\Stop;
use Slim\Http\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A bouncer that checks to see if the current user is logged in
 */
class LoginBouncer implements MiddlewareInterface
{
    /**
     * @type Session
     */
    private $session;

    /**
     * @type EntityRepository
     */
    private $userRepo;

    /**
     * @type ContainerInterface
     */
    private $container;

    /**
     * @type UrlHelper
     */
    private $url;

    /**
     * @type Request
     */
    private $request;

    /**
     * @param Session $session
     * @param EntityManagerInterface $em
     * @param ContainerInterface $container
     * @param UrlHelper $url
     * @param Request $request
     */
    public function __construct(
        Session $session,
        EntityManagerInterface $em,
        ContainerInterface $container,
        UrlHelper $url,
        Request $request
    ) {
        $this->session = $session;
        $this->userRepo = $em->getRepository(User::CLASS);
        $this->container = $container;
        $this->url = $url;
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     * @throws Stop
     */
    public function __invoke()
    {
        if (!$this->session->get('user_id')) {
            $query = [];
            if ($this->request->getPathInfo() !== '/') {
                $query = ['redirect' => $this->request->getPathInfo()];
            }

            $this->url->redirectFor('login', [], $query);
            throw new Stop;
        }

        if (!$user = $this->userRepo->find($this->session->get('user_id'))) {
            // log user out if not found
            $this->url->redirectFor('logout');
            throw new Stop;
        }

        $this->container->set('currentUser', $user);
    }
}
