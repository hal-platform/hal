<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Bouncers;

use QL\Hal\Core\Entity\Repository\UserRepository;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Session;
use Slim\Exception\Stop;
use Slim\Http\Request;
use Slim\Http\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A bouncer that checks to see if the current user is logged in
 */
class LoginBouncer
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var UserRepository
     */
    private $repository;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var UrlHelper
     */
    private $url;

    /**
     * @param Session $session
     * @param UserRepository $repository
     * @param ContainerInterface $container
     * @param UrlHelper $url
     */
    public function __construct(
        Session $session,
        UserRepository $repository,
        ContainerInterface $container,
        UrlHelper $url
    ) {
        $this->session = $session;
        $this->repository = $repository;
        $this->container = $container;
        $this->url = $url;
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @throws Stop
     *
     * @return null
     */
    public function __invoke(Request $request, Response $response)
    {
        if (!$this->session->get('user_id')) {
            $this->url->redirectFor('login', [], ['redirect' => $request->getPathInfo()]);
            throw new Stop;
        }

        if (!$user = $this->repository->find($this->session->get('user_id'))) {
            // log user out if not found
            $this->url->redirectFor('logout');
            throw new Stop;
        }

        $this->container->set('currentUser', $user);
    }
}
