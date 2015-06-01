<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Middleware\ACL;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;
use QL\Hal\Core\Entity\User;
use QL\Hal\Session;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Utility\Url;
use Slim\Http\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoginMiddleware implements MiddlewareInterface
{
    const SESSION_KEY = 'user_id';

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
    private $di;

    /**
     * @type Url
     */
    private $url;

    /**
     * @type Request
     */
    private $request;

    /**
     * @param ContainerInterface $di
     * @param EntityManagerInterface $em
     * @param Session $session
     * @param UrlHelper $url
     * @param Request $request
     */
    public function __construct(
        ContainerInterface $di,
        EntityManagerInterface $em,
        Session $session,
        Url $url,
        Request $request
    ) {
        $this->session = $session;

        $this->userRepo = $em->getRepository(User::CLASS);
        $this->di = $di;

        $this->url = $url;
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function __invoke()
    {
        if (!$this->session->get(self::SESSION_KEY)) {

            $query = [];
            if ($this->request->getPathInfo() !== '/') {
                $query = ['redirect' => $this->request->getPathInfo()];
            }

            return $this->url->redirectFor('login', [], $query);
        }

        // log user out if not found
        if (!$user = $this->userRepo->find($this->session->get(self::SESSION_KEY))) {
            return $this->url->redirectFor('logout');
        }

        $this->di->set('currentUser', $user);
    }
}