<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Middleware\ACL;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;
use Hal\UI\Session;
use QL\Hal\Core\Entity\User;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Utility\Url;
use Slim\Http\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoginMiddleware implements MiddlewareInterface
{
    const SESSION_KEY = 'user_id';

    /**
     * @var Session
     */
    private $session;

    /**
     * @var EntityRepository
     */
    private $userRepo;

    /**
     * @var ContainerInterface
     */
    private $di;

    /**
     * @var Url
     */
    private $url;

    /**
     * @var Request
     */
    private $request;

    /**
     * @param ContainerInterface $di
     * @param EntityManagerInterface $em
     * @param Session $session
     * @param Url $url
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
     * @inheritDoc
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

        // Save user to session and DI container
        $this->session->user($user);

        // @todo stop storing state in DI container
        $this->di->set('currentUser', $user);
    }
}
